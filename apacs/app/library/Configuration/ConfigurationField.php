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
        $this->tableName = $field['tableName'];
        
        $this->decodeField = $field['decodeField'];
        $this->decodeTable = $field['decodeTable'];
        $this->hasDecode = $field['hasDecode'];
        $this->codeAllowNewValue = $field['codeAllowNewValue'];

        $this->includeInForm = isset($field['includeInForm']) ? $field['includeInForm'] : 1;
        $this->formName = $field['formName'];
        $this->formFieldType = $field['formFieldType'];

        $this->validationRegularExpression = $field['validationRegularExpression'];
        $this->validationErrorMessage = $field['validationErrorMessage'];

        $this->isRequired = $field['isRequired'];

        $this->includeInSOLR = isset($field['includeInSOLR']) ? $field['includeInSOLR'] : 0;
        $this->SOLRFieldName = isset($field['SOLRFieldName']) ? $field['SOLRFieldName'] : "solrFieldNameNotGiven";
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