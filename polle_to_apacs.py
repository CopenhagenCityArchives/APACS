import json
import pymysql
import argparse
import getpass


def image_url(station_number, filmrulle_number, filename):
    """Construct the URL for a page."""
    return f"https://superbruger.politietsregisterblade.dk/registerblade/{station_number}/{filmrulle_number}/{filename}.jpg"


class UnitInfo:

    def __init__(self, collection_id, description, filmrulle_id, unit_id=None):
        self.collection_id = collection_id
        self.description = description
        self.filmrulle_id = filmrulle_id
        self.insert_with_id = unit_id is not None
        self.unit_id = unit_id
        self.pages = 0
    
    def insert(self, mysql):
        if self.unit_id is not None and not self.insert_with_id:
            raise Exception("Unit already inserted")
        
        with mysql.cursor() as cursor:
            if self.insert_with_id:
                cursor.execute(f"INSERT IGNORE INTO `apacs_units` (`id`, `collections_id`, `description`) VALUES ('{self.unit_id}', '{self.collection_id}', '{self.description}');")
            else:
                cursor.execute(f"INSERT INTO `apacs_units` (`collections_id`, `description`) VALUES ('{self.collection_id}', '{self.description}');")
                self.unit_id = cursor.lastrowid
    
    def update(self, mysql):
        if self.unit_id is None:
            raise Exception("Unit has not been inserted")

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
        self.pages_done = 0
    
    def insert(self, mysql):
        if self.task_unit_id is not None:
            raise Exception("Task unit relation already inserted")
        
        if self.unit.unit_id is None:
            raise Exception("Must save unit before task unit relation")
        
        with mysql.cursor() as cursor:
            cursor.execute(f"INSERT INTO `apacs_tasks_units` (`tasks_id`,`units_id`,`index_active`) VALUES ({self.task_id},{self.unit.unit_id},1);")
            self.task_unit_id = cursor.lastrowid
    
    def update(self, mysql):
        if self.task_unit_id is None:
            raise Exception("Task unit relation has not been inserted")

        with mysql.cursor() as cursor:
            cursor.execute(f"UPDATE `apacs_tasks_units` SET `pages_done` = '{self.pages_done}' WHERE id = '{self.task_unit_id}'")

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

    @staticmethod
    def insertmany(mysql, subposts):
        with mysql.cursor() as cursor:
            values = ",".join(map(lambda s: f'({s.page.page_id}, {s.post.post_id}, 1, 1, 0, 0)', subposts))
            cursor.execute(f"INSERT INTO `apacs_subposts` (`pages_id`, `posts_id`, `width`, `height`, `x`, `y`) VALUES {values};")

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

    @staticmethod
    def insertmany(mysql, entries):
        with mysql.cursor() as cursor:
            values = ",".join(map(lambda e: f'({e.task_id}, {e.post.post_id}, 0, {e.concrete_entry_id}, 1)', entries))
            cursor.execute(f"INSERT INTO `apacs_entries` (`tasks_id`, `posts_id`, `users_id`, `concrete_entries_id`, `complete`) VALUES {values};")

def generate_units(mysql, collection_id, unit_id_generator):
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
            yield UnitInfo(collection_id, f"Station {row['station_nummer']} - rulle {row['filmrulle_nummer']}", row['filmrulle_id'], next(unit_id_generator))


def generate_apacs_items(mysql, task_id, unit):
    """Generate all the needed APACS metadata items for the given unit in the task identified by the task_id."""

    if unit.unit_id is None:
        raise Exception("Invalid unit info")

    task_unit = TaskUnitInfo(unit, task_id)
    yield task_unit

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
    
    task_unit.pages_done = page_number
    task_unit.update(mysql)


def main(task_id, collection_id, start_unit_id, mysql):
    for unit in generate_units(mysql, collection_id, unit_id_generator(start_unit_id)):
        print(f"Unit (filmrulle id {unit.filmrulle_id})", flush=True, end="\r")
        unit.insert(mysql)
        print(f"Unit (filmrulle id {unit.filmrulle_id}) - {unit.unit_id}", flush=True, end="\r")
        entries = []
        subposts = []
        j = 0
        for i, item in enumerate(generate_apacs_items(mysql, task_id, unit)):
            j = i
            if i % 50 == 0:
                print(f"Unit (filmrulle id {unit.filmrulle_id}) - {unit.unit_id} - {i}", flush=True, end="\r")

            if isinstance(item, EntryInfo):
                entries.append(item)
            elif isinstance(item, SubPostInfo):
                subposts.append(item)
            else:
                item.insert(mysql)

            if len(entries) > 1000:
                EntryInfo.insertmany(mysql, entries)
                entries = []
            if len(subposts) > 1000:
                SubPostInfo.insertmany(mysql, subposts)
                subposts = []
        EntryInfo.insertmany(mysql, entries)
        SubPostInfo.insertmany(mysql, subposts)
        unit.update(mysql)
        mysql.commit()
        print(f"Unit (filmrulle id {unit.filmrulle_id}) - {unit.unit_id} - {j}", flush=True)
    print()


def unit_id_generator(start):
    i = start
    while True:
        yield i
        i += 1


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
    parser.add_argument('--start-unit-id', type=int, default=False)

    namespace = parser.parse_args()

    if namespace.password is None:
        namespace.password = getpass.getpass()
    
    if namespace.start_unit_id == False:
        namespace.start_unit_id = namespace.collection_id * 100000
        if input(f'Using starting unit id {namespace.start_unit_id} based on collection id (y/n): ').lower() != 'y':
            exit(0)

    with pymysql.connect(
        host=namespace.host,
        user=namespace.user,
        password=None if namespace.password == False else namespace.password,
        db=namespace.db,
        charset='utf8'
    ) as mysql:
        main(namespace.task_id, namespace.collection_id, namespace.start_unit_id, mysql)