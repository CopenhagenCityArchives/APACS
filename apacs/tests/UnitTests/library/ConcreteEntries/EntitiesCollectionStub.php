<?php
namespace Mocks;

class EntitiesCollectionStub extends \EntitiesCollection implements \IEntitiesCollection {
    
    public function __construct(Array $config){
        parent::__construct($config);

        $this->config = $config;
        $this->entitiesSet = false;
        $this->setEntities($this->config['entity']);
    }

    protected function setEntities($entity){
        if(!$this->entitiesSet){
            $this->entities = new ConfigurationEntityStub($entity);
            $this->entitiesSet = true;        
        }
    }
}