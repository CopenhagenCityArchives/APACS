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
	}

	public function createTables() {
		//Creating entities table
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities');
		$this->di->get('db')->query("CREATE TABLE `apacs_entities` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `task_id` int(11) NOT NULL,
			  `name` char(11) NOT NULL,
			  `isPrimaryEntity` tinyint(1) DEFAULT '0',
			  `entityKeyName` char(45) COLLATE utf8_danish_ci DEFAULT NULL,
			  `type` char(6) COLLATE utf8_danish_ci DEFAULT NULL,
			  `required` tinyint(1) NOT NULL DEFAULT '0',
			  `countPerEntry` char(3) COLLATE utf8_danish_ci NOT NULL DEFAULT 'one',
			  `guiName` char(50) COLLATE utf8_danish_ci NOT NULL,
			  `primaryTableName` char(250) COLLATE utf8_danish_ci NOT NULL,
			  `includeInSOLR` tinyint(1) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
		");

		//Creating fields table
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_fields');
		$this->di->get('db')->query("CREATE TABLE `apacs_fields` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`entities_id` int(11) DEFAULT NULL COMMENT 'FK til entities',
			`steps_id` int(11) DEFAULT NULL,
			`tableName` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
			`fieldName` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
			`formFieldType` varchar(45) COLLATE utf8_danish_ci DEFAULT 'string',
			`hasDecode` tinyint(1) DEFAULT '0',
			`decodeField` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			`decodeTable` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			`foreignkeyFieldname` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
			`codeAllowNewValue` tinyint(1) NOT NULL DEFAULT '0',
			`includeInForm` tinyint(1) DEFAULT '1',
			`formName` char(50) COLLATE utf8_danish_ci DEFAULT NULL,
			`defaultValue` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			`helpText` char(150) COLLATE utf8_danish_ci DEFAULT NULL,
			`placeholder` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			`isRequired` tinyint(1) NOT NULL DEFAULT '0',
			`validationRegularExpression` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			`validationErrorMessage` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			`includeInSOLR` tinyint(1) DEFAULT '1',
			`SOLRFieldName` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
			`formFieldOrder` varchar(45) COLLATE utf8_danish_ci DEFAULT NULL,
			`datasources_id` INT(11) DEFAULT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;
			");
	}

	public function insertEntity() {
		$this->di->get('db')->query(
			"INSERT INTO `apacs_entities` VALUES
			(1,1,'persons',1,'persons_id','object',1,'1','Personer','burial_persons',1),
			(2,1,'deathcauses',0,'persons_id','array',1,'1','Dødsårsag','burial_persons_deathcauses',1),
			(3,1,'positions',0,'persons_id','array',0,'1','Stilling','burial_persons_positions',1),
			(4,1,'addresses',0,'persons_id','object',1,'1','Adresse','burial_addresses',1);"
		);

		$this->di->get('db')->query(
			"INSERT INTO `apacs_fields` VALUES
			(7,1,NULL,'burial_persons','firstnames','string',0,NULL,NULL,NULL,0,1,'Fornavne',NULL,'Skriv den begravede persons fornavn. Alle navne der ikke er efternavnet er fornavne.',NULL,1,'/\\\\w{1,}/','Du må kun skrive bogstaver',1,'firstnames', 1,null),
			(9,1,NULL,'burial_persons','lastname','string',0,NULL,NULL,NULL,0,1,'Efternavn',NULL,'Skriv den begravede persons efternavn. Bemærk at du kun må taste ET efternavn.',NULL,0,NULL,NULL,1,'lastname', 1,null),
			(10,2,NULL,'burial_persons_deathcauses','deathcauses_id','typeahead',1,'deathcause','burial_deathcauses','?',1,1,'Dødsårsag',NULL,'Vælg den begravede persons dødsårsag fra listen. Hvis dødsårsagen ikke findes så skriv den.',NULL,0,'/\\\\w{1,}/','Du må kun skrive bogstaver',1,'deathcauses',1,1),
			(11,3,NULL,'burial_persons_positions','positions_id','typeahead',1,'position','burial_positions',NULL,0,1,'Stilling',NULL,'Vælg den begravede persons stilling fra listen. Hvis stillingen ikke findes så vælg *Skal oprettes',NULL,0,'/\\\\w{1,}/','Du skal vælge en værdi fra listen',1,'positions',1,null),
			(12,3,NULL,'burial_persons_positions','relationtypes_id',1,'select','relationtype','burial_relationtypes',NULL,0,1,'Relation til stillingen','Egen','Ændre hvis stillingen ikke er den begravede persons egen',NULL,0,'/\\\\w{1,}/','Du skal vælge en værdi fra listen',0,NULL,1,null);");
	}

	public function getEntity($id = 1) {
		/*$id = 1;
			$result = $this->di->get('db')->query('select * from apacs_entities where id = ' . $id);
			//Return associative array without integer indexes
			$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
			$return = $result->fetchAll()[0];

			$result = $this->di->get('db')->query('select * from apacs_fields WHERE entity_id = ' . $id);
			//Return associative array without integer indexes
			$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
			$return['fields'] = $result->fetchAll();

			$i = 0;
			foreach ($return['fields'] as $key => $field) {
				$return['fields'][$field['dbFieldName']] = $field;
				unset($return['fields'][$key]);
				$i++;
		*/

		$entity = \Entities::find(['conditions' => 'id = ' . $id])[0];

		return $entity;
	}
}