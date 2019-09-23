<?php
namespace Mocks;

class CrudMock {
	public $primaryTableName;
	public $fields;
	public $isPrimaryEntity;
	public $entityKeyName;
	public $name;
	public $guiName;
	public $task_id;
	public $type;

	public function __construct($di = null) {
	}

	public function find($table, $field, $value){

	}

	public function save($table, $data){
		return -1;
	}

	public function load(){
		
	}

	public function delete($tableName, $id){

	}

	public function startTransaction(){

	}

	public function rollBackTransaction(){

	}

	public function commitTransaction(){
		
	}
}	