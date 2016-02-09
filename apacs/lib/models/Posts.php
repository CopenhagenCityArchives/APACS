<?php

class Posts extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'posts';
	}

	public function initialize() {
		$this->belongsTo('id', 'Pages', 'pages_id');
		$this->hasMany('id', 'TasksPosts', 'tasks_id');
		$this->hasMany('id', 'Entries', 'posts_id');
	}

	public function SaveEntries($postId, $taskId, $data) {
		$entities = Entities::find(['conditions' => 'task_id = ' . $taskId]);

		//TODO: Save post info

		$mainEntityKey = array_search(1, array_column($entities->toArray(), 'isPrimaryEntity'));

		if ($mainEntityKey === false) {
			throw new Exception('no primary entity found for task id ' . $taskId);
		}

		$entry = new Entries();
		$data[$entities[$mainEntityKey]->name]['post_id'] = $postId;
		$mainEntityId = $entry->SaveEntry($entities[$mainEntityKey], $data[$entities[$mainEntityKey]->name]);
		$mainEntityName = $entities[$mainEntityKey]->name;
		//TODO: Validate data?

		//Save entities
		foreach ($entities as $key => $entity) {
			//Don't save entity if data for it is not set, or if it is the main entity
			if (!isset($data[$mainEntityName][$entity->name]) || $entity->name == $mainEntityName) {
				continue;
			}
			if ($entity->type == 'array') {
				foreach ($data[$mainEntityName][$entity->name] as $data) {
					$data[$entity->entityKeyName] = $mainEntityId;
					$entry->SaveEntry($entity, $data);
				}
			} else {
				$data[$entity->entityKeyName] = $mainEntityId;
				$entry->SaveEntry($entity, $data[$mainEntityName][$entity->name]);
			}
		}
	}

	/**
	 * Method for converting data to SOLR format
	 * For entities of type object and includeInSOLR = 1, all related fields with includeInSOLR = 1 is sent to
	 * SOLR in a 1:1 form, using SOLRFieldName as name. The entity itself is concated an sent to SOLR.
	 * For entities of type array and includeInSOLR = 1, all related fields with includeInSOLR = 1 is sent to
	 * SOLR in a concated form, one row pr. entity, and all values are put in arrays according to the field
	 * they belong to
	 * @param Array $entities The entities to save
	 * @param Array $data     The data to convert
	 */
	public function GetSolrData($entities, $data) {
		$solrData = [];

		$primaryEntity = array_filter($entities, function ($el) {return $el->isPrimaryEntity;})[0];

		foreach ($entities as $entity) {
			$row = null;
			if ($entity->isPrimaryEntity == '1') {
				$row = $data[$entity->name];
			} else {
				if (isset($data[$primaryEntity->name][$entity->name])) {
					$row = $data[$primaryEntity->name][$entity->name];
				} else {
					$row = [];
				}
			}

			if (count($row) > 0) {
				if ($entity->includeInSOLR == '1') {
					$solrData[$entity->name] = $entity->ConcatDataByEntity($row);
				}
				$solrData = array_merge($solrData, $entity->ConcatDataByField($row));
			}
		}

		return $solrData;
	}

	public function GetPostsByPage($pageId) {
		$posts = Posts::find(['conditions' => 'pages_id = ' . $pageId]);

		if (count($posts) == 0) {
			//TODO: Calculate posts based on page image size
		}

		return $posts;
	}

	/*private function CalculatePosts($x, $y, $width, $height, $postsX, $postsY)
		{
			$threshold = 0.8;
			//Go right
			while()

	*/
}