<?php
namespace Mocks;

class FieldsMock {

	public function __construct(Array $fields) {
		$this->fields = $fields;
	}

	// For dependency purposes
	public function toArray(){
		return $this->getFieldsAsArrays();
	}

	//Return a list of fields in array form
	private function getFieldsAsArrays(){
		$fieldsArr = [];
		foreach($this->fields as $row){
			$fieldsArr[] = get_object_vars($row);
		}
		return $fieldsArr;
	}

	//Return a list of Field objects
	public function getFieldsAsObjects(){
		$fields = [];
		foreach($this->fields as $field){
			$fields[] = new FieldMock($field);
		}

		return $fields;
	}
}

class FieldMock {
	public $decodeField;
	public $decodeTable;
	public $hasDecode;
	public $codeAllowNewValue;
	
	public function __construct($field){
		$this->fieldName = $field->fieldName;
		$this->decodeField = $field->decodeField;
		$this->decodeTable = $field->decodeTable;
		$this->hasDecode = $field->hasDecode;
		$this->codeAllowNewValue = $field->codeAllowNewValue;
	}

	public function toArray(){
		return [
			'fieldName' => $this->fieldName,
			'decodeField' => $this->decodeField,
			'decodeTable' => $this->decodeTable,
			'hasDecode' => $this->hasDecode,
			'codeAllowNewValue' => $this->codeAllowNewValue
		];
	}
}