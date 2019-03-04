<?php

class TaskConfigurationLoader2 {
    private $configPath;
    private $loadedConfig;

    public function __construct($configPath = null){
        if(is_null($configPath)){
            $this->configPath = dirname(__DIR__,2) . '/config';
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
}