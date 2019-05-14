# -*- coding: utf-8 -*-
import sys
import urllib3
import json
from datetime import datetime

import pysolr
import pymysql
import requests

from config import Config
from sns import SNS_Notifier

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

COLLECTION_ID = 100

def create_document(record):
    firstnames = []
    lastname = []
    comment = []
    in_comment = False
    for name_part in record['Navn'].split():
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
        print(record)
    
    # assumption: if the date is defined, and age (or birth date in age field) is defined, the date is entry date
    # if the age is not defined, it is the birth date
    dateofentry = None
    dateofbirth = None
    ageYears = None
    yearOfBirth = None
    date = None
    if record[u'Årstal'] is not None and record[u'Måned'] is not None and record['Dag'] is not None:
        try:
            date = datetime(int(record[u'Årstal']), int(record[u'Måned']), int(record['Dag']))
        except:
            pass

    # handle ages that are really dates of birth
    if record['Alder'] is not None and len(str(record['Alder'])) > 2:
        dob = str(record['Alder'])
        if len(dob) == 7:
            dob = "0" + dob
        try:
            dateofbirth = datetime(int(dob[4:8]), int(dob[2:4]), int(dob[0:2]))
        except:
            pass

    if date is not None and record['Alder'] is not None:
        dateofentry = date
    elif date is not None and record['Alder'] is None:
        dateofbirth = date
    
    if dateofbirth is None and record['Alder'] is not None:
        ageYears = record['Alder']
    elif dateofbirth is not None and dateofentry is not None:
        ageYears = dateofentry.year - dateofbirth.year - ((dateofentry.month, dateofentry.day) < (dateofbirth.month, dateofbirth.day))
    
    if dateofbirth is not None:
        yearOfBirth = dateofbirth.year
    elif record['Alder'] is None and record[u'Årstal']:
        yearOfBirth = record[u'Årstal']

    data = {
        'id': "%s-%s" % (COLLECTION_ID, record['IndexFieldID']),
        'collection_id': COLLECTION_ID,
        'collection_info': "Skoleprotokoller",
        'person_id': record['IndexFieldID'],
        'page_id': record['apacs_page_id'],

        'fullname': record['Navn'],
        'firstnames': " ".join(firstnames),
        'lastname': " ".join(lastname),
        'comments': " ".join(comment) if comment else None,
        'ageYears': ageYears,
        'yearOfBirth': yearOfBirth,
        'dateOfBirth': dateofbirth.isoformat() + "Z" if dateofbirth is not None else None,
        'dateOfEntry': dateofentry.isoformat() + "Z" if dateofentry is not None else None,
        'schoolName': record['SkoleNavn'],
        'imageUrl': f"http://kbhkilder.dk/getfile.php?fileId={record['apacs_page_id']}" if record.get('apacs_page_id') is not None else record.get('ImagePath'),
        'page_number': record['OpslagsNr'],
        'unit_description': record['description'],
        'collected_year': dateofentry.year if dateofentry is not None else None,
        'kildeviser_url': f"http://kbharkiv.dk/kildeviser/#!?collection=100&item={record['apacs_page_id']}" if record.get('apacs_page_id') is not None else None
    }
    return {
        'id': "%s-%s" % (COLLECTION_ID, record['IndexFieldID']),
        'collection_id': COLLECTION_ID,
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
    }


if __name__ == "__main__":
    try:
        print("Connecting to MySQL... ", end='', flush=True)
        mysql = pymysql.connect(
            host=Config['apacs_db']['host'],
            user=Config['apacs_db']['user'],
            password=Config['apacs_db']['password'],
            db=Config['apacs_db']['database'],
            charset='utf8')
        print("OK.")
    except Exception as e:
        print(f"Failed with {repr(e)}")
        SNS_Notifier.error(str(repr(e)))
        sys.exit(1)

    try:
        print("Connecting to Solr... ", end='', flush=True)
        solr = pysolr.Solr(Config['solr']['url'], auth=(Config['solr']['user'], Config['solr']['password']), timeout=300)
        print("OK.")
    except Exception as e:
        print("Failed.\nError: %s" % repr(e))
        SNS_Notifier.error(repr(e))
        sys.exit(1)

    try:
        print(
            "Deleting all Skoleprotokol documents in Solr... ",
            end='',
            flush=True)
        solr.delete(q="collection_id:%s" % COLLECTION_ID)
        print("OK.")
    except Exception as e:
        print("Failed.\nError: %s" % repr(e))
        SNS_Notifier.error(repr(e))
        sys.exit(1)

    print("Creating documents... ", end='', flush=True)
    with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
        query = ("SELECT * FROM "
                 "(SELECT * FROM skole_solr WHERE alder IS NOT NULL AND alder < 100 AND årstal - alder <= 1908"
                 " UNION SELECT * FROM skole_solr WHERE alder IS NULL AND årstal <= 1908"
                 " UNION SELECT * FROM skole_solr WHERE alder > 100 AND RIGHT(alder, 4) <= 1908) tmp "
                 "JOIN apacs_units u ON u.id = tmp.starbas")
        cursor.execute(query)
        documents = list(map(create_document, cursor.fetchall()))
    print("Created %s documents." % len(documents))
    
    chunk = 10000
    for offset in range(0, len(documents), chunk):
        print("\rComitting SOLR documents... %s/%s" % (offset, len(documents)), end='', flush=True)
        solr.add(documents[offset:offset+chunk], commit=True)
    print("\rComitting SOLR documents... %s/%s" % (len(documents), len(documents)))
    print("All done!")