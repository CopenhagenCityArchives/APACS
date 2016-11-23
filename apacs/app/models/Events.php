<?php

use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class Events extends \Phalcon\Mvc\Model {

	//Consts used to determine the type of event
	const TypeCreate = 'create';
	const TypeEdit = 'edit';
	const TypeReportError = 'report_error';

	//Constant used to determine when a user is active
	const UserActivityTimeLimit = '15 MINUTE';

	public function getSource() {
		return 'apacs_' . 'events';
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
		$sql = 'SELECT username, Units.description, Units.id, Pages.page_number, Pages.id as page_id, Events.tasks_id as task_id, timestamp  FROM apacs_events as Events
			LEFT JOIN apacs_users as Users on Events.users_id = Users.id
			LEFT JOIN apacs_units as Units on Events.units_id = Units.id
			LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
			INNER JOIN (select unit_id, max(timestamp) as time
						FROM apacs_events as Events
									LEFT JOIN apacs_users as Users on Events.users_id = Users.id
									LEFT JOIN apacs_units as Units on Events.units_id = Units.id
									LEFT JOIN apacs_pages as Pages on Events.pages_id = Pages.id
									WHERE Events.users_id = 651 group by unit_id) SUBQ
			ON SUBQ.unit_id = Units.id AND SUBQ.time = timestamp
			WHERE Events.users_id = ' . $userId . ' AND (event_type = \'' . self::TypeCreate . '\' OR event_type = \'' . self::TypeEdit . '\') order by Units.id';

		// Base model
		$events = new Events();

		// Execute the query
		return new Resultset(null, $events, $events->getReadConnection()->query($sql));
	}

	public function GetActiveUsers($conditions = null) {
		$sql = 'SELECT distinct username, page_number FROM apacs_events as Events
			LEFT JOIN apacs_users as Users ON Events.users_id = Users.id
			LEFT JOIN apacs_pages as Pages ON Events.pages_id = Pages.id
			WHERE timestamp > TIMESTAMP(NOW() - INTERVAL ' . self::UserActivityTimeLimit . ')';

		if (!is_null($conditions)) {
			$sql = $sql . ' AND ' . $conditions;
		}
		// Base model
		$events = new Events();

		// Execute the query
		return new Resultset(null, $events, $events->getReadConnection()->query($sql));
	}
}