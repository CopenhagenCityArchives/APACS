<?php

class DBConfigurationLoader
{
	public function GetCollections()
	{
		$collections = Collections::find();
		return $collections->toArray();
	}

	public function GetCollection($collectionId)
	{
		$data = [];

		$collection = Collections::findFirstById($collectionId);

		$data = $collection->toArray();

		$data['tasks'] = [];

		$tasks = Tasks::find(['condition' => 'collection_id = ' . $collectionId]);

		foreach($tasks as $task){
			$data['tasks'][] = $this->GetTask($task->id);
		}

		$data['filters'] = [];

		$filters = Filters::find(['condition' => 'collection_id = ' , $collectionId]);

		foreach($filters as $filter){
			$data['filters'][] = $this->GetFiltersAndFilterLevels($filter->id);
		}

		return $data;
	}

	public function GetTasks()
	{
		$tasks = Tasks::find();
		return $tasks->toArray();
	}

	public function GetTask($taskId)
	{
		$data = [];

		$task = Tasks::findFirstById($taskId);

		$data = $task->toArray();
		$data['entities'] = [];

		$entities = Entities::find(['condition' => 'task_id = ' . $taskId]);

		foreach($entities as $entity)
		{
			$data['entities'][] = $this->GetEntityAndFields($entity->id);
		}

		return $data;
	}

	public function GetEntityAndFields($entityId)
	{
		$data = [];

		$entity = Entities::findFirstById($entityId);

		$data = $entity->toArray();

		$entitiesFields = $entity->getEntitiesFields();

		$fields = [];

		foreach($entitiesFields as $field)
		{
			//Here we have it!
			$fieldData = $field->getFields()->toArray();
			$fieldData['field_group_number'] = $field->field_group_number;
			$fields[] = $fieldData;
		}

		$data['fields'] = $fields;

		return $data;
	}

	public function GetFiltersAndFilterLevels($filterId)
	{
		$data = [];

		$filter = Filters::findFirstById($filterId);

		$data = $filter->toArray();

		$data['filter_levels'] = [];

		$filterlevels = $filter->getFilterLevels(); 

		foreach($filterlevels as $fl)
		{
			$data['filter_levels'][] = $fl->toArray();
		}

		return $data;		
	}
}