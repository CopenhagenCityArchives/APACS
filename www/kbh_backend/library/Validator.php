<?php

	class Validator
	{
		private $_validationRule;

		function __construct(ValidationRuleSet $vr){
			$this->_validationRule = $vr;
		}

		/**
		 * Validates a given input based on the ValidationRuleSet.
		 * @return bool Wether or not the input is valid
		 */
		public function Validate($dataToValidate){
			if($this->_validationRule->required){
				if(!isset($dataToValidate) || $dataToValidate === NULL  || $dataToValidate === null || trim($dataToValidate) == "")
					return false;
			}

			if($this->_validationRule->regularExpression === false)
				return true;

			if(preg_match($this->_validationRule->regularExpression, $dataToValidate) == 1)
				return true;	

			return false;
		}

		public function GetErrorMessage()
		{
			return $this->_validationRule->errorMessage;
		}
	}