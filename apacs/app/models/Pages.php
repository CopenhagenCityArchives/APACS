<?php

class Pages extends \Phalcon\Mvc\Model {

	protected $id;
	protected $unitsId;
	protected $collectionId;

	private $status = [];
	static $publicFields = ['id', 'collection_id', 'unit_id'];

	const OPERATION_TYPE_CREATE = 'create';
	const OPERATION_TYPE_UPDATE = 'update';

	public function getSource() {
		return 'apacs_' . 'pages';
	}

	public function initialize() {
		$this->hasMany('id', 'Entries', 'page_id');
		$this->hasMany('id', 'TasksPages', 'page_id');
		$this->belongsTo('unit_id', 'Units', 'id');
	}

	public function GetLocalPathToConcreteImage() {

		//Get pageImageLocation to figure out where to get images from
		$pathInfo = $this->getDI()->get('pageImageLocation');

		if ($pathInfo['type'] == 'http') {
			return $pathInfo['path'] . $this->former_id;
		}

		//Settings for ORM db access
		ORM::configure('mysql:host=' . $this->getDI()->get('config')['host'] . ';dbname=' . $this->getDI()->get('config')['dbname']);
		ORM::configure('username', $this->getDI()->get('config')['username']);
		ORM::configure('password', $this->getDI()->get('config')['password']);
		ORM::configure('charset', $this->getDI()->get('config')['charset']);
		//ORM::configure('logging', true);
		//echo ORM::get_last_query();

		$crud = new CRUD\CRUD();

		$joins = ORM::for_table($this->getUnits()->getCollections()->concreteImagesTableName);

		if ($pathInfo['type'] == 'file') {
			return $pathInfo['path'] . $joins->select('relative_filename_converted')->where('id', $this->former_id)->find_one()['relative_filename_converted'];
		}
	}

	//TODO: Delete when starbas API is implemented
	private function getImportCreateSQL() {
		return 'INSERT INTO ' . $this->getSource() . ' (concrete_page_id, collection_id, concrete_unit_id, tablename, image_url) SELECT :id, :collectionId, :unitId, ":table", :imageUrl FROM :table :conditions';
	}

	//TODO: Delete when starbas API is implemented
	private function getImportUpdateSQL() {
		return 'UPDATE ' . $this->getSource() . ' LEFT JOIN :table ON ' . $this->getSource() . '.concrete_page_id = :table.:id SET tablename = ":table", image_url = :imageUrl :conditions';
	}

	//TODO: Delete when starbas API is implemented
	public function Import($type, $collectionId, $idField, $unitIdField, $table, $image_url_field, $conditions = NULL) {
		if ($type == self::OPERATION_TYPE_CREATE && $this->dataAlreadyImported('apacs_pages', $collectionId)) {
			$this->status = ['error' => 'pages are already imported (collection and tablename already exists'];
			return false;
		}

		$sql = ($type == self::OPERATION_TYPE_UPDATE ? $this->getImportUpdateSQL() : $this->getImportCreateSQL());

		$sql = str_replace(':collectionId', $collectionId, $sql);
		$sql = str_replace(':id', $idField, $sql);
		$sql = str_replace(':unitId', $unitIdField, $sql);
		$sql = str_replace(':table', $table, $sql);
		$sql = str_replace(':imageUrl', $image_url_field, $sql);
		$sql = str_replace(':conditions', $conditions == NULL ? '' : 'WHERE ' . $conditions, $sql);

		return $this->runQueryGetStatus($sql);
	}

	//TODO: Delete when starbas API is implemented. Check for usage!
	private function runQueryGetStatus($query) {
		$connection = $this->getDI()->get('db');
		$success = $connection->execute($query);

		if ($success) {
			$this->status = ["affected_rows" => $connection->affectedRows()];
		} else {
			$this->status = ["status" => "could not execute query", "error_message" => $connection->getErrorInfo()];
		}

		return $success;
	}

	//TODO: Delete when starbas API is implemented
	private function dataAlreadyImported($type, $collectionId) {
		$sql = 'SELECT * FROM ' . $type . ' WHERE collection_id = \'' . $collectionId . '\' LIMIT 1';
		$resultSet = $this->getDI()->get('db')->query($sql);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $resultSet->fetchAll();

		return count($results) > 0;
	}

	public function GetStatus() {
		return $this->status;
	}
}