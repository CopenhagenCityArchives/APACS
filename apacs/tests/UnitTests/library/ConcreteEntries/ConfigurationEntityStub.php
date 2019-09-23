<?php
namespace Mocks;

class ConfigurationEntityStub extends \ConfigurationEntity {

	//Public variables used to set method returns
	public $valid;
	public $current;
	public $isDataValid;
	public $next;
	public $AllEntityFieldsAreEmptyReturn;

	public function __construct(Array $entity) {
        parent::__construct($entity);
		$this->setEntities($entity['entities']);
		$this->valid = $entity['valid'] ?? true;
		$this->current = $entity['current'] ?? false;
		$this->isDataValid = $entity['isDataValid'] ?? true;
		$this->next = $entity['next'] ?? false;
		$this->AllEntityFieldsAreEmptyReturn = $entity['AllEntityFieldsAreEmpty'] ?? false;
		
	}

	public function rewind(){
	}
	public function valid(){
		return $this->valid;
	}
	public function current(){
		return $this->current;

	}

	public function isDataValid(array $entityData){
		return $this->isDataValid;
	}

	public function next(){
		return $this->next;
	}

	public function AllEntityFieldsAreEmpty(array $entityData){
		return $this->AllEntityFieldsAreEmptyReturn;
	}
	public function GetValidationStatus(){
		return "validation status";
	}

	private function setEntities($entities){
		$this->entities = [];
		if(!is_array($entities) || count($entities)==0){
			return;
		}
		foreach($entities as $ent){
			$this->entities[] = new ConfigurationEntityStub($ent);
		}
	}
}	