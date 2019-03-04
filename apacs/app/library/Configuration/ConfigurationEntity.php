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
	public $task_id;
	public $type;

	private $validationMessages;

	//array representation of Entity
	private $array;

	public function __construct(Array $entity) {
		$this->primaryTableName = $entity['primaryTableName'];
		$this->isPrimaryEntity = $entity['isPrimaryEntity'];
		$this->entityKeyName = $entity['entityKeyName'];
		$this->name = $entity['name'];
		$this->guiName = $entity['guiName'];
		$this->task_id = $entity['task_id'];
		$this->type = $entity['type'];
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