<?php

class Datasources extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'datasources';
	}

	public function initialize() {
		$this->hasMany('datasources_id', 'Fields', 'id');
	}

	public function GetData($searchString) {
		$query = str_replace(':query', $searchString, $this->sql);
		$result = $this->getDI()->get('db')->query($query);
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		return $result->fetchAll();
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
}