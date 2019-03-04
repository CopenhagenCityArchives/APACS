<?php

class ApacsCRUD {
	private $crud;
	private $di;

	public function __construct($di) {
		$this->di = $di;
		
		//Settings for ORM db access
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname'] . ';charset=utf8;');
		ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('id_column', 'id');
		//This is necessary for PDO for PHP earlier than 5.3.some, as the charset=utf8 option above is ignored
		ORM::get_db()->exec("set names utf8");
		//ORM::configure('logging', true);
		//echo ORM::get_last_query();
		$this->crud = new CRUD\CRUD();
	}

	protected function getDI(){
		return $this->di;
	}

	public function find($table, $field, $value){
		$this->crud->find($table, $field, $value);
	}

	public function save($table, $data){
		return $this->crud->save($table,$data);
	}

	public function delete($tableName, $id){
		$this->crud->delete($tableName, $id);
	}
	public function getTable($tableName){
		return ORM::for_table($tableName);
	}

	public function startTransaction(){
		$dbCon = ORM::get_db();
		$dbCon->beginTransaction();
	}

	public function rollBackTransaction(){
		$dbCon = ORM::get_db();
		$dbCon->rollBack();
	}

	public function commitTransaction(){
		$dbCon = ORM::get_db();
		$dbCon->commit();
	}
}	