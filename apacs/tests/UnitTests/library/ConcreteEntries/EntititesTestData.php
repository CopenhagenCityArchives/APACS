<?php
class EntitiesTestData{
    public static function getSimpleEntity(){
        $field =  [];
        $field['fieldName'] = 'field1';
        $field['decodeField'] = null;
        $field['hasDecode'] = null;
        $field['decodeTable'] = null;
        $field['codeAllowNewValue'] = false;
        $field['validationErrorMessage'] = 'error in input';
        $field['validationRegularExpression'] = '/^\d{1}$/';
        $field['isRequired'] = 1;

        $entity = [
            'name' => 'simpleEntity',
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 1,
            'type' => 'object',
            'fieldObjects' => [],
            'fields' => [$field],
            'entities' => []
        ];

        //$entity = new Mocks\ConfigurationEntityStub($entityInfo);
        
        return $entity;
    }

    public static function getSimpleSecondaryEntity(){
        $field = [];
        $field['fieldName'] = 'field2';
        $field['decodeField'] = null;
        $field['hasDecode'] = null;
        $field['decodeTable'] = null;
        $field['codeAllowNewValue'] = false;

        $entity = [
            'name' => 'simpleSecondaryEntity',
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 0,
            'entityKeyName'=> 'parentEntityReferenceField',
            'type' => 'object',
            'fieldObjects' => [],
            'fields' => [$field],
            'entities' => []
        ];

        //$entity = new Mocks\ConfigurationEntityStub($entityInfo);
        
        return $entity;    
    }

    public static function getSimpleArrayEntity(){
        $field = [];
        $field['fieldName'] = 'field1';
        $field['decodeField'] = null;
        $field['hasDecode'] = null;
        $field['decodeTable'] = null;
        $field['codeAllowNewValue'] = false;

        $entity = [
            'name' => 'simpleArrayEntity',
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 1,
            'entityKeyName'=> 'parentEntityReferenceField',
            'type' => 'array',
            'fieldObjects' => [],
            'fields' => [$field],
            'entities' => []
        ];

        //$entity = new Mocks\ConfigurationEntityStub($entityInfo);
        
        return $entity; 
    }

    public static function getDecodeEntity(){
  
        $entity = self::getSimpleEntity();
        $entity['name'] = 'decodeEntity';

        $fields = $entity['fields'];
        $decodeField = [];
        $decodeField['fieldName'] = 'field_to_decode';
        $decodeField['hasDecode'] = 1;
        $decodeField['decodeField'] = 'decodeField1';
        $decodeField['decodeTable'] = 'decodeTable1';
        $decodeField['codeAlowNewValue'] = 0;
        $fields[] = $decodeField;
        $entity['fields'] = $fields;

        return $entity;
    }

    public static function getDecodeEntityNewValuesAllowed(){
  
        $entity = self::getDecodeEntity();
        $entity['name'] = 'decodeEntityNewValuesAllowed';

        $fields = $entity['fields'];
        $fields[1]['codeAllowNewValue'] = 1;

        $entity['fields'] = $fields;

        return $entity;
    }
}