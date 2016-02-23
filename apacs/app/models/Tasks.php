<?php

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

	public function GetTaskSchema() {
		$mainEntity = Entities::findFirst(['conditions' => 'task_id = ' . $this->id . ' AND isPrimaryEntity = 1']);
		$mainArr = $mainEntity->toArray();
		$mainArr['title'] = $this->name;
		$mainArr['description'] = $this->description;
		$mainArr['properties'] = $mainEntity->ConvertToJSONSchemaObject()['properties'];
		$entities = Entities::find(['conditions' => 'task_id = ' . $this->id . ' AND isPrimaryEntity = 0']);

		foreach ($entities as $entity) {
			$mainArr['properties'][$entity->name] = $entity->ConvertToJSONSchemaObject();
		}

		$response = [];
		$response['keyName'] = $mainEntity->name;
		$response['schema']['type'] = 'object';
		$response['schema']['properties'][$mainEntity->name] = $mainArr;
		$steps = new Steps();
		$response['steps'] = $steps->GetStepsAndFields($this->id, $mainEntity->name);

		return $response;
	}
}