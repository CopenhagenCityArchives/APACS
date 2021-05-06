import json
import pymysql
import argparse
import getpass


def image_url(station_number, filmrulle_number, filename):
    """Construct the URL for a page."""
    return f"https://superbruger.politietsregisterblade.dk/registerblade/{station_number}/{filmrulle_number}/{filename}.jpg"


class UnitInfo:

    def __init__(self, collection_id, description, filmrulle_id):
        self.collection_id = collection_id
        self.description = description
        self.filmrulle_id = filmrulle_id
        self.unit_id = None
        self.pages = 0
    
    def insert(self, mysql):
        if self.unit_id is not None:
            raise Exception("Unit already inserted")

        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_units` (`collections_id`, `description`) VALUES ('{self.collection_id}', '{self.description}');")
            self.unit_id = cursor.lastrowid
    
    def update(self, mysql):
        with mysql.cursor() as cursor:
            cursor.execute(f"UPDATE `apacs_units` SET `pages` = '{self.pages}' WHERE id = '{self.unit_id}'")


class PageInfo:

    def __init__(self, unit, page_number, image_url):
        self.unit = unit
        self.image_url = image_url
        self.page_id = None
        self.page_number = page_number
    
    def insert(self, mysql):
        if self.page_id is not None:
            raise Exception("Page already inserted")
        
        if self.unit.unit_id is None:
            raise Exception("Must save unit before page")

        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_pages` (`unit_id`, `page_number`, `image_url`) VALUES ('{self.unit.unit_id}', '{self.page_number}', '{self.image_url}');")
            self.page_id = cursor.lastrowid
    

class TaskUnitInfo:

    def __init__(self, unit, task_id):
        self.task_unit_id = None
        self.task_id = task_id
        self.unit = unit
    
    def insert(self, mysql):
        if self.task_unit_id is not None:
            raise Exception("Task unit relation already inserted")
        
        if self.unit.unit_id is None:
            raise Exception("Must save unit before task unit relation")
        
        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_tasks_units` (`tasks_id`,`units_id`,`index_active`) VALUES ({self.task_id},{self.unit.unit_id},1);")


class TaskPageInfo:

    def __init__(self, page, task_id):
        self.task_page_id = None
        self.task_id = task_id
        self.page = page
    
    def insert(self, mysql):
        if self.task_page_id is not None:
            raise Exception("Task page relation already inserted")
        
        if self.page.page_id is None:
            raise Exception("Must save page before task page relation")
        
        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_tasks_pages` (`tasks_id`, `pages_id`, `units_id`, `is_done`) VALUES ({self.task_id}, {self.page.page_id}, {self.page.unit.unit_id}, 1);")


class PostInfo:

    def __init__(self, page):
        self.page = page
        self.post_id = None
    
    def insert(self, mysql):
        if self.post_id is not None:
            raise Exception("Post already inserted")
        
        if self.page.page_id is None:
            raise Exception("Must save page before post")
        
        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_posts` (`pages_id`, `complete`, `width`, `height`, `x`, `y`) VALUES ({self.page.page_id}, 1, 1, 1, 0, 0);")
            self.post_id = cursor.lastrowid


class SubPostInfo:

    def __init__(self, post, page):
        self.post = post
        self.page = page
        self.subpost_id = None
    
    def insert(self, mysql):
        if self.subpost_id is not None:
            raise Exception("Subpost already inserted")
        
        if self.post.post_id is None:
            raise Exception("Must save post before subpost")
        
        if self.page.page_id is None:
            raise Exception("Must save page before subpost")
        
        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_subposts` (`pages_id`, `posts_id`, `width`, `height`, `x`, `y`) VALUES ({self.page.page_id}, {self.post.post_id}, 1, 1, 0, 0);")
            self.subpost_id = cursor.lastrowid


