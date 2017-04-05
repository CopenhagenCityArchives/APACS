<?php

class SystemExceptions extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'exceptions';
	}

	public function initialize() {
		$this->skipAttributesOnCreate(['time']);
	}

	public function getLastExceptionsByTypeAndHours($type, $hours) {
		$phql = 'SELECT * FROM apacs_exceptions WHERE CURRENT_TIMESTAMP - INTERVAL :hours HOUR AND type = :type ORDER BY time DESC';
		$resultSet = $this->getDI()->get('db')->query($phql, ['hours' => $hours, 'type' => $type]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		return $resultSet->fetchAll();
	}
}