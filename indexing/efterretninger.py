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

	COLLECTION_ID = 19

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
		writeflush("Deleting all Politiets Efterretninger documents in Solr... ")
		solr.delete(q="collection_id:%s" % COLLECTION_ID)
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\nError: %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)

	writeflush("Creating Solr documents... ")
	for i, efterretning in enumerate(cip.searchall("erindringskatalog", view="erindringskatalog", querystring="Samlingsnavn == 'Politiets Efterretninger' && Offentlig == true", chunk=50)):
		try:
			writeflush("\rCreating Solr documents... %d" % (i+1))
			# if(efterretning['Transkriberet'] == 1):
			# 	query = 'ID == %s' % efterretning['Related Sub Assets']
			# 	print(cip.search(catalog="erindringskatalog", querystring=query))
			# 	sys.exit(1)

			jsonObj = {}
			jsonObj['id'] = "%d-%d" % (COLLECTION_ID, efterretning['ID'])
			jsonObj['org_id'] = "%d" % efterretning['ID']
			jsonObj['collection_id'] = COLLECTION_ID
			jsonObj['number'] = efterretning.get("Nummer")
			jsonObj['date'] = efterretning.get(u"Indsamlingsår").isoformat() if u"Indsamlingsår" in efterretning else None
			jsonObj['fileName'] = efterretning.get("Record Name")
			
			documents.append({
				'id': "%d-%d" % (COLLECTION_ID, efterretning['ID']),
				'task_id': -1,
				'post_id': -1,
				'entry_id': -1,
				'user_id': -1,
				'user_name': ' ',
				'unit_id': -1,
				'page_id': -1,
				'jsonObj': json.dumps(jsonObj),
				'collection_id': COLLECTION_ID,
				'collection_info': 'Politiets Efterretninger',
				'collected_year': efterretning.get(u"Indsamlingsår").year if u"Indsamlingsår" in efterretning else None,
				'efterretning_number': efterretning.get("Nummer"),
				'efterretning_date': efterretning.get(u"Indsamlingsår"),
				'efterretning_fileName': efterretning.get("Record Name"),
				'erindring_document_text': efterretning.get('Document Text')
			})
		except Exception as e:
			msg = "Fejl under skabelse af Efterretning SOLR Dokument %d, sandsynligvis dato-formatering:\n\t%s\nIndhold af efterretning\n\t%s" % (i, repr(e), "\n\t".join(["%s : %s" % (k, v) for (k, v) in efterretning.items()]))
			SNS_Notifier.error(msg)
	print("")
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
