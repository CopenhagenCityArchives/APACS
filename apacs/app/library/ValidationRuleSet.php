<?php

	class ValidationRuleSet
	{
		public $regularExpression;
		public $errorMessage;
		public $required;

		function __construct($regexp, $required, $errorMessage){
			$this->regularExpression = $regexp;
			$this->required = $required;
			$this->errorMessage = $errorMessage;
		}
	}