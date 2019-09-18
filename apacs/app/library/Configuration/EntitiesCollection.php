<?php

class EntitiesCollection implements IEntitiesCollection {
    
    private $config;
    protected $entities;
    protected $entitiesSet;

    public function __construct(Array $config){
        $this->config = $config;
        $this->entitiesSet = false;
        $this->setEntities($this->config['entity']);
    }

    public function getEntities(){
        return $this->entities;
    }

    public function getEntitiesAsFlatArray($entity = null, $entArray = []){
        if(is_null($entity)){
            $entity = $this->entities;
        }
        $entArray[] = $entity;
        foreach($entity->getEntities() as $ent){
            $entArray[] = $ent;
            $this->getEntitiesAsFlatArray($ent, $entArray);
        }        

        return $entArray;
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

    public function GetPrimaryEntity(){
		return $this->entities;
	}

	public function GetSecondaryEntities(){
        $entities = $this->getEntitiesAsFlatArray();
        array_shift($entities);
        return $entities;
	}

    protected function setEntities($entity){
        if(!$this->entitiesSet){
            $this->entities = new ConfigurationEntity($entity);
            $this->entitiesSet = true;        
        }
    }
}