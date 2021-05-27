<?php
class EntitiesTestData{
    public static function getField(){
        $field =  [];
        $field['fieldName'] = 'field1';
        $field['decodeField'] = null;
        $field['hasDecode'] = null;
        $field['decodeTable'] = null;
        $field['codeAllowNewValue'] = false;
        $field['validationErrorMessage'] = 'error in input';
        $field['validationRegularExpression'] = '/^value\d{1}$/';
        $field['isRequired'] = 1;
        $field['tableName'] = 'testTable';
        $field['includeInForm'] = 1;
        $field['formName'] = "testFormName";
        $field['formFieldType'] = "string";
        $field['includeInSOLR'] = 0;
        $field['SOLRFieldName'] = "solrFieldNameNotGiven";
        return $field;
    }

    public static function getSimpleEntity(){
        $field = EntitiesTestData::getField();

        $entity = [
            'name' => 'simpleEntity',
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 1,
            'guiName' => 'testGuiName',
            'type' => 'object',
            'fieldObjects' => [],
            'fields' => [$field],
            'entities' => []
        ];
        
        return $entity;
    }

    public static function getSimpleSecondaryEntity(){
        $field = [];
        $field['fieldName'] = 'field2';
        $field['decodeField'] = null;
        $field['hasDecode'] = null;
        $field['decodeTable'] = null;
        $field['codeAllowNewValue'] = false;
        $field['formName'] = 'testFormName';
        $field['formFieldType'] = 'string';


        $entity = [
            'name' => 'simpleSecondaryEntity',
            'primaryTableName' => 'secondaryTableName',
            'isPrimaryEntity' => 0,
            'entityKeyName'=> 'parentEntityReferenceField',
            'type' => 'object',
            'fieldObjects' => [],
            'fields' => [$field],
            'guiName' => 'testGuiName',
            'entities' => []
        ];

        return $entity;    
    }

    public static function getSimpleArrayEntity(){
        $field = [];
        $field['fieldName'] = 'field1';
        $field['decodeField'] = null;
        $field['hasDecode'] = null;
        $field['decodeTable'] = null;
        $field['codeAllowNewValue'] = false;
        $field['formFieldType'] = 'string';

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

        return $entity; 
    }

    public static function getObjectEntityWithTwoFields(){
        $field1 = [];
        $field1['fieldName'] = 'field1';
        $field1['decodeField'] = null;
        $field1['hasDecode'] = null;
        $field1['decodeTable'] = null;
        $field1['codeAllowNewValue'] = false;
        $field1['includeInSOLR'] = 1;
        $field1['formFieldType'] = 'string';

        $field2 = [];
        $field2['fieldName'] = 'field2';
        $field2['decodeField'] = null;
        $field2['hasDecode'] = null;
        $field2['decodeTable'] = null;
        $field2['codeAllowNewValue'] = false;
        $field2['includeInSOLR'] = 1;
        $field2['formFieldType'] = 'string';

        $entity = [
            'name' => 'simpleObjectEntityWithTwoFields',
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 1,
            'entityKeyName'=> 'parentEntityReferenceField',
            'type' => 'array',
            'fieldObjects' => [],
            'fields' => [$field1, $field2],
            'entities' => []
        ];

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
        $decodeField['formFieldType'] = 'string';
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