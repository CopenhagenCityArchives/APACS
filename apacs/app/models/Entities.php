<?php

class Entities extends \Phalcon\Mvc\Model {
	public static $publicFields = ['id', 'required', 'countPerEntry', 'isMarkable', 'guiName', 'task_id'];
	public static $entityJsonSchemaFields = ['id', 'validationErrorMessage', 'pattern', 'title', 'isRequired'];
	private $validationStatus = [];

	public function getSource() {
		return 'apacs_' . 'entities';
	}

	public function initialize() {
		$this->hasMany('id', 'Fields', 'entities_id');
		$this->belongsTo('task_id', 'Task', 'id');
	}

	public function isDataValid($data = null) {
		$isValid = true;
		if ($this->required == '1' && $data == null) {
			$this->validationStatus[] = 'No data given for entity ' . $this->name;
			return false;
		}
		foreach ($this->getFields() as $field) {
			$validator = new Validator(new ValidationRuleSet($field->validationRegularExpression, $field->isRequired, $field->validationErrorMessage));

			if (!$validator->IsValid($data, $field->GetRealFieldName())) {
				$this->validationStatus[] = $field->GetRealFieldName() . ': ' . $validator->GetErrorMessage();
				$isValid = false;
			}
		}

		return $isValid;
	}

	/**
	 * Returns the position of the entity in the format primaryEntityName.secondaryEntityName
	 * @param Array $entities The entities
	 * @param Entity $entity   The entity for which the position is needed
	 */
	public function GetEntityPosition($entities, $entity) {
		$primaryEntity = $this->GetPrimaryEntity($entities);

		if ($entity == $primaryEntity) {
			return $entity->name;
		}

		return $primaryEntity->name . '.' . $entity->name;
	}

	public function GetValidationStatus() {
		return count($this->validationStatus) == 1 ? $this->validationStatus[0] : implode('. ', $this->validationStatus);
	}

	/**
	 * Converts the entity to JSON schema form
	 * @return Array An array representing the entity in JSON schema form
	 */
	public function ConvertToJSONSchemaObject() {
		$entity = $this->toArray();

		$entity['title'] = $entity['guiName'];
		$entity['fields'] = $this->GetFieldsAsAssocArray();

		$iterateFields = $entity['fields'];

		//iterating fields
		foreach ($iterateFields as $key => $field) {
			//Remove fields not included in form (includeInForm = 0)
			if ($field['includeInForm'] == 0) {
				unset($entity['fields'][$key]);
				continue;
			}
			//Converting field property validationRegularExpression to pattern
			//if (!is_null($field['validationRegularExpression'])) {
			$field['pattern'] = null;
			$field['pattern'] = str_replace('/', '', $field['validationRegularExpression']);
			//}

			$field['title'] = $field['formName'];
			$field['description'] = $field['helpText'];

			//Converting field property to
			if (!is_null($field['formFieldType'])) {
				$field['type'] = $field['formFieldType'];
				if ($field['formFieldType'] == 'typeahead') {
					$field['type'] = 'string';
					$field['format'] = 'typeahead';
				}
				if ($field['formFieldType'] == 'date') {
					$field['type'] = 'string';
					$field['format'] = 'date';
				}
			}
			//	echo $field['datasources_id'];
			//Getting info from datasource, if any
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

			//When using decode fields, the decode field name is used as name
			if ($field['hasDecode'] == 1) {
				$field['fieldName'] = $field['decodeField'];
			}

			unset($entity['fields'][$key]);
			$entity['fields'][$field['fieldName']] = $field;
			//	$entity['fields'][$field['fieldName']] = array_intersect_assoc(Entities::$entityJsonSchemaFields, $entity['fields']);
		}

		$entity['required'] = [];

		$requiredFields = array_filter($entity['fields'], function ($el) {return $el['isRequired'] == '1';});
		foreach ($requiredFields as $reqField) {
			//if decodeField: Use decodeFieldName
			if (is_null($reqField['decodeField'])) {
				$entity['required'][] = $reqField['fieldName'];
			} else {
				$entity['required'][] = $reqField['decodeField'];
			}

		}

		//Converts fields to properties or items, depending on entity type
		if ($entity['type'] == 'object') {
			$entity['properties'] = $entity['fields'];
		} else {
			$entity['items'] = [];
			$entity['items']['title'] = $entity['title'];
			$entity['items']['type'] = 'object';
			$entity['items']['properties'] = $entity['fields'];
			//}
		}

		unset($entity['fields']);

		return $entity;
	}

	/**
	 * Returns a concatted string on an array of concatted strings based on the input data
	 * @param Array $data The data to concat
	 */
	public function ConcatDataByEntity($data) {
		$concat = '';
		if ($this->type == 'array') {
			$concat = [];
			$concatStr = '';
			foreach ($data as $row) {
				foreach (array_filter($this->getFields()->toArray(), function ($el) {return $el['includeInSOLR'] == '1';}) as $field) {
					$fieldName = Fields::GetRealFieldNameFromField($field);

					$concatStr .= $row[$fieldName] . ' ';
				}
				$concat[] = trim($concatStr);
				$concatStr = '';
			}
			return $concat;
		} else {
			$concatStr = '';
			foreach (array_filter($this->getFields()->toArray(), function ($el) {return $el['includeInSOLR'] == '1';}) as $field) {
				if (isset($data[Fields::GetRealFieldNameFromField($field)])) {
					$concatStr .= $data[Fields::GetRealFieldNameFromField($field)] . ' ';
				}

			}
			return trim($concatStr);
		}
	}

	/**
	 * Returns an array of concatted field data ordered by field type
	 * @param Array $data The data to concat
	 */
	public function ConcatDataByField($data) {
		$concat = [];

		if ($this->type == 'array') {
			foreach (array_filter($this->getFields()->toArray(), function ($el) {return $el['includeInSOLR'] == '1';}) as $field) {
				foreach ($data as $row) {
					$concat[$field['SOLRFieldName']][] = $this->getFieldData($field, $row);
				}
			}
			return $concat;
		} else {
			foreach (array_filter($this->getFields()->toArray(), function ($el) {return $el['includeInSOLR'] == '1';}) as $field) {
				$concat[$field['SOLRFieldName']] = $this->getFieldData($field, $data);
			}
			return $concat;
		}
	}

	private function getFieldData($field, $data) {
		if (isset($data[Fields::GetRealFieldNameFromField($field)])) {
			if ($field['formFieldType'] == 'date') {
				return date('d-m-Y', strtotime($data[Fields::GetRealFieldNameFromField($field)]));
			}
			if (trim($data[Fields::GetRealFieldNameFromField($field)]) == '') {
				$fieldsAndData[$field['fieldName']] = null;
			}
			return $data[Fields::GetRealFieldNameFromField($field)];
		}

		return null;
	}

	/**
	 * Returns the fields of the enitity to an associative array with fieldName as key
	 * @return Array An array of fields in an associative array
	 */
	public function GetFieldsAsAssocArray() {
		$keyArr = [];

		$fields = Fields::find(['conditions' => 'entities_id = ' . $this->id, 'order' => 'formFieldOrder'])->toArray();

		foreach ($fields as $key => $field) {
			if ($field['includeInForm'] == '1') {
				$keyArr[$field['fieldName']] = $field;
			}
		}

		return $keyArr;
	}

	/**
	 * Returns the primary entity for an array of entities
	 * @param ResultSet $entities A ResultSet representing the entities to search
	 * @return Entities The first occuring primary entity. If none is found null is returned
	 */
	public static function GetPrimaryEntity($entities) {
		$entities->rewind();
		while ($entities->valid()) {
			$entity = $entities->current();
			if ($entity->isPrimaryEntity == '1') {
				return $entity;
			}

			$entities->next();
		}

		return null;
	}

	public static function GetSecondaryEntities($entities) {
		$entities->rewind();
		$secondaryEntities = [];
		while ($entities->valid()) {
			$entity = $entities->current();
			if ($entity->isPrimaryEntity !== '1') {
				$secondaryEntities[] = $entity;
			}

			$entities->next();
		}

		return $secondaryEntities;
	}
}