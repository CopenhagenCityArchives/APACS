<?php
class EntitiesDataMock{
    public static function getSimpleEntity(){
        $field = new stdClass();
        $field->fieldName = 'field1';
        $field->decodeField = false;
        $field->hasDecode = false;
        $field->decodeTable = false;
        $field->codeAllowNewValue = false;

        $entityInfo = [
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 1,
            'type' => null,
            'fieldObjects' => [],
            'fieldsList' => [$field]
        ];

        $entity = new Mocks\EntitiesMock($entityInfo);
        
        return $entity;
    }

    public static function getSimpleSecondaryEntity(){
        $field = new stdClass();
        $field->fieldName = 'field2';
        $field->decodeField = false;
        $field->hasDecode = false;
        $field->decodeTable = false;
        $field->codeAllowNewValue = false;

        $entityInfo = [
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 0,
            'entityKeyName'=> 'parentEntityReferenceField',
            'type' => 'object',
            'fieldObjects' => [],
            'fieldsList' => [$field]
        ];

        $entity = new Mocks\EntitiesMock($entityInfo);
        
        return $entity;    
    }

    public static function getSimpleArrayEntity(){
        $field = new stdClass();
        $field->fieldName = 'field1';
        $field->decodeField = false;
        $field->hasDecode = false;
        $field->decodeTable = false;
        $field->codeAllowNewValue = false;

        $entityInfo = [
            'primaryTableName' => 'primaryTableName',
            'isPrimaryEntity' => 1,
            'entityKeyName'=> 'parentEntityReferenceField',
            'type' => 'array',
            'fieldObjects' => [],
            'fieldsList' => [$field]
        ];

        $entity = new Mocks\EntitiesMock($entityInfo);
        
        return $entity; 
    }

    public static function getDecodeEntity(){
  
        $entity = self::getSimpleEntity();

        $fields = $entity->fieldsList;
        $decodeField = new stdClass();
        $decodeField->fieldName = 'field_to_decode';
        $decodeField->hasDecode = 1;
        $decodeField->decodeField = 'decodeField1';
        $decodeField->decodeTable = 'decodeTable1';
        $decodeField->codeAlowNewValue = 0;
        $fields[] = $decodeField;
        $entity->setFields($fields);

        return $entity;
    }

    public static function getDecodeEntityNewValuesAllowed(){
  
        $entity = self::getDecodeEntity();

        $fields = $entity->fieldsList;
        $fields[1]->codeAllowNewValue = 1;

        $entity->setFields($fields);

        return $entity;
    }
}