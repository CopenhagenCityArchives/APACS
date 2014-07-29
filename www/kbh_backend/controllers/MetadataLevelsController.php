<?php

class MetadataLevelsController extends \Phalcon\Mvc\Controller
{  
    public $configurationLocation = false;
    private $_configuration = false;
    
    public function getMetadataLevels($collectionId = false, $metadataLevelName = false)
    {
        if(!is_numeric($collectionId)){
            $this->returnError(404, "Wrong request");
            return;
        }
        
        $configuration = $this->initConfiguration();
        
        if($metadataLevelName){
            $this->returnJson($configuration->getMetadataLevels($collectionId, $metadataLevelName));
        }
        else{
            $this->returnJson($configuration->getMetadataLevels($collectionId)); 
        }
    }
    
    
    private function initConfiguration(){
        if(!$this->configurationLocation)
            throw new Exception ('No configuration location given!');
        
        if(!$this->_configuration){
            try{
                $configuration = new CollectionsConfigurationModel();
                $configuration->loadConfig(require($this->configurationLocation));  
                $this->_configuration = $configuration;
            } catch (Exception $ex) {
                throw new Exception('Could not load configuration!');
                //$this->returnError(404, 'Could not load data');
            }

            return $configuration;
        }
        else{
            return $this->_configuration;
        }
    }
    
    public function getCollectionInfo($collectionId)
    {
        $configuration = $this->initConfiguration();
        
        $collectionData = $configuration->getConfigurationForCollection($collectionId);
        
        $this->returnJson($collectionData['info']);
    }
 
    //Should load data from a metadata level, either by query or at once, defined by the filter
    public function getMetadata($collectionId, $metadataLevelName){
        $metadataLevel = $configuration = $metadataModel = $sql = null;
        
        $configuration = $this->initConfiguration();
        
        $metadataLevel = $configuration->getMetadataLevels($collectionId, $metadataLevelName);
        
        $metadataModel = new MetadataModel();
        $sql = $metadataModel->createMetadataSearchQuery(
                $metadataLevel, 
                $metadataModel->getMetadataSearchParameters($metadataLevel)
        );
echo $sql;
        $this->returnJson($metadataModel->getData($sql));
    }
    
    public function getObjectData($collectionId){
        $configuration = $this->initConfiguration();
        $dataLevel = $configuration->getDataLevel($collectionId);
        $allFilters = $configuration->getAllFilters($collectionId);
                
        $objectsModel = new ObjectsModel();
        $incomingFilters = $objectsModel->getFilters($allFilters, $configuration->getRequiredFilters($collectionId));
        
        if($incomingFilters){
            $results = $objectsModel->getData($objectsModel->createObjectQuery($dataLevel['data_sql'], $incomingFilters));
            $this->returnJson($objectsModel->convertResultToObjects($results, $allFilters));
        }
    }
    
    private function returnJson($data){
        //Create a response instance
        $response = new \Phalcon\Http\Response();
        
        $request = new Phalcon\Http\Request();
        $callback = $request->get('callback');
        
        //Set the content of the response
        if($callback){
            $response->setContent($callback . '(' . json_encode($data) . ')');
        }
        else{
            $response->setContent(json_encode($data));    
        }

        //Return the response
        $response->send();     
    }
    
    /**
     * Returns an error
     * @param int Error code. Defaults to 404 (not found)
     * @param string Error message. Defaults to blank
     */
    private function returnError($errorCode = 404, $errorMessage = ''){
        //Getting a response instance
        $response = new \Phalcon\Http\Response();

        //Set status code
        $response->setStatusCode($errorCode, '');

        //Set the content of the response
        $response->setContent($errorMessage);

        //Send response to the client
        $response->send();        
    }
}