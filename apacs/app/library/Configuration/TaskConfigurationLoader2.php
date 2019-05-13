<?php

class TaskConfigurationLoader2 {
    private $configPath;
    private $loadedConfig;

    public function __construct($configPath = null){
        if(is_null($configPath)){
            $this->configPath = dirname(dirname(__DIR__)) . '/config';
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
        return $this->fillConfig($this->getConfig($config['parentTask']),$config);
    }

    private function fillConfig( $default, $specific )
    {
        foreach( $specific as $key=> $val )
        {
            if( isset( $default[$key] ) && $key != 'steps' )
            {
                if( ! is_array( $default[$key] ) )
                {
                    $default[$key] = $val;
                }
                elseif( array_keys($default[$key]) === range(0, count($default[$key]) - 1) )
                {
                    $default[$key] = array_unique( array_merge( $default[$key], $val ) );
                }
                else
                {
                    $default[$key] = $this->fillConfig( $default[$key], $val );
                }
            }
            else
            {
                // This happens when a specific key doesn't exists in default configuration.

                $default[$key] = $val;
            }
        }
        return $default;
    }
}