class EntryInfo:

    def __init__(self, task_id, post, concrete_entry_id):
        self.task_id = task_id
        self.post = post
        self.concrete_entry_id = concrete_entry_id
        self.entry_id = None
    
    def insert(self, mysql):
        if self.entry_id is not None:
            raise Exception("Entry already inserted")
        
        with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
            cursor.execute(f"INSERT INTO `apacs_entries` (`tasks_id`, `posts_id`, `users_id`, `concrete_entries_id`, `complete`) VALUES ({self.task_id}, {self.post.post_id}, 0, {self.concrete_entry_id}, 1);")
            self.entry_id = cursor.lastrowid


def generate_units(mysql, collection_id):
    with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
        cursor.execute("""
            SELECT
                fi.id as filmrulle_id,
                fi.nummer as filmrulle_nummer,
                st.nummer as station_nummer,
                st.beskrivelse
            FROM PRB_filmrulle fi
            LEFT JOIN PRB_station st ON st.id = fi.station_id
        """)

        for row in cursor.fetchall():
            yield UnitInfo(collection_id, f"Station {row['station_nummer']} - rulle {row['filmrulle_nummer']}", row['filmrulle_id'])


def generate_apacs_items(mysql, task_id, unit):
    """
    """

    if unit.unit_id is None:
        raise Exception("Invalid unit info")

    yield TaskUnitInfo(unit, task_id)

    page_number = 1
    with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
        cursor.execute(f"""
            SELECT re.*, st.nummer as station_nummer, fi.nummer as filmrulle_nummer FROM PRB_registerblad re
            LEFT JOIN PRB_station st ON st.id = re.station_id
            LEFT JOIN PRB_filmrulle fi ON fi.id = re.filmrulle_id
            WHERE fi.id = {unit.filmrulle_id}
        """)

        for row in cursor.fetchall():
            front = PageInfo(unit, page_number, image_url(row['station_nummer'], row['filmrulle_nummer'], row['filnavn']))
            page_number += 1
            yield front
            yield TaskPageInfo(front, task_id)

            post = PostInfo(front)
            yield post

            yield EntryInfo(task_id, post, row['id'])

            if row['filnavn2']:
                back = PageInfo(unit, page_number, image_url(row['station_nummer'], row['filmrulle_nummer'], row['filnavn2']))
                page_number += 1
                yield back
                yield TaskPageInfo(back, task_id)

                subpost = SubPostInfo(post, back)
                yield subpost
    unit.pages = page_number


def main(task_id, collection_id, mysql):
    for unit in generate_units(mysql, collection_id):
        print(f"Unit (filmrulle id {unit.filmrulle_id})", flush=True, end="\r")
        unit.insert(mysql)
        print(f"Unit (filmrulle id {unit.filmrulle_id}) - {unit.unit_id}", flush=True, end="\r")
        for i, item in enumerate(generate_apacs_items(mysql, task_id, unit)):
            if i % 1000 == 0:
                mysql.commit()
            item.insert(mysql)
            print(f"Unit (filmrulle id {unit.filmrulle_id}) - {unit.unit_id} - {i}", flush=True, end="\r")
        unit.update(mysql)
        print()
        mysql.commit()
    print()


if __name__ == "__main__":
    parser = argparse.ArgumentParser(
        description="Create Police Registration Sheets in APACS"
    )
    parser.add_argument('--collection-id', '-cid', type=int, required=True)
    parser.add_argument('--task-id', '-tid', type=int, required=True)
    parser.add_argument('host', nargs='?', type=str, default="127.0.0.1")
    parser.add_argument('--db', '-d', type=str, default="apacs")
    parser.add_argument('--user', '-u', type=str, default="root")
    parser.add_argument('--password', '-p', type=str, nargs='?', const=None, default=False)

    namespace = parser.parse_args()

    if namespace.password is None:
        namespace.password = getpass.getpass()

    with pymysql.connect(
        host=namespace.host,
        user=namespace.user,
        password=None if namespace.password == False else namespace.password,
        db=namespace.db,
        charset='utf8'
    ) as mysql:
        main(namespace.task_id, namespace.collection_id, mysql)