from base import IndexerBase
from config import Config

import sys
import urllib3
import json
from datetime import datetime

import pysolr
import pymysql
import requests

class SchoolsIndexer(IndexerBase):
    """Indexing of school protocols."""

    def __init__(self):
        super().__init__()
        self.added_documents = 0
        self.progress_threshold = 0.05
        self.progress_threshold_next = self.progress_threshold

    
    def collection_info(self):
        return "Skoleprotokoller"
    

    def collection_id(self):
        return 100


    def setup(self):
        self.log("Connecting to MySQL... ")
        self.mysql = pymysql.connect(
            host=Config['apacs_db']['host'],
            user=Config['apacs_db']['user'],
            password=Config['apacs_db']['password'],
            db=Config['apacs_db']['database'],
            charset='utf8')
        self.log("OK.")

        self.log("Connecting to Solr... ")
        self.solr = pysolr.Solr(Config['solr']['url'], auth=(Config['solr']['user'], Config['solr']['password']), timeout=300)
        self.log("OK.")

        self.log("Deleting all Skoleprotokol documents in Solr... ")
        self.solr.delete(q=f"collection_id:{self.collection_id()}")
        self.log("OK.")
    

    def get_total(self):
        with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
            query = ("SELECT SUM(count) as total FROM (SELECT count(*) as count FROM skole_solr WHERE alder IS NOT NULL AND alder < 100 AND årstal - alder <= 1908"
                    " UNION SELECT count(*) as count FROM skole_solr WHERE alder IS NULL AND årstal <= 1908"
                    " UNION SELECT count(*) as count FROM skole_solr WHERE alder > 100 AND RIGHT(alder, 4) <= 1908) tmp")
            cursor.execute(query)
            result = cursor.fetchone()
            return result["total"]

    

    def get_entries(self):
        with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
            query = ("SELECT * FROM "
                    "(SELECT * FROM skole_solr WHERE alder IS NOT NULL AND alder < 100 AND årstal - alder <= 1908"
                    " UNION SELECT * FROM skole_solr WHERE alder IS NULL AND årstal <= 1908"
                    " UNION SELECT * FROM skole_solr WHERE alder > 100 AND RIGHT(alder, 4) <= 1908) tmp "
                    "JOIN apacs_units u ON u.id = tmp.starbas")
            cursor.execute(query)
            entries = cursor.fetchall()
            return entries


    def handle_entry(self, entry):
        firstnames = []
        lastname = []
        comment = []
        in_comment = False
        for name_part in entry['Navn'].split():
            if name_part[0] == '(' and name_part[-1] == ')':
                comment.append(name_part[1:-1])
            elif name_part[0] == '(':
                comment.append(name_part[1:])
                in_comment = True
            elif in_comment and name_part[-1] == ')':
                comment.append(name_part[:-1])
                in_comment = False
            elif in_comment:
                comment.append(name_part)
            elif name_part == 'van' or name_part == 'von' or lastname:
                lastname.append(name_part)
            else:
                firstnames.append(name_part)
        try:
            if not firstnames and comment:
                firstnames = comment
                comment = []
            if not lastname:
                lastname = [firstnames[-1]]
                firstnames = firstnames[:-1]
        except:
            print(entry)
        
        # assumption: if the date is defined, and age (or birth date in age field) is defined, the date is entry date
        # if the age is not defined, it is the birth date
        dateofentry = None
        dateofbirth = None
        ageYears = None
        yearOfBirth = None
        date = None
        if entry[u'Årstal'] is not None and entry[u'Måned'] is not None and entry['Dag'] is not None:
            try:
                date = datetime(int(entry[u'Årstal']), int(entry[u'Måned']), int(entry['Dag']))
            except:
                pass

        # handle ages that are really dates of birth
        if entry['Alder'] is not None and len(str(entry['Alder'])) > 2:
            dob = str(entry['Alder'])
            if len(dob) == 7:
                dob = "0" + dob
            try:
                dateofbirth = datetime(int(dob[4:8]), int(dob[2:4]), int(dob[0:2]))
            except:
                pass

        if date is not None and entry['Alder'] is not None:
            dateofentry = date
        elif date is not None and entry['Alder'] is None:
            dateofbirth = date
        
        if dateofbirth is None and entry['Alder'] is not None:
            ageYears = entry['Alder']
        elif dateofbirth is not None and dateofentry is not None:
            ageYears = dateofentry.year - dateofbirth.year - ((dateofentry.month, dateofentry.day) < (dateofbirth.month, dateofbirth.day))
        
        if dateofbirth is not None:
            yearOfBirth = dateofbirth.year
        elif entry['Alder'] is None and entry[u'Årstal']:
            yearOfBirth = entry[u'Årstal']

        data = {
            'id': "%s-%s" % (self.collection_id(), entry['IndexFieldID']),
            'collection_id': self.collection_id(),
            'collection_info': self.collection_info(),
            'person_id': entry['IndexFieldID'],
            'page_id': entry['apacs_page_id'],

            'fullname': entry['Navn'],
            'firstnames': " ".join(firstnames),
            'lastname': " ".join(lastname),
            'comments': " ".join(comment) if comment else None,
            'ageYears': ageYears,
            'yearOfBirth': yearOfBirth,
            'dateOfBirth': dateofbirth.isoformat() + "Z" if dateofbirth is not None else None,
            'dateOfEntry': dateofentry.isoformat() + "Z" if dateofentry is not None else None,
            'schoolName': entry['SkoleNavn'],
            'imageUrl': f"http://kbhkilder.dk/getfile.php?fileId={entry['apacs_page_id']}" if entry.get('apacs_page_id') is not None else entry.get('ImagePath'),
            'page_number': entry['OpslagsNr'],
            'unit_description': entry['description'],
            'collected_year': dateofentry.year if dateofentry is not None else None,
            'kildeviser_url': f"http://kbharkiv.dk/kildeviser/#!?collection=100&item={entry['apacs_page_id']}" if entry.get('apacs_page_id') is not None else None
        }
        
        self.solr.add([{
            'id': "%s-%s" % (self.collection_id(), entry['IndexFieldID']),
            'collection_id': self.collection_id(),
            'collection_info': "Skoleprotokoller",
            'task_id': -1,
            'post_id': -1,
            'entry_id': -1,
            'user_id': -1,
            'user_name': ' ',
            'unit_id': -1,
            'page_id': data['page_id'],

            'jsonObj': json.dumps(data),

            'fullname': " ".join(firstnames + lastname),
            'firstnames': data['firstnames'],
            'lastname': data['lastname'],
            'ageYears': data['ageYears'],
            'dateOfBirth': data['dateOfBirth'],
            'yearOfBirth': data['yearOfBirth'],
            'collected_year': data['collected_year'],
            'schoolName': data['schoolName'],
            'comments': data['comments']
        }], commit=False)
        self.added_documents += 1
        if self.added_documents >= 10000:
            self.solr.commit()
            self.added_documents = 0

    
    def wrapup(self):
        self.solr.commit()


if __name__ == "__main__":
    indexer = SchoolsIndexer()
    indexer.index()
    sys.exit(0)