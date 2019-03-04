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
    
    public function __construct(Array $field){

        $this->fieldName = $field['fieldName'];
        $this->tableName = $field['tableName'];
        
        $this->decodeField = $field['decodeField'];
        $this->decodeTable = $field['decodeTable'];
        $this->hasDecode = $field['hasDecode'];
        $this->codeAllowNewValue = $field['codeAllowNewValue'];

        $this->formName = $field['formName'];
        $this->formFieldType = $field['formFieldType'];

        $this->validationRegularExpression = $field['validationRegularExpression'];
        $this->validationErrorMessage = $field['validationErrorMessage'];

        $this->isRequired = $field['isRequired'];

        $this->includeInForm = isset($field['includeInForm']) ? $field['includeInForm'] : 1;
    }

    public function toArray(){
        return [
            'fieldName' => $this->fieldName,
            'tableName' => $this->tableName,
            'decodeField' => $this->decodeField,
            'decodeTable' => $this->decodeTable,
            'hasDecode' => $this->hasDecode,
            'codeAllowNewValue' => $this->codeAllowNewValue,

            'formName' => $this->formName,
            'formFieldType' => $this->formFieldType,

            'validationRegularExpression' => $this->validationRegularExpression,
            'validationErrorMessage' => $this->validationErrorMessage,

            'isRequired' => $this->isRequred
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
}