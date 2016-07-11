<?php

class ValidationRuleSet {
	public $regularExpression;
	public $errorMessage;
	public $required;

	function __construct($regexp, $required, $errorMessage = null) {
		$this->regularExpression = $regexp;
		$this->required = $required;
		$this->errorMessage = $errorMessage;
	}
}