#! python3
# -*- coding: utf-8 -*-
from config import Config
import pysolr
import sys
import json
from datetime import datetime
from functools import reduce
import xml.etree.ElementTree as etree
from cip import CIP
import zlib, base64
import urllib3
from sns import SNS_Notifier
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def writeflush(str):
	sys.stdout.write(str)
	sys.stdout.flush()

if __name__ == "__main__":
	documents = []
	solr = None
	cip = None

	COLLECTION_ID = 18

	try:
		writeflush("Connecting to CIP... ")
		cip = CIP(Config['cumulus']['url'], Config['cumulus']['port'], Config['cumulus']['user'], Config['cumulus']['password'], Config['cumulus']['catalog'])
		cip.load_layout(Config['cumulus']['layout'], Config['cumulus']['layout'])
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\nError: %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)

	try:
		writeflush("Connecting to Solr... ")
		solr = pysolr.Solr(Config['solr']['url'], timeout=300)
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\nError: %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)

	try:
		writeflush("Deleting all Erindringer documents in Solr... ")
		solr.delete(q="collection_id:%s" % COLLECTION_ID)
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\nError: %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)

	writeflush("Creating Solr documents... ")
	for i, erindring in enumerate(cip.searchall("erindringskatalog", view="erindringskatalog", querystring="Offentlig == true && 'Related Master Assets' !*", chunk=50)):
		writeflush("\rCreating Solr documents... %d" % (i+1))

		# if(erindring['Transkriberet'] == 1):
		# 	query = 'ID == %s' % erindring['Related Sub Assets']
		# 	print(cip.search(catalog="erindringskatalog", querystring=query))
		# 	sys.exit(1)

		jsonObj = {}
		jsonObj['id'] = "erindring-%d" % erindring['ID']
		jsonObj['org_id'] = "%d" % erindring['ID']
		jsonObj['collection_id'] = COLLECTION_ID
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
		if "Stilling forældre" in erindring:
			jsonObj['position_parent'] = erindring['Stilling forældre']
		if "Fødselsår" in erindring:
			jsonObj['yearOfBirth'] = erindring['Fødselsår']
		if "Description" in erindring:
			jsonObj['description'] = erindring['Description']
		if "Erindringsnummer" in erindring:
			jsonObj['erindring_number'] = erindring['Erindringsnummer']
		if "Skrevet år" in erindring:
			jsonObj['writtenYear'] = erindring['Skrevet år']
		if "Omfang" in erindring:
			jsonObj['extent'] = erindring['Omfang']
		if "Håndskrevne/maskinskreven" in erindring:
			jsonObj['writeMethod'] = erindring['Håndskrevne/maskinskreven']
		if "Document Name" in erindring:
			jsonObj['filename'] = erindring['Document Name']
		if "Transkriberet" in erindring:
			jsonObj['transcribed'] = erindring['Transkriberet']
			jsonObj['transcribed_filename'] = erindring['Document Name'].replace(".pdf", "_transcribed.pdf") if erindring['Transkriberet'] and erindring['Document Name'] else None
		if "Civilstand" in erindring:
			jsonObj['civilstatus'] = erindring['Civilstand']
		if "Keywords" in erindring:
			jsonObj['keywords'] = erindring['Keywords'].split(",")
		if "Køn" in erindring:
			jsonObj['sex'] = erindring['Køn']
		jsonObj['containsPhotos'] = 'Foto' in erindring and erindring['Foto']

		documents.append({
			'id': "erindring-%d" % erindring['ID'],
			'task_id': -1,
			'post_id': -1,
			'entry_id': -1,
			'user_id': -1,
			'user_name': ' ',
			'unit_id': -1,
			'page_id': -1,
			'jsonObj': json.dumps(jsonObj),
			'collection_id': COLLECTION_ID,
			'collection_info': 'Erindringer',
			'firstnames': erindring['Fornavne'] if 'Fornavne' in erindring else (erindring['Navn'].split(',')[1].strip() if 'Navn' in erindring and len(erindring['Navn'].split(',')) > 1 else None),
			'lastname': erindring['Efternavn'] if 'Efternavn' in erindring else (erindring['Navn'].split(',')[0].strip() if 'Navn' in erindring and len(erindring['Navn'].split(',')) > 0 else None),
			'sex': erindring['Køn'] if 'Køn' in erindring else None,
			'civilstatus': erindring['Civilstatus'] if 'Civilstatus' in erindring else None,
			'yearOfBirth': erindring['Fødselsår'] if 'Fødselsår' in erindring else None,
			"erindring_position": erindring['Stilling hovedperson'] if 'Stilling hovedperson' in erindring else None,
			"erindring_parent_position": erindring['Stilling forældre'] if 'Stilling forældre' in erindring else None,
			"erindring_handwritten_typed": erindring['Håndskrevne/maskinskreven'] if 'Håndskrevne/maskinskreven' in erindring else None,
			"erindring_description": erindring['Description'] if 'Description' in erindring else None,
			"erindring_number": erindring['Erindringsnummer'] if 'Erindringsnummer' in erindring else None,
			"erindring_written_year": erindring['Skrevet år'] if 'Skrevet år' in erindring else None,
			"erindring_extent": erindring['Omfang'] if 'Omfang' in erindring else None,
			"erindring_photos": 'Foto' in erindring and erindring['Foto'],
			"erindring_keywords": erindring['Keywords'].split(',') if 'Keywords' in erindring and erindring['Keywords'] is not None else None,
			"erindring_document_text": erindring['Document Text'] if 'Document Text' in erindring else None,
			"erindring_transcribed": "Transkriberet" in erindring and erindring['Transkriberet']
		})

	print()
	chunksize = 100
	index = 0
	writeflush("Comitting Solr documents... %d/%d\r" % (index, len(documents)))
	try:
		while index < len(documents):
			solr.add(documents[index:index+chunksize])
			index += chunksize
			writeflush("Comitting Solr documents... %d/%d\r" % (min(index, len(documents)), len(documents)))
	except Exception as e:
		writeflush("\nFailed.\nError %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)
	print()
