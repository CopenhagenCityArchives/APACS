<?php

use Phalcon\Mvc\Model\Query;

class Entities extends \Phalcon\Mvc\Model {
	public static $publicFields = ['id', 'required', 'countPerEntry', 'isMarkable', 'guiName', 'task_id'];

	public function getSource() {
		return 'apacs_' . 'entities';
	}

	public function initialize() {
		$this->hasMany('id', 'EntitiesFields', 'entity_id');
		$this->belongsTo('task_id', 'Task', 'id');
	}

	private function GetEntitiesFields($entityId) {
		$query = new Query("SELECT Entities.id as entity_id, EntitiesFields.id as entity_field_id, Fields.name, Fields.type, Fields.validationRegularExpression, Fields.helpText, Fields.validationErrorMessage, Fields.defaultValue, Fields.required, Fields.foreignEntityName, Fields.foreignFieldName, Fields.dbFieldName, Fields.includeInForm FROM Entities LEFT JOIN EntitiesFields ON Entities.id = EntitiesFields.entity_id LEFT JOIN Fields ON EntitiesFields.field_id = Fields.id WHERE Entities.id = :entityId:", $this->getDI());
		$fields = $query->execute(['entityId' => $entityId])->toArray();
		return $fields;
	}

	/*private function GetEntitiesRelatingTo($entityId) {
		$relatingFields = Fields::find(['conditions' => 'entities_id = ' . $entityId])->toArray();
		$uniqueEntryIds = array_unique(array_map(function ($i) {return $i['entities_id'];}, $relatingFields));

		return Entities::find([
			'id IN (:ids:)',
			'bind' => ['ids' => $uniqueEntryIds],
		])->toArray();
	}*/

	public function getEntityAsJSONSchema($entity) {

		foreach ($entity['fields'] as $key => $field) {
			//If the field has fields, it is an object or array, and is converted recursively
			if (isset($field['fields'])) {
				$entity['fields'][$key] = $this->getEntityAsJSONSchema($entity['fields'][$key]);
			}
		}
		//Converting fields to assoc array
		$entity['fields'] = $this->ConvertFieldsToAssocArray($entity['fields']);
		//Converting the entity itself to JSONSchema object
		$entity = $this->ConvertEntityToJSONSchemaObject($entity);
		return $entity;
	}

	public function GetEntityAndFields($entityId, $parentEntity = null) {
		$entity = Entities::find(['conditions' => 'id =' . $entityId])[0];
		$entityArr = [];
		$entityArr = $entity->toArray();
		$entityArr['fields'] = $this->GetEntitiesFields($entityId);
		//$entityArr['fields'] = $this->ConvertFieldsToAssocArray($entityArr['fields']);
		if ($entityArr['countPerEntry'] == -1 || $entityArr['countPerEntry'] > 1) {
			$entityArr['type'] = 'array';
		} else {
			$entityArr['type'] = 'object';
		}

		//Loading related entities by identifying fields of type object and array
		foreach (array_filter($entityArr['fields'], function ($el) {return $el['type'] == 'object' || $el['type'] == 'array';}) as $key => $field) {
			//We dont wnat to receive the parent entity (this will generate an eternal loop)
			if ($field['foreignEntityName'] !== $parentEntity) {
				$entityArr['fields'][$key] = $this->GetEntityAndFields($field['foreignEntityName'], $field['entity_id']);
			}
		}

		return $entityArr;
	}

	public function ConvertFieldsToAssocArray($fields) {
		$keyArr = [];
		foreach ($fields as $key => $field) {
			if (isset($field['dbFieldName'])) {
				//if ($field['includeInForm'] == 1) {
				$keyArr[$field['dbFieldName']] = $field;
				//	}
			} else if (isset($field['dbTableName'])) {
				$keyArr[$field['dbTableName']] = $field;
			} else {
				$keyArr[] = $field;
			}
		}

		return $keyArr;
	}

	public function ConvertEntityToJSONSchemaObject($entity) {
		foreach ($entity as $key => $property) {
			switch ($key) {
			case 'dbTableName':
				$entity['title'] = $entity[$key];
				break;
			case 'name':
				$entity['title'] = $entity[$key];
				break;
			case 'validationRegularExpression':
				$entity['pattern'] = $entity[$key];
				break;
			}
		}

		//Remove fields not included in form (includeInForm = 0)
		foreach ($entity['fields'] as $key => $field) {
			if (isset($field['includeInForm']) && $field['includeInForm'] == 0) {
				unset($entity['fields'][$key]);
			}
		}

		unset($entity['required']);

		$requiredFields = array_column(array_filter($entity['fields'], function ($el) {return $el['required'] == 1;}), 'dbFieldName');
		$entity['required'] = $requiredFields;

		if ($entity['countPerEntry'] == 1) {
			$entity['type'] = 'object';
			$entity['properties'] = $entity['fields'];
		} else {
			$entity['type'] = 'array';
			$entity['items'] = $entity['fields'];
		}
		unset($entity['fields']);

		return $entity;
	}

	public static function LoadEntitiesHierarchy($taskId) {
		$parentEntities = Entities::find(['conditions' => 'task_id = ' . $taskId . ' AND parent_id IS NULL']);

		//Loading entities with children
		$entities = [];
		$i = 0;
		foreach ($parentEntities as $entity) {
			$entities[$i] = Entities::GetEntityRecursively($entity);
			$i++;
		}

		return $entities;
	}

	private static function GetEntityRecursively($ingoingEntity) {
		$entity = $ingoingEntity->toArray();

		//Loading fields
		$query = new Query('SELECT f.* FROM EntitiesFields as ef LEFT JOIN Fields as f ON ef.field_id = f.id WHERE ef.entity_id = ' . $ingoingEntity->id, \Phalcon\DI\FactoryDefault::getDefault());
		$fields = $query->execute()->toArray();

		//Adding the already known table name manually
		for ($i = 0; $i < count($fields); $i++) {
			$fields[$i]['dbTableName'] = $ingoingEntity->dbTableName;
		}

		$entity['fields'] = $fields;

		foreach (Entities::find(['conditions' => 'parent_id = ' . $ingoingEntity->id]) as $child) {
			$c = Entities::GetEntityRecursively($child);
			if ($c != NULL) {
				$entity['children'][] = $c;
			}
		}
		return $entity;
	}
}