<?php
use Phalcon\Mvc\Model\Resultset;
use Phalcon\Mvc\Model\Query;

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

	public function GetTask($collectionId)
	{
		$task = Tasks::findFirstById($collectionId);
		return $task->toArray();
	}

	/**
	 * Retrieves a list of entities in JSON Schema form
	 * @param integer $taskId The id of the task
	 */
	public function GetTaskFieldsSchema($taskId)
	{
		$data = [];

		$task = Tasks::findFirstById($taskId);

		$data = $task->toArray();
		$data['schema'] = [];

		$entities = Entities::find(['conditions' => 'task_id = ' . $taskId . ' AND parent_id IS NULL', 'hydration' => Resultset::HYDRATE_ARRAYS]);
		$fields = new Tasks();
		$data['schema'] = [];
		$data['schema']['title'] = $data['name'];
		$data['schema']['type'] = 'object';
		$data['schema']['properties'] = [];

		if(count($entities) > 1)
		{
			$i = 0;
			foreach($entities as $entity)
			{
				$entityWithChildren = $this->GetEntityRecursively($entity);
				$data['schema']['properties'][$entity['id']] = $entityWithChildren;

				$i++;
			}
		}
		else{
			$entityWithChildren = $this->GetEntityRecursively($entities[0]);
			$data['schema']['properties'] = $entityWithChildren['properties'];
		}

		$data['steps'] = $this->GetSteps($taskId);

		return $data;
	}

	private function GetEntityRecursively($ingoingEntity)
	{
		$entity = $ingoingEntity;
		$entity = [];

		$steps = [];

		$task = new Tasks();

		$entity['title'] = $ingoingEntity['guiName'];
		$entity['type'] = $ingoingEntity['countPerEntry'] == 1 ? 'object' : 'array';

		$propertiesAndRequired = $task->GetFieldsSchema($ingoingEntity['id']);

		if($entity['type'] == 'array'){
			if($ingoingEntity['countPerEntry'] > 1)
				$entity['maxItems'] = $ingoingEntity['countPerEntry'];

			$entity['items'] = [];
			$entity['items']['type'] = 'object';
			foreach($propertiesAndRequired['properties'] as $property)
			{
				$item = $property;
				$item['type'] = 'object';
				$item['title'] = $property['entity_field_id'];
				$entity['items']['properties'][$item['entity_field_id']] = $item;
			}
		}
		else{
			$entity['properties'] = $propertiesAndRequired['properties'];
		}

		
		$entity['required'] = $propertiesAndRequired['required'];

		foreach(Entities::find(['conditions' => 'parent_id = ' . $ingoingEntity['id'], 'hydration' => Resultset::HYDRATE_ARRAYS]) as $child){
			$c = $this->GetEntityRecursively($child);
			$entity['properties'][$c['title']] = $c;
		}

		return $entity;
	}

	public function GetSteps($taskId)
	{
		$step = new Steps();
		//$steps = $step->GetSteps($taskId);
		$steps = Steps::find(['condition' => 'task_id = ' . $taskId, 'columns' => ['id', 'name', 'description']]);

		$data = [];
		$i = 0;
		foreach($steps as $step)
		{
			$data[$i] = $step->toArray();
			
			$data[$i]['fields'] = [];
			$result = new Query('SELECT e.guiName, ef.id, e.countPerEntry, e.parent_id FROM EntitiesFields ef LEFT JOIN Entities e ON ef.entity_id = e.id WHERE ef.step_id = ' . $step->id, \Phalcon\DI\FactoryDefault::getDefault());
			$fields = $result->execute()->toArray();
			//	var_dump($fields);
			

			foreach($fields as $field)
			{
				if($field['countPerEntry'] == -1)
				{
					$data[$i]['fields'][] = $field['guiName'];
					break;
				}
				else
				{
					if(is_null($field['parent_id'])){
						$data[$i]['fields'][] = $field['id'];
					}
					else{
						$data[$i]['fields'][] = $field['guiName'] . '.' . $field['id'];
					}
				}
			}
			$i++;
		}

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