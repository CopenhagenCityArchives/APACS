<?php
namespace Mocks;

class EntitiesMock implements \IEntitiesInfo {
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

	//Public variables used to indicate method returns
	public $valid;
	public $current;
	public $isDataValid;
	public $next;
	public $AllEntityFieldsAreEmpty;


	public function __construct(Array $entity) {
		$this->primaryTableName = $entity['primaryTableName'];
		$this->isPrimaryEntity = $entity['isPrimaryEntity'];
		$this->entityKeyName = $entity['entityKeyName'];
		$this->name = $entity['name'];
		$this->guiName = $entity['guiName'];
		$this->task_id = $entity['task_id'];
		$this->type = $entity['type'];
		$this->setFields($entity['fieldsList']);

		$this->array = $entity;

		$this->valid = $entity['valid'] ?? true;
		$this->current = $entity['current'] ?? false;
		$this->isDataValid = $entity['isDataValid'] ?? true;
		$this->next = $entity['next'] ?? false;
		$this->AllEntityFieldsAreEmpty = $entity['AllEntityFieldsEmpty'] ?? false;
		
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
		$this->fields = new FieldsMock($fields);
	}
	public function rewind(){
	}
	public function valid(){
		return $this->valid;
	}
	public function current(){
		return $this->current;

	}

	public function isDataValid(){
		return $this->isDataValid;
	}

	public function next(){
		return $this->next;
	}

	public function AllEntityFieldsAreEmpty(){
		return $this->AllEntityFieldsAreEmpty;
	}
	public static function GetValidationStatus(){
		return "validation status";
	}
}	