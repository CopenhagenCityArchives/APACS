<?php

use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class Events extends \Phalcon\Mvc\Model {

	//Consts used to determine the type of event
	const TypeCreate = 'create';
	const TypeEdit = 'edit';
	const TypeReportError = 'report_error';
	const TypeCreateUpdatePost = 'create_update_post';
	const TypeDeletePost = 'delete';

	//Constant used to determine when a user is active
	const UserActivityTimeLimit = '15 MINUTE';

	public function getSource() {
		return 'apacs_events';
	}

	public function beforeSave() {
		$this->timestamp = date('Y-m-d H:i:s');
	}

	public function GetUserActivitiesForUnits($userId) {
		/*$sql = 'SELECT username, Units.description, Pages.page_number, Pages.id as page_id, Events.tasks_id as task_id, Events.timestamp FROM apacs_events as Events
			LEFT JOIN apacs_users as Users on Events.users_id = Users.id
			LEFT JOIN apacs_units as Units on Events.units_id = Units.id
			LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
			WHERE Events.users_id = ' . $userId . ' AND (event_type = \'' . self::TypeCreate . '\' OR event_type = \'' . self::TypeEdit . '\') GROUP BY units_id order by Events.timestamp desc';*/
		//Getting the last activity for the user in each unit
		$sql = 'SELECT username, Units.description, Units.id, Pages.page_number, Pages.id as page_id, Events.tasks_id as task_id, timestamp, TaskUnits.index_active, TaskUnits.pages_done as task_unit_pages_done, Units.pages as unit_pages FROM apacs_events as Events
			LEFT JOIN apacs_users as Users on Events.users_id = Users.id
			LEFT JOIN apacs_units as Units on Events.units_id = Units.id
			LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
			LEFT JOIN apacs_tasks_units as TaskUnits on TaskUnits.units_id = Units.id AND TaskUnits.tasks_id = Events.tasks_id
			INNER JOIN (select unit_id, max(timestamp) as time
						FROM apacs_events as Events
									LEFT JOIN apacs_users as Users on Events.users_id = Users.id
									LEFT JOIN apacs_units as Units on Events.units_id = Units.id
									LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
									WHERE Events.users_id =  ' . $userId . ' group by unit_id) SUBQ
			ON SUBQ.unit_id = Units.id AND SUBQ.time = timestamp
			WHERE TaskUnits.index_active = 1 AND Events.users_id = ' . $userId . ' AND (event_type = \'' . self::TypeCreate . '\' OR event_type = \'' . self::TypeEdit . '\' OR event_type = \'' . self::TypeCreateUpdatePost . '\' OR event_type = \'' . self::TypeDeletePost . '\') order by Units.description';

		// Base model
		$events = new Events();

		// Execute the query
		return new Resultset(null, $events, $events->getReadConnection()->query($sql));
	}

	public function GetActiveUsersForTaskAndUnit($taskId, $unitId){

		$sql = 'select users_id, User.username as username, page_number, timestamp, apacs_pages.id
				from apacs_events
				join apacs_pages on apacs_events.pages_id = apacs_pages.id
				JOIN apacs_users User on apacs_events.users_id = User.id

				where units_id = :unitId and tasks_id = :taskId AND timestamp > TIMESTAMP(NOW() - INTERVAL ' . self::UserActivityTimeLimit . ') AND event_type IN (\''. self::TypeCreate . '\',\'' . self::TypeEdit . '\',\'' . self::TypeCreateUpdatePost .'\')

				order by timestamp desc';

		$resultSet = $this->getDI()->get('db')->query($sql, ['unitId' => $unitId, 'taskId' => $taskId ]);

/*
		$sql = 'SELECT username,Pages.page_number, timestamp, Pages.id as page_id FROM apacs_events as Events
			LEFT JOIN apacs_users as Users on Events.users_id = Users.id
			LEFT JOIN apacs_units as Units on Events.units_id = Units.id
			LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
			LEFT JOIN apacs_tasks_units as TaskUnits on TaskUnits.units_id = Units.id AND TaskUnits.tasks_id = Events.tasks_id
			INNER JOIN (select unit_id, max(timestamp) as time
						FROM apacs_events as Events
									LEFT JOIN apacs_users as Users on Events.users_id = Users.id
									LEFT JOIN apacs_units as Units on Events.units_id = Units.id
									LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
									WHERE Events.units_id =  :unitId AND Events.tasks_id = :taskId group by users_id) SUBQ
			ON SUBQ.unit_id = Units.id AND SUBQ.time = timestamp
			WHERE (event_type = \'' . self::TypeCreate . '\' OR event_type = \'' . self::TypeEdit . '\' OR event_type = \'' . self::TypeCreateUpdatePost . '\') AND timestamp > TIMESTAMP(NOW() - INTERVAL ' . self::UserActivityTimeLimit . ') order by timestamp';
*/
		// Execute the query
		//$resultSet = $this->getDI()->get('db')->query($sql, ['unitId' => $unitId, 'taskId' => $taskId]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		//Get distinct users
		$usersAdded = [];
		$result = [];
		foreach($resultSet->fetchAll() as $row){

			if(!in_array($row['users_id'], $usersAdded)){
				$result[] = $row;
				$usersAdded[] = $row['users_id'];
			}
		}

		return $result;
	}

	public function GetNumEventsForUsers($event_type, $unix_time, array $task_ids) {

		if ($event_type == null) {
			$event_type = "create";
		}
		if ($unix_time == null) {
			$unix_time = strtotime("-1 week");
		}

		$task_condition = '';
		if (count($task_ids) > 0) {
			$task_condition = 'AND tasks_id IN (' . implode(',', $task_ids) . ')';
		}
		
		$sql = 'SELECT users_id, count(users_id) AS count, User.username
		        FROM apacs_events join apacs_users User ON apacs_events.users_id = User.id
				WHERE event_type = :event_type AND apacs_events.timestamp > from_unixtime(:unix_time) ' . $task_condition . '
				GROUP BY users_id ORDER BY count DESC, username ASC';

		$resultSet = $this->getDI()->get('db')->query($sql, ['event_type' => $event_type, 'unix_time' => $unix_time]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$result = [];
		foreach($resultSet->fetchAll() as $row){
			($result[] = $row);
		}
		return $result;
	}
}
