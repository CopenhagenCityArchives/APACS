<?php

class Datasources extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'datasources';
	}

	public function initialize() {
		$this->hasMany('datasources_id', 'Fields', 'id');
	}

	public function GetData($searchString) {
		$searchString = str_replace("'", "\'", $searchString);
		$searchString = str_replace('"', '\"', $searchString);
		$query = str_replace(':query', $searchString, $this->sql);
	//	die($query);
		$result = $this->getDI()->get('db')->query($query);
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		return $result->fetchAll();
	}

	public function GetDataById($id) {
		$query = "select * from " . $this->dbTableName . " WHERE id = " . $id;
		$result = $this->getDI()->get('db')->query($query);
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		return $result->fetchAll();
	}

	public function GetDataBySpecificString($value)
	{
		$query = "select * from " . $this->dbTableName . " WHERE `" . $this->valueField . "` = '" . $value . "'";
		$result = $this->getDI()->get('db')->query($query);
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		return $result->fetchAll();
	}

	public function GetAllRows(){
		$query = "select * from " . $this->dbTableName . " WHERE 1";
		$result = $this->getDI()->get('db')->query($query);
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		return $result->fetchAll();
	}

	public function CreateValue($value){
		if($this->isPublicEditable == 0){
			throw new Exception('Could not create new value for datasource ' . $this->name . '. It is not public editable');
		}

		$existingValues = $this->GetDataBySpecificString($value);

		if(count($existingValues) > 0){
			throw new InvalidArgumentException('Kunne ikke oprette værdien for datalisten ' . $this->name . '. Værdien eksisterer allerede');
		}

		$query = 'INSERT INTO ' . $this->dbTableName . ' (`' . $this->valueField . '`) VALUES ("' . $value . '")';

		return $this->getDI()->get('db')->query($query);
	}

	public function UpdateValue($id, $value){
		if($this->isPublicEditable == 0){
			throw new Exception('Could not update value for datasource ' . $this->name . '. It is not public editable');
		}

		$existingValues = $this->GetDataById($id);

		if(count($existingValues) !== 1){
			throw new InvalidArgumentException('Could not update value for datasource ' . $this->name . '. No value with id '. $id .' exists');
		}

		$query = 'UPDATE ' . $this->dbTableName . ' SET ' . $this->valueField . ' = "' . $value . '" WHERE id = ' . $id . ' LIMIT 1';

		return $this->getDI()->get('db')->query($query);
	}

	public function GetValuesAsArray() {
		if ($this->includeValuesInForm == 1) {
			$allRowsQuery = substr($this->sql, 0, strpos($this->sql, 'WHERE'));

			//If the query contains a 'order by' part, include it in the query
			$orderByPos = strpos($this->sql, ' order by');
			if($orderByPos){
				$allRowsQuery .= substr($this->sql, $orderByPos);
			}

			$result = $this->getDI()->get('db')->query($allRowsQuery);
			$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);

			$resultRows = [];
			foreach ($result->fetchAll() as $row) {
				$resultRows[] = $row[$this->valueField];
			}

			return $resultRows;
		}

		return false;
	}

	public static function SetDatasourceOrEnum($field) {
		if (!is_null($field['datasources_id'])) {
			$datasource = Datasources::findFirst(['conditions' => 'id = ' . $field['datasources_id']]);

			if (isset($datasource) && $datasource !== false) {
				$values = $datasource->GetValuesAsArray();
				if (!$values) {
					$field['datasource'] = 'https://www.kbhkilder.dk/api/datasource/' . $datasource->id . '?q=';
					$field['datasourceValueField'] = $datasource->valueField;
				} else {
					$field['enum'] = $values;
					$field['type'] = 'string';
				}
			}
		}

		return $field;
	}
}
