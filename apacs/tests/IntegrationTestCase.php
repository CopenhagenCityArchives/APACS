<?php
use \Phalcon\Di;
class IntegrationTestCase extends \UnitTestCase
{
    protected $testDBManager;

    public static function setUpBeforeClass() : void {       
        // Create database entries for entities and fields        
        $testDBManager = new Mocks\TestDatabaseManager(UnitTestCase::createDI());
        $testDBManager->createApacsStructure();
        $testDBManager->createEntitiesAndFieldsForTask1();
        $testDBManager->createApacsMetadataForEntryPost10000Task1();
        $testDBManager->createBurialDataForEntryPost1000Task1();
        $testDBManager->createEventEntries();
    }
       
    public static function tearDownAfterClass() : void {
        // Clear database
        $testDBManager = new Mocks\TestDatabaseManager(UnitTestCase::createDI());
        $testDBManager->cleanUpApacsStructure();
        $testDBManager->cleanUpBurialStructure();
    }

    public function setUp($di = null) : void
    {
        parent::setUp();
    }

    public function tearDown() : void {
        parent::tearDown();
    }
}
