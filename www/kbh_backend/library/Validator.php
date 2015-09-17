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
				if(!isset($dataToValidate) || $dataToValidate === null || trim($dataToValidate) == "")
					return false;
			}

			if(preg_match($this->_validationRule->regularExpression, $dataToValidate) == 1)
				return true;	

			return false;
		}
	}