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

count_query = "SELECT COUNT(*) as count FROM PRB_person"

person_query = """
SELECT
	p.registerblad_id,
	p.person_id,
	p.fornavne as firstnames,
    p.efternavn as lastname,
    p.pigenavn as birthname,
    p.foedselsaar as year_of_birth,
    p.foedselsmaaned as month_of_birth,
    p.foedselsdag as day_of_birth,
	p.afdoed_aar as year_of_death,
	p.afdoed_maaned as month_of_death,
	p.afdoed_dag as day_of_death,
    p.person_type as person_type,
    fs.foedested as birthplace,
    p.gift as married,
    pc.kommentar as person_comment,
    rc.kommentar as registerblad_comment,
    r.saerlige_bemaerkninger as special_comments,
    r.udfyldelse_dag as completion_day,
    r.udfyldelse_maaned as completion_month,
    r.udfyldelse_aar as completion_year,
    st.nummer as station,
    fr.nummer as film,
    rn.nummer as number,
    r.filnavn as file_front,
    r.filnavn2 as file_back,
    p.koen as sex,
    p.gift as married,
	IF(date(p.last_changed) = '000-00-00', "", date(p.last_changed)) as last_changed
FROM
	PRB_person p
    LEFT JOIN PRB_registerblad r ON r.registerblad_id = p.registerblad_id
    LEFT JOIN PRB_foedested fs ON p.foedested_id = fs.foedested_id
    LEFT JOIN PRB_kommentar rc ON r.registerblad_id = rc.registerblad_id
    LEFT JOIN PRB_kommentar pc ON p.person_id = pc.person_id
    LEFT JOIN PRB_station st ON st.station_id = r.station_id
    LEFT JOIN PRB_filmrulle fr ON fr.filmrulle_id = r.filmrulle_id
    LEFT JOIN PRB_registerblad_nummerering rn ON rn.registerblad_id = r.registerblad_id
	ORDER BY r.registerblad_id
	LIMIT %d, %d
"""

address_query = """
SELECT *, CONCAT(
	IF(street IS NULL, "", street),
	IF(street IS NOT NULL and number IS NOT NULL, " ", ""),
	IF(number IS NULL, "", number),
	IF((street IS NOT NULL or number IS NOT NULL) and letter IS NOT NULL, ", ", ""),
	IF(letter IS NULL, "", letter),
	IF((street IS NOT NULL or number IS NOT NULL or letter IS NOT NULL) and floor IS NOT NULL,", ", ""),
	IF(floor IS NULL, "", floor),
	IF((street IS NOT NULL or number IS NOT NULL or letter IS NOT NULL or floor IS NOT NULL) and side IS NOT NULL,", ", ""),
	IF(side IS NULL, "", side),
	IF((street IS NOT NULL or number IS NOT NULL or letter IS NOT NULL or floor IS NOT NULL or side IS NOT NULL) and place IS NOT NULL,", ", ""),
	IF(place IS NULL, "", place),
	IF((street IS NOT NULL or number IS NOT NULL or letter IS NOT NULL or floor IS NOT NULL or side IS NOT NULL or place IS NOT NULL) and servant_staying_at IS NOT NULL,", hos ", ""),
	IF(servant_staying_at IS NULL, "", servant_staying_at)) as full_address
FROM
(SELECT
	a.registerblad_id as card_id,
    IF(v.vej_id NOT IN ('2636','3003','2772','2637'), TRIM(v.burial_streets_streetAndHood), NULL) as street,
	IF(v.burial_institutions_id IS NOT NULL, v.burial_institutions_institution, NULL) as institution,
    IF(a.vejnummer <> "", a.vejnummer, null) as number,
    IF(a.vejnummerbogstav <> "", a.vejnummerbogstav, null) as letter,
    IF(a.etage <> "", a.etage, null) as floor,
    IF(a.sideangivelse <> "", a.sideangivelse, null) as side,
    IF(a.sted <> "", a.sted, null) as place,
    IF(a.tjenesteLogerendeHos <> "", a.tjenesteLogerendeHos, null) as servant_staying_at,
    CAST(k.latitude as CHAR) as latitude,
    CAST(k.longitude as CHAR) as longitude,
    a.adresse_dag as day,
    a.adresse_maaned as month,
    a.adresse_aar as year,
	a.fra_note as from_note,
	a.til_note as to_note,
	a.frameldt as frameldt,
    CONCAT(k.latitude, ",", k.longitude) as location,
	ko.kommentar as adr_comment
FROM PRB_adresse a
LEFT JOIN PRB_vej v ON v.vej_id = a.vej_id
LEFT JOIN PRB_kommentar ko ON ko.adresse_id = a.adresse_id
LEFT JOIN PRB_koordinat k ON k.koordinat_id = a.koordinat_id
WHERE a.registerblad_id IN (%s)) sub ORDER BY year asc, month asc, day asc
"""

