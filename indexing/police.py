from base import IndexerBase
from config import Config

import pymysql
import pysolr
import sys
import json
from datetime import datetime
from functools import reduce


class PoliceIndexer(IndexerBase):

    def __init__(self):
        super().__init__()
        self.documents = []
        self.progress_threshold = 0.02
        self.progress_threshold_next = self.progress_threshold

        self.count_query = "SELECT COUNT(*) as count FROM PRB_person"

        self.person_query = """
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
    r.saerlige_bemaerkninger as special_remarks,
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
    ORDER BY p.registerblad_id
    LIMIT %d, %d
"""

        self.address_query = """
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
    IF((street IS NOT NULL or number IS NOT NULL or letter IS NOT NULL or floor IS NOT NULL or side IS NOT NULL or place IS NOT NULL) and entrance IS NOT NULL,", ", ""),
    IF(entrance IS NULL, "", entrance),
    IF((street IS NOT NULL or number IS NOT NULL or letter IS NOT NULL or floor IS NOT NULL or side IS NOT NULL or place IS NOT NULL or entrance IS NOT NULL) and servant_staying_at IS NOT NULL,", hos ", ""),
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
    IF(a.opgang <> "", a.opgang, null) as entrance,
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

        self.position_query = """
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


    def collection_id(self):
        return 17
    

    def collection_info(self):
        return "Politiets registerblade"


    def setup(self):
        self.log("Connecting to MySQL...")
        self.mysql = pymysql.connect(host=Config['polle_db']['host'], user=Config['polle_db']['user'], password=Config['polle_db']['password'], db=Config['polle_db']['database'], charset='utf8')
        self.log("OK.")

        self.log("Connecting to Solr...")
        self.solr = pysolr.Solr(Config['solr']['url'], auth=(Config['solr']['user'], Config['solr']['password']), timeout=300)
        self.log("OK.")
        
        self.log("Deleting all Politiets registerblade from Solr...")
        self.solr.delete(q=f"collection_id:{self.collection_id()}",commit=True)
        self.log("OK.")
    

    def get_total(self):
        with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
            cursor.execute(self.count_query)
            result = cursor.fetchone()
            return int(result['count'])
    

    def get_entries(self):
        for _, loaded_persons in self.chunk_query(self.person_query):
            cards = {}
            persons = {}
            errors = 0

            for person in loaded_persons:
                card_id = person['registerblad_id']
                person_type = person['person_type']

                person['person_type_text'] = self.person_type_text(person['person_type'])

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

            card_ids = ",".join(map(lambda p: str(p['registerblad_id']), loaded_persons))
            person_ids = ",".join(map(lambda p: str(p['person_id']), loaded_persons))

            # Load and add addresses
            with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
                cursor.execute(self.address_query % card_ids)
                for address in cursor.fetchall():
                    card_id = address['card_id']
                    address['address_date'] = self.get_formatted_date_or_default(address['year'], address['month'], address['day'], None)
                    if card_id in cards:
                        if 'addresses' in cards[card_id]:
                            cards[card_id]['addresses'].append(address)
                        else:
                            cards[card_id]['addresses'] = [address]
                    else:
                        errors += 1
            
            # Load and add positions
            with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
                q = self.position_query % person_ids
                cursor.execute(q)
                for result in cursor.fetchall():
                    person_id = result['person_id']
                    position = result['position'] if result['position_correct'] is None else result['position_correct']
                    if person_id in persons:
                        if position is not None and "positions" in persons[person_id]:
                            persons[person_id]["positions"].append(position)
                        elif position is not None:
                            persons[person_id]["positions"] = [position]
            
            for person_id in persons:
                yield persons[person_id], cards[persons[person_id]['registerblad_id']]

    
    def handle_entry(self, entry):
        person, card = entry
        person_id = person['person_id']

        valid_birthdate = self.valid_date(person["year_of_birth"], person['month_of_birth'], person['day_of_birth'])
        valid_deathdate = self.valid_date(person["year_of_death"], person['month_of_death'], person['day_of_death'])
        valid_completion_date = self.valid_date(person['completion_year'], person['completion_month'], person['completion_day'])

        # json object
        data = {
            'id': "%d-%d" % (self.collection_id(), person_id),
            'registerblad_id': person['registerblad_id'],
            'firstnames': person['firstnames'],
            'lastname': person['lastname'],
            'birthname': person['birthname'],
            'person_type': person['person_type'],
            'birthplace': person['birthplace'],
            'sex': person['sex'],
            'married': person['married'],
            'positions': list(map(lambda position: { 'position': position }, person['positions'])) if 'positions' in person else [],
            'dateOfBirth': self.get_formatted_date_or_default(person['year_of_birth'], person['month_of_birth'], person['day_of_birth'], None),
            'dateOfDeath': self.get_formatted_date_or_default(person['year_of_death'], person['month_of_death'], person['day_of_death'], None),
            'dateOfCompletion': self.get_formatted_date_or_default(person['completion_year'], person['completion_month'], person['completion_day'], None),
            'specialRemarks': None if person['special_remarks'] is None else person['special_remarks'],
            'person_comment': None if person['person_comment'] is None else person['person_comment'],
            'registerblad_comment': None if person['registerblad_comment'] is None else person['registerblad_comment'],
            'person_type_text': person['person_type_text'],
            "person_id": person_id,
            "station": person["station"],
            "film": person["film"],
            "number": person["number"],
            "file_front": person["file_front"],
            "file_back": person["file_back"],
            "collection_id": self.collection_id(),
            "collection_info": 'Politiets registerblade',
            'last_changed': person['last_changed']
        }
        if person["person_type"] == 3 and card['main'] is not None:
            data['parent'] = {
            'person_id': card['main']['person_id'],
            'firstnames': card['main']['firstnames'],
            'lastname': card['main']['lastname'],
            'birthplace': card['main']['birthplace'],
            'birthdate': self.get_formatted_date_or_default(card['main']['year_of_birth'], card['main']['month_of_birth'], card['main']['day_of_birth'], None),
            'deathdate': self.get_formatted_date_or_default(card['main']['year_of_death'], card['main']['month_of_death'], card['main']['day_of_death'], None),
            ' ': None if card['main']['special_remarks'] is None else person['special_remarks'],
            'post_id': "%d-%d" % (self.collection_id(), card['main']['person_id']),
            'positions': list(map(lambda position: { 'position': position }, card['main']['positions'])) if 'positions' in card['main'] else [] }
        elif person["person_type"] == 2 and card['main'] is not None:
            data['spouses'] = [{
                'person_id': card['main']['person_id'],
                'firstnames': card['main']['firstnames'],
                'lastname': card['main']['lastname'],
                'birthplace': card['main']['birthplace'],
                'birthdate': self.get_formatted_date_or_default(card['main']['year_of_birth'], card['main']['month_of_birth'], card['main']['day_of_birth'], None),
                'deathdate': self.get_formatted_date_or_default(card['main']['year_of_death'], card['main']['month_of_death'], card['main']['day_of_death'], None),
                'post_id': "%d-%d" % (self.collection_id(), card['main']['person_id']),
                'positions': list(map(lambda position: { 'position': position },card['main']['positions'])) if 'positions' in card['main'] else []
            }]
        elif person["person_type"] == 1:
            data['addresses'] = card['addresses'] if 'addresses' in card else []
            data['spouses'] = list(map(lambda spouse: {
                'person_id': spouse['person_id'],
                'firstnames': spouse['firstnames'],
                'lastname': spouse['lastname'],
                'birthplace': spouse['birthplace'],
                'birthdate': self.get_formatted_date_or_default(spouse['year_of_birth'], spouse['month_of_birth'], spouse['day_of_birth'], None),
                'deathdate': self.get_formatted_date_or_default(spouse['year_of_death'], spouse['month_of_death'], spouse['day_of_death'], None),
                'post_id': "%d-%d" % (self.collection_id(), spouse['person_id']),
                'positions': [] }, card['spouses']))
            data['children'] = list(map(lambda child: {
                'person_id': child['person_id'],
                'firstnames': child['firstnames'],
                'lastname': child['lastname'],
                'birthplace': child['birthplace'],
                'birthdate': self.get_formatted_date_or_default(child['year_of_birth'], child['month_of_birth'], child['day_of_birth'], None),
                'deathdate': self.get_formatted_date_or_default(child['year_of_death'], child['month_of_death'], child['day_of_death'], None),
                'post_id': "%d-%d" % (self.collection_id(), child['person_id']),
                'positions': [] }, card['children']))

        self.documents.append({
            'id': "%d-%d" % (self.collection_id(), person_id),
            'task_id': -1,
            'post_id': -1,
            'entry_id': -1,
            'user_id': -1,
            'user_name': ' ',
            'unit_id': -1,
            'page_id': -1,
            'jsonObj': json.dumps(data),
            'collection_id': self.collection_id(),
            'collection_info': self.collection_info(),
            'firstnames': person['firstnames'],
            'lastname': person['lastname'],
            'fullname': u"{0} {1}".format(person['firstnames'], person['lastname']),
            'birthname': person['birthname'],
            'positions': person['positions'] if 'positions' in person else [],
            'sex': "Mand" if person['sex'] == 1 else "Kvinde" if person['sex'] == 2 else "Ukendt",
            'person_id': person_id,
            'personType': person['person_type'],
            'card_id': person["registerblad_id"],
            'dateOfBirth': self.get_formatted_date_or_default(person['year_of_birth'], person['month_of_birth'], person['day_of_birth'], ""),
            'dateOfDeath': self.get_formatted_date_or_default(person['year_of_death'], person['month_of_death'], person['day_of_death'], ""),
            'yearOfBirth': person['year_of_birth'] if 'year_of_birth' in person else '',
            'yearOfDeath': person['year_of_death'] if 'year_of_death' in person else '',
            'birthplace': person['birthplace'],
            'spouseNames': list(map(lambda spouse: "%s %s" % (spouse['firstnames'], spouse['lastname']), card['spouses'])) if person['person_type'] == 1 else ["%s %s" % (card['main']['firstnames'], card['main']['lastname'])] if card['main'] is not None and person['person_type'] == 2 else "",
            'childNames': list(map(lambda child: "%s %s" % (child['firstnames'], child['lastname']), card['children'])) if person['person_type'] == 1 else [],
            'addresses': list(map(lambda address: address['full_address'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'streets': list(map(lambda address: address['street'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'places': list(map(lambda address: address['place'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'entrances': list(map(lambda address: address['entrance'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'institutions': list(map(lambda address: address['institution'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'locations': list(map(lambda address: address['location'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'spousePositions': list(reduce(lambda positions, spouse: positions + (spouse['positions'] if 'positions' in spouse else []), card['spouses'], [])) if person['person_type'] == 1 else [],
            'comments': ([person['person_comment']] if 'person_comment' in person else []) + list(filter(lambda n: n is not None, map(lambda address: address.get('adr_comment'), card.get('addresses') or []))) + ([person['registerblad_comment']] if 'registerblad_comment' in person else []) + ([] if person['special_remarks'] is None else [person['special_remarks']]),
            'adr_to_note': list(map(lambda address: address['to_note'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else [],
            'adr_from_note': list(map(lambda address: address['from_note'], card['addresses'])) if person['person_type'] == 1 and 'addresses' in card else []
        })

        if len(self.documents) >= 10000:
            self.solr.add(self.documents, commit=True)
            self.documents = []
    

    def wrapup(self):
        self.solr.add(self.documents, commit=True)


    def valid_date(self, year, month, day):
        if day == None or month == None or year == None:
            return False
        else:
            try:
                datetime(year, month, day)
                return True
            except ValueError:
                return False


    def get_formatted_date_or_default(self, year, month, day, default = None):
        if self.valid_date(year, month, day):
            return "%04d-%02d-%02dT00:00:00Z" % (year, month, day)
        else:
            return default


    def person_type_text(self, x):
        types = ['Ukendt','Hovedperson','Ægtefælle','Barn']
        return types[x]
        

    def chunk_query(self, query, chunksize=8192):
        results = []
        at = 0
        with self.mysql.cursor(pymysql.cursors.DictCursor) as cursor:
            while at == 0 or len(results) > 0:
                del results
                # Assume query already has replacement characters for limits
                cursor.execute(query % (at, chunksize))
                results = cursor.fetchall()
                if len(results) > 0:
                    yield (at, results)
                at += chunksize


if __name__ == "__main__":
    indexer = PoliceIndexer()
    indexer.index()
    sys.exit(0)