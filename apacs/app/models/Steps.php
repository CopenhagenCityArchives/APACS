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
		$steps = $this->find(['conditions' => ['tasks_id = ' . $taskId]]);
		$stepsAndFields = [];
		$entities = new Entities();

		if (!is_null($prefix)) {
			$prefix = $prefix . '.';
		}

		foreach ($steps as $step) {
			$stepInfo = [];
			$stepInfo = $step->toArray();
			$stepInfo['fields'] = array_map(function ($el) use ($prefix) {
				$fieldName = $el->fields->fieldName;
				if (!is_null($el->fields->decodeField)) {
					$fieldName = $el->fields->decodeField;
				}

				$elementName = '';
				if ($el->entities->type == 'array') {
					$elementName = $el->entities->name;
				} else {
					$elementName = $el->entities->name . '.' . $fieldName;
				}

				return ($prefix != $el->entities->name . '.') ? $prefix . $elementName : $elementName;
			},
				$step->GetRelatedEntitiesAndFields()->toArray());

			if (count($stepInfo['fields']) > 0) {
				$stepInfo['fields'] = array_unique($stepInfo['fields']);
			}

			$stepsAndFields[] = $stepInfo;
		}

		return $stepsAndFields;
	}

	public function GetRelatedEntitiesAndFields() {
		$query = new Query('SELECT Fields.*, Entities.* FROM Fields LEFT JOIN Entities ON Fields.entities_id = Entities.id WHERE Entities.task_id = :taskId: AND Fields.steps_id = :stepsId: ORDER BY formFieldOrder', $this->getDI());
		return $query->execute(['taskId' => $this->tasks_id, 'stepsId' => $this->id]);
	}
}