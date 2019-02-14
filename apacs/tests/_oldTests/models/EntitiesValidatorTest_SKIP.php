<?php

class EntitiesValidatorTest extends \UnitTestCase {

	private $entityMock;
	private $entityValidator;

	public function setUp(Phalcon\DiInterface $di = NULL, Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);

		$this->entitiesFieldsMockConf = new \Mocks\EntitiesMock($this->di);

		$field1 = new Fields();
		$field1->fieldName = 'fieldOne';
		$field1->isRequired = 1;
		$field1->validationRegularExpression = '/\\w{1,}/';
		$field1->validationErrorMessage = 'fieldOne should contain at least one letter';

		$field2 = new Fields();
		$field2->fieldName = 'fieldTwo';
		$field2->isRequired = 1;
		$field2->validationRegularExpression = null;
		$field2->validationErrorMessage = null;

		$this->entityMock = new Entities();

		$this->entityMock->name = 'entityOne';
		$this->entityMock->fields = [$field1, $field2];

		$this->entityValidator = new EntitiesValidator();
		$this->entityValidator->setEntity($this->entityMock);
	}

	public function tearDown() {
		$this->entitiesFieldsMockConf->clearDatabase();
		parent::tearDown();
	}

	public function testValidateDataStructure() {
		$validData = [
			'entityOne' => [
				'fieldOne' => [],
				'fieldTwo' => [],
			],
		];

		$this->assertTrue($this->entityValidator->isDataStructureValid($validData), 'should return true when entity and field keys exists');

		$invalidEntityName = [
			'invalidEntity' => [],
		];

		$this->assertFalse($this->entityValidator->isDataStructureValid($invalidEntityName), 'should return false when invalid entity name is used');

		$invalidDataStructure = [
			'entityOne' => [
				'invalidField' => [],
			],
		];

		$this->assertFalse($this->entityValidator->isDataStructureValid($invalidDataStructure), 'should return false when data structure does not have a entity field');
	}

	public function testValidateRequiredData() {
		$validDataSet = [
			'entityOne' => [
				'fieldOne' => 'testValue1',
				'fieldTwo' => 'testValue2',
			],
		];
		$this->assertTrue($this->entityValidator->isRequiredDataSet($validDataSet), 'should return true when required values are set');
	}

	public function testValidateData() {
		$validEntityData = [
			'entityOne' => [
				'fieldOne' => 'hasValue',
				'fieldTwo' => 'hasValue',
			],
		];

		$this->assertTrue($this->entityValidator->isDataValid($validEntityData), 'should return true on valid data');

		$invalidEntityData = [
			'entityOne' => [
				'fieldOne' => null,
				'fieldTwo' => '',
			],
		];

		$this->assertFalse($this->entityValidator->isDataValid($invalidEntityData), 'should return false on invalid data');
	}

	public function testOverallValidation() {
		$invalidEntityData = [
			'entityOne' => [
				'fieldOne' => null,
				'fieldTwo' => '',
			],
		];

		$this->assertTrue(count($this->entityValidator->isDataStructureValid($invalidEntityData)) > 0, 'should set validation status on invalid data');
	}
}