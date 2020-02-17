#! python3
# -*- coding: utf-8 -*-

from config import Config
from base import IndexerBase

import pymysql
import sys
import json


class BurialIndexer(IndexerBase):

	def __init__(self):
		super().__init__()
		self.errors = 0

		self.count_query = "SELECT COUNT(*) as count FROM burial_persons"

		self.person_query = """
SELECT
burial_persons.id as 'burial_persons.id',
burial_persons.firstnames as 'burial_persons.firstnames',
burial_persons.lastname as 'burial_persons.lastname',
burial_persons.birthname as 'burial_persons.birthname',
CAST(burial_persons.ageYears as SIGNED) as 'burial_persons.ageYears',
CAST(burial_persons.ageMonth as SIGNED) as 'burial_persons.ageMonth',
burial_persons.dateOfBirth as 'burial_persons.dateOfBirth',
burial_persons.dateOfDeath as 'burial_persons.dateOfDeath',
burial_persons.deathplaces_id as 'burial_persons.deathplaces_id',
burial_persons.civilstatuses_id as 'burial_persons.civilstatuses_id',
burial_persons.birthplaces_id as 'burial_persons.birthplaces_id',
burial_persons.birthplace_free as 'burial_persons.birthplace_free',
burial_persons.yearOfBirth as 'burial_persons.yearOfBirth',
burial_persons.adressOutsideCph as 'burial_persons.adressOutsideCph',
burial_persons.comment as 'burial_persons.comment',
CAST(burial_persons.ageWeeks as SIGNED) as 'burial_persons.ageWeeks',
CAST(burial_persons.ageDays as SIGNED) as 'burial_persons.ageDays',
CAST(burial_persons.ageHours as SIGNED) as 'burial_persons.ageHours',
burial_addresses.id as 'burial_addresses.id',
burial_addresses.streets_id as 'burial_addresses.streets_id',
burial_addresses.number as 'burial_addresses.number',
burial_addresses.letter as 'burial_addresses.letter',
burial_addresses.floors_id as 'burial_addresses.floors_id',
burial_addresses.persons_id as 'burial_addresses.persons_id',
burial_addresses.institutions_id as 'burial_addresses.institutions_id',
burial_floors.id as 'burial_floors.id',
burial_floors.floor as 'burial_floors.floor',
burial_streets.id as 'burial_streets.id',
burial_streets.street as 'burial_streets.street',
burial_streets.code as 'burial_streets.code',
burial_streets.hoods_id as 'burial_streets.hoods_id',
burial_streets.hood as 'burial_streets.hood',
burial_streets.streetAndHood as 'burial_streets.streetAndHood',
burial_hoods.id as 'burial_hoods.id',
burial_hoods.hood as 'burial_hoods.hood',
burial_institutions.institution as 'burial_institutions.institution',
burial_institutions.id as 'burial_institutions.id',
burial_birthplaces.id as 'burial_birthplaces.id',
burial_birthplaces.birthplace as 'burial_birthplaces.birthplace',
burial_burials.id as 'burial_burials.id',
burial_burials.cemetaries_id as 'burial_burials.cemetaries_id',
burial_burials.chapels_id as 'burial_burials.chapels_id',
burial_burials.parishes_id as 'burial_burials.parishes_id',
burial_burials.persons_id as 'burial_burials.persons_id',
burial_burials.number as 'burial_burials.number',
burial_cemetaries.id as 'burial_cemetaries.id',
burial_cemetaries.cemetary as 'burial_cemetaries.cemetary',
burial_chapels.id as 'burial_chapels.id',
burial_chapels.chapel as 'burial_chapels.chapel',
burial_parishes.id as 'burial_parishes.id',
burial_parishes.parish as 'burial_parishes.parish',
burial_parishes.fromYear as 'burial_parishes.fromYear',
burial_deathplaces.id as 'burial_deathplaces.id',
burial_deathplaces.deathplace as 'burial_deathplaces.deathplace',
burial_civilstatuses.id as 'burial_civilstatuses.id',
burial_civilstatuses.civilstatus as 'burial_civilstatuses.civilstatus',
burial_persons_sex.sex as 'burial_persons_sex.sex', burial_persons_sex.id as 'burial_persons_sex.id',

Collections.id as collection_id,
Collections.name as collection_info,
Tasks.id as task_id,
Units.id as unit_id,
Posts.id as post_id,
Units.description as unit_description,
Units.pages as unit_pages,
Pages.id as page_id,
Pages.page_number,
Entries.id as entry_id,
Entries.updated as updated,
Entries.id as entries_id,
Entries.concrete_entries_id,
Users.username as user_name,
Users.id as user_id

FROM burial_persons
LEFT JOIN burial_addresses ON burial_addresses.persons_id = burial_persons.id
LEFT JOIN burial_floors ON burial_floors.id = burial_addresses.floors_id
LEFT JOIN burial_streets ON burial_streets.id = burial_addresses.streets_id
LEFT JOIN burial_hoods ON burial_hoods.id = burial_streets.hoods_id
LEFT JOIN burial_institutions ON burial_institutions.id = burial_addresses.institutions_id
LEFT JOIN burial_birthplaces ON burial_birthplaces.id = burial_persons.birthplaces_id
LEFT JOIN burial_burials ON burial_burials.persons_id = burial_persons.id
LEFT JOIN burial_cemetaries ON burial_cemetaries.id = burial_burials.cemetaries_id
LEFT JOIN burial_chapels ON burial_chapels.id = burial_burials.chapels_id
LEFT JOIN burial_parishes ON burial_parishes.id = burial_burials.parishes_id
LEFT JOIN burial_deathplaces ON burial_deathplaces.id = burial_persons.deathplaces_id
LEFT JOIN burial_civilstatuses ON burial_civilstatuses.id = burial_persons.civilstatuses_id
LEFT JOIN burial_persons_sex ON burial_persons_sex.id = burial_persons.sex_id

LEFT JOIN apacs_entries as Entries ON Entries.concrete_entries_id = burial_persons.id

LEFT JOIN apacs_posts as Posts ON Entries.posts_id = Posts.id
LEFT JOIN apacs_pages as Pages ON Posts.pages_id = Pages.id
LEFT JOIN apacs_units as Units ON Pages.unit_id = Units.id
LEFT JOIN apacs_collections as Collections ON Units.collections_id = Collections.id
LEFT JOIN apacs_tasks as Tasks ON Entries.tasks_id = Tasks.id
LEFT JOIN apacs_users as Users ON Entries.users_id = Users.id


LIMIT %d, %d
"""

		self.deathcauses_query = """
SELECT
burial_persons_deathcauses.id as 'burial_persons_deathcauses.id',
burial_persons_deathcauses.persons_id as 'burial_persons_deathcauses.persons_id',
burial_persons_deathcauses.deathcauses_id as 'burial_persons_deathcauses.deathcauses_id',
burial_deathcauses.id as 'burial_deathcauses.id',
burial_deathcauses.deathcause as 'burial_deathcauses.deathcause'

FROM burial_persons_deathcauses
LEFT JOIN burial_deathcauses ON burial_deathcauses.id = burial_persons_deathcauses.deathcauses_id

WHERE burial_persons_deathcauses.persons_id IN (%s) AND 'burial_persons_deathcauses.persons_id' is not null
ORDER BY burial_persons_deathcauses.order, burial_persons_deathcauses.id ASC
"""

		self.positions_query = """
SELECT
burial_persons_positions.id as 'burial_persons_positions.id',
burial_persons_positions.persons_id as 'burial_persons_positions.persons_id',
burial_persons_positions.positions_id as 'burial_persons_positions.positions_id',
burial_persons_positions.relationtypes_id as 'burial_persons_positions.relationtypes_id',
burial_persons_positions.workplaces_id as 'burial_persons_positions.workplaces_id',
burial_positions.id as 'burial_positions.id',
burial_positions.position as 'burial_positions.position',
burial_relationtypes.id as 'burial_relationtypes.id',
burial_relationtypes.relationtype as 'burial_relationtypes.relationtype',
burial_workplaces.workplace as 'burial_workplaces.workplace'

FROM burial_persons_positions
LEFT JOIN burial_positions ON burial_positions.id = burial_persons_positions.positions_id
LEFT JOIN burial_relationtypes ON burial_relationtypes.id = burial_persons_positions.relationtypes_id
LEFT JOIN burial_workplaces ON burial_workplaces.id = burial_persons_positions.workplaces_id

WHERE burial_persons_positions.persons_id IN (%s) AND 'burial_persons_positions.persons_id' is not null
ORDER BY burial_persons_positions.order, burial_persons_positions.id ASC
"""

	# Assume query already has replacement characters for limits
	def chunk_query(self, query, chunksize=8192):
		results = []
		at = 0
		with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
			while at == 0 or len(results) > 0:
				del results
				cursor.execute(query % (at, chunksize))
				results = cursor.fetchall()
				if len(results) > 0:
					yield (at, results)
				at += chunksize
	

	def collection_id(self):
		return 1


	def collection_info(self):
		return "Begravelsesprotokoller"


	def setup(self):
		self.log("Connecting to MySQL... ")
		self.mysql = pymysql.connect(host=Config['apacs_db']['host'], user=Config['apacs_db']['user'], password=Config['apacs_db']['password'], db=Config['apacs_db']['database'], charset='utf8')
		self.log("OK.")


	def get_total(self):
		with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
			cursor.execute(self.count_query)
			result = cursor.fetchone()
			return int(result['count'])


	def get_entries(self):
		# Everything is based on the chunked loading of persons
		for _, loaded_persons in self.chunk_query(self.person_query, chunksize=8192):
			persons = {}
			for person in loaded_persons:
				person_id = person['burial_persons.id']
				person["address"] = "%s %s %s" % (person['burial_streets.street'] if person['burial_streets.street'] is not None else "", person['burial_addresses.number'] if person['burial_addresses.number'] is not None else "", person['burial_addresses.letter'] if person['burial_addresses.letter'] is not None else "")
				person["address"] = person["address"].strip()
				persons[person_id] = person
			# Load and add addresses
			person_ids = ",".join(map(lambda p: str(p['burial_persons.id']), loaded_persons))

			with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
				cursor.execute(self.deathcauses_query % person_ids)
				for deathcause in cursor.fetchall():
					person_id = deathcause['burial_persons_deathcauses.persons_id']
					if person_id in persons:
						if 'deathcauses' in persons[person_id]:
							persons[person_id]['deathcauses'].append(deathcause)
						else:
							persons[person_id]['deathcauses'] = [deathcause]
					else:
						self.errors += 1

			# Load and add positions
			with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
				q = self.positions_query % person_ids
				cursor.execute(q)
				for result in cursor.fetchall():
					person_id = result['burial_persons_positions.persons_id']
					position = { "position": result['burial_positions.position'], "workplace": result['burial_workplaces.workplace'], "relationtype": result['burial_relationtypes.relationtype'] }
					if person_id in persons:
						if position is not None and "positions" in persons[person_id]:
							persons[person_id]["positions"].append(position)
						elif position is not None:
							persons[person_id]["positions"] = [position]

			for person_id in persons:
				person = persons[person_id]
				yield person


	def handle_entry(self, person):
		person_id = person['burial_persons.id']
		if person['burial_persons.dateOfDeath'] is not None:
			strDateOfDeath = str(person['burial_persons.dateOfDeath']).split('-')
			dateOfDeath = "%04d-%02d-%02dT00:00:00Z" % (int(strDateOfDeath[0]), int(strDateOfDeath[1]), int(strDateOfDeath[2]))
			yearOfDeath = int(strDateOfDeath[0])
		else:
			yearOfDeath = None
			dateOfDeath = None

		# json object
		data = {
			'id': "%d-%d" % (self.collection_id(), person_id),
			'task_id': person['task_id'],
			'post_id': person['post_id'],
			'entry_id': person['entries_id'],
			'user_id': person['user_id'],
			'user_name': person['user_name'],
			'unit_id': person['unit_id'],
			'unit_description' : person['unit_description'],
			'page_id': person['page_id'],
			'page_number' : person['page_number'],
			'collection_id': self.collection_id(),
			'collection_info': person['collection_info'],
			'updated': person['updated'].isoformat() + "Z",
			'kildeviser_url': "https://www.kbharkiv.dk/kildeviser/#!?collection=5&item=%s" % (person['page_id']),

			#Person
			'person_id': person_id,
			'firstnames': "" if person['burial_persons.firstnames'] is None else person['burial_persons.firstnames'],
			'lastname': "" if person['burial_persons.lastname'] is None else person['burial_persons.lastname'],
			'comment': "" if person['burial_persons.comment'] is None else person['burial_persons.comment'],
			'birthname': "" if person['burial_persons.birthname'] is None else person['burial_persons.birthname'],
			'sex': person['burial_persons_sex.sex'],
			'civilstatus': person['burial_civilstatuses.civilstatus'],
			'ageYears': str(person['burial_persons.ageYears']) if(person['burial_persons.ageYears']) is not None else None,
			'ageMonth': str(person['burial_persons.ageMonth']) if(person['burial_persons.ageMonth']) is not None else None,
			'ageWeeks': str(person['burial_persons.ageWeeks']) if(person['burial_persons.ageWeeks']) is not None else None,
			'ageDays': str(person['burial_persons.ageDays']) if(person['burial_persons.ageDays']) is not None else None,
			'ageHours': str(person['burial_persons.ageHours']) if(person['burial_persons.ageHours']) is not None else None,
			'yearOfBirth': person['burial_persons.yearOfBirth'] if person.get('burial_persons.yearOfBirth') is not None else None,
			'dateOfBirth': person['burial_persons.dateOfBirth'].isoformat() if person.get('burial_persons.dateOfBirth') is not None else None,
			'yearOfDeath': yearOfDeath,
			'dateOfDeath': dateOfDeath,
			'birthplace': person['burial_birthplaces.birthplace'],
			'birthplace_free': person['burial_persons.birthplace_free'],
			'deathplace': person['burial_deathplaces.deathplace'],

			#Burial
			'burials' : {
				'number': person['burial_burials.number'],
				'chapel': person['burial_chapels.chapel'],
				'parish': person['burial_parishes.parish'],
				'cemetary': person['burial_cemetaries.cemetary'],
			},
			'institution': "" if person['burial_institutions.institution'] is None else person['burial_institutions.institution'],

			#Address
			'addresses': { "street": person['burial_streets.street'], "hood": person['burial_hoods.hood'], "streetAndHood": person['burial_streets.streetAndHood'], "number": person['burial_addresses.number'], "letter": person['burial_addresses.letter'], "floor": person['burial_floors.floor'], "adressOutsideCph": person['burial_persons.adressOutsideCph'], "institution": person['burial_institutions.institution'] }  if "address" in person else {},

			#Deathcauses
			"deathcauses": list(map(lambda deathcause: {'deathcause': deathcause['burial_deathcauses.deathcause'] }, person["deathcauses"] )) if "deathcauses" in person else [],

			#Positions
			'positions': list(map(lambda position: { "position": position['position'], "relationtype": position['relationtype'], "workplace": position['workplace'] }, person["positions"]))  if "positions" in person else [],
		}

		self.documents.append({
			#Metadata
			'id': "%d-%d" % (self.collection_id(), person_id),
			'task_id': person['task_id'],
			'post_id': person['post_id'],
			'entry_id': person['entries_id'],
			'user_id': person['user_id'],
			'user_name': person['user_name'],
			'unit_id': person['unit_id'],
			'page_id': person['page_id'],
			'updated': person['updated'].isoformat() + "Z",
			'collection_id': self.collection_id(),
			'collection_info': person['collection_info'],
			'jsonObj': json.dumps(data),

			#Person
			'firstnames': "" if person['burial_persons.firstnames'] is None else person['burial_persons.firstnames'],
			'lastname': "" if person['burial_persons.lastname'] is None else person['burial_persons.lastname'],
			'fullname': "" if person['burial_persons.firstnames'] is None or person['burial_persons.lastname'] is None else u"{0} {1}".format(person['burial_persons.firstnames'], person['burial_persons.lastname']),
			'comments': [] if person['burial_persons.comment'] is None else [person['burial_persons.comment']],
			'birthname': "" if person['burial_persons.birthname'] is None else person['burial_persons.birthname'],
			'sex': person['burial_persons_sex.sex'],
			'civilstatus': person['burial_civilstatuses.civilstatus'],
			'ageYears': person['burial_persons.ageYears'] if person['burial_persons.ageYears'] is not None else 0,
			'ageMonth': person['burial_persons.ageMonth'] if person['burial_persons.ageMonth'] is not None else 0,
			'yearOfBirth': person['burial_persons.yearOfBirth'] if 'burial_persons.yearOfBirth' in person else '',
			'dateOfBirth': person['burial_persons.dateOfBirth'] if 'burial_persons.dateOfBirth' in person else '',
			'yearOfDeath': yearOfDeath,
			'dateOfDeath': dateOfDeath,
			'birthplace': person['burial_birthplaces.birthplace'],
			'birthplace_free': person['burial_persons.birthplace_free'],
			'deathplace' : person['burial_deathplaces.deathplace'],

			#Burial
			'record_number': person['burial_burials.number'],
			'chapel': person['burial_chapels.chapel'],
			'parish': person['burial_parishes.parish'],
			'cemetary': person['burial_cemetaries.cemetary'],
			'institutions': [] if person['burial_institutions.institution'] is None else [person['burial_institutions.institution']],

			#Address
			'addresses': [person['address']]  if "address" in person else [],
			'streets': person['burial_streets.streetAndHood'],
			'hood': person['burial_hoods.hood'],
			"adressOutsideCph": person['burial_persons.adressOutsideCph'] if "burial_persons.adressOutsideCph" in person else "",

			#Deathcauses
			"deathcauses": list(map(lambda deathcause: deathcause['burial_deathcauses.deathcause'], person["deathcauses"] )) if "deathcauses" in person else [],

			#Positions
			'positions': list(map(lambda position: position['position'], person["positions"]))  if "positions" in person else [],
			'workplace': list(map(lambda position: position['workplace'], person["positions"]))  if "positions" in person else []
		})

		if len(self.documents) >= 10000:
			self.solr.add(self.documents, commit=True)
			self.documents = []


if __name__ == "__main__":
	indexer = BurialIndexer()
	indexer.index()
	sys.exit(0)