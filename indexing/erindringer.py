#! python3
# -*- coding: utf-8 -*-

from config import Config
from cip import CIP
from base import IndexerBase

import sys
import json
import urllib3
import datetime
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

class ErindringerIndexer(IndexerBase):


    def __init__(self):
        super().__init__()
        self.progress_threshold = 0.1
        self.progress_threshold_next = self.progress_threshold
        self.commit_threshold = 100


    def collection_id(self):
        return 18
    

    def collection_info(self):
        return "Erindringer"


    def setup(self):
        self.log("Connecting to CIP and loading layout...")
        self.cip = CIP(Config['cumulus']['url'], Config['cumulus']['port'], Config['cumulus']['user'], Config['cumulus']['password'], Config['cumulus']['location'])
        self.cip.load_layout(Config['cumulus']['layout'], Config['cumulus']['layout'])
        self.log("OK.")

        self.log("Creating index of transcribed...")
        self.transcribed = {}
        for erindring in self.cip.searchall(Config['cumulus']['catalog'], view=Config['cumulus']['catalog'], querystring="Offentlig == true && 'Related Master Assets' * && Samlingsnavn == 'Erindring'"):
            self.transcribed[erindring['Erindringsnummer']] = erindring
        self.log(f"OK. Created index of {len(self.transcribed)} transcribed.")


    def get_total(self):
        search_result = self.cip.search(Config['cumulus']['catalog'], view=Config['cumulus']['catalog'], querystring="Offentlig == true && 'Related Master Assets' !* && Samlingsnavn == 'Erindring'", maxreturned=1)
        return search_result['totalcount']


    def get_entries(self):
        return self.cip.searchall(Config['cumulus']['catalog'], view=Config['cumulus']['catalog'], querystring="Offentlig == true && 'Related Master Assets' !* && Samlingsnavn == 'Erindring'", chunk=50)
        

    def handle_entry(self, erindring):
        jsonObj = {}
        jsonObj['id'] = "%d-%d" % (self.collection_id(), erindring['ID'])
        jsonObj['org_id'] = "%d" % erindring['ID']
        jsonObj['collection_id'] = self.collection_id()
        if "Fornavne" in erindring:
            jsonObj['firstnames'] = erindring['Fornavne']
        elif "Navn" in erindring and len(erindring['Navn'].split(',')) > 1:
            jsonObj['firstnames'] = erindring['Navn'].split(',')[1].strip()
        if "Efternavn" in erindring:
            jsonObj['lastname'] = erindring['Efternavn']
        elif "Navn" in erindring and len(erindring['Navn'].split(',')) > 0:
            jsonObj['lastname'] = erindring['Navn'].split(',')[0].strip()
        if "Stilling hovedperson" in erindring:
            jsonObj['position'] = erindring['Stilling hovedperson']
        if u"Stilling forældre" in erindring:
            jsonObj['position_parent'] = erindring[u'Stilling forældre']
        if u"Stilling ægtefælle" in erindring:
            jsonObj['position_spouse'] = erindring[u'Stilling ægtefælle']
        if "Periode" in erindring:
            jsonObj['period'] = erindring['Periode']
        if u"Fødselsår" in erindring:
            jsonObj['yearOfBirth'] = erindring[u'Fødselsår'].year if isinstance(erindring[u'Fødselsår'], datetime.date) else erindring[u'Fødselsår']
        if "Description" in erindring:
            jsonObj['description'] = erindring['Description']
        if "Erindringsnummer" in erindring:
            jsonObj['erindring_number'] = erindring['Erindringsnummer']
        if u"Indsamlingsår" in erindring:
            jsonObj['collectedYear'] = erindring[u'Indsamlingsår']
        if "Omfang" in erindring:
            jsonObj['extent'] = erindring['Omfang']
        if u"Håndskrevne/maskinskreven" in erindring:
            jsonObj['writeMethod'] = erindring[u'Håndskrevne/maskinskreven']
        if "Document Name" in erindring:
            jsonObj['filename'] = erindring['Document Name']
        if "Transkriberet" in erindring:
            jsonObj['transcribed'] = erindring['Transkriberet']
            jsonObj['transcribed_filename'] = erindring['Document Name'].replace(".pdf", "_transcribed.pdf") if erindring['Transkriberet'] and erindring['Document Name'] else None
        if "Civilstand" in erindring:
            jsonObj['civilstatus'] = erindring['Civilstand']
        if "Keywords" in erindring:
            jsonObj['keywords'] = erindring['Keywords'].split(",")
        if u"Køn" in erindring:
            jsonObj['sex'] = erindring[u'Køn']
        if "Erindringsnummer" in erindring and erindring["Erindringsnummer"] in self.transcribed:
            jsonObj['transcribed_id'] = self.transcribed[erindring["Erindringsnummer"]]['ID']
        jsonObj['containsPhotos'] = 'Foto' in erindring and erindring['Foto']

        self.documents.append({
            'id': "%d-%d" % (self.collection_id(), erindring['ID']),
            'task_id': -1,
            'post_id': -1,
            'entry_id': -1,
            'user_id': -1,
            'user_name': ' ',
            'unit_id': -1,
            'page_id': -1,
            'jsonObj': json.dumps(jsonObj),
            'collection_id': self.collection_id(),
            'collection_info': self.collection_info(),
            'firstnames': erindring['Fornavne'] if 'Fornavne' in erindring else (erindring['Navn'].split(',')[1].strip() if 'Navn' in erindring and len(erindring['Navn'].split(',')) > 1 else None),
            'lastname': erindring['Efternavn'] if 'Efternavn' in erindring else (erindring['Navn'].split(',')[0].strip() if 'Navn' in erindring and len(erindring['Navn'].split(',')) > 0 else None),
            'sex': erindring.get('Køn'),
            'civilstatus': erindring.get('Civilstatus'),
            'yearOfBirth':  jsonObj.get('Fødselsår'),
            "erindring_position": erindring.get('Stilling hovedperson'),
            "erindring_parent_position": erindring.get(u'Stilling forældre'),
            "erindring_spouse_position": erindring.get(u'Stilling ægtefælle'),
            "erindring_handwritten_typed": erindring.get(u'Håndskrevne/maskinskreven'),
            "erindring_description": erindring.get('Description'),
            "erindring_number": erindring.get('Erindringsnummer'),
            "erindring_period": erindring.get('Periode'),
            "collected_year": erindring.get(u"Indsamlingsår"),
            "erindring_extent": erindring.get('Omfang'),
            "erindring_photos": 'Foto' in erindring and erindring['Foto'],
            "erindring_keywords": erindring['Keywords'].split(',') if 'Keywords' in erindring and erindring['Keywords'] is not None else None,
            "erindring_document_text": erindring.get('Document Text'),
            "erindring_transcribed": "Transkriberet" in erindring and erindring['Transkriberet']
        })
 
        if len(self.documents) >= 100:
            self.solr.add(self.documents, commit=True)
            self.documents = []

    def wrapup(self):
        self.solr.add(self.documents, commit=True)

if __name__ == "__main__":
    indexer = ErindringerIndexer()
    indexer.index()
    sys.exit(0)
