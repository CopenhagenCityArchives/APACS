<?php

class DBConfigurationLoader {
	public function GetCollections() {
		$collections = Collections::find();
		return $collections->toArray();
	}

	public function GetCollection($collectionId) {
		$data = [];

		$collection = Collections::findFirstById($collectionId);

		if ($collection == false) {
			throw new InvalidArgumentException('No collection found with id ' . $collectionId);
		}

		$data = $collection->toArray();

		$data['tasks'] = [];

		$tasks = Tasks::find(['condition' => 'collection_id = ' . $collectionId]);

		foreach ($tasks as $task) {
			$data['tasks'][] = $this->GetTask($task->id);
		}

		$data['filters'] = [];

		$filters = Filters::find(['condition' => 'collection_id = ', $collectionId]);

		foreach ($filters as $filter) {
			$data['filters'][] = $this->GetFiltersAndFilterLevels($filter->id);
		}

		return $data;
	}

	public function GetTasks() {
		$tasks = Tasks::find();
		$tasksData = [];
		foreach ($tasks->toArray() as $taskData) {
			$taskId = $taskData['id'];
			$taskData['multiPost'] = $taskId == 4 || $taskId == 5;
			$tasksData[] = $taskData;
		}
		return $tasksData;
	}

	public function GetTask($taskId) {
		$task = Tasks::findFirstById($taskId);
		$taskData = $task->toArray();
		$taskData['multiPost'] = $taskId == 4 || $taskId == 5;
		return $taskData;
	}

	public function GetFiltersAndFilterLevels($filterId) {
		$data = [];

		$filter = Filters::findFirstById($filterId);

		$data = $filter->toArray();

		$data['filter_levels'] = [];

		$filterlevels = $filter->getFilterLevels();

		foreach ($filterlevels as $fl) {
			$data['filter_levels'][] = $fl->toArray();
		}

		return $data;
	}
}