<?php

include '../kbh_backend/library/ValidationRuleSet.php';
include '../kbh_backend/library/Validator.php';

class ValidationRuleSetTest extends \UnitTestCase {
    
    public function setUp(\Phalcon\DiInterface $di = NULL, \Phalcon\Config $config = NULL) {
        parent::setUp($di, $config);
    }
    
    public function tearDown() {
        parent::tearDown();
    }

    public function testValidateByRegularExpression()
    {
        $validationRule = new ValidationRuleSet('/\d{1}/', false, 'Input should contain a single digit');
        $Validator = new Validator($validationRule);

        $this->assertEquals(true, $Validator->Validate(1), 'Should return true when data is valid');

        $this->assertEquals(false, $Validator->Validate("a"), 'Should return false when data is invalid');
    }

    public function testValidateWhenRequired()
    {
        $validationRule = new ValidationRuleSet('/\w{0,1}/', true, 'Input should contain zero to one character');
        $Validator = new Validator($validationRule);

        $this->assertEquals(false, $Validator->Validate(null), 'should return false if input required and null is given');

        $this->assertEquals(false, $Validator->Validate(""), 'should return false if input required and empty string is given');
    }

    public function testValidateWhenNotRequired()
    {
        $ValidationRule = new ValidationRuleSet('/\w{0,1}/', false, 'Input is not required');
        $Validator = new Validator($ValidationRule);
        $this->assertEquals(true, $Validator->Validate(''), 'should return true if input is not required and empty');
    }
}