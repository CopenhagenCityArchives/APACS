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
     * Appends configuration to existing configuration
     * @params array configuration to append
     */
    public function removeTestData(){
        $i = 0;
        foreach($this->_configuration as $conf){
            if($conf['test'] == true){
                unset($this->_configuration[$i]);
            }
            $i++;
        }
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
                    /*$info = array();
                    $info = $col['info'];
                    $info['id'] = $col['id'];
                    $collectionInfo[] = $info;*/
                    unset($col['data_sql']);
                    $collectionInfo[] = $col;
                }
                else{
                    $collectionInfo[] = $col;
                }
            }
        }
        
        if(count($collectionInfo) == 0){
            throw new Exception('Collection empty!');
        }
        
        $i = 0;
        foreach($collectionInfo as $col){
            $collectionInfo[$i] = $this->setDefaults($collectionInfo[$i]);
            $i++;
        }
        
        return $collectionInfo;
    }
    
    /**
     * Set defaults in configuration arrays.
     * TODO: Not implemented. Suggests a new structure of the over complicated configuration structure
     * 
     * @param Array A collection configuration
     */
    private function setDefaults($collectionConfig){
        $DefaultInfo= array(
            //Id of the collection. Main entrance for API requests
            'id' => -1,
            //Is the collection in test or public?
            'test' => true,
            //Description of the collection
            'description' => false,
            //Image type (image or tile)
            'image_type' => 'image',
            //Link for further information about the collection
            'link' => false,
            //Short name of collection
            'short_name' => false,
            //Long name of collection
            'long_name' => false,
            //Name of the collection
            'primary_table_name' => 'name',
            //Type of levels. Can be flat or hierarkic
            'levels_type' => false,
            //Query for loading objects. Should at least include the field "image"
            'data_sql' => false,
            //Textual description of the required fields needed for object search
            'gui_required_fields_text' => false,            
            //An array of levels of metadata
            'levels' => array(),
            //Text used to introduce the error reporting
            'error_intro' => '',
            //Text presented to the user when an error report is submitted
            'error_confirm' => '',
            //An array of possible error reports
            'error_reports' => array()
        );
        
        $DefaultMetadataLevel = array(
            //Ordering of levels in hierarkic metadata structures. Also used in GUI for form field ordering
            'order' => -1,
            //Name in GUI
            'gui_name' => false,
            //Description in GUI
            'gui_description' => false,
            //Link to further information, GUI
            'gui_info_link' => false,
            //Internal name, also used in requests
            'name' => false,
            //GUI type, preset, getallbyfilter, typehead
            'gui_type' => false,
            //Query for receiving data for this field (for example adresses). Digits written as %d, strings as %
            //(Example: SELECT id, name WHERE id = %d AND name LIKE %s)
            'data_sql' => false,
            //Data for the field. Required if no data_sql is given. Format: array(id, text)
            'data' => false,
            //Wheter or not the data should be visible in the metadata info when displaying images
            'gui_hide' => true,
            //Is this a required field when searching objects?
            'required' => false,
            //Is this a searchable field when searching objects?
            'searchable' => true,
            //Other levels required to get data from this level
            'required_levels' => array()
        );
        
        $collectionConfig = array_merge($DefaultInfo, $collectionConfig);
        
        $i = 0;
        foreach($collectionConfig['levels'] as $metadataLevel){
            $collectionConfig['levels'][$i] = array_merge($DefaultMetadataLevel, $metadataLevel);
            
            //Logic validation
            
            //Either data or data_sql has to be filled out
            if(!$collectionConfig['levels'][$i]['data'] && !$collectionConfig['levels'][$i]['data_sql']){
                throw new Exception('Invalid configuration format. Either data or data_sql should be set.');
            }
            
            //If gui_type is preset, the data field has to by filled
            if($collectionConfig['levels'][$i]['gui_type'] == 'preset' && count($collectionConfig['levels'][$i]['data']) == 0){
                throw new Exception('Invalid configuration format. GUI type \'preset\' requires data to have content.');
            }
            $i++;
        }
        
        $DefaultErrorConfig = array(
            'id' => -1,
            'name' => '',
            'sql' => false,
            'order' => -1,
        );
        
        $i = 0;
        foreach($collectionConfig['error_reports'] as $errorReport){
            $collectionConfig['error_reports'][$i] = array_merge($DefaultErrorConfig, $errorReport);
            $i++;
        }
        
        return $collectionConfig;
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
            foreach($config[0]['levels'] as $level){
                if($level['name'] == $metadataLevelName){
                    return $level;
                }
            }
            
            throw new Exception('Metadatalevel with given name not found!');
        }
        
        return $config[0]['levels'];
    }
    
    /**
     * Returns an array with possible error reports for a given collection
     * @param int Id of the collection
     * @return Array Array holding the error reports for the collection
     */
    public function getErrorReports($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        return $config[0]['error_reports'];
    }
    
    /**
     * Gets data level for the given id
     * @param int id of the collection
     * @return array data level for the collection
     */    /*
    public function getDataLevel($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        
        return $config[0]['dataLevel'];
    }    */
    
    /**
     * Gets all possible filters for collection
     * @param int id of the collection
     * @return array all filters for the collection
     */    
    public function getAllFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config[0]['levels'] as $curLevel){
                $filters[] = $curLevel['name'];
        }
        
        return $filters;
    }      
    
    public function getSearchableFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config[0]['levels'] as $curLevel){
            if($curLevel['searchable'] == true){
                $filters[] = $curLevel['name'];
            }
        }
        
        return $filters;
    }        
    
    public function getRequiredFilters($collectionId){
        $config = $this->getConfigurationForCollection($collectionId);
        $filters = array();
        
        foreach($config[0]['levels'] as $curLevel){
            if($curLevel['required']){
                $filters[] = $curLevel['name'];
            }
        }
        
        return $filters;
    }
}