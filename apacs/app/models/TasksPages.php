<?php

use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class TasksPages extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'tasks_pages';
	}

	public function initialize() {
		$this->hasMany('id', 'Pages', 'page_id');
		$this->hasMany('id', 'Tasks', 'task_id');
	}

	public function beforeSave() {
		$this->last_activity = date('Y-m-d H:i:s');
	}

	public static function GetNextAvailablePage($taskId, $unitId, $curPageNumber) {
		$query = 'SELECT * FROM apacs_tasks_pages as TasksPages LEFT JOIN apacs_pages as Pages ON TasksPages.pages_id = Pages.id WHERE tasks_id = :task_id AND unit_id = :unit_id AND Pages.page_number > :current_page_number AND Pages.unit_id = :unit_id AND last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND is_done = 0 ORDER BY Pages.page_number LIMIT 1';

		$taskPage = new TasksPages();
		$result = new Resultset(null, $taskPage,
			$taskPage->getReadConnection()->query($query,
				['unit_id' => $unitId, 'task_id' => $taskId, 'current_page_number' => $curPageNumber]
			)
		);

		return $result;
	}

	public static function GetRandomAvailablePage($taskId, $unitId, $curPageNumber) {
		$query = 'SELECT * FROM apacs_tasks_pages as TasksPages LEFT JOIN apacs_pages as Pages ON TasksPages.pages_id = Pages.id WHERE tasks_id = :task_id AND unit_id = :unit_id AND is_done = 0 ORDER BY Pages.page_number';

		$taskPage = new TasksPages();
		$result = new Resultset(null, $taskPage,
			$taskPage->getReadConnection()->query($query,
				['unit_id' => $unitId, 'task_id' => $taskId]
			)
		);

		$rand = rand(0, count($result));

		return $result[$rand];
	}
}