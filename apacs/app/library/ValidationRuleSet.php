<?php

	class ValidationRuleSet
	{
		public $regularExpression;
		public $errorMessage;
		public $required;
		public $type;

		function __construct($regexp, $required, $errorMessage, $type){
			$this->regularExpression = $regexp;
			$this->required = $required;
			$this->errorMessage = $errorMessage;
			$this->type = $type;
		}
	}