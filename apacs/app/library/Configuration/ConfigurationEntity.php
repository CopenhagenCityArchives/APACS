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
		$this->isPrimaryEntity = $entity['isPrimaryEntity'] ?? null;
		$this->entityKeyName = $entity['entityKeyName'] ?? null;
		$this->name = $entity['name'];
		$this->guiName = $entity['guiName'] ?? null;
		$this->type = $entity['type'];
		$this->required = $entity['required'] ?? null;
		$this->includeInSOLR = $entity['includeInSOLR'] ?? 0;

		$this->setFields($entity['fields']);
		
		if(isset($entity['entities'])){
			$this->setEntities($entity['entities']);
		}

		$this->description = $entity['description'] ?? null;

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

	public function toArray(): Array {
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
			$validator = new Validator(new ValidationRuleSet($field->validationRegularExpression, $field->isRequired, $field->validationErrorMessage, $field->formFieldType));
			
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
	 * Get the denormalizes version of the given data, corresponding to this entity tree,
	 * where all values in arrays or descendants of an array will be stored in arrays, and
	 * all fields in the root or in object typed children are stored flat (except in the case)
	 * of nested arrays in an object typed child.
	 * 
	 * @param Array $data The data to be denormalized according to this entity tree.
	 * @return Array The denormalized data.
	 */
	public function getDenormalizedData(Array $data): Array {
		$result = [];

		if ($this->includeInSOLR == 1) {
			$entityConcat = "";
			foreach ($this->getFields() as $field) {
				if (isset($data[$field->GetRealFieldName()])) {
					$entityConcat .= $data[$field->GetRealFieldName()] . ' ';
				}
			}
			$entityConcat = trim($entityConcat);
			$result[$this->name] = $entityConcat;
		}
		

		foreach ($this->getFields() as $field) {
			if ($field->includeInSOLR != 1) {
				continue;
			}

			$result[$field->SOLRFieldName] = $this->transformSolrValue($field, $data);
		}

		foreach ($this->getChildren() as $childEntity) {
			// Skip children without data
			if (!isset($data[$childEntity->name])) {
				continue;
			}

			if ($childEntity->type == 'array') {
				foreach ($data[$childEntity->name] as $item) {
					foreach ($childEntity->getDenormalizedData($item) as $key => $value) {
						if (is_array($value)) {
							// if the denormalized data already contains an array, we
							// add the individual subvalues to prevent nested arrays
							foreach ($value as $subvalue) {
								$result[$key][] = $subvalue;
							}
						} else {
							$result[$key][] = $value;
						}
					}
				}
			} else {
				$childData = $childEntity->getDenormalizedData($data[$childEntity->name]);
				foreach ($childData as $key => $value) {
					$result[$key] = $value;
				}
			}
		}

		return $result;
	}

	private function transformSolrValue($field, $data) {
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
