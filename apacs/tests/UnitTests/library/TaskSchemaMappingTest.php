<?php

use \Phalcon\Di;
use \Phalcon\Mvc\Model\Manager;
use \Phalcon\Mvc\Model\MetaData\Memory;

class TaskSchemaMappingTest extends UnitTestCase {
    public static function setUpBeforeClass() : void {
        // Set config and db in DI
        $di = new Di();
        //TODO Hardcoded db credentials for tests
		$di->setShared('config', function () {
            return [
                "host" => "mysql",
                "username" => "dev",
                "password" => "123456",
                "dbname" => "apacs",
                'charset' => 'utf8',
            ];
        });
        
		$di->setShared('db', function () use ($di) {
            return new \Phalcon\Db\Adapter\Pdo\Mysql($di->get('config'));
        });
        
        // Create database entries for entities and fields        
        $testDBManager = new Mocks\TestDatabaseManager($di);
        $testDBManager->createApacsStructure();
        $testDBManager->createEntitiesAndFieldsForTask1();
        $testDBManager->createApacsMetadataForEntryPost10000Task1();
        $testDBManager->createBurialDataForEntryPost1000Task1();
    }
    
    public function setUp($di = NULL): void {
        parent::setUp($di);

        $this->getDI()->set('modelsManager', function() {
            return new Manager();
        });

        $this->getDI()->set('modelsMetadata', function() {
            return new Memory();
        });
    }

    public function test_setRequiredFields_skipNotRequired_setFieldNameAsRequired() {
        $schema = [
            'fields' => [
                0 => [ 'isRequired' => 0, 'fieldName' => 'Field1' ],
                1 => [ 'isRequired' => 1, 'fieldName' => 'Field2' ],
                2 => [ 'isRequired' => 0, 'fieldName' => 'Field3' ]
            ]
        ];

        $schema = TaskSchemaMapping::setRequiredFields($schema);

        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('Field2', $schema['required']);
        $this->assertCount(1, $schema['required']);
    }

    public function test_setRequiredFields_skipNotRequired_setDecodeFieldAsRequired() {
        $schema = [
            'fields' => [
                0 => [ 'isRequired' => 0, 'fieldName' => 'Field1' ],
                1 => [ 'isRequired' => 1, 'fieldName' => 'Field2', 'decodeField' => 'DecodeField1' ],
                2 => [ 'isRequired' => 0, 'fieldName' => 'Field3' ]
            ]
        ];

        $schema = TaskSchemaMapping::setRequiredFields($schema);

        $this->assertArrayHasKey('required', $schema);
        $this->assertContains('DecodeField1', $schema['required']);
        $this->assertCount(1, $schema['required']);
    }

    public function test_GetFieldsAsAssocArray_FieldNamesAreKeys() {
        $fields = [
            0 => [ 'fieldName' => 'Field1', 'formFieldOrder' => 1, 'includeInForm' => 1, 'otherValue' => 'value1'],
            1 => [ 'fieldName' => 'Field2', 'formFieldOrder' => 2, 'includeInForm' => 1, 'otherValue' => 'value2'],
            2 => [ 'fieldName' => 'Field3', 'formFieldOrder' => 3, 'includeInForm' => 1, 'otherValue' => 'value3'],
            3 => [ 'fieldName' => 'Field4', 'formFieldOrder' => 4, 'includeInForm' => 1, 'otherValue' => 'value4']
        ];

        $fieldsAssoc = TaskSchemaMapping::GetFieldsAsAssocArray($fields);

        $this->assertCount(4, $fieldsAssoc);
        $this->assertArrayHasKey('Field1', $fieldsAssoc);
        $this->assertArrayHasKey('Field2', $fieldsAssoc);
        $this->assertArrayHasKey('Field3', $fieldsAssoc);
        $this->assertArrayHasKey('Field4', $fieldsAssoc);
        $this->assertEquals('value1', $fieldsAssoc['Field1']['otherValue']);
        $this->assertEquals('value2', $fieldsAssoc['Field2']['otherValue']);
        $this->assertEquals('value3', $fieldsAssoc['Field3']['otherValue']);
        $this->assertEquals('value4', $fieldsAssoc['Field4']['otherValue']);
    }

    public function test_GetFieldsAsAssocArray_Skips_NotIncludeInForm() {
        $fields = [
            0 => [ 'fieldName' => 'Field1', 'formFieldOrder' => 1, 'includeInForm' => 1, 'otherValue' => 'value1'],
            1 => [ 'fieldName' => 'Field2', 'formFieldOrder' => 2, 'includeInForm' => 0, 'otherValue' => 'value2'],
            2 => [ 'fieldName' => 'Field3', 'formFieldOrder' => 3, 'includeInForm' => 0, 'otherValue' => 'value3'],
            3 => [ 'fieldName' => 'Field4', 'formFieldOrder' => 4, 'includeInForm' => 1, 'otherValue' => 'value4']
        ];

        $fieldsAssoc = TaskSchemaMapping::GetFieldsAsAssocArray($fields);

        $this->assertCount(2, $fieldsAssoc);
        $this->assertArrayHasKey('Field1', $fieldsAssoc);
        $this->assertArrayNotHasKey('Field2', $fieldsAssoc);
        $this->assertArrayNotHasKey('Field3', $fieldsAssoc);
        $this->assertArrayHasKey('Field4', $fieldsAssoc);
        $this->assertEquals('value1', $fieldsAssoc['Field1']['otherValue']);
        $this->assertEquals('value4', $fieldsAssoc['Field4']['otherValue']);
    }

    public function test_ConvertToJSONSchemaObject_simple() {
        $entity = new ConfigurationEntity([
            "name" => "NameMain",
            "isPrimaryEntity" => 1,
            "entityKeyName" => "id",
            "type" => "object",
            "required" => 1,
            "countPerEntry" => 1,
            "guiName" => "GUINameMain",
            "primaryTableName" => "table_name_main",
            "includeInSOLR" => 0,
            "viewOrder" => 1,
            "parent_id" => null,
            "fieldRelatingToParent" => null,
            "allowNewValues" => 1,
            "typeOfRelationToParent" => "connection",
            "saveOrderAccordingToParent" => "after",
            "fields" => [
                "1" => [
                    "datasources_id" => null,
                    "tableName" => "table_name_main",
                    "fieldName" => "field_1",
                    "hasDecode" => 0,
                    "decodeTable" => null,
                    "decodeField" => null,
                    "codeAllowNewValue" => 1,
                    "includeInForm" => 1,
                    "formName" => "Field 1",
                    "formFieldType" => "string",
                    "formFieldOrder" => 1,
                    "defaultValue" => null,
                    "helpText" => "Help for field 1.",
                    "placeholder" => null,
                    "isRequired" => 1,
                    "validationRegularExpression" => "VALIDATION_REGEX",
                    "validationMessage" => "VALIDATION_MESSAGE",
                    "includeInSOLR" => 0,
                    "SOLRFieldName" => "field_1",
                    "SOLRFacet" => 0,
                    "SOLRResult" => 0,
                    "name" => "field_1",
                ],
                "2" => [
                    "datasources_id" => 5,
                    "tableName" => "table_name_main",
                    "fieldName" => "field_2",
                    "hasDecode" => 1,
                    "decodeTable" => "decode_table_name",
                    "decodeField" => "field_2_decode_field",
                    "codeAllowNewValue" => 1,
                    "includeInForm" => 1,
                    "formName" => "Field 2",
                    "formFieldType" => "string",
                    "formFieldOrder" => 1,
                    "defaultValue" => null,
                    "helpText" => "Help for field 2.",
                    "placeholder" => null,
                    "isRequired" => 0,
                    "validationRegularExpression" => "VALIDATION_REGEX",
                    "validationMessage" => "VALIDATION_MESSAGE",
                    "includeInSOLR" => 0,
                    "SOLRFieldName" => "field_2",
                    "SOLRFacet" => 0,
                    "SOLRResult" => 0,
                    "name" => "field_2",
                ],
                "3" => [
                    "datasources_id" => null,
                    "tableName" => "table_name_main",
                    "fieldName" => "field_3",
                    "hasDecode" => 0,
                    "decodeTable" => null,
                    "decodeField" => null,
                    "codeAllowNewValue" => 0,
                    "includeInForm" => 0,
                    "formName" => "Field 3",
                    "formFieldType" => "string",
                    "formFieldOrder" => 3,
                    "defaultValue" => null,
                    "helpText" => "Help for field 3.",
                    "placeholder" => null,
                    "isRequired" => 0,
                    "validationRegularExpression" => "VALIDATION_REGEX",
                    "validationMessage" => "VALIDATION_MESSAGE",
                    "includeInSOLR" => 0,
                    "SOLRFieldName" => "field_3",
                    "SOLRFacet" => 0,
                    "SOLRResult" => 0,
                    "name" => "field_3",
                ],
            ]
        ]);

        $schema = TaskSchemaMapping::ConvertToJSONSchemaObject($entity);
        $this->assertEquals("GUINameMain", $schema['title'], "use guiName as title");

        $this->assertArrayHasKey("properties", $schema);
        $this->assertArrayNotHasKey("field_3", $schema['properties'], "should have been skipped");
        $this->assertCount(2, $schema['properties']);
        $this->assertArrayHasKey("field_1", $schema['properties']);
        $this->assertArrayHasKey("field_2_decode_field", $schema['properties']);

        $this->assertArrayHasKey("required", $schema);
        $this->assertCount(1, $schema['required']);
        $this->assertContains("field_1", $schema['required']);
    }


    public function test_ConvertToJSONSchemaObject_nested_entity() {
        $entity = new ConfigurationEntity([
            "name" => "NameMain",
            "isPrimaryEntity" => 1,
            "entityKeyName" => "id",
            "type" => "object",
            "required" => 1,
            "countPerEntry" => 1,
            "guiName" => "GUINameMain",
            "primaryTableName" => "table_name_main",
            "includeInSOLR" => 0,
            "viewOrder" => 1,
            "parent_id" => null,
            "fieldRelatingToParent" => null,
            "allowNewValues" => 1,
            "typeOfRelationToParent" => "connection",
            "saveOrderAccordingToParent" => "after",
            "fields" => [],
            "entities" => [
                0 => [
                    "name" => "SecondaryEntity",
                    "isPrimaryEntity" => 0,
                    "entityKeyName" => "main_id",
                    "type" => "array",
                    "required" => 0,
                    "countPerEntry" => 1,
                    "guiName" => "GUINameSecondary",
                    "primaryTableName" => "table_name_secondary",
                    "includeInSOLR" => 0,
                    "viewOrder" => 2,
                    "parent_id" => null,
                    "fieldRelatingToParent" => null,
                    "allowNewValues" => 1,
                    "typeOfRelationToParent" => "connection",
                    "saveOrderAccordingToParent" => "after",
                    "fields" => [
                        0 => [
                            "datasources_id" => null,
                            "tableName" => "table_name_secondary",
                            "fieldName" => "secondary_entity_field",
                            "hasDecode" => 0,
                            "decodeTable" => null,
                            "decodeField" => null,
                            "codeAllowNewValue" => 1,
                            "includeInForm" => 1,
                            "formName" => "SecondaryField1",
                            "formFieldType" => "string",
                            "formFieldOrder" => 1,
                            "defaultValue" => null,
                            "helpText" => "Secondary field 1 help text",
                            "placeholder" => null,
                            "isRequired" => 0,
                            "validationRegularExpression" => "/Secondary field 1 regex/",
                            "validationMessage" => "Secondary field 1 validation message",
                            "includeInSOLR" => 0,
                            "SOLRFieldName" => "secondary_field_1",
                            "SOLRFacet" => 0,
                            "SOLRResult" => 0
                        ]
                    ],
                    "entities" => [
                        0 => [
                            "name" => "TertiaryEntity",
                            "isPrimaryEntity" => 0,
                            "entityKeyName" => "secondary_id",
                            "type" => "array",
                            "required" => 0,
                            "countPerEntry" => 1,
                            "guiName" => "GUINameTertiary",
                            "primaryTableName" => "table_name_tertiary",
                            "includeInSOLR" => 0,
                            "viewOrder" => 5,
                            "parent_id" => null,
                            "fieldRelatingToParent" => null,
                            "allowNewValues" => 1,
                            "typeOfRelationToParent" => "connection",
                            "saveOrderAccordingToParent" => "after",
                            "fields" => [
                                0 => [
                                    "datasources_id" => 22,
                                    "tableName" => "resolutions_person_roles",
                                    "fieldName" => "person_role_types_id",
                                    "hasDecode" => 1,
                                    "decodeTable" => "resolutions_person_role_types",
                                    "decodeField" => "role",
                                    "codeAllowNewValue" => 1,
                                    "includeInForm" => 1,
                                    "formName" => "Rolle",
                                    "formFieldType" => "typeahead",
                                    "formFieldOrder" => 2,
                                    "defaultValue" => null,
                                    "helpText" => "Begynd at skrive for at fremsøge værdier, eller tilføje en ny værdi.",
                                    "placeholder" => null,
                                    "isRequired" => 1,
                                    "validationRegularExpression" => "/\\w{1,}/",
                                    "validationMessage" => "Du skal vælge en rolle fra listen",
                                    "includeInSOLR" => 0,
                                    "SOLRFieldName" => "sex",
                                    "SOLRFacet" => 0,
                                    "SOLRResult" => 0
                                ]
                            ],
                            "entities" => null
                        ]
                    ]
                ]
            ]
        ]);

        $schema = TaskSchemaMapping::ConvertToJSONSchemaObject($entity);
        $this->assertEquals("GUINameMain", $schema['title'], "use guiName as title");

        $this->assertArrayHasKey("properties", $schema);
        $this->assertCount(1, $schema['properties']);
        $this->assertArrayHasKey("SecondaryEntity", $schema['properties']);

        $secondarySchema = $schema['properties']['SecondaryEntity'];
        $this->assertArrayHasKey("items", $secondarySchema);
        $this->assertArrayHasKey("properties", $secondarySchema['items']);
        $this->assertCount(2, $secondarySchema['items']['properties']);
        $this->assertArrayHasKey("secondary_entity_field", $secondarySchema['items']['properties']);
        $this->assertArrayHasKey("TertiaryEntity", $secondarySchema['items']['properties']);

        $tertiarySchema = $secondarySchema['items']['properties']['TertiaryEntity'];
        $this->assertNotNull($tertiarySchema);
        $this->assertArrayHasKey("items", $tertiarySchema);
        $this->assertArrayHasKey("properties", $tertiarySchema['items']);
        $this->assertCount(1, $tertiarySchema['items']['properties']);
        $this->assertArrayHasKey("role", $tertiarySchema['items']['properties']);
    }
}

?>