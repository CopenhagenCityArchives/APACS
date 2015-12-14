<?php

class Entries extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pagesId;
	protected $tasksId;
	protected $collectionId;

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

    /**
     * Validates an array of entities (POST data from users)
     * @param Array $entities An array of entities with data
     * @return bool Returns true is all fields are valid, false if not
     */
    public function ValidateEntities(Array $entities)
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

    public function SaveEntities($collectionId, $taskId, $pageId, $postId, $userId, $inputEntities, $validate = true)
    {

        //TODO: Validate if $validate == true
        
        $entry = new Entries();
        $entry->save(['user_id' => $collectionId, 'task_id' => $taskId, 'collection_id' => $collectionId, 'page_id' => $pageId, 'post_id' => $postId]);

        for($j = 0; $j < count($inputEntities); $j++)
        {
            $input = $inputEntities[$j];

            //Loading metaEntity, if it is not loaded already
            $metaEntity = $this->loadMetaEntity($input['entity_id']);

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
            $this->_metaEntities[$id] = Entities::findFirst($id);
            $this->_metaEntities[$id]['fields'] = $this->manager->query('SELECT f.* FROM Fields f LEFT JOIN EntitiesFields ef ON f.id = ef.field_id WHERE ef.entity_id = ' . $entity['entity_id'])->execute()->toArray();
        }
    }    
}