position_query = """
SELECT
	p.registerblad_id,
	ps.person_id,
	ks.kontrolleret_stilling as position_correct,
	s.stilling as position
FROM
	PRB_person_stilling ps
    LEFT JOIN PRB_stilling s ON s.stilling_id = ps.stilling_id
    LEFT JOIN PRB_kontrolleret_stilling ks ON s.kontrolleret_stilling_id = ks.kontrolleret_stilling_id
	LEFT JOIN PRB_person p ON ps.person_id = p.person_id
WHERE p.person_id IN (%s)
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

def valid_date(year, month, day):
	if day == None or month == None or year == None:
		return False
	else:
		try:
			datetime(year, month, day)
			return True
		except ValueError:
			return False

def get_formatted_date_or_default(year, month, day, default = None):
	if(valid_date(year, month, day)):
		return "%04d-%02d-%02dT00:00:00Z" % (year, month, day)
	else:
		return default;

def person_type_text(x):
	types = ['Ukendt','Hovedperson','Ægtefælle','Barn']
	return types[x]

if __name__ == "__main__":
	solr = None
	mysql = None

	COLLECTION_ID = 17

	try:
		writeflush("Connecting to MySQL... ")
		mysql = pymysql.connect(host=Config['polle_db']['host'], user=Config['polle_db']['user'], password=Config['polle_db']['password'], db=Config['polle_db']['database'], charset='utf8')
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\n")
		writeflush(repr(e))
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
		if Config["debug"] == False:
			writeflush("Deleting all Police documents in Solr... ")
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

	documents = []
	totalDocuments = 0
	for (at, loaded_persons) in chunk_query(mysql, person_query, chunksize=10000):
		cards = {}
		persons = {}
		errors = 0
		docspsec = at / (time()-start)

		writeflush("%7d/%7d (%5f docs/sec) - Generating person and card data structures.\r" % (totalDocuments, person_count, docspsec))
		for person in loaded_persons:
			card_id = person['registerblad_id']
			person_type = person['person_type']

			person['person_type_text'] = person_type_text(person['person_type'])

			#If the registerblad already exists in the loaded cards, append the person to the index
			if card_id in cards:
				card = cards[card_id]
				if person_type == 1:
					card['main'] = person
				elif person_type == 2:
					card['spouses'].append(person)
				elif person_type == 3:
					card['children'].append(person)
			else:
				#Else add the card with the person type
				if person_type == 1:
					cards[card_id] = { 'main': person, 'spouses': [], 'children': [] }
				elif person_type == 2:
					cards[card_id] = { 'main': None, 'spouses': [person], 'children': [] }
				elif person_type == 3:
					cards[card_id] = { 'main': None, 'spouses': [], 'children': [person] }
			persons[person['person_id']] = person

		# Load and add addresses
		card_ids = ",".join(map(lambda p: str(p['registerblad_id']), loaded_persons))
		person_ids = ",".join(map(lambda p: str(p['person_id']), loaded_persons))

		writeflush("%7d/%7d (%5f docs/sec) - Loading and adding addresses.              \r" % (totalDocuments, person_count, docspsec))
		try:
			with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
				#print(address_query % card_ids)
				cursor.execute(address_query % card_ids)
				for address in cursor.fetchall():
					card_id = address['card_id']
					address['address_date'] = get_formatted_date_or_default(address['year'], address['month'], address['day'], None);
					if card_id in cards:
						if 'addresses' in cards[card_id]:
							cards[card_id]['addresses'].append(address)
						else:
							cards[card_id]['addresses'] = [address]
					else:
						errors += 1
		except Exception as e:
			writeflush("\nFailed after %s rows.\nError: %s\n" % (len(cards), repr(e)))
			SNS_Notifier.error(repr(e))
			sys.exit(1)

		# Load and add positions
		writeflush("%7d/%7d (%5f docs/sec) - Loading and adding positions.              \r" % (totalDocuments, person_count, docspsec))
		try:
			with mysql.cursor(pymysql.cursors.DictCursor) as cursor:
				q = position_query % person_ids
				#print(q)
				cursor.execute(q)
				for result in cursor.fetchall():
					person_id = result['person_id']
					position = result['position'] if result['position_correct'] is None else result['position_correct']
					if person_id in persons:
						if position is not None and "positions" in persons[person_id]:
							persons[person_id]["positions"].append(position)
						elif position is not None:
							persons[person_id]["positions"] = [position]
		except Exception as e:
			writeflush("\nFailed.\nError: %s\n" % repr(e))
			SNS_Notifier.error(repr(e))
			sys.exit(1)

		writeflush("%7d/%7d (%5f docs/sec) - Generating SOLR documents                 \r" % (totalDocuments, person_count, docspsec))
		count = 0
		for person_id in persons:
			person = persons[person_id]
			card = cards[person["registerblad_id"]]

			valid_birthdate = valid_date(person["year_of_birth"], person['month_of_birth'], person['day_of_birth'])
			valid_deathdate = valid_date(person["year_of_death"], person['month_of_death'], person['day_of_death'])
			valid_completion_date = valid_date(person['completion_year'], person['completion_month'], person['completion_day'])
			data = {}
			# json object
			try:
				data = {
					'id': "%d-%d" % (COLLECTION_ID, person_id),
					'registerblad_id': person['registerblad_id'],
					'firstnames': person['firstnames'],
					'lastname': person['lastname'],
					'birthname': person['birthname'],
					'person_type': person['person_type'],
					'birthplace': person['birthplace'],
					'sex': person['sex'],
					'married': person['married'],
					'positions': list(map(lambda position: { 'position': position }, person['positions'])) if 'positions' in person else [],
					'dateOfBirth': get_formatted_date_or_default(person['year_of_birth'], person['month_of_birth'], person['day_of_birth'], None),
					'dateOfDeath': get_formatted_date_or_default(person['year_of_death'], person['month_of_death'], person['day_of_death'], None),
					'dateOfCompletion': get_formatted_date_or_default(person['completion_year'], person['completion_month'], person['completion_day'], None),
					'civilstand': "Gift" if person['married'] == 1 and person['person_type'] == 1 else "",
					'specialComment': None if person['special_comments'] is None else person['special_comments'],
					'person_comment': None if person['person_comment'] is None else person['person_comment'],
					'registerblad_comment': None if person['registerblad_comment'] is None else person['registerblad_comment'],
					'person_type_text': person['person_type_text'],
					"person_id": person_id,
					"station": person["station"],
					"film": person["film"],
					"number": person["number"],
					"file_front": person["file_front"],
					"file_back": person["file_back"],
					"collection_id": COLLECTION_ID,
					"collection_info": 'Politiets registerblade',
					'last_changed': person['last_changed']
				}
				if person["person_type"] == 3 and card['main'] is not None:
					data['parent'] = {
					'person_id': card['main']['person_id'],
					'firstnames': card['main']['firstnames'],
					'lastname': card['main']['lastname'],
					'birthplace': card['main']['birthplace'],
					'birthdate': get_formatted_date_or_default(card['main']['year_of_birth'], card['main']['month_of_birth'], card['main']['day_of_birth'], None),
					'deathdate': get_formatted_date_or_default(card['main']['year_of_death'], card['main']['month_of_death'], card['main']['day_of_death'], None),
					'specialComment': None if card['main']['special_comments'] is None else person['special_comments'],
					'post_id': "%d-%d" % (COLLECTION_ID, card['main']['person_id']),
					'positions': list(map(lambda position: { 'position': position }, card['main']['positions'])) if 'positions' in card['main'] else [] }
				elif person["person_type"] == 2 and card['main'] is not None:
					data['spouses'] = [{
						'person_id': card['main']['person_id'],
						'firstnames': card['main']['firstnames'],
						'lastname': card['main']['lastname'],
						'birthplace': card['main']['birthplace'],
						'birthdate': get_formatted_date_or_default(card['main']['year_of_birth'], card['main']['month_of_birth'], card['main']['day_of_birth'], None),
						'deathdate': get_formatted_date_or_default(card['main']['year_of_death'], card['main']['month_of_death'], card['main']['day_of_death'], None),
						'post_id': "%d-%d" % (COLLECTION_ID, card['main']['person_id']),
						'positions': list(map(lambda position: { 'position': position },card['main']['positions'])) if 'positions' in card['main'] else []
					}]
				elif person["person_type"] == 1:
					data['addresses'] = card['addresses'] if 'addresses' in card else []
					data['spouses'] = list(map(lambda spouse: {
						'person_id': spouse['person_id'],
						'firstnames': spouse['firstnames'],
						'lastname': spouse['lastname'],
						'birthplace': spouse['birthplace'],
						'birthdate': get_formatted_date_or_default(spouse['year_of_birth'], spouse['month_of_birth'], spouse['day_of_birth'], None),
						'deathdate': get_formatted_date_or_default(spouse['year_of_death'], spouse['month_of_death'], spouse['day_of_death'], None),
						'post_id': "%d-%d" % (COLLECTION_ID, spouse['person_id']),
						'positions': [] }, card['spouses']))
					data['children'] = list(map(lambda child: {
						'person_id': child['person_id'],
						'firstnames': child['firstnames'],
						'lastname': child['lastname'],
						'birthplace': child['birthplace'],
						'birthdate': get_formatted_date_or_default(child['year_of_birth'], child['month_of_birth'], child['day_of_birth'], None),
						'deathdate': get_formatted_date_or_default(child['year_of_death'], child['month_of_death'], child['day_of_death'], None),
						'post_id': "%d-%d" % (COLLECTION_ID, child['person_id']),
						'positions': [] }, card['children']))

			except TypeError as error:
				print("error: %s" % repr(error))
				print("person:")
				print(person)
				print("card:")
				print(card)
				SNS_Notifier.error(repr(error))
				sys.exit(1)

			documents.append({
				'id': "%d-%d" % (COLLECTION_ID, person_id),
				'task_id': -1,
				'post_id': -1,
				'entry_id': -1,
				'user_id': -1,
				'user_name': ' ',
				'unit_id': -1,
				'page_id': -1,
				'jsonObj': json.dumps(data),
				'collection_id': COLLECTION_ID,
				'collection_info': 'Politiets registerblade',
				'firstnames': person['firstnames'],
				'lastname': person['lastname'],
				'birthname': person['birthname'],
				'positions': person['positions'] if 'positions' in person else [],
				'sex': "Mand" if person['sex'] == 1 else "Kvinde" if person['sex'] == 2 else "Ukendt",
				'civilstatus': "Gift" if person['married'] == 1 and person['person_type'] == 1 else "",
				'person_id': person_id,
				'personType': person['person_type'],
				'card_id': person["registerblad_id"],
				'dateOfBirth': get_formatted_date_or_default(person['year_of_birth'], person['month_of_birth'], person['day_of_birth'], ""),
				'dateOfDeath': get_formatted_date_or_default(person['year_of_death'], person['month_of_death'], person['day_of_death'], ""),
				'yearOfBirth': person['year_of_birth'] if 'year_of_birth' in person else '',
				'birthplace': person['birthplace'],
				'spouseNames': list(map(lambda spouse: "%s %s" % (spouse['firstnames'], spouse['lastname']), card['spouses'])) if person['person_type'] == 1 else ["%s %s" % (card['main']['firstnames'], card['main']['lastname'])] if card['main'] is not None and person['person_type'] == 2 else "",
				'childNames': list(map(lambda child: "%s %s" % (child['firstnames'], child['lastname']), card['children'])) if person['person_type'] == 1 else [],
				'addresses': list(map(lambda address: address['full_address'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
				'streets': list(map(lambda address: address['street'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
				#'institutions': list(map(lambda address: address['institution'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
				'locations': list(map(lambda address: address['location'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
				'spousePositions': list(reduce(lambda positions, spouse: positions + (spouse['positions'] if 'positions' in spouse else []), card['spouses'], [])) if person['person_type'] == 1 else [],
				'comment': "" if person['person_comment'] is None else person['person_comment'],
				'cardComment':  "" if person['registerblad_comment'] is None else person['registerblad_comment'],
				'specialComment': "" if person['special_comments'] is None else person['special_comments'],
				'adr_to_note': list(map(lambda address: address['to_note'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
				'adr_from_note': list(map(lambda address: address['from_note'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
			})

# Ægtefælles fødested
# Fødested
# Hovedpersons fødested
# Stilling
# Ægtefælles stilling
# Hovedpersons stilling
# Fødselsdato (dd-mm-åååå)
# Fødselsår (åååå)
# Fødselsmåned (mm)
# Fødelsdag (dd)
# Dødsdato (dd-mm-åååå)
# Dødsår (åååå)
# Dødsmåned (mm)
# Dødsdag (dd)
# Hovedpersons fornavne
# Hovedpersons efternavn
# Ægtefælles fornavne
# Ægtefælles efternavn
# Barns fornavne
# Barns efternavn
# Vejnavn
# Vejnummer
# Vejnummerbogstav
# Etage
# Sideangivelse
# Indgang
# I tjeneste/logerende hos
# Sted

		writeflush("%7d/%7d (%5f docs/sec) - Adding SOLR documents                     \r" % (totalDocuments, person_count, docspsec))

		try:
			count = count + len(documents)
			totalDocuments = count + totalDocuments
			if(count > 5000):
				writeflush("%7d/%7d (%5f docs/sec) - Committing SOLR documents                     \r" % (totalDocuments, person_count, docspsec))
				solr.add(documents, commit=False)
				documents = []
				solr.commit()
				count = 0;
			#sys.exit(1)
		except Exception as e:
			writeflush("\nFailed.\nError %s\n" % repr(e))
			SNS_Notifier.error(repr(e))
			sys.exit(1)

	if len(documents) > 0:
		writeflush("%7d/%7d (%5f docs/sec) - Committing SOLR documents                     \r" % (totalDocuments, person_count, docspsec))
		solr.add(documents, commit=False)
		documents = []
		solr.commit()
		count = 0;

	#print("Committing!")
	solr.commit()
	print("\nAll done!")
	sys.exit(0)
