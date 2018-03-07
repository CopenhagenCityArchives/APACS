<?php

class SystemExceptions extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'exceptions';
	}

	public function initialize() {
		$this->skipAttributesOnCreate(['time']);
	}

	public function getLastExceptionsByTypeAndHours($type, $hours) {
		$phql = 'SELECT * FROM apacs_exceptions WHERE time > NOW() - INTERVAL :hours HOUR AND type = :type ORDER BY time DESC';
		$resultSet = $this->getDI()->get('db')->query($phql, ['hours' => $hours, 'type' => $type]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result = $resultSet->fetchAll();

		foreach($result as &$row){
			$row['details'] = json_decode($row['details'], true);
			if(isset($row['details']['rawPostData'])){
				$row['details']['entryData'] = json_decode($row['details']['rawPostData']);
				unset($row['details']['rawPostData']);
			}
		}

		return $result;
	}

	public function getLastExceptionsByHours($hours) {
		$phql = 'SELECT * FROM apacs_exceptions WHERE time > NOW() - INTERVAL :hours HOUR ORDER BY time DESC';
		$resultSet = $this->getDI()->get('db')->query($phql, ['hours' => $hours]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result = $resultSet->fetchAll();

		foreach($result as &$row){
			$row['details'] = json_decode($row['details'], true);
			if(isset($row['details']['rawPostData'])){
				$row['details']['entryData'] = json_decode($row['details']['rawPostData']);
				unset($row['details']['rawPostData']);
			}
		}

		return $result;
	}
}
