<?php

class EntitiesCollectionToTaskSchemaMapper{

    private $entitiesCollection;
    
    public function __construct(IEntitiesCollection $collection){
        $this->entitiesCollection = $collection;
    }

    public function getSchema($title, $description, $steps){
        $mainEntity = $this->entitiesCollection->getPrimaryEntity();
		$mainArr = $mainEntity->toArray();
		$mainArr['title'] = $title;
		$mainArr['description'] = $description;

		$mainSchema = $this->ConvertToJSONSchemaObject($mainEntity);

		$mainArr['required'] = $mainSchema['required'];
        $mainArr['properties'] = $mainSchema['properties'];
        unset($mainArr['fields']);
        unset($mainArr['entities']);

		$entities = $this->entitiesCollection->getSecondaryEntities();

		foreach ($entities as $entity) {
			$mainArr['properties'][$entity->name] = $this->ConvertToJSONSchemaObject($entity);
		}

		$response = [];
		$response['keyName'] = $mainEntity->name;
		$response['schema']['type'] = 'object';
		$response['schema']['properties'][$mainEntity->name] = $mainArr;
		$response['steps'] = $steps;

		return $response;
    }
    
    /**
	 * Converts the entity to JSON schema form
	 * @return Array An array representing the entity in JSON schema form
	 */
	public function ConvertToJSONSchemaObject(IEntity $orgEntity) {
		$entity = $orgEntity->toArray();

		$entity['title'] = $entity['guiName'];
		$entity['fields'] = $this->GetFieldsAsAssocArray($entity['fields']);

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
				if ($field['formFieldType'] == 'number') {
					$field['type'] = 'string';
				}
			}
			//	echo $field['datasources_id'];
			//Getting info from datasource, if any
			$field = Datasources::SetDatasourceOrEnum($field);

			//When using decode fields, the decode field name is used as name
			if ($field['hasDecode'] == 1) {
				$field['fieldName'] = $field['decodeField'];
			}

			unset($entity['fields'][$key]);
			$entity['fields'][$field['fieldName']] = $field;
			//	$entity['fields'][$field['fieldName']] = array_intersect_assoc(//Entities::$entityJsonSchemaFields, $entity['fields']);
		}

		$entity = $this->setRequiredFields($entity);

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
        unset($entity['entities']);
        
		return $entity;
    }
    
    private function setRequiredFields($entity){
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
        
        return $entity;
    }

	/**
	 * Returns the fields of the enitity to an associative array with fieldName as key
	 * @return Array An array of fields in an associative array
	 */
	private function GetFieldsAsAssocArray($fields) {
		$keyArr = [];
        $orderedFields = $fields;

        uasort($orderedFields, function ($a, $b){
            if($a['formFieldOrder'] == $b['formFieldOrder']){
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