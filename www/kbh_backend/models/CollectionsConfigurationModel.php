<?php

/*
 * Handles the loading of the whole configuration object, as well as specified parts
 */
class CollectionsConfigurationModel extends \Phalcon\Mvc\Model
{      
    private $_configuration;
    private $_configurationLoaded;
    
    /**
     * Loads a configuration array
     * @param array configuration array
     */
    public function loadConfig($config = false){
        $this->_configuration = null;
        $this->_configurationLoaded = false;
        
        if(!$config)
            throw new Exception('A config input must be given!');
        
        $this->_configuration = $config;
        $this->_configurationLoaded = true;
    }
    
    /**
     * Returns the configuration for a specified collection
     * @param int collection id
     * @return array configuration array for the collection
     */
    public function getConfigurationForCollection($collectionId){        
        if(!$this->_configurationLoaded)
            return false;
        
        foreach($this->_configuration as $col){
            if($col['id'] == $collectionId){
                return $col;
            }
        }
        
        return false;
    }
    
    /**
     * Gets metadatalevels for the given id, stripped for sensitive data
     * @param int id
     * @return array metadata levels
     */
    public function getPublicMetadataLevels($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
    
        if(!$config)
            return false;
        
        foreach($config['config']['metadataLevels']['levels'] as $key => $col){
            if(isset($config['config']['metadataLevels']['levels'][$key]['data_sql']))
                $config['config']['metadataLevels']['levels'][$key]['data_sql'] = false;
        }
        
        return $config['config']['metadataLevels'];
    }
    
    /**
     * Gets metadatalevels for the given id and metadatalevel, if given
     * @param int id of the collection
     * @param string name of the metadata level, if any
     * @return array metadata levels or specific level
     */    
    public function getMetadataLevels($collectionId, $metadataLevelName = false){
        $config = $this->getConfigurationForCollection($collectionId);
    
        if(!$config)
            return false;
        
        if($metadataLevelName){
            foreach($config['config']['metadataLevels']['levels'] as $level){
                if($level['name'] == $metadataLevelName){
                    return $level;
                }
            }
            
            throw new Exception('Metadatalevel with given name not found!');
        }
        
        return $config['config']['metadataLevels'];
    }
    
    /**
     * Gets data level for the given id
     * @param int id of the collection
     * @return array data level for the collection
     */    
    public function getDataLevel($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
    
        if(!$config)
            return false;
        
        return $config['config']['dataLevel'];
    }    
    
    /**
     * Gets all possible filters for collection
     * @param int id of the collection
     * @return array all filters for the collection
     */    
    public function getAllFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config['config']['metadataLevels']['levels'] as $curLevel){
            $filters[] = $curLevel['name'];
        }
        
        return $filters;
    }      
    
    public function getRequiredFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config['config']['metadataLevels']['levels'] as $curLevel){
            if($curLevel['required']){
                $filters[] = $curLevel['name'];
            }
        }
        
        return $filters;
    }
}