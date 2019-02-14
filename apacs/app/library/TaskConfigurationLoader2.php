<?php

class TaskConfigurationLoader2 {
    private $configPath;
    private $loadedConfig;

    public function __construct($configPath = null){
        if(is_null($configPath)){
            $this->configPath = dirname(__DIR__) . '/config';
        }
        else{
            $this->configPath = $configPath;
        }
    }

    //Returns a task config array
    public function getConfig($id){
        if(!is_numeric($id)){
            throw new Exception("given id is not an integer value");
        }
        
        $configFile = $this->configPath . '/task_' . $id . '.json' ;

        if(!file_exists($configFile)){
            throw new Exception("config file not found! Looked here: " . $configFile);
        }

        $config = json_decode(file_get_contents($configFile), true);
        
        
        if(is_null($config)){
            throw new Exception("could not decode config from JSON: " . json_last_error());
        }

        //Return config if no parent task
        if(is_null($config['parentTask'])){
            $this->loadedConfig = $config;
            return $config;
        }

        //Return config overridden by parent task
        return $this->override($config);
    }

    //Returns a recursive merged array of config and parent config
    private function override($config){
        return array_replace_recursive($this->getConfig($config['parentTask']),$config);
    }

    public function getEntities(){
        if(is_null($this->loadedConfig)){
            throw new Exception("config not loaded. Cannot get entities");
        }

        return $this->getConfigEntitiesAsArray($this->loadedConfig['schema']['properties'][$this->loadedConfig['keyName']]);
    }

    private function getEntityFromProperty($property){
        //We only want entities
        if(!isset($property['entityKeyName'])){
            return null;
        }

        if($property['type'] == 'object'){
            return $property['properties'];
        }

        if($property['type'] == 'array'){
            return $property['items'];
        }
    }

    //If row is entity, return row and child entities
    private function getConfigEntitiesAsArray($property){
        
        $entity = [];

        //Add all properties as fields
        $entity['fields'] = [];
        $entity['entities'] = [];
        $entity = $property;
        $entity['entities'] = $this->getEntity($property);
        unset($entity['properties']);
        unset($entity['items']);
        var_dump($entity['fields']);
        foreach($property as $key => $subProperty){
            // Sub prop is entity. Add as entity
            if($key == 'properties' || $key == 'items'){
                //$subEntities = $this->getEntityFromProperty($property);
                $entity['entities'][] = $this->getConfigEntitiesAsArray($subProperty);
            }
            // Sub prop is field. Add as field.
            else{
                $entity['fields'][$key] = $subProperty;
            }
        }
       

      //  var_dump($entity['fields']);die();
        
        return $entity;
    }
    
    private function getEntity($property){
        $entity = [];
        if(isset($property['properties'])){
            foreach($property['properties'] as $key => $value){
                $value = $this->getEntity($value);
                if(!is_null($value)){
                    $entity['entities'][] = $value;
                }

                if(!is_null($value)){
                    $entity['fields'][] = $value;
                }
            }
        }

        if(count($entity)==0){
            return null;
        }

        return $entity;
    }
}