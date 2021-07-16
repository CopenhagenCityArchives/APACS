<?php

class ConfigurationField{
    public $fieldName;
    public $tableName;
    public $decodeField;
    public $decodeTable;
    public $hasDecode;
    public $codeAllowNewValue;

    public $formName;
    public $formFieldType;

    public $validationRegularExpression;
    public $validationErrorMessage;

    public $isRequired;

    public $includeInSOLR;
    public $SOLRFieldName;
    
    public function __construct(Array $field){

        $this->fieldName = $field['fieldName'];
        $this->tableName = $field['tableName'] ?? null;
        
        $this->decodeField = $field['decodeField'] ?? null;
        $this->decodeTable = $field['decodeTable'] ?? null;
        $this->hasDecode = $field['hasDecode'] ?? null;
        $this->codeAllowNewValue = $field['codeAllowNewValue'] ?? null;

        $this->includeInForm = $field['includeInForm'] ?? 1;
        $this->formName = $field['formName'] ?? null;
        $this->formFieldType = $field['formFieldType'] ?? "string";

        $this->validationRegularExpression = $field['validationRegularExpression'] ?? null;
        $this->validationErrorMessage = $field['validationErrorMessage'] ?? null;

        $this->isRequired = $field['isRequired'] ?? null;

        $this->includeInSOLR = $field['includeInSOLR'] ?? 0;
        $this->SOLRFieldName = $field['SOLRFieldName'] ?? "solrFieldNameNotGiven";
    }

    public function toArray(){
        return [
            'fieldName' => $this->fieldName,
            'tableName' => $this->tableName,
            'decodeField' => $this->decodeField,
            'decodeTable' => $this->decodeTable,
            'hasDecode' => $this->hasDecode,
            'codeAllowNewValue' => $this->codeAllowNewValue,

            'includeInForm' => $this->includeInForm,
            'formName' => $this->formName,
            'formFieldType' => $this->formFieldType,

            'validationRegularExpression' => $this->validationRegularExpression,
            'validationErrorMessage' => $this->validationErrorMessage,

            'isRequired' => $this->isRequired,

            'includeInSOLR' => $this->includeInSOLR,
            'SOLRFieldName' => $this->SOLRFieldName
        ];
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
        if(is_array($field)){
            return !is_null($field['decodeField']) ? $field['decodeField'] : $field['fieldName'];
        }

        throw new Exception("GetRealFieldNameFromField can only be called on array representations of ConfigurationField");
	}
}