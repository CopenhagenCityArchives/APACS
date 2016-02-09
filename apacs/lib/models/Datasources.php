<?php

class Datasources extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'datasources';
	}

	public function initialize() {
		$this->hasMany('datasources_id', 'Fields', 'id');
	}

	public function GetData($searchString) {

		if (!is_null($this->values)) {
			$this->GetValuesAsArray();
		}

		$query = str_replace(':query', $searchString, $this->sql);
		$result = $this->getDI()->get('db')->query($query);
		$result->setFetchMode(\Phalcon\Db::FETCH_ASSOC);
		return $result->fetchAll();
	}

	public function GetValuesAsArray() {
		if (!is_null($this->values)) {
			return explode(';', $this->values);
		} else {
			return false;
		}
	}
}