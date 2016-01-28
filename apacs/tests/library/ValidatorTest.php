<?php

include '../lib/library/ValidationRuleSet.php';
include '../lib/library/Validator.php';

class ValidationRuleSetTest extends \UnitTestCase {

	public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
		parent::setUp($di, $config);
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function testValidateByRegularExpression() {
		$validationRule = new ValidationRuleSet('/^\w{2,}$/', false, 'Input should contain a single digit');
		$Validator = new Validator($validationRule);

		$this->assertEquals(true, $Validator->IsValid('sd'), 'Should return true when data is valid');

		$this->assertEquals(false, $Validator->IsValid(''), 'Should return false when data is invalid');
	}

	public function testSupportForFalseRegularExpression() {
		$validationRule = new ValidationRuleSet(false, false, 'Really not a required field');
		$Validator = new Validator($validationRule);

		$this->assertEquals(true, $Validator->IsValid(1));
	}

	public function testValidateWhenRequired() {
		$validationRule = new ValidationRuleSet('/\w{0,1}/', true, 'Input should contain zero to one character');
		$Validator = new Validator($validationRule);

		$this->assertEquals(false, $Validator->IsValid(null), 'should return false if input required and null is given');

		$this->assertEquals(false, $Validator->IsValid(NULL), 'should return false if input required and null is given');

		$this->assertEquals(false, $Validator->IsValid(""), 'should return false if input required and empty string is given');
	}

	public function testValidateWhenNotRequired() {
		$validationRule = new ValidationRuleSet('/\w{0,1}/', false, 'Input should contain zero to one character');
		$Validator = new Validator($validationRule);

		$this->assertEquals(true, $Validator->IsValid(null), 'should return false if input required and null is given');
	}

	public function testValidationWhenNullValuesAreIgnored() {
		$ValidationRule = new ValidationRuleSet('/\w{1,10}/', true, 'Should contain 1 to 10 characters', true);
		$Validator = new Validator($ValidationRule);
		$this->assertEquals(true, $Validator->IsValid(null, true), 'should return true if input is not required and empty');
	}

	public function testGetErrorMessage() {
		$ValidationRule = new ValidationRuleSet('', false, 'ErrorMessage');
		$Validator = new Validator($ValidationRule);

		$this->assertEquals('ErrorMessage', $Validator->GetErrorMessage(), 'should return error message for the given validation rule');
	}
}