<?php
namespace Mocks;

class EntitiesMock {
	private $di;
	public function __construct() {
		$di = new \Phalcon\Di\FactoryDefault;
		$di->set('db', function () {
			return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
				"host" => "localhost",
				"username" => "root",
				"password" => "",
				"dbname" => "unit_tests",
				'charset' => 'utf8',
			));
		}
		);

		$this->di = $di;
	}

	public function clearDatabase() {
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities');
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_fields');
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities_fields');
	}

	public function createTables() {
		//Creating entities table
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities');
		$this->di->get('db')->query("CREATE TABLE `apacs_entities` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `required` tinyint(1) NOT NULL DEFAULT '0',
			  `countPerEntry` char(3) COLLATE utf8_danish_ci NOT NULL DEFAULT 'one',
			  `dbTableName` char(50) COLLATE utf8_danish_ci NOT NULL,
			  `isMarkable` tinyint(1) NOT NULL DEFAULT '0',
			  `guiName` char(50) COLLATE utf8_danish_ci NOT NULL,
			  `task_id` int(11) NOT NULL,
			  `primaryKeyFieldName` char(100) COLLATE utf8_danish_ci NOT NULL DEFAULT 'id',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);

		//Creating fields table
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_fields');
		$this->di->get('db')->query("CREATE TABLE `apacs_fields` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `name` char(50) COLLATE utf8_danish_ci NOT NULL,
			  `defaultValue` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `placeholder` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `helpText` char(150) COLLATE utf8_danish_ci DEFAULT NULL,
			  `dbFieldName` char(50) COLLATE utf8_danish_ci NOT NULL,
			  `type` char(20) COLLATE utf8_danish_ci NOT NULL DEFAULT 'string',
			  `includeInSolr` tinyint(1) NOT NULL DEFAULT '0',
			  `includeInForm` tinyint(1) NOT NULL DEFAULT '1',
			  `required` tinyint(1) NOT NULL DEFAULT '0',
			  `validationRegularExpression` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `validationErrorMessage` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `foreignEntityName` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `foreignFieldName` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `unique` tinyint(1) NOT NULL DEFAULT '0',
			  `newValueAllowed` tinyint(1) NOT NULL DEFAULT '1',
			  `internal_description` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
			"
		);

		//Creating entities_fields table
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities_fields');
		$this->di->get('db')->query("CREATE TABLE `apacs_entities_fields` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `field_id` int(11) NOT NULL,
			  `entity_id` int(11) NOT NULL,
			  `step_id` int(11) NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);
	}

	public function insertEntityWithObjectRelation() {
		$this->di->get('db')->query("INSERT INTO `apacs_entities` VALUES (1,1,'1','begrav_persons',1,'Personer',1,'id'),(2,1,'1','begrav_deathcauses',0,'Dødsårsag',1,'id'),(3,1,'1','begrav_personer_deathcauses',0,'Dødsårsag_mange_til_mange',1,'id');");
		$this->di->get('db')->query("INSERT INTO `apacs_entities_fields` VALUES (10,7,1,1),(11,8,1,1),(12,9,1,1),(13,10,1,1),(15,11,1,1),(14,12,2,1);");
		$this->di->get('db')->query("INSERT INTO `apacs_fields` VALUES
			(7,'id',NULL,'',NULL,'id','string',0,0,0,NULL,NULL,NULL,NULL,1,1,'Primærnøgle'),
			(8,'firstname',NULL,'Fornavn','Personens fornavne','firstnames','string',1,1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Fornavn'),
			(9,'Lastname',NULL,'Efternavn','Efternavn','lastname','string',1,1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Efternavn'),
			(10,'begrav_deathcauses',NULL,'Dødsårsag','Dødsårsag','begrav_deathcauses','object',0,0,0,NULL,NULL,'2','id',0,0,'Fremmednøgle til dødsårsag'),
			(11,'entry_id',NULL,'',NULL,'entry_id','string',0,0,0,NULL,NULL,NULL,NULL,1,1,'Entry id'),
			(12,'begrav_deathcauses',NULL,'Dødsårsag','Dødsårsag','begrav_deathcauses','string',1,1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,1,1,'Dødsårsag');");
	}

	public function insertEntityWithoutRelations() {
		$this->di->get('db')->query("INSERT INTO `apacs_entities` VALUES (1,1,'1','begrav_persons',1,'Personer',1,'id'),(2,1,'1','begrav_deathcauses',0,'Dødsårsag',1,'id');");
		$this->di->get('db')->query("INSERT INTO `apacs_entities_fields` VALUES (10,7,1,1),(11,8,1,1),(12,9,1,1),(13,10,1,1),(14,12,2,1);");
		$this->di->get('db')->query("INSERT INTO `apacs_fields` VALUES
			(7,'id',NULL,'',NULL,'id','value',0,0,0,NULL,NULL,NULL,NULL,1,1,'Primærnøgle'),
			(8,'firstname',NULL,'Fornavn','Personens fornavne','firstnames','value',1,1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Fornavn'),
			(9,'Lastname',NULL,'Efternavn','Efternavn','lastname','value',1,1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Efternavn');"
		);
	}

	public function insertEntityWithArrayRelations() {
		$this->di->get('db')->query("INSERT INTO `apacs_entities` VALUES (1,1,'1','begrav_persons',1,'Personer',1,'id'),(2,1,'1','begrav_deathcauses',0,'Dødsårsag',1,'id'),(3,0,'1','begrav_addresses',0,'Adresse',1,'id'),(4,1,'1','begrav_streets',0,'Vej',1,'id'),(5,0,'-1','begrav_persons_deathcauses',0,'Personers dødsårsager',1,'id'),(6,0,'1','begrav_chapels',0,'Kapel',1,'id'),(7,0,'1','begrav_floors',0,'Etage',1,'id');");
		$this->di->get('db')->query("INSERT INTO `apacs_entities_fields` VALUES (10,7,1,1),(11,8,1,1),(12,9,1,1),(15,13,1,1),(16,14,1,1),(17,15,1,1),(18,16,1,1),(19,17,1,1),(20,18,1,1),(21,23,1,1),(22,7,5,1),(23,21,5,1),(24,22,5,1),(25,7,2,1),(26,12,2,1),(27,7,3,1),(28,19,3,1),(29,7,4,1),(30,20,4,1),(31,7,6,1),(32,24,6,1),(33,7,5,1),(34,21,5,1),(35,22,5,1),(36,7,7,1),(37,25,7,1),(38,26,3,1),(39,7,2,1),(40,12,2,1),(41,27,1,1);");
		$this->di->get('db')->query("INSERT INTO `apacs_fields` VALUES (7,'id',NULL,'',NULL,'id','string',0,0,0,NULL,NULL,NULL,NULL,1,1,'Primærnøgle'),(8,'firstname',NULL,'Fornavn','Personens fornavne','firstnames','string',1,1,1,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Fornavn'),(9,'Lastname',NULL,'Efternavn','Efternavn','lastname','string',1,1,1,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Efternavn'),(12,'begrav_deathcauses',NULL,'Dødsårsag','Dødsårsag','begrav_deathcauses','string',1,1,1,'/\\w{1,}/','Feltet skal udfyldes',NULL,NULL,1,1,'Dødsårsag'),(13,'birthname',NULL,'Fødenavn','Fødenavn','birthname','string',1,1,0,'/\\w{1,}/','',NULL,NULL,0,1,'Fødenavn'),(14,'age_years',NULL,'Alder, år','Alder, år','age_years','string',1,1,1,'/\\d{1,3}/','Feltet skal udfyldes',NULL,NULL,0,1,'Alder i år'),(15,'age_months',NULL,'Alder, måneder','Alder, måneder','age_months','string',0,1,0,NULL,NULL,NULL,NULL,0,1,'Alder i måneder'),(16,'birth_date',NULL,'Fødselsdato','Fødselsdato','birth_date','string',1,1,0,NULL,NULL,NULL,NULL,0,1,'Fødselsdato'),(17,'death_date',NULL,'Dødsdato','Dødsdato','death_date','string',1,1,0,NULL,NULL,NULL,NULL,0,1,'Dødsdato'),(18,'addresses',NULL,NULL,NULL,'addresses','object',0,1,1,NULL,NULL,'3','id',0,1,'Fremmednøgle til begrav_addresses'),(19,'street_id',NULL,NULL,NULL,'','object',0,0,0,NULL,NULL,'4','id',0,1,'Fremmednøgle til begrav_streets'),(20,'name',NULL,'Vejnavn','Vejnavn','name','string',1,1,1,'/\\w{1,}','Feltet skal udfyldes',NULL,NULL,1,0,'Fremmednøgle til vejnavn'),(21,'persons_id',NULL,NULL,NULL,'persons_id','object',0,0,1,NULL,NULL,'1','id',0,1,'Fremmednøgle til begrav_persons fra begrav_persons_deathcauses'),(22,'deathcauses_id',NULL,NULL,NULL,'deathcauses_id','object',0,0,1,NULL,NULL,'2','id',0,1,'Fremmednøgle til begrav_deathcauses'),(23,'chapels_id',NULL,NULL,NULL,'chapels_id','object',0,0,1,NULL,NULL,'6','id',0,1,'Fremmednøgle til begrav_chapels'),(24,'name',NULL,'Kapelnavn','Kapelnavn','name','string',1,1,1,'/\\w{1,}','Feltet skal udfyldes',NULL,NULL,1,0,'Kapelnavn'),(25,'floor',NULL,'Etage','Etage','floor','string',1,1,1,'/\\w{1,}','Feltet skal udfyldes',NULL,NULL,1,0,'Etage'),(26,'floor_id',NULL,NULL,NULL,'floor_id','object',0,0,0,NULL,NULL,'7','id',0,1,'Fremmednøgle til begrav_etage'),(27,'begrav_deathcauses',NULL,NULL,NULL,'begrav_deathcauses','array',0,0,0,NULL,NULL,'5','begrav_deathcauses',0,0,'Kunstig, omvendt relation fra begrav_person til begrav_deathcauses');");
	}

	public function getDefaultEntity() {
		$id = 1;
		$result = $this->di->get('db')->query('select * from apacs_entities where id = ' . $id);
		//Return associative array without integer indexes
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		$return = $result->fetchAll()[0];

		$result = $this->di->get('db')->query('select * from apacs_entities_fields left join apacs_fields on apacs_entities_fields.field_id = apacs_fields.id WHERE apacs_entities_fields.entity_id = ' . $id);
		//Return associative array without integer indexes
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		$return['fields'] = $result->fetchAll();

		$i = 0;
		foreach ($return['fields'] as $key => $field) {
			$return['fields'][$field['dbFieldName']] = $field;
			unset($return['fields'][$key]);
			$i++;
		}

		return $return;
	}
}