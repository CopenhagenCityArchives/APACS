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
    public function getConfigurationForCollection($collectionId, $publicData = false){        
        if(!$this->_configurationLoaded)
            return false;
        
        $matchAll = !is_numeric($collectionId);
        
        $collectionInfo = array();
        
        foreach($this->_configuration as $col){
            if($col['id'] == $collectionId || $matchAll){
                if($publicData){
                    $info = array();
                    $info = $col['info'];
                    $info['id'] = $col['id'];
                    $collectionInfo[] = $info;
                }
                else{
                    $collectionInfo[] = $col;
                }
            }
        }
        
        if(count($collectionInfo) == 0){
            throw new Exception('Collection empty!');
        }
        
        return $collectionInfo;
    }
    
    /**
     * Gets metadatalevels for the given id and metadatalevel, if given
     * @param int id of the collection
     * @param string name of the metadata level, if any
     * @return array metadata levels or specific level
     */    
    public function getMetadataLevels($collectionId, $metadataLevelName = false){
        $config = $this->getConfigurationForCollection($collectionId);
        
        if($metadataLevelName){
            foreach($config[0]['config']['metadataLevels']['levels'] as $level){
                if($level['name'] == $metadataLevelName){
                    return $level;
                }
            }
            
            throw new Exception('Metadatalevel with given name not found!');
        }
        
        return $config[0]['config']['metadataLevels'];
    }
    
    /**
     * Gets data level for the given id
     * @param int id of the collection
     * @return array data level for the collection
     */    
    public function getDataLevel($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        
        return $config[0]['config']['dataLevel'];
    }    
    
    /**
     * Gets all possible filters for collection
     * @param int id of the collection
     * @return array all filters for the collection
     */    
    public function getAllFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config[0]['config']['metadataLevels']['levels'] as $curLevel){
            $filters[] = $curLevel['name'];
        }
        
        return $filters;
    }      
    
    public function getRequiredFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config[0]['config']['metadataLevels']['levels'] as $curLevel){
            if($curLevel['required']){
                $filters[] = $curLevel['name'];
            }
        }
        
        return $filters;
    }
}