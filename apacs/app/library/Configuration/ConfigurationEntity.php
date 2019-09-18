<?php

class ConfigurationEntity implements IEntity {
	public $primaryTableName;
	public $fieldsList;
	public $fields;
	protected $entities;
	public $isPrimaryEntity;
	public $entityKeyName;
	public $name;
	public $guiName;
	public $type;
	public $required;
	public $includeInSOLR;

	private $validationMessages;

	//array representation of Entity
	private $array;

	public function __construct(Array $entity) {
		$this->primaryTableName = $entity['primaryTableName'];
		$this->isPrimaryEntity = $entity['isPrimaryEntity'];
		$this->entityKeyName = $entity['entityKeyName'];
		$this->name = $entity['name'];
		$this->guiName = $entity['guiName'];
		$this->type = $entity['type'];
		$this->required = $entity['required'];
		$this->includeInSOLR = isset($entity['includeInSOLR']) ? $entity['includeInSOLR'] : 0;
		$this->setFields($entity['fields']);
		$this->setEntities($entity['entities']);

		$this->array = $entity;		

		$this->validationMessages = [];
	}

	public function toArray(){
		return $this->array;
	}

	//Return a list of FieldMock objects
	public function getFields(){
		return $this->fields->getFieldsAsObjects();
	}

	public function isDataValid(array $entityData){
		$isValid = true;
		if ($this->required == '1' && $entityData == null) {
			$this->validationMessages[] = 'No data given for entity ' . $this->name;
			return false;
		}
		foreach ($this->getFields() as $field) {
			
			$validator = new Validator(new ValidationRuleSet($field->validationRegularExpression, $field->isRequired, $field->validationErrorMessage));
			
			if (!$validator->IsValid($entityData, $field->GetRealFieldName())) {
				$this->validationMessages[] = $field->GetRealFieldName() . ': ' . $validator->GetErrorMessage();
				$isValid = false;
			}
		}

		return $isValid;
	}

	public function AllEntityFieldsAreEmpty(array $entityData){
		foreach ($this->getFields() as $field) {
			if (isset($entityData[$field->getRealFieldName()]) && !is_null($entityData[$field->getRealFieldName()])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a concatted string or an array of of all field values for fields where includeInSOLR = 1 (return type depending on entity type)
	 * @param Array $data The data to concat
	 */
	public function ConcatDataByEntity($data) {
		$concat = '';

		if ($this->type == 'array') {
			$concat = [];
			$concatStr = '';
			foreach ($data as $row) {
				foreach (array_filter($this->getFields(), function ($el) {return $el->includeInSOLR == '1';}) as $field) {
					$fieldName = $field->GetRealFieldName();

					$concatStr .= $row[$fieldName] . ' ';
				}
				$concat[] = trim($concatStr);
				$concatStr = '';
			}
			return $concat;
		} else {
			$concatStr = '';

			foreach (array_filter($this->getFields(), function ($el) { return $el->includeInSOLR == '1';}) as $field) {
				if (isset($data[$field->GetRealFieldName()])) {
					$concatStr .= $data[$field->GetRealFieldName()] . ' ';
				}

			}
			return trim($concatStr);
		}
	}

	/**
	 * Returns an array of concatted field data ordered by field type
	 * @param Array $data The data to concat
	 */
	public function ConcatDataByField($data) {
		$concat = [];

		if ($this->type == 'array') {
			foreach (array_filter($this->getFields(), function ($el) {return $el->includeInSOLR == '1';}) as $field) {
				foreach ($data as $row) {
					$concat[$field->SOLRFieldName][] = $this->getFieldData($field, $row);
				}
			}
			return $concat;
		} else {
			foreach (array_filter($this->getFields(), function ($el) { return $el->includeInSOLR == '1';}) as $field) {
				$concat[$field->SOLRFieldName] = $this->getFieldData($field, $data);
			}
			return $concat;
		}
	}

	public function getDenormalizedData($data){
		$denormalizedData = [];

		if ($this->includeInSOLR == 1) {
			$denormalizedData[$this->name] = $this->ConcatDataByEntity($data);
		}

		$denormalizedData = array_merge($denormalizedData, $this->ConcatDataByField($data));

		return $denormalizedData;
	}

	private function getFieldData($field, $data) {
		if (isset($data[$field->GetRealFieldName()])) {
			if ($field->formFieldType == 'date') {
				return date('Y-m-d\TH:i:s.000\Z', strtotime($data[$field->GetRealFieldName()]));
				//return date('d-m-Y', strtotime($data[//Fields::GetRealFieldNameFromField($field)]));
			}

			if($field->fieldName == 'ageWeeks' || $field->fieldName == 'ageDays' || $field->fieldName == 'ageHours' || $field->fieldName == 'ageMonth' || $field->fieldName == 'ageYears'){
				return str_replace(',', '.', $data[$field->GetRealFieldName()]);
			}

			if (trim($data[$field->GetRealFieldName()]) == '') {
				$data[$field->fieldName] = null;
			}
			return $data[$field->GetRealFieldName()];
		}

		return null;
	}

	public function setFields($fields){
		if(is_null($fields)){
			$this->fields = [];
			$this->fieldsList = [];
			return;
		}
		$this->fieldsList = $fields;
		$this->fields = new ConfigurationFieldsHolder($fields);
	}

	public function getEntities(){
		return $this->entities;
	}

	private function setEntities($entities){
		$this->entities = [];
		if(!is_array($entities) || count($entities)==0){
			return;
		}
		foreach($entities as $ent){
			$this->entities[] = new ConfigurationEntity($ent);
		}
	}

	public function GetValidationStatus(){
		return implode(' ',$this->validationMessages);
	}
}	