#! python3
# -*- coding: utf-8 -*-

from config import Config
import pymysql
import pysolr
import sys
import json
from datetime import datetime
from functools import reduce
from time import time
import logging
from sns import SNS_Notifier

count_query = "SELECT COUNT(*) as count FROM burial_persons"

person_query = """
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
burial_persons.birthplaceOther as 'burial_persons.birthplaceOther',
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
burial_birthplaces.name as 'burial_birthplaces.name',
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
Entries.last_update as last_update,
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

deathcauses_query = """
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

positions_query = """
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

def writeflush(str):
	sys.stdout.write(str)
	sys.stdout.flush()

# Assume query already has replacement characters for limits
def chunk_query(mysql, query, chunksize=8192):
	results = []
	at = 0
	with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
		while at == 0 or len(results) > 0:
			del results
			cursor.execute(query % (at, chunksize))
			results = cursor.fetchall()
			if len(results) > 0:
				yield (at, results)
			at += chunksize

if __name__ == "__main__":
	solr = None
	mysql = None

	COLLECTION_ID = 1

	try:
		writeflush("Connecting to MySQL... ")
		mysql = pymysql.connect(host=Config['apacs_db']['host'], user=Config['apacs_db']['user'], password=Config['apacs_db']['password'], db=Config['apacs_db']['database'], charset='utf8')
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\n")
		writeflush(repr(e))
		SNS_Notifier.error(str(repr(e)))
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
		if(Config["debug"] == False):
			writeflush("Deleting all burial documents in Solr... ")

			solr.delete(q="collection_id:%s" % COLLECTION_ID)
			writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\nError: %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)

	person_count = None
	with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
		cursor.execute(count_query)
		result = cursor.fetchone()
		person_count = int(result['count'])

	start = time()

	# Everything is based on the chunked loading of persons
	for (at, loaded_persons) in chunk_query(mysql, person_query, chunksize=8192):
		documents = []
		persons = {}
		errors = 0
		docspsec = at / (time()-start)

		writeflush("%7d/%7d (%5f docs/sec) - Generating person data structures.\r" % (at, person_count, docspsec))
		for person in loaded_persons:
			person_id = person['burial_persons.id']
			person["address"] = "%s %s %s" % (person['burial_streets.street'] if person['burial_streets.street'] is not None else "", person['burial_addresses.number'] if person['burial_addresses.number'] is not None else "", person['burial_addresses.letter'] if person['burial_addresses.letter'] is not None else "")
			person["address"] = person["address"].strip()
			persons[person_id] = person
		# Load and add addresses
		person_ids = ",".join(map(lambda p: str(p['burial_persons.id']), loaded_persons))

		writeflush("%7d/%7d (%5f docs/sec) - Loading and adding deathcauses.              \r" % (at, person_count, docspsec))
		try:
			with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
			#	writeflush(deathcauses_query % person_ids)
				cursor.execute(deathcauses_query % person_ids)
				for deathcause in cursor.fetchall():
					person_id = deathcause['burial_persons_deathcauses.persons_id']
					if person_id in persons:
						if 'deathcauses' in persons[person_id]:
							persons[person_id]['deathcauses'].append(deathcause)
						else:
							persons[person_id]['deathcauses'] = [deathcause]
					else:
						errors += 1
		except Exception as e:
			writeflush("\nFailed after %s rows.\nError: %s\n" % (len(persons), repr(e)))
			SNS_Notifier.error(repr(e))
			sys.exit(1)

		# Load and add positions
		writeflush("%7d/%7d (%5f docs/sec) - Loading and adding positions.              \r" % (at, person_count, docspsec))
		try:
			with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
				q = positions_query % person_ids
				cursor.execute(q)
				for result in cursor.fetchall():
					person_id = result['burial_persons_positions.persons_id']
					position = { "position": result['burial_positions.position'], "workplace": result['burial_workplaces.workplace'], "relationtype": result['burial_relationtypes.relationtype'] }
					if person_id in persons:
						if position is not None and "positions" in persons[person_id]:
							persons[person_id]["positions"].append(position)
						elif position is not None:
							persons[person_id]["positions"] = [position]
		except Exception as e:
			writeflush("\nFailed.\nError: %s\n" % repr(e))
			SNS_Notifier.error(repr(e))
			sys.exit(1)

		writeflush("%7d/%7d (%5f docs/sec) - Generating SOLR documents                 \r" % (at, person_count, docspsec))
		for person_id in persons:
			person = persons[person_id]

			if(person['burial_persons.dateOfDeath'] is not None):
				strDateOfDeath = str(person['burial_persons.dateOfDeath']).split('-');
				dateOfDeath = "%04d-%02d-%02dT00:00:00Z" % (int(strDateOfDeath[0]), int(strDateOfDeath[1]), int(strDateOfDeath[2]))
				yearOfDeath = int(strDateOfDeath[0])
			else:
				yearOfDeath = None
				dateOfDeath = None

			# json object
			try:
				data = {
					'id': "%d-%d" % (COLLECTION_ID, person_id),
					'task_id': person['task_id'],
					'post_id': person['post_id'],
					'entry_id': person['entries_id'],
					'user_id': person['user_id'],
					'user_name': person['user_name'],
					'unit_id': person['unit_id'],
					'unit_description' : person['unit_description'],
					'page_id': person['page_id'],
					'page_number' : person['page_number'],
					'collection_id': COLLECTION_ID,
					'collection_info': person['collection_info'],
					'kildeviser_url': "https://www.kbharkiv.dk/kildeviser/#!?collection=5&item=%s" % (person['page_id']),

					#Person
					"person_id": person_id,
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
					'yearOfBirth': person['burial_persons.yearOfBirth'] if 'burial_persons.yearOfBirth' in person else None,
					'dateOfBirth': person['burial_persons.dateOfBirth'] if 'burial_persons.dateOfBirth' in person else None,
					'yearOfDeath': yearOfDeath,
				#	'dateOfDeath': person['burial_persons.dateOfDeath'].strftime("%Y-%m-%dT00:00:00Z") if person['burial_persons.dateOfDeath'] is not None else None,
					'dateOfDeath': dateOfDeath,
					'birthplace': person['burial_birthplaces.name'],
					'deathplace' : person['burial_deathplaces.deathplace'],

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
			except TypeError as e:
				writeflush("TypeError! Person:")
				print(repr(e))
				print(person)
				SNS_Notifier.error(repr(e))
				sys.exit(1)

			documents.append({
				#
				#Metadata
				'id': "%d-%d" % (COLLECTION_ID, person_id),
				'task_id': person['task_id'],
				'post_id': person['post_id'],
				'entry_id': person['entries_id'],
				'user_id': person['user_id'],
				'user_name': person['user_name'],
				'unit_id': person['unit_id'],
				'page_id': person['page_id'],
				'collection_id': COLLECTION_ID,
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
		#		'ageWeeks': person['burial_persons.ageWeeks'],
			#	'ageDays': person['burial_persons.ageDays'],
				#'ageHours': person['burial_persons.ageHours'],
				'yearOfBirth': person['burial_persons.yearOfBirth'] if 'burial_persons.yearOfBirth' in person else '',
				'dateOfBirth': person['burial_persons.dateOfBirth'] if 'burial_persons.dateOfBirth' in person else '',
				'yearOfDeath': yearOfDeath,
				'dateOfDeath': dateOfDeath,
				'birthplace': person['burial_birthplaces.name'],
				'deathplace' : person['burial_deathplaces.deathplace'],

				#Burial
				'record_number': person['burial_burials.number'],
				'chapel': person['burial_chapels.chapel'],
				'parish': person['burial_parishes.parish'],
				'cemetary': person['burial_cemetaries.cemetary'],
				'institution': "" if person['burial_institutions.institution'] is None else person['burial_institutions.institution'],

				#Address
				'addresses': [person['address']]  if "address" in person else [],
				'streets': person['burial_streets.street'],
				'hood': person['burial_hoods.hood'],
				"adressOutsideCph": person['burial_persons.adressOutsideCph'] if "burial_persons.adressOutsideCph" in person else "",

				#Deathcauses
				"deathcauses": list(map(lambda deathcause: deathcause['burial_deathcauses.deathcause'], person["deathcauses"] )) if "deathcauses" in person else [],

				#Positions
				'positions': list(map(lambda position: position['position'], person["positions"]))  if "positions" in person else [],
				'workplace': list(map(lambda position: position['workplace'], person["positions"]))  if "positions" in person else [],
				#'last_update': person['last_update'],
				'last_update': person['last_update']#.strftime("%Y-%m-%dTHH:mm:SSZ") if person['last_update'] is not None else None
			})

		writeflush("%7d/%7d (%5f docs/sec) - Adding SOLR documents                     \r" % (at, person_count, docspsec))
		try:
			solr.add(documents, commit=False)
			solr.commit()
			#writeflush("does not run: solr.add(documents, commit=False)")
		except Exception as e:
			writeflush("\nFailed.\nError %s\n" % repr(e))
			SNS_Notifier.error(repr(e))
			sys.exit(1)

	#print("Committing!")
	#solr.commit()
	print("All done!")
	sys.exit(0)
