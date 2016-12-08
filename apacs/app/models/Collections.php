<?php

class Collections extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'collections';
	}

	public function initialize() {
		$this->hasMany('id', 'Tasks', 'collection_id');
	}

	public function GetSearchConfig() {
		$phql = 'SELECT Fields.id, SOLRFieldName as solr_name, SOLRFacet as facetable, formName as name, Fields.includeInSOLR as searchable, SOLRResult as include_in_result, SOLRResult as sortable, CASE Entities.isPrimaryEntity WHEN 1 THEN 1 ELSE 0 END as sortable FROM apacs_fields Fields left join apacs_entities Entities ON Fields.entities_id = Entities.id LEFT JOIN apacs_tasks Tasks ON Entities.task_id = Tasks.id LEFT JOIN apacs_collections Collections ON Tasks.collection_id = Collections.id WHERE Fields.includeInSOLR = 1 AND Tasks.id = :id';
		$resultSet = $this->getDI()->get('db')->query($phql, ['id' => $this->id]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		return $resultSet->fetchAll();
	}
}