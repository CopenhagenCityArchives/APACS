<?php

class Fields extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'fields';
	}

	public function initialize() {
		$this->belongsTo('id', 'Entities', 'entities_id');
		$this->hasMany('datasources_id', 'Datasources', 'id');
	}

	/**
	 * Returns the fieldname used when accessing data. This name can be either fieldName,
	 * or decodeField, depending on wheter the field is decoded or not
	 */
	public function GetRealFieldName() {
		if ($this->decodeField !== null) {
			return $this->decodeField;
		}

		return $this->fieldName;
	}

	public static function GetRealFieldNameFromField($field) {
		return !is_null($field['decodeField']) ? $field['decodeField'] : $field['fieldName'];
	}

	public static function GetFieldSearchOperators($field) {

		if ($field['includeInSolr'] == 0) {
			return $field;
		}

		$operators = [];
		switch ($field['formFieldType']) {
		case 'string':
			$operators[] = [
				'label' => 'lig med',
				'solr_query' => '%f%: "%q%"',
			];

			$operators[] = [
				'label' => 'indeholder',
				'solr_query' => '%f%: *%q%*',
			];

			$operators[] = [
				'label' => 'indeholder ikke',
				'solr_query' => '-%f%: *%q%*',
			];

			$operators[] = [
				'label' => 'starter med',
				'solr_query' => '%f%: %q%*',
			];

			$operators[] = [
				'label' => 'ender med',
				'solr_query' => '%f%: *%q%',
			];

			break;

		case 'date':
			$operators[] = [
				'label' => 'lig med',
				'solr_query' => '%f%:"%q%"',
			];

			$operators[] = [
				'label' => 'mindre end',
				'solr_query' => '%f%:[* TO %q%]',
			];

			$operators[] = [
				'label' => 'større end',
				'solr_query' => '%f%:[%q% TO *]',
			];
			break;

		case 'number':
			$operators[] = [
				'label' => 'lig med',
				'solr_query' => '%f%: %q%',
			];

			$operators[] = [
				'label' => 'mindre end',
				'solr_query' => '%f%: [0 TO %q%]',
			];

			$operators[] = [
				'label' => 'større end',
				'solr_query' => '%f%: [%q% TO *]',
			];

			break;

		case 'typeahead':
			$operators[] = [
				'label' => 'lig med',
				'solr_query' => '%f%: "%q%"',
			];

			$operators[] = [
				'label' => 'ikke lig med',
				'solr_query' => '-%f%: "%q%"',
			];
		}

		return $operators;
	}

	public static function SetFieldSearchFacets($field) {

		if ($field['includeInSolr'] == 0 || $field['SOLRFacet'] == 0) {
			return null;
		}

		$facet = null;
		switch ($field['formFieldType']) {
		case 'string':
		case 'typeahead':
			$facet = [
				'result_key' => 'facet_fields',
				'url_parameter' => 'facet.field=' . $field['solr_name'] . '&facet.limit=10',
			];
			break;

		case 'number':
			$facet = [
				'result_key' => 'facet_ranges',
				'url_parameter' => 'facet.range=' . $field['solr_name'] . '&facet.range.start=0&facet.range.end=100000&facet.range.gap=10&facet.limit=10',
			];
			break;

			/*case 'date':
				$facet = [
					'result_key' => 'facet_ranges',
					'url_key' => 'facet.date=' . $field['solr_name'] . '&facet.date.start=0001-01-01T00:00:00Z&facet.date.end=NOW&facet.date.gap=+5YEAR&facet.limit=25',
				];
			*/
		}

		return $facet;
	}

	public static function SetDatasourceOrEnum($field) {
		if (!is_null($field['datasources_id'])) {
			$datasource = Datasources::findFirst(['conditions' => 'id = ' . $field['datasources_id']]);

			if (isset($datasource) && $datasource !== false) {
				$values = $datasource->GetValuesAsArray();
				if (!$values) {
					$field['datasource'] = 'http://www.kbhkilder.dk/1508/stable/api/datasource/' . $datasource->id . '?q=';
					$field['datasourceValueField'] = $datasource->valueField;
				} else {
					$field['enum'] = $values;
					$field['type'] = 'string';
				}
			}
		}

		return $field;
	}
}
