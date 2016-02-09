<?php
include_once '../lib/library/GenericEntry.php';

class GenericEntryTest extends \UnitTestCase {

	private $ge;
	private $fields;
	private $tablename;
	protected $di;

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
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
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();

		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_persons');
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_deathcause');
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities');
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_fields');
		$this->di->get('db')->query('DROP TABLE IF EXISTS apacs_entities_fields');

		$this->ge = null;
		$this->entity = [];
	}

	public function createTables() {
		$this->di->get('db')->query('DROP TABLE IF EXISTS entry_table');
		$this->di->get('db')->query('DROP TABLE IF EXISTS normalized_table');
		$this->di->get('db')->query('CREATE TABLE entry_table (id INT(11) NOT NULL AUTO_INCREMENT, testFieldDb CHAR(50), normalizedFieldDb CHAR(50), post_id INT(11), PRIMARY KEY (`id`))');
		$this->di->get('db')->query('CREATE TABLE normalized_table (id INT(11) NOT NULL AUTO_INCREMENT, normalized_field CHAR(50), PRIMARY KEY (`id`))');

		//Concrete table begrav_person
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_persons');
		$this->di->get('db')->query("CREATE TABLE `begrav_persons` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `firstnames` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `lastname` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `birthname` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `deathcause` int(11) DEFAULT NULL,
			  `age_years` int(11) DEFAULT NULL,
			  `age_month` int(11) DEFAULT NULL,
			  `dateofbirth` datetime DEFAULT NULL,
			  `dateofdeath` datetime DEFAULT NULL,
			  `placeofdeath_id` int(11) DEFAULT NULL,
			  `cemetary_id` int(11) DEFAULT NULL,
			  `parish_id` int(11) DEFAULT NULL,
			  `civilstatus_id` int(11) DEFAULT NULL,
			  `birthplace_id` int(11) DEFAULT NULL,
			  `birthplace_other` char(125) COLLATE utf8_danish_ci DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);

		//Concrete table begrav_deathcause
		$this->di->get('db')->query('DROP TABLE IF EXISTS begrav_deathcause');
		$this->di->get('db')->query("CREATE TABLE `begrav_deathcause` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `deathcasuse` varchar(125) COLLATE utf8_danish_ci DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
		);

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
			  `required` tinyint(1) NOT NULL DEFAULT '0',
			  `validationRegularExpression` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `validationErrorMessage` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `foreignEntity` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `foreignFieldName` char(100) COLLATE utf8_danish_ci DEFAULT NULL,
			  `unique` tinyint(1) NOT NULL DEFAULT '0',
			  `newValueAllowed` tinyint(1) NOT NULL DEFAULT '1',
			  `internal_description` char(250) COLLATE utf8_danish_ci DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_danish_ci;"
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
		$this->di->get('db')->query("INSERT INTO `apacs_entities` VALUES (1,1,'1','begrav_persons',1,'Personer',1,'id'),(2,1,'1','begrav_deathcause',0,'Dødsårsag',1,'id');");
		$this->di->get('db')->query("INSERT INTO `apacs_entities_fields` VALUES (10,7,1,1),(11,8,1,1),(12,9,1,1),(13,10,1,1),(14,12,2,1);");
		$this->di->get('db')->query("INSERT INTO `apacs_fields` VALUES (7,'id',NULL,'',NULL,'id','value',0,0,NULL,NULL,NULL,NULL,1,1,'Primærnøgle'),(8,'firstname',NULL,'Fornavn','Personens fornavne','firstnames','value',1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Fornavn'),(9,'Lastname',NULL,'Efternavn','Efternavn','lastname','value',1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,0,1,'Efternavn'),(10,'deathcause',NULL,'Dødsårsag','Dødsårsag','deathcause','object',0,0,NULL,NULL,'2','id',0,0,'Fremmednøgle til dødsårsag'),(12,'deathcause',NULL,'Dødsårsag','Dødsårsag','deathcause','value',1,1,'/\\\\w{1,}/','Feltet skal udfyldes',NULL,NULL,1,1,'Dødsårsag');");
	}

	public function getDefaultEntity() {
		$id = 1;
		$result = $this->getDI()->get('db')->query('select * from apacs_entities where id = ' . $id);
		$return['table'] = $result->fetchAll()[0];

		$result = $this->getDI()->get('db')->query('select * from apacs_entities_fields left join apacs_fields on apacs_entities_fields.field_id = apacs_fields.id WHERE apacs_entities_fields.entity_id = ' . $id);
		$return['fields'] = $result->fetchAll();

		return $return;
	}

	public function getSimpleEntry() {
		return [
			'firstnames' => 'Jens',
			'lastname' => 'Larsen',
			'deathcause' => [
				'deathcause' => 'Hjertefejl',
			],
		];
	}

	public function getSimpleEntityWithError() {
		return [
			'firstnames' => 'Jens',
			'lastname' => '',
			'deathcause' => [
				'deathcause' => 'Hjertefejl',
			],
		];
	}

	public function testValidateEntry() {
		$this->createTables();
		$this->insertEntityWithObjectRelation();
		$entity = $this->getDefaultEntity();

		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$valuesAreValid = $this->ge->ValidateValues($this->getSimpleEntityWithError());

		$this->assertEquals(false, $valuesAreValid, 'should return false on invalid data');
	}

	public function testSaveEntry() {
		$this->createTables();
		$this->insertEntityWithObjectRelation();
		$entity = $this->getDefaultEntity();

		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$couldSave = $this->ge->Save($this->getSimpleEntry());

		$this->assertEquals(true, $couldSave, 'should save data');
	}

	public function testLoadEntry() {
		$this->createTables();
		$this->insertEntityWithObjectRelation();
		$entity = $this->getDefaultEntity();

		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`) VALUES (1,'Jens','Nielsen');");

		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$result = $this->ge->Load(1);
		$this->assertEquals(1, count($result), 'should return a row of data');
		$this->assertEquals(['id' => '1', 'firstnames' => 'Jens', 'lastname' => 'Nielsen'], $result[0], 'should return values of type value');
	}

	public function testUpdateEntry() {
		$this->createTables();
		$this->insertEntityWithObjectRelation();
		$entity = $this->getDefaultEntity();

		//Inserting data to update
		$this->di->get('db')->query("INSERT INTO `begrav_persons` (`id`, `firstnames`, `lastname`) VALUES (1,'Jens','Nielsen');");

		//Updating
		$this->ge = new GenericEntry($entity['table'], $entity['fields'], $this->di->get('db'));
		$updatedValues = ['id' => '1', 'firstnames' => 'Niels', 'lastname' => 'Hansen', 'deathcause' => 'shouldnt have effect'];
		$this->ge->Update($updatedValues);

		$expectedValuesAfterUpdate = ['id' => '1', 'firstnames' => 'Niels', 'lastname' => 'Hansen'];

		//Loading result
		$result = $this->di->get('db')->query("SELECT id,firstnames,lastname FROM `begrav_persons` WHERE id = 1;");
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$this->assertEquals($expectedValuesAfterUpdate, $result->fetchAll()[0], 'should update existing values');
	}
}