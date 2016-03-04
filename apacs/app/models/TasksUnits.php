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

	public static function FindUnitsAndTasks($conditions, $params = null) {
		$sql = "SELECT * FROM apacs_units as Units LEFT JOIN apacs_tasks_units as TasksUnits ON Units.id = TasksUnits.units_id WHERE $conditions ORDER BY Units.description";

		// Base model
		$units = new TasksUnits();

		// Execute the query
		return new Resultset(null, $units, $units->getReadConnection()->query($sql, $params));
	}
}