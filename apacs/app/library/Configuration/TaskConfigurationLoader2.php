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
    private function override($config) {
        $parent = $this->getConfig($config['parentTask']);
        return $this->fillConfig($parent, $config);
    }

    private function fillConfig($parent, $child)
    {
        foreach ($child as $prop => $val)
        {
            // if the parent config has the property, and it isn't steps
            if (isset($parent[$prop]) && $prop != 'steps')
            {
                // for simple values, child values are used
                if (!is_array($parent[$prop]))
                {
                    $parent[$prop] = $val;
                }
                // for lists, unique values of the concatenated lists are used
                elseif (array_keys($parent[$prop]) === range(0, count($parent[$prop])-1))
                {
                    $parent[$prop] = array_values(array_unique(array_merge($parent[$prop], $val), SORT_REGULAR));
                }
                // for objects, recursively fill configuration
                else
                {
                    $parent[$prop] = $this->fillConfig($parent[$prop], $val);
                }
            }
            // use child property if parent is missing it
            else
            {
                $parent[$prop] = $val;
            }
        }
        return $parent;
    }
}