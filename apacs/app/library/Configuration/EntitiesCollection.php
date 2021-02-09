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

    public function GetPrimaryEntity() {
		return $this->entities;
	}

	public function GetSecondaryEntities() {
        $entities = $this->entities->flattenTree();
        array_shift($entities);
        return $entities;
	}

    protected function setEntities($entity) {
        if(!$this->entitiesSet){
            $this->entities = new ConfigurationEntity($entity);
            $this->entitiesSet = true;        
        }
    }
}