<?php

class ConfigurationField{
    public $fieldName;
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