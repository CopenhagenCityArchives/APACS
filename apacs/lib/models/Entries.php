<?php
use Phalcon\Mvc\Model\Query;

class Entries extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pagesId;
	protected $tasksId;
	protected $collectionId;

    private $_metaEntities;

    public function getSource()
    {
        return 'apacs_' . 'entries';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Errors', 'entry_id');
        $this->belongsTo('page_id', 'Pages', 'id');
        $this->belongsTo('task_id', 'Tasks', 'id');
    }

    public static function SaveInSolr($data)
    {
        $config = [ 
            'endpoint' => 
                [ 'localhost' => 
                    [ 'host' => '54.194.233.62', 'hostname' => '54.194.233.62', 'port' => 80, 'login' => '', 'path' => '/solr/apacs_core'] 
                ]
            ];


        // create a client instance
        $client = new Solarium\Client($config);


        // get a select query instance
     //   $query = $client->createQuery($client::QUERY_SELECT);

       // $resultset = $client->execute($query);

        $update = $client->createUpdate();
        $doc1 = $update->createDocument(); 
        $doc1->id = rand(0,10000000); 
        $doc1->collection_id = 1;
        $doc1->page_id = 1;
        $doc1->post_id = 1;
        $doc1->task_id = 1;
        $doc1->entry_id = 1;
        $doc1->firstnames = 'testdoc-1'; 
        $doc1->lastname = "364";
        $doc1->birthdate = "1972-05-20T17:33:18Z";
        $doc1->birthplace = "København";
                
   /*     $childDoc = $update->createDocument();
        //$childDoc = [];
        $childDoc->id = rand(0,1000000);
        $childDoc->address1_s ='Jens Nielsens Allé 1, st. tv., 2100 København Ø';
        $childDoc->street = 'Jens Nielsens Allé';
        $childDoc->parent_id = $doc1->id;
        $childDoc->collection_id = 1;
        $childDoc->page_id = 1;
        $childDoc->post_id = 1;
        $childDoc->task_id = 1;
        $childDoc->entry_id = 1;*/
        $doc1->address = ['Jens Nielsens Allé 1, st. tv., 2100 København Ø', 'Rådhuspladsen 13, midtfor, 1599 København K'];
        $doc1->area = ['Storstrøms Amt', 'Københavns Amt'];
        $doc1->sogn = ['Testsogn'];
  /*
        $childDoc1 = $update->createDocument();
       // $childDoc1 = [];
        $childDoc1->id = rand(0,1000000);
        $childDoc1->address2_s ='Rådhuspladsen 13, midtfor, 1599 København K';
        $childDoc1->street = 'Rådhuspladsen';        
        $childDoc1->parent_id = $doc1->id;
        $childDoc1->collection_id = 1;
        $childDoc1->page_id = 1;
        $childDoc1->post_id = 1;
        $childDoc1->task_id = 1;
        $childDoc1->entry_id = 1;
*/
  //      $doc1->address_concat[] = $childDoc1->address;


        $doc2 = $update->createDocument(); 
        $doc2->id = rand(0,10000000); 
        $doc2->collection_id = 1;
        $doc2->page_id = 1;
        $doc2->post_id = 1;
        $doc2->task_id = 1;
        $doc2->entry_id = 1;
        $doc2->firstnames = 'testdoc-2';
        $doc2->lastname = "340";
        $doc2->birthdate = "1972-05-20T17:33:18Z";
        $doc2->birthplace = "København";
        $doc2->address = ['Hans Knudsens Plads 23, 2100 København Ø'];


        $result = $update->addDocuments([$doc1, $doc2]);
        $update->addCommit();
        $result = $client->update($update);
        //echo  $result->getStatus();

        // get a select query instance
        $query = $client->createQuery($client::QUERY_SELECT);
        // this executes the query and returns the result
        $resultset = $client->execute($query);
        // display the total number of documents found by solr
      //  echo 'NumFound: '.$resultset->getNumFound();

        echo json_encode($resultset->GetData());


    }

    public static function SaveEntryRecursively($dbCon, $entity, $values, $parentId = NULL)
    {
        $genericEntry = new GenericEntry($entity['dbTableName'], $entity['fields'], $dbCon);
        
        //Setting parent ids
        //We have several child elements
        if(array_keys($values) == range(0, count($values) - 1))
        {
            for($i = 0; $i< count($values); $i++)
            {
                $values[$i]['parent_id'] = $parentId;
            }
        }
        else{
            $values['parent_id'] = $parentId;
            $newVal = [];
            $newVal[0] = $values;
            $values = $newVal;
        }

        for($i = 0; $i < count($values); $i++)
        {
            $insertId = -1;

            //If entity is required to be unique
            //we test if it exists
            if($entity['unique'] == 1){
                $result = $genericEntry->FindByValues($values[$i]);
                if(count($result) > 0){
                    $insertId = $result[0]['id'];
                }
            }
            
            if($insertId == -1){
                if(!$genericEntry->Save($values[$i]))
                    throw new Exception("Could not save!");

                $insertId = $genericEntry->GetInsertId();
            }

            //Saving many to many relations here by adding a virtual entity and using the two ids
            //(id and parent id)
            if($entity['manyToManyTable'] !== NULL)
            {
                $middleEntity = [];
                $middleEntity['tablename'] = $entity['manyToManyTable'];

                //Creating a many to many entry, by using an artificial code table
                $middleEntity['fields'][] = [
                    'dbFieldName' => $entity['manyToManyForeignField'], 
                    'codeTable' => NULL, 
                    'codeField' => NULL, 
                    'codeAllowNewValue' => 0
                ];

                $middleEntity['fields'][] = [
                    'dbFieldName' => $entity['manyToManyParentField'],
                    'codeTable' => NULL, 
                    'codeField' => NULL, 
                    'codeAllowNewValue' => 0
                ];

                $middleData[$entity['manyToManyParentField']] = $values[$i]['parent_id'];
                $middleData[$entity['manyToManyForeignField']] = $insertId;

                $middleGE = new GenericEntry($middleEntity['tablename'], $middleEntity['fields'], $dbCon);
                
                if(!$middleGE->Save($middleData))
                {
                    throw new Exception("Could not save middle data!");
                }
            }

            //Saving children entities
            if(isset($entity['children']))
            {
                foreach($entity['children'] as $child)
                {               
                    if(isset($values[$i][$child['guiName']])){
                        Entries::SaveEntryRecursively($dbCon, $child, $values[$i][$child['guiName']], $insertId);
                    }
                }
            }
        }
    }   

    public static function ValidateJSONData($schema, $data)
    {
        var_dump($schema);
        // Validate
        $validator = new JsonSchema\Validator();
        $validator->check($data, $schema);

        $messages = [];
        if (!$validator->isValid()){
            foreach ($validator->getErrors() as $error) {
                $messages[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
        }

        return $messages;
    }

    /**
     * Validates an array of entities (POST data from users)
     * @param Array $entities An array of entities with data
     * @return bool Returns true is all fields are valid, false if not
     */
    public function ValidateEntry(Array $entities)
    {
        //Array for metaEntities. Loaded as entities of specific metaEntity type is reached
        $metaEntities = [];
        $isValid = true;

        for($j = 0; $j < count($entities); $j++)
        {
            $entity = $entities[$j];

            //Get metaEntity
            $this->loadMetaEntity($entity['entity_id']);

            $ge = new GenericEntry($this->metaEntities[$entity['entity_id']], $entity['fields'], $this->getDI());

            if(!$ge->ValidateData()){
                $isValid = false;
            }

            $entities[$j] = $entity;
        }

        return $isValid;
    }

    public function UpdateEntry()
    {

    }

    public function LoadEntitiesByPost($postId)
    {
        $entries = Entries::find(['post_id' => $postId]);

        $entriesData = [];

        foreach($entries as $entry)
        {
            $metaEntity = $this->loadMetaEntity($entry->entity_id);
            $ge = new GenericEntry($metaEntity, [], $this->getDI());

            $entriesData[] = $ge->Load($postId);
        }

        $this->response->setJsonContent($entriesData);
    }

    /*public function UpdateEntryData($id)
    {
        //This is the heavy one! What we want here is this:
        //Get all metaEntities
        //Get all metaFields
        //Get concrete field data from entities
        //Map it all together in this:
        /*
        
        {
            entity_groups:[
                {
                    name: "Hovedperson",
                    entities: [
                        {
                            id: 232,
                            fields: [
                                {
                                    id: 3525
                                    fieldname: fornavn,
                                    value: Hans,
                                    unreadable: false
                                }
                            ]
                        },
                        {
                            id: 252,
                            fields: [
                                {
                                    id: 32425,
                                    fieldname: fornavn,
                                    value: Jens,
                                    unreadable: false
                                }
                            ]
                        }
                    ]
                },
                {
                    name: "Ægtefæller",
                    entities: [
                        {
                            id: 523,
                            fields: [
                                {
                                    id: 2423,
                                    fieldname: fornavn,
                                    value: Jensine,
                                    unreadable: true
                                }
                            ]
                        }
                    ]
                }
            ]            
        }
        
        */  
}