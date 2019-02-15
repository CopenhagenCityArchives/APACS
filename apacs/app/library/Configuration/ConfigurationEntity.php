<?php

class ConfigurationEntity implements IEntitiesInfo {
	public $primaryTableName;
	public $fieldsList;
	public $fields;
	public $isPrimaryEntity;
	public $entityKeyName;
	public $name;
	public $guiName;
	public $task_id;
	public $type;

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

		$this->array = $entity;		
	}

	public function GetPrimaryEntity(Array $entities){
		throw new Exception("not implemented");
	}

	public function GetSecondaryEntities(Array $entities){
		throw new Exception("not implemented");
	}

	public function toArray(){
		return $this->array;
	}

	//Return a list of FieldMock objects
	public function getFields(){
		return $this->fields->getFieldsAsObjects();
	}

	public function setFields($fields){
		$this->fieldsList = $fields;
		$this->fields = new ConfigurationFieldsHolder($fields);
	}
}	