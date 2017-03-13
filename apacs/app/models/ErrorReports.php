<?php

use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class ErrorReports extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'errorreports';
	}

	public function initialize() {
		$this->belongsTo('reporting_user_id', 'Users', 'id');
		$this->belongsTo('user_id', 'Users', 'id');
	}

	public function beforeSave() {
		$this->superUserTime = date('Y-m-d H:i:s', strtotime("+1 week"));
		$this->last_update = date('Y-m-d H:i:s');
	}

	/*public function GetWithUsers($conditions) {
		$query = new Query('SELECT Errors.*, Users.* FROM Errors LEFT JOIN Errors.user_id = Users.id LEFT JOIN Users Errors.reporting_user_id = Users.id WHERE Errors.deleted = 0 AND ' . $conditions, $this->getDI());
		return $query->execute(['taskId' => $this->tasks_id, 'stepsId' => $this->id]);
	}*/

	public static function FindByRawSql($conditions = null, $params = null) {
		// A raw SQL statement
		$sql = "SELECT DISTINCT apacs_errorreports.*, apacs_users.username, CONCAT(apacs_collections.name, ' ', apacs_units.description) as unit_description, apacs_fields.formName as field_formName, apacs_entities.guiName as entity_name_gui FROM apacs_errorreports LEFT JOIN apacs_users ON apacs_errorreports.users_id = apacs_users.id LEFT JOIN apacs_posts ON apacs_errorreports.posts_id = apacs_posts.id LEFT JOIN apacs_pages ON apacs_posts.pages_id = apacs_pages.id LEFT JOIN apacs_units ON apacs_pages.unit_id = apacs_units.id LEFT JOIN apacs_collections ON apacs_units.collections_id = apacs_collections.id LEFT JOIN apacs_entities ON apacs_entities.id = apacs_errorreports.entities_id LEFT JOIN apacs_fields ON apacs_fields.entities_id = apacs_errorreports.entities_id AND (apacs_errorreports.field_name = apacs_fields.fieldName OR apacs_errorreports.field_name = apacs_fields.decodeField) WHERE apacs_errorreports.deleted = 0";

		if (!is_null($conditions)) {
			$sql = $sql . ' AND ' . $conditions;
		}

		// Base model
		$errorReport = new ErrorReports();

		// Execute the query
		return new Resultset(null, $errorReport, $errorReport->getReadConnection()->query($sql, $params));
	}
}