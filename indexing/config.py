#! python3
# -*- coding: utf-8 -*-
import os
from pathlib import Path  # python3 only
from dotenv import load_dotenv

env_path = Path('/etc/.env')
load_dotenv(dotenv_path=env_path)

if os.getenv('ENVIRONMENT', 'DEV') == 'DEV':
    print("Using development settings.")
else:
    print("Using production settings!")

Config = {
    "debug" : os.getenv("ENVIRONMENT") == 'DEV',
    "cumulus" : {
        "url": os.getenv("CUMULUS_HOST"),
        "port": os.getenv("CUMULUS_PORT"),
        "user": os.getenv("CUMULUS_USER"),
        "password": os.getenv("CUMULUS_PASS"),
        "catalog": os.getenv("CUMULUS_CATALOG"),
        "layout": os.getenv("CUMULUS_LAYOUT"),
    },
    'polle_db' : {
        "host": os.getenv("POLLE_DB_HOST"),
        "port": os.getenv("POLLE_DB_PORT"),
        "user": os.getenv("POLLE_DB_USER"),
        "password": os.getenv("POLLE_DB_PASSWORD"),
        "database": os.getenv("POLLE_DB_DATABASE")
    },
    "apacs_db" : {
        "host": os.getenv("APACS_DB_HOST"),
        "port": os.getenv("APACS_DB_PORT"),
        "user": os.getenv("APACS_DB_USER"),
        "password": os.getenv("APACS_DB_PASSWORD"),
        "database": os.getenv("APACS_DB_DATABASE")
    },
    "aws_sns" : {
        "access_key_id": os.getenv("AWS_SNS_KEY_ID"),
        "secret_access_key": os.getenv("AWS_SNS_ACCESS_KEY"),
    },
    "solr": {
        "url": os.getenv("SOLR_INTERNAL_URL")
    },
    "ftp_kbharkiv": {
        "url": os.getenv("KBHARKIV_FTP_HOST"),
        "user": os.getenv("KBHARKIV_FTP_USER"),
        "password": os.getenv("KBHARKIV_FTP_PASSWORD")
    }
}

