<?php

use Phalcon\Mvc\Model\Query;

class Errorreports extends \Phalcon\Mvc\Model {

	protected $id;
	protected $entriesId;
	protected $collectionId;
	protected $reportingUser;
	protected $reportedTime;
	protected $toSuperuser;

	public function getSource() {
		return 'apacs_' . 'errorreports';
	}

	public function initialize() {
		$this->belongsTo('reporting_user_id', 'Users', 'id');
		$this->belongsTo('user_id', 'Users', 'id');
	}

	public function GetWithUsers($conditions) {
		$query = new Query('SELECT Errors.*, Users.* FROM Errors LEFT JOIN Errors.user_id = Users.id LEFT JOIN Users Errors.reporting_user_id = Users.id WHERE ' . $conditions, $this->getDI());
		return $query->execute(['taskId' => $this->tasks_id, 'stepsId' => $this->id]);
	}
}