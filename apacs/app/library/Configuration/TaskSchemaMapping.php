<?php

class TaskSchemaMapping {
    public static function createTaskSchema($configurationEntity, $taskTitle, $taskDescription, $taskSteps){
        $mainEntity = $configurationEntity;

		$mainSchema = TaskSchemaMapping::ConvertToJSONSchemaObject($mainEntity);
		$mainSchema['title'] = $taskTitle;
		$mainSchema['description'] = $taskDescription;

		$taskSchema = [];
		$taskSchema['keyName'] = $mainEntity->name;
		$taskSchema['schema']['type'] = 'object';
		$taskSchema['schema']['properties'][$mainEntity->name] = $mainSchema;
		$taskSchema['steps'] = $taskSteps;

		return $taskSchema;
    }
    
    /**
	 * Converts the entity to JSON schema form
	 * @return Array An array representing the entity in JSON schema form
	 */
	public static function ConvertToJSONSchemaObject(IEntity $orgEntity) {
		$schema = $orgEntity->toArray();

		$schema['title'] = $schema['guiName'];
		$schema['fields'] = TaskSchemaMapping::GetFieldsAsAssocArray($schema['fields']);

		foreach ($schema['fields'] as $key => $field) {
			// Remove fields not included in form (includeInForm = 0)
			if ($field['includeInForm'] == 0) {
				unset($schema['fields'][$key]);
				continue;
			}

			// Converting field property validationRegularExpression to pattern
			$field['pattern'] = str_replace('/', '', $field['validationRegularExpression']);

			$field['title'] = $field['formName'];
			$field['description'] = $field['helpText'];

			// Converting field property to
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
				if ($field['formFieldType'] == 'number') {
					$field['type'] = 'string';
				}
			}
			
			// Getting info from datasource, if any
			$field = Datasources::SetDatasourceOrEnum($field);

			// When using decode fields, the decode field name is used as name
			if ($field['hasDecode'] == 1) {
				$field['fieldName'] = $field['decodeField'];
			}

			unset($schema['fields'][$key]);
			$schema['fields'][$field['fieldName']] = $field;
		}

		// Converts fields to properties or items, depending on entity type
		// ASSUMPTION: entity type MUST be 'object' or 'array'
		if ($schema['type'] == 'object') {
			$schema['properties'] = $schema['fields'];
		} else {
			$schema['items'] = [];
			$schema['items']['title'] = $schema['title'];
			$schema['items']['type'] = 'object';
			$schema['items']['properties'] = $schema['fields'];
		}

		$childEntities = $orgEntity->getChildren();
		if (!is_null($childEntities)) {
			foreach ($childEntities as $childEntity) {
				$childSchema = TaskSchemaMapping::ConvertToJSONSchemaObject($childEntity);
				if ($schema['type'] == 'object') {
					$schema['properties'][$childEntity->name] = $childSchema;
				} else {
					// assume array
					$schema['items']['properties'][$childEntity->name] = $childSchema;
				}
			}
		}

		$schema = TaskSchemaMapping::setRequiredFields($schema);

        unset($schema['fields']);
        unset($schema['entities']);
        
		return $schema;
    }
	
	/**
	 * Adds the property 'required' to the schema Array, and fills it with the
	 * fields that are set as required.
	 * 
	 * @return Array The modified schema array.
	 */
    public static function setRequiredFields(Array $schema) {
        $schema['required'] = [];

		foreach ($schema['fields'] as $field) {
			if ($field['isRequired'] != '1') {
				continue;
			}

			// Use decodeField as field name if it's defined
			$requiredFieldKey = is_null($field['decodeField']) ? $field['fieldName'] : $field['decodeField'];
			if ($schema['type'] == 'array') {
				$schema['items']['required'][] = $requiredFieldKey;
			} else {
				$schema['required'][] = $requiredFieldKey;
			}
		}
        
        return $schema;
    }

	/**
	 * Returns the fields of the entity to an associative array with fieldName as key
	 * @return Array An array of fields in an associative array
	 */
	public static function GetFieldsAsAssocArray($fields) {
		$keyArr = [];
        $orderedFields = $fields;

        uasort($orderedFields, function ($a, $b) {
            if ($a['formFieldOrder'] == $b['formFieldOrder']) {
                return 0;
            }
    
            return ($a['formFieldOrder'] < $b['formFieldOrder']) ? -1 : 1;
        });

		foreach ($orderedFields as $key => $field) {
			if ($field['includeInForm'] == '1') {
				$keyArr[$field['fieldName']] = $field;
			}
		}

		return $keyArr;
    }
}