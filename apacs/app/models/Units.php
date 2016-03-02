<?php

class Units extends \Phalcon\Mvc\Model {

	protected $id;
	protected $numOfPages;
	protected $collectionId;

	private $status = [];
	static $publicFields = ['id', 'collection_id', 'pages', 'description'];

	const OPERATION_TYPE_CREATE = 'create';
	const OPERATION_TYPE_UPDATE = 'update';

	public function getSource() {
		return 'apacs_' . 'units';
	}

	public function initialize() {
		$this->hasMany('id', 'Pages', 'units_id');
		$this->hasMany('id', 'TasksUnits', 'units_id');
		$this->belongsTo('collections_id', 'Collections', 'id');
	}

	private function getImportCreateSQL() {
		return 'INSERT INTO ' . $this->getSource() . ' (concrete_unit_id, description, collection_id, tablename) SELECT :id, :fields, :collectionId, ":table" FROM :table :conditions';
	}

	private function getImportUpdateSQL() {
		return 'UPDATE ' . $this->getSource() . ' LEFT JOIN :table ON ' . $this->getSource() . '.concrete_unit_id = :table.:id SET ' . $this->getSource() . '.description = :fields, tablename = ":table" :conditions';
	}

	public function Import($type, $collectionId, $idField, $infoField, $table, $conditions = NULL) {
		if ($type == self::OPERATION_TYPE_CREATE && $this->dataAlreadyImported('apacs_units', $table, $collectionId)) {
			$this->status = ['error' => 'units are already imported (collection and tablename already exists'];
			return false;
		}
		$sql = ($type == self::OPERATION_TYPE_UPDATE ? $this->getImportUpdateSQL() : $this->getImportCreateSQL());

		$sql = str_replace(':collectionId', $collectionId, $sql);
		$sql = str_replace(':id', $idField, $sql);
		$sql = str_replace(':fields', $infoField, $sql);
		$sql = str_replace(':table', $table, $sql);
		$sql = str_replace(':conditions', $conditions == NULL ? '' : 'WHERE ' . $conditions, $sql);

		return $this->runQueryGetStatus($sql);
	}

	private function dataAlreadyImported($type, $tableName, $collectionId) {
		$sql = 'SELECT * FROM ' . $type . ' WHERE tablename = \'' . $tableName . '\' AND collection_id = \'' . $collectionId . '\' LIMIT 1';
		$resultSet = $this->getDI()->get('database')->query($sql);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$results = $resultSet->fetchAll();

		return count($results) > 0;
	}

	private function runQueryGetStatus($query) {
		$connection = $this->getDI()->get('database');
		$success = $connection->execute($query);

		if ($success) {
			$this->status = ["affected_rows" => $connection->affectedRows()];
		} else {
			$this->status = ["status" => "could not execute query", "error_message" => $connection->getErrorInfo()];
		}

		return $success;
	}

	public function GetStatus() {
		return $this->status;
	}
}