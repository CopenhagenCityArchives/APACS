<?php
use Phalcon\Mvc\Model\Query;

class Steps extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'steps';
	}

	public function initialize() {
		$this->hasMany('id', 'Fields', 'steps_id');
	}

	public function GetStepsAndFields($taskId, $prefix = '') {
		$steps = $this->find('tasks_id = ' . $taskId);
		$stepsAndFields = [];
		$entities = new Entities();

		if (!is_null($prefix)) {
			$prefix = $prefix . '.';
		}

		foreach ($steps as $step) {
			$stepInfo = [];
			$stepInfo = $step->toArray();
			$stepInfo['fields'] = $this->GetFieldsAsSteps($step->GetRelatedEntitiesAndFields(), $prefix);
			$stepsAndFields[] = $stepInfo;
		}

		return $stepsAndFields;
	}

	private function GetFieldsAsSteps($entities, $prefix) {
		$stepsFields = [];
		foreach ($entities->toArray() as $el) {

			$fieldName = $el->fields->fieldName;
			if (!is_null($el->fields->decodeField)) {
				$fieldName = $el->fields->decodeField;
			}

			$elementName = [];
			if ($el->entities->type == 'array') {
				$elementName = [
					'key' => $el->entities->name,
					'add' => 'Tilføj',
				];
				//$elementName = $el->entities->name;
			} else {
				$elementName['key'] = $el->entities->name . '.' . $fieldName;
			}

			if ($prefix != $el->entities->name . '.') {
				$elementName['key'] = $prefix . $elementName['key'];
			}

			if (!array_search($elementName, $stepsFields)) {
				$stepsFields[] = $elementName;
			}
		}

		return $stepsFields;
	}

	public function GetRelatedEntitiesAndFields() {
		$query = new Query('SELECT Fields.*, Entities.* FROM Fields LEFT JOIN Entities ON Fields.entities_id = Entities.id WHERE Entities.task_id = :taskId: AND Fields.steps_id = :stepsId: ORDER BY formFieldOrder', $this->getDI());
		return $query->execute(['taskId' => $this->tasks_id, 'stepsId' => $this->id]);
	}
}