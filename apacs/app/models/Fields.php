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