#! python3
# -*- coding: utf-8 -*-

from config import Config
from cip import CIP
from base import IndexerBase
import pysolr
import sys
import json
from datetime import datetime
import urllib3

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


class EfterretningerIndexer(IndexerBase):


	def __init__(self):
		super().__init__()
		self.documents = []

	
	def collection_id(self):
		return 19

	
	def collection_info(self):
		return "Politiets efterretninger"
	

	def setup(self):
		self.log("Connecting to CIP... ")
		self.cip = CIP(Config['cumulus']['url'], Config['cumulus']['port'], Config['cumulus']['user'], Config['cumulus']['password'], Config['cumulus']['location'])
		self.cip.load_layout(Config['cumulus']['layout'], Config['cumulus']['layout'])
		self.log("OK.")

		self.log("Connecting to Solr... ")
		self.solr = pysolr.Solr(Config['solr']['url'], auth=(Config['solr']['user'], Config['solr']['password']), timeout=300)
		self.log("OK.")

		self.log("Deleting all Politiets Efterretninger documents in Solr... ")
		self.solr.delete(q=f"collection_id:{self.collection_id()}")
		self.log("OK.")


	def get_total(self):
		result = self.cip.search(Config['cumulus']['catalog'], view=Config['cumulus']['catalog'], querystring="Samlingsnavn == 'Politiets Efterretninger' && Offentlig == true", maxreturned=1)
		return result['totalcount']
	

	def get_entries(self):
		return self.cip.searchall("erindringskatalog", view="erindringskatalog", querystring="Samlingsnavn == 'Politiets Efterretninger' && Offentlig == true", chunk=50)
	

	def handle_entry(self, efterretning):
		jsonObj = {}
		jsonObj['id'] = "%d-%d" % (self.collection_id(), efterretning['ID'])
		jsonObj['org_id'] = "%d" % efterretning['ID']
		jsonObj['collection_id'] = self.collection_id()
		jsonObj['number'] = efterretning.get("Nummer")
		jsonObj['date'] = efterretning.get(u"Indsamlingsår").isoformat() if u"Indsamlingsår" in efterretning else None
		jsonObj['fileName'] = efterretning.get("Record Name")
		jsonObj['efterretning_type'] = efterretning.get(u"Description")
		
		self.documents.append({
			'id': "%d-%d" % (self.collection_id(), efterretning['ID']),
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
			'collected_year': efterretning.get(u"Indsamlingsår").year if u"Indsamlingsår" in efterretning else None,
			'efterretning_number': efterretning.get("Nummer"),
			'efterretning_date': efterretning.get(u"Indsamlingsår"),
			'efterretning_fileName': efterretning.get("Record Name"),
			'efterretning_type': efterretning.get(u"Description"),
			'erindring_document_text': efterretning.get('Document Text')
		})

		if len(self.documents) >= 100:
			self.solr.add(self.documents, commit=True)
			self.documents = []
	
	
	def wrapup(self):
		self.solr.add(self.documents, commit=True)
		self.documents = []


if __name__ == "__main__":
	indexer = EfterretningerIndexer()
	indexer.index()
	sys.exit(0)
