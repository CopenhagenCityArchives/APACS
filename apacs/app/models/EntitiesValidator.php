<?php

class EntitiesValidator extends \Entities {
	private $validationStatus = [];
	private $entity;

	/**
	 * Sets the current entity
	 * @param Entities $entity The entity used for validation
	 */
	public function SetEntity(Entities $entity) {
		$this->entity = $entity;
	}

	/**
	 * Validates if the structure of the data is valid. Does not validate the concrete data
	 * @param  Array   $structureToValidate The structure to validate. Typically, this will be an entire data structure
	 * @return boolean True if the structure is valid
	 */
	public function isDataStructureValid(Array $structureToValidate) {
		if (!isset($structureToValidate[$this->entity->name])) {
			return false;
		}

		foreach ($this->entity->fields as $field) {
			if (!array_key_exists($field->fieldName, $structureToValidate[$this->entity->name])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns wheter or not required fields has data
	 * @param  Array   $dataToValidate The data to validate
	 * @return boolean True if the required fields has data
	 */
	public function isRequiredDataSet(Array $dataToValidate) {
		foreach ($this->entity->fields as $field) {
			if ($field->isRequired == true && !isset($dataToValidate[$this->entity->name][$field->getRealFieldName()])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validates the data of a data structure
	 * @param  Array   $dataToValidate The data to validate
	 * @return boolean True if the data is valid
	 */
	public function isDataValid(Array $dataToValidate) {
		$isValid = true;

		foreach ($this->entity->fields as $field) {
			$validator = new Validator(new ValidationRuleSet($field->validationRegularExpression, $field->isRequired, $field->validationErrorMessage));

			if (!$validator->IsValid($dataToValidate[$this->entity->name], $field->GetRealFieldName())) {
				$this->validationStatus[] = $field->GetRealFieldName() . ': ' . $validator->GetErrorMessage();
				$isValid = false;
			}
		}

		return $isValid;
	}

	/**
	 * Performs an overall validation of structure and data. Returns false and sets validation status on error.
	 * @param array $dataToValidate The data to validate
	 * @return boolean True if structure and data is valid. False if not.
	 */
	public function ValidateStructureAndData(array $dataToValidate) {
		if (!$this->isDataStructureValid($dataToValidate)) {
			$this->validationStatus[] = 'Data structure is not valid';
			return false;
		}

		if (!$this->isRequiredDataSet($dataToValidate)) {
			$this->validationStatus[] = 'Missing required field values';
			return false;
		}

		//This method sets validation status on validation error
		if (!$this->isDataValid($dataToValidate)) {
			return false;
		}

		return true;
	}

	/**
	 * Returns a complete array of error messages. Only has error messages if data validation failed
	 */
	public function GetValidationStatus() {
		return count($this->validationStatus) == 1 ? $this->validationStatus[0] : implode('. ', $this->validationStatus);
	}
}