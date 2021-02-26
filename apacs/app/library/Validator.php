<?php

class Validator {
	private $_validationRule;

	function __construct(ValidationRuleSet $vr) {
		$this->_validationRule = $vr;
	}

	/**
	 * Validates a given input based on the ValidationRuleSet.
	 * @return bool Wether or not the input is valid
	 */
	public function IsValid($data, $keyName = null, $ignoreNullValues = false) {

		if ($keyName !== null) {
			if (!isset($data[$keyName]) && $this->_validationRule->required == '1') {
				return false;
			}
			if (!isset($data[$keyName]) && $this->_validationRule->required == '0') {
				return true;
			}
			$dataToValidate = $data[$keyName];
		} else {
			$dataToValidate = $data;
		}

		if ($dataToValidate == null && $ignoreNullValues == true) {
			return true;
		}

		if ($this->_validationRule->required == '1' || $this->_validationRule->required == true) {
			if ($this->_validationRule->type == 'boolean' && $dataToValidate === false) {
				return true;
			}

			if (!isset($dataToValidate) || $dataToValidate === NULL || $dataToValidate === null || trim($dataToValidate) == "") {
				return false;
			}
		}

		if ($this->_validationRule->regularExpression == false || $this->_validationRule->regularExpression == null) {
			return true;
		}

		if (preg_match($this->_validationRule->regularExpression, $dataToValidate) == 1) {
			return true;
		}

		if (trim($dataToValidate) == '') {
			return true;
		}

		return false;
	}

	public function GetErrorMessage() {
		return $this->_validationRule->errorMessage;
	}
}