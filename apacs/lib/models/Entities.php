<?php

use Phalcon\Mvc\Model\Query;

class Entities extends \Phalcon\Mvc\Model
{
    public static $publicFields = ['id', 'required', 'countPerEntry', 'isMarkable','guiName', 'task_id'];

    public function getSource()
    {
        return 'apacs_' . 'entities';
    }

    public function initialize()
    {
  	 	$this->hasMany('id', 'EntitiesFields', 'entity_id');
    	$this->belongsTo('task_id', 'Task', 'id');
    }

    public static function LoadEntitiesHierarchy($taskId)
    {
        $parentEntities = Entities::find(['conditions' => 'task_id = ' . $taskId . ' AND parent_id IS NULL']);

        //Loading entities with children
        $entities = [];
        $i = 0;
        foreach($parentEntities as $entity)
        {
            $entities[$i] = Entities::GetEntityRecursively($entity);
            $i++;
        }

        return $entities;
    }

    private static function GetEntityRecursively($ingoingEntity)
    {
        $entity = $ingoingEntity->toArray();

        //Loading fields
        $query = new Query('SELECT f.* FROM EntitiesFields as ef LEFT JOIN Fields as f ON ef.field_id = f.id WHERE ef.entity_id = ' . $ingoingEntity->id, \Phalcon\DI\FactoryDefault::getDefault());
        $fields  = $query->execute()->toArray();
        
        //Adding the already known table name manually
        for($i = 0; $i < count($fields); $i++)
        {
            $fields[$i]['dbTableName'] = $ingoingEntity->dbTableName;
        }
       
        $entity['fields'] = $fields;

        foreach(Entities::find(['conditions' => 'parent_id = ' . $ingoingEntity->id]) as $child){
            $c = Entities::GetEntityRecursively($child);
            if($c != NULL){
                $entity['children'][] = $c;
            }
        }
        return $entity;
    }
}