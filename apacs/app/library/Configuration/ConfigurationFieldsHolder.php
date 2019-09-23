<?php

class ConfigurationFieldsHolder{
    private $fields;
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
            $fieldsArr[] = (Array)$row;
        }
        return $fieldsArr;
    }

    //Return a list of Field objects
    public function getFieldsAsObjects(){
        $fields = [];
        foreach($this->fields as $field){
            $fields[] = new ConfigurationField($field);
        }

        return $fields;
    }
}