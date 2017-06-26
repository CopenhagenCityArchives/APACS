<?php

class Collections extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'collections';
	}

	public function initialize() {
		$this->hasMany('id', 'Tasks', 'collection_id');
		$this->hasMany('id', 'Units', 'collections_id');
	}

	public function GetSearchConfig() {
		$phql = 'SELECT Fields.id, Fields.formFieldType, Fields.datasources_id, Fields.includeInSolr, Fields.SOLRFacet, SOLRFieldName as solr_name, SOLRFacet as facetable, formName as name, CASE WHEN Fields.includeInSOLR = 1 THEN 1 WHEN Fields.fieldName = "freetext_store" THEN 1 ELSE 0 END as searchable, SOLRResult as include_in_result, SOLRResult as sortable /*, CASE WHEN Entities.isPrimaryEntity = 1 OR SOLRResult = 1 THEN 1 ELSE 0 END as sortable*/ FROM apacs_fields Fields left join apacs_entities Entities ON Fields.entities_id = Entities.id LEFT JOIN apacs_tasks Tasks ON Entities.task_id = Tasks.id LEFT JOIN apacs_collections Collections ON Tasks.collection_id = Collections.id WHERE (Fields.includeInSOLR = 1 OR Fields.fieldName = "freetext_store") AND Tasks.id = :id';
		$resultSet = $this->getDI()->get('db')->query($phql, ['id' => $this->id]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		return $resultSet->fetchAll();
	}

	public function GetStats(){
		$stats = [];

		$stats['units'] = 0;
		$stats['public_units'] = 0;
		$stats['units_without_pages'] = 0;

		$stats['pages'] = 0;
		$stats['public_pages'] = 0;

		$units = $this->getUnits()->toArray();

		foreach($units as $unit){
			$stats['units']++;
			$stats['pages'] += $unit['pages'];

			//Increment if public
			if($unit['is_public'] == 1){
				$stats['public_units']++;
				$stats['public_pages'] += $unit['pages'];
			}

			if($unit['pages'] == 0){
				$stats['units_without_pages']++;
			}
		}

		if($stats['units'] == 0){
			return null;
		}

		return $stats;
	}
}
