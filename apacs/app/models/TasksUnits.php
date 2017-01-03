<?php

use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class TasksUnits extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'tasks_units';
	}

	public function initialize() {
		$this->hasMany('id', 'Units', 'unit_id');
		$this->hasMany('id', 'Tasks', 'task_id');
	}

	public static function GetTasksUnitsAndActiveUsers($taskId = null, $unitId = null, $indexActive = null) {

		$conditions = [];

		if (!is_null($taskId)) {
			$conditions[] = 'TasksUnits.tasks_id = ' . $taskId;
		}

		if (!is_null($unitId)) {
			$conditions[] = 'TasksUnits.units_id = ' . $unitId;
		}

		if (!is_null($indexActive)) {
			$conditions[] = 'TasksUnits.index_active = ' . $indexActive;
		}

		$condition = implode(' AND ', $conditions);

		$sql = 'SELECT Units.*, TasksUnits.* FROM apacs_units as Units LEFT JOIN apacs_tasks_units as TasksUnits ON Units.id = TasksUnits.units_id WHERE ' . $condition . ' ORDER BY Units.description';
		//echo $sql;
		// Base model
		$units = new TasksUnits();

		// Execute the query
		$resultSet = new Resultset(null, $units, $units->getReadConnection()->query($sql));

		$result = $resultSet->toArray();

		for ($i = 0; $i < count($resultSet); $i++) {
			$result[$i]['active_users'] = $resultSet[$i]->GetActiveUsers()->toArray();
		}

		return $result;
	}

	public function GetActiveUsers($interval = '(NOW() - INTERVAL 15 MINUTE)') {
		$sql = 'SELECT DISTINCT Users.* FROM apacs_users as Users LEFT JOIN apacs_events Events ON Users.id = Events.users_id WHERE Events.timestamp > ' . $interval . ' AND Events.units_id = ' . $this->units_id;

		// Base model
		$users = new Users();

		// Execute the query
		return new Resultset(null, $users, $users->getReadConnection()->query($sql));
	}
}