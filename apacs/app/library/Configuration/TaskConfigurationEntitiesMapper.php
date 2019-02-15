<?php

class TaskConfigurationEntitiesMapper{
    private $config;
    public function __construct(Array $config){
        $this->config = $config;
    }

    public function getEntities(){
        $entities = [];
        foreach($this->config as $row){
            $entities[] = new ConfigurationEntity($row);
        }

        return $entities;
    }
}