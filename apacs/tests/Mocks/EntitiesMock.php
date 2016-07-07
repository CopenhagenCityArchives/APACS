<?php
namespace Mocks;

class EntitiesMock {
	private $di;
	
	public function __construct($di) {
		$this->di = $di;
	}

	public function clearDatabase() {
		$this->di->get('db')->query('DELETE FROM apacs_entities WHERE 1');
		$this->di->get('db')->query('DELETE FROM apacs_fields WHERE 1');
	}

	public function insertEntities() {
		$this->di->get('db')->query(
			"INSERT INTO `apacs_entities`
			(id, task_id, name, isPrimaryEntity, entityKeyName, type, required, countPerEntry, guiName,primaryTableName, includeInSOLR)
			VALUES
			(1,1,'persons',1,'persons_id','object',1,'1','Personer','burial_persons',1),
			(2,1,'deathcauses',0,'persons_id','array',1,'1','Dødsårsag','burial_persons_deathcauses',1),
			(3,1,'positions',0,'persons_id','array',0,'1','Stilling','burial_persons_positions',1),
			(4,1,'addresses',0,'persons_id','object',1,'1','Adresse','burial_addresses',1);"
		);

		$this->di->get('db')->query(
			"INSERT INTO `apacs_fields`
			(id, entities_id, tableName, fieldName, formFieldType, includeInForm, formName, helpText, isRequired, validationRegularExpression, validationErrorMessage,includeInSOLR, SOLRFieldName, SOLRFacet)
			VALUES
			(7,1,'burial_persons','firstnames','string',1,'Fornavne','hjælpetekst',1,'/\\\\w{1,}/','Du må kun skrive bogstaver',1,'firstnames', 1),
			(9,1,'burial_persons','lastname','string',1,'Efternavn','hjælpetekst',1,NULL,NULL,1,'lastname', 1);"
			/*
			(10,2,'burial_persons_deathcauses','deathcauses_id','typeahead',1,'deathcause','burial_deathcauses','?',1,1,'Dødsårsag',NULL,'Vælg den begravede persons dødsårsag fra listen. Hvis dødsårsagen ikke findes så skriv den.',NULL,0,'/\\\\w{1,}/','Du må kun skrive bogstaver',1,'deathcauses',1,1),
			(11,3,'burial_persons_positions','positions_id','typeahead',1,'position','burial_positions',NULL,0,1,'Stilling',NULL,'Vælg den begravede persons stilling fra listen. Hvis stillingen ikke findes så vælg *Skal oprettes',NULL,0,'/\\\\w{1,}/','Du skal vælge en værdi fra listen',1,'positions',1,null),
			(12,3,'burial_persons_positions','relationtypes_id',1,'select','relationtype','burial_relationtypes',NULL,0,1,'Relation til stillingen','Egen','Ændre hvis stillingen ikke er den begravede persons egen',NULL,0,'/\\\\w{1,}/','Du skal vælge en værdi fra listen',0,NULL,1,null);"*/); 
	}

	public function getEntity($id = 1) {
		$entity = \Entities::find(['conditions' => 'id = ' . $id])[0];

		return $entity;
	}
}	