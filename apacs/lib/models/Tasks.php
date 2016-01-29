<?php

use Phalcon\Mvc\Model\Query;

class Tasks extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'tasks';
	}

	public function initialize() {
		$this->hasMany('id', 'TasksUnits', 'task_id');
		$this->hasMany('id', 'Entries', 'task_id');
		$this->hasMany('id', 'TasksPages', 'task_id');
		$this->hasMany('id', 'Entities', 'task_id');
		$this->belongsTo('collection_id', 'Collections', 'id');
	}

	public function GetFieldsSchema($entityId) {
		$query = new Query("SELECT Entities.id as entity_id, EntitiesFields.id as entity_field_id, Fields.name as title, Fields.type as type, Fields.validationRegularExpression as pattern, Fields.helpText as description, Fields.validationErrorMessage as validationMessage, Fields.defaultValue as default, Fields.required FROM Entities LEFT JOIN EntitiesFields ON Entities.id = EntitiesFields.entity_id LEFT JOIN Fields ON EntitiesFields.field_id = Fields.id WHERE Entities.id = :entityId:", $this->getDI());
		$fields = $query->execute(['entityId' => $entityId])->toArray();

		usort($fields, function ($a, $b) {
			if ($a['entity_id'] == $b['entity_id']) {
				return 0;
			}

			return ($a['entity_id'] < $b['entity_id']) ? -1 : 1;
		});

		$return = [];
		$return['properties'] = [];
		$return['required'] = [];

		foreach ($fields as $field) {
			if ($field['required'] == 1) {
				$return['required'][] = $field['entity_field_id'];
			}

			unset($field['required']);
			//    unset($field['entity_id']);
			//  unset($field['parent_id']);
			if ($field['type'] == 'typeahead') {
				$field['type'] = 'string';
				$field['format'] = 'typeahead';
			}
			$return['properties'][$field['entity_field_id']] = $field;
		}

		return $return;
	}

	private function compareArray($a, $b) {
		if ($a['entity_id'] == $b['entity_id']) {
			return 0;
		}

		return ($a['entity_id'] < $b['entity_id']) ? -1 : 1;
	}
}