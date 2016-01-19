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

    public function SaveEntry($collectionId, $taskId, $pageId, $postId, $userId, $inputEntities)
    {
        $entry = new Entries();
        $entry->save(['user_id' => $collectionId, 'task_id' => $taskId, 'collection_id' => $collectionId, 'page_id' => $pageId, 'post_id' => $postId]);

        if(!is_array($inputEntities))
            throw new Exception("Entries: Entities not given");

        for($j = 0; $j < count($inputEntities); $j++)
        {
            $input = $inputEntities[$j];

            //Loading metaEntity, if it is not loaded already
            $metaEntity = $this->loadMetaEntity($input['entity_id']);
            
            if($metaEntity == false)
                throw new Exception("entity not found");

            $ge = new GenericEntry($metaEntity, $input, $this->getDI());
            
            if(!$ge->Save($input)){
                throw new Exception("Could not save entry.");
            }
        }        
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
      
    private function loadMetaEntity($id)
    {
        //Loading metaEntity, if it is not loaded already
        if(!isset($this->_metaEntities[$id])){
            $this->_metaEntities[$id] = Entities::findFirst($id)->toArray();

            $query = new Query('SELECT f.* FROM Fields f LEFT JOIN EntitiesFields ef ON f.id = ef.field_id WHERE ef.entity_id = ' . $id, $this->getDI());
            $this->_metaEntities[$id]['fields'] = $query->execute()->toArray();
        }
    }    
}