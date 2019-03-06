#! python3
# -*- coding: utf-8 -*-
import os
if os.getenv('PYTHON_ENV', 'DEV') == 'DEV':
    print("Using development settings.")
    Config = {
        "debug" : True,
        "cumulus" : {
            #"url": "https://neaonline.dk",
            "url": "https://cumulus.DAF.local",
            "port": 8443,
            "user": "CIP-erindringsbilleder",
            "password": "***REMOVED***",
            "catalog": "CIP-erindringsbilleder",
            "layout": "erindringskatalog"
        },
        'polle_db' : {
            "host": "138.201.87.24",
            "port": 3306,
            "user": "politietsregiste",
            "password": "***REMOVED***",
            "database": "politietsregisterblade"
        },
        "apacs_db" : {
            "host": "148.251.122.164",
            "port": 3306,
            "user":"kbharkiv",
            "password":"***REMOVED***",
            "database": "kbharkiv"
        },
        "aws_sns" : {
            "access_key_id": "***REMOVED***",
            "secret_access_key": "***REMOVED***"
        },
        "solr": {
            "url": "http://solr:8983/solr/apacs_core",
            "user": "kbharkiv",
            "password": "***REMOVED***"
        },
        "ftp_kbharkiv": {
            "url": "phhw-160601.cust.powerhosting.dk",
            "user": "kbharkiv",
            "password": "***REMOVED***"
        }
    }
else:
    print("Using production settings!")
    Config = {
        "debug" : False,
        "cumulus" : {
            "url": "https://cumulus.kbhbilleder.dk",
            "port": 8443,
            "user": "CIP-erindringsbilleder",
            "password": "***REMOVED***",
            "catalog": "CIP-erindringsbilleder",
            "layout": "erindringskatalog"
        },
        'polle_db' : {
            "host": "138.201.87.24",
            "port": 3306,
            "user": "politietsregiste",
            "password": "***REMOVED***",
            "database": "politietsregisterblade"
        },
        "apacs_db" : {
            "host": "148.251.122.164",
            "port": 3306,
            "user":"kbharkiv",
            "password":"***REMOVED***",
            "database": "kbharkiv"
        },
        "aws_sns" : {
            "access_key_id": "***REMOVED***",
            "secret_access_key": "***REMOVED***"
        },
        "solr": {
            "url": "http://solr:8983/solr/apacs_core",
            "user": "kbharkiv",
            "password": "***REMOVED***"
        },
        "ftp_kbharkiv": {
            "url": "ftp:kbharkiv.dk",
            "user": "",
            "password": ""
        }
    }
