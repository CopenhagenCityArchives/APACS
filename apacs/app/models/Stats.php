<?php

class Stats extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'stats';
	}

	public function getCountSince($interval) {
		$query = 'SELECT count(*) as number FROM Stats WHERE time > DATE_SUB(curdate(),INTERVAL ' . $interval . ' )';
		$resultSet = $this->getDI()->get('db')->query($query);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		return $resultSet->fetchAll()[0]['number'];
	}
}
