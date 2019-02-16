<?php

class EntitiesCollection{
    
    private $config;
    private $entities;
    private $entitiesSet;

    public function __construct(Array $config){
        $this->config = $config;
        $this->entitiesSet = false;
        $this->setEntities();
    }

    public function getEntities(){
        return $this->entities;
    }

    public function getEntityByName($name, $entity = null){
        
        if($entity == null){
            $entity = $this->entities;
        }

        if($entity->name == $name){
            return $entity;
        }

        foreach($entity->getEntities() as $ent){
            $result = $this->getEntityByName($name, $ent);
            if(!is_null($result)){
                return $result;
            }
        }

        return null;
    }

    private function setEntities(){
        if(!$this->entitiesSet){
            $this->entities = new ConfigurationEntity($this->config['entity']);
            $this->entitiesSet = true;        
        }
    }
}