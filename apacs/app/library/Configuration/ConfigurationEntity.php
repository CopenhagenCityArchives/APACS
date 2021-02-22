<?php

class ConfigurationEntity implements IEntity {
	public $primaryTableName;
	public $fieldsList;
	public $fields;
	protected $entities;
	public $isPrimaryEntity;
	public $entityKeyName;
	public $name;
	public $guiName;
	public $type;
	public $required;
	public $includeInSOLR;
	public $description;

	private $validationMessages;

	//array representation of Entity
	private $array;

	public function __construct(Array $entity) {
		$this->primaryTableName = $entity['primaryTableName'];
		$this->isPrimaryEntity = $entity['isPrimaryEntity'];
		$this->entityKeyName = $entity['entityKeyName'];
		$this->name = $entity['name'];
		$this->guiName = $entity['guiName'];
		$this->type = $entity['type'];
		$this->required = $entity['required'];
		$this->includeInSOLR = isset($entity['includeInSOLR']) ? $entity['includeInSOLR'] : 0;
		$this->setFields($entity['fields']);
		$this->setEntities($entity['entities']);
		$this->description = $entity['description'];

		$this->array = $entity;		

		$this->validationMessages = [];
	}

	/**
	 * Returns a flattened array of ConfigurationEntity tree structure,
	 * with the first element being the current ConfiguratioEntity.
	 * 
	 * For example, it transforms the structure:
	 *   entity1 {
	 * 	   entities: [
	 *       entity2 { entities: null },
	 *       entity3 { entities: [
	 * 		     entity4 { entities: null }
	 *         ]
	 *       },
	 *       entity5 { entities: null },
	 *     ]
	 *   }
	 * 
	 * into:
	 *   [ entity1 { .. }, entity2 { .. }, entity3 { .. }, entity4 { .. }, entity5 { .. } ]
	 */
	public function flattenTree() {
		$flattened = [];
		$flattened[] = $this;

		if (!$this->entities == null) {
			foreach ($this->entities as $childEntity) {
				foreach ($childEntity->flattenTree() as $flattenedEntity) {
					$flattened[] = $flattenedEntity;
				}
			}
		}

		return $flattened;
	}

	public function toArray(){
		return $this->array;
	}

	//Return a list of FieldMock objects
	public function getFields(){
		return $this->fields->getFieldsAsObjects();
	}

	public function isDataValid(array $entityData){
		$isValid = true;
		if ($this->required == '1' && $entityData == null) {
			$this->validationMessages[] = 'No data given for entity ' . $this->name;
			return false;
		}
		foreach ($this->getFields() as $field) {
			
			$validator = new Validator(new ValidationRuleSet($field->validationRegularExpression, $field->isRequired, $field->validationErrorMessage));
			
			if (!$validator->IsValid($entityData, $field->GetRealFieldName())) {
				$this->validationMessages[] = $field->GetRealFieldName() . ': ' . $validator->GetErrorMessage();
				$isValid = false;
			}
		}

		return $isValid;
	}

	/**
	 * Check if the data from a user entry for this entity is all empty.
	 * 
	 * @return boolean false if any field or sub-entity has a value in any item, true otherwise.
	 */
	public function UserEntryIsEmpty(Array $data) {
		if ($this->type == 'array') {
			// Check each item of the entry data
			foreach ($data as $item) {
				if (!$this->UserEntryItemIsEmpty($item)) {
					return false;
				}
			}
		} else if (!$this->UserEntryItemIsEmpty($data)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the data from a single entry item for this entity is all empty.
	 * 
	 * @param Array $item The specific item to check if it is empty according to the
	 * 				entity. This is always a single item, even for array entities
	 * 
	 * @return boolean false if any field or sub-entity has a value, true otherwise.
	 * 
	 */
	public function UserEntryItemIsEmpty(Array $item) {
		// Check the entry data for each field of the entity
		foreach ($this->getFields() as $field) {
			if (isset($item[$field->getRealFieldName()]) && !is_null($item[$field->getRealFieldName()])) {
				return false;
			}
		}

		// Check the entry data for the child entities
		foreach ($this->getChildren() as $child) {
			if (isset($item[$child->name]) && !is_null($item[$child->name]) && !$child->UserEntryIsEmpty($item[$child->name])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns a concatted string or an array of of all field values for fields where includeInSOLR = 1 (return type depending on entity type)
	 * @param Array $data The data to concat
	 */
	public function ConcatDataByEntity($data) {
		$concat = '';

		if ($this->type == 'array') {
			$concat = [];
			$concatStr = '';
			foreach ($data as $row) {
				foreach (array_filter($this->getFields(), function ($el) {return $el->includeInSOLR == '1';}) as $field) {
					$fieldName = $field->GetRealFieldName();

					$concatStr .= $row[$fieldName] . ' ';
				}
				$concat[] = trim($concatStr);
				$concatStr = '';
			}
			return $concat;
		} else {
			$concatStr = '';

			foreach (array_filter($this->getFields(), function ($el) { return $el->includeInSOLR == '1';}) as $field) {
				if (isset($data[$field->GetRealFieldName()])) {
					$concatStr .= $data[$field->GetRealFieldName()] . ' ';
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
			foreach (array_filter($this->getFields(), function ($el) {return $el->includeInSOLR == '1';}) as $field) {
				foreach ($data as $row) {
					$concat[$field->SOLRFieldName][] = $this->getFieldData($field, $row);
				}
			}
			return $concat;
		} else {
			foreach (array_filter($this->getFields(), function ($el) { return $el->includeInSOLR == '1';}) as $field) {
				$concat[$field->SOLRFieldName] = $this->getFieldData($field, $data);
			}
			return $concat;
		}
	}

	public function getDenormalizedData(Array $data): Array {
		$denormalizedData = [];

		if ($this->includeInSOLR == 1) {
			$denormalizedData[$this->name] = $this->ConcatDataByEntity($data);
		}

		$denormalizedData = array_merge($denormalizedData, $this->ConcatDataByField($data));

		return $denormalizedData;
	}

	private function getFieldData($field, $data) {
		if (isset($data[$field->GetRealFieldName()])) {
			if ($field->formFieldType == 'date') {
				return date('Y-m-d\TH:i:s.000\Z', strtotime($data[$field->GetRealFieldName()]));
			}

			if($field->fieldName == 'ageWeeks' || $field->fieldName == 'ageDays' || $field->fieldName == 'ageHours' || $field->fieldName == 'ageMonth' || $field->fieldName == 'ageYears'){
				return str_replace(',', '.', $data[$field->GetRealFieldName()]);
			}

			if (trim($data[$field->GetRealFieldName()]) == '') {
				$data[$field->fieldName] = null;
			}
			return $data[$field->GetRealFieldName()];
		}

		return null;
	}

	public function setFields($fields){
		if(is_null($fields)){
			$this->fields = [];
			$this->fieldsList = [];
			return;
		}
		$this->fieldsList = $fields;
		$this->fields = new ConfigurationFieldsHolder($fields);
	}

	public function getChildren() {
		if (is_null($this->entities)) {
			return [];
		}
		
		return $this->entities;
	}

	private function setEntities($entities){
		$this->entities = [];
		if(!is_array($entities) || count($entities)==0){
			return;
		}
		foreach($entities as $ent){
			$this->entities[] = new ConfigurationEntity($ent);
		}
	}

	public function GetValidationStatus(): string {
		return implode(' ',$this->validationMessages);
	}

	/**
	 * Traverses through the Entity tree depth first, and finds the entity by
	 * the given name, if it exists.
	 * 
	 * @return IEntity The first entity with the given name, or NULL if none
	 *                 is found.
	 */
	public function getEntityByName(string $name) {
        if ($this->name == $name){
            return $this;
        }

        foreach ($this->getChildren() as $childEntity){
            $result = $childEntity->getEntityByName($name);
            if (!is_null($result)){
                return $result;
            }
        }

        return null;
    }
}	
