<?php

class MetadataLevelsController extends \Phalcon\Mvc\Controller
{  
    public $configurationLocation = false;
    private $_configuration = false;
    
    public function getMetadataLevels($collectionId = false, $metadataLevelName = false)
    {
        if(!is_numeric($collectionId)){
            throw new Exception ('No collection id given');
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
            throw new Exception ('No configuration location given');
        
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
    
    public function getCollectionInfo($collectionId = false)
    {       
        $configuration = $this->initConfiguration();
        
        $collectionData = $configuration->getConfigurationForCollection($collectionId, true);
        
        $this->returnJson($collectionData);
    }
 
    //Should load data from a metadata level, either by query or at once, defined by the filter
    public function getMetadata($collectionId, $metadataLevelName){
        $metadataLevel = $configuration = $metadataModel = $sql = null;
        
        $configuration = $this->initConfiguration();
        
        $metadataLevel = $configuration->getMetadataLevels($collectionId, $metadataLevelName);
        
        $metadataModel = new MetadataModel();
        
        if($metadataLevel['data']){
            $this->returnJson($metadataLevel['data']);
            return;
        }
        
        $searchParameters = $metadataModel->getMetadataSearchParameters($metadataLevel);
        
        $sql = $metadataModel->createMetadataSearchQuery($metadataLevel, $searchParameters);

        $this->returnJson($metadataModel->getData($sql));
    }
    
    public function getObjectData($collectionId){
        $configuration = $this->initConfiguration();
        $config = $configuration->getConfigurationForCollection($collectionId);
        $searchableFilters = $configuration->getSearchableFilters($collectionId);
                
        $objectsModel = new ObjectsModel();
        $incomingFilters = $objectsModel->getFilters($searchableFilters, $configuration->getRequiredFilters($collectionId));
        
        if(!$incomingFilters){
            $incomingFilters = $objectsModel->getFilters(['id'], ['id']);
            if(isset($incomingFilters['id'])){
                $incomingFilters[$config[0]['primary_table_name'] .'.id'] = $incomingFilters['id'];
                unset($incomingFilters['id']);
            }
        }
        
        if(count($incomingFilters) > 0){
            $query = $objectsModel->createObjectQuery($config[0]['data_sql'], $incomingFilters);
            $results = $objectsModel->getData($query);
            $this->returnJson($objectsModel->convertResultToObjects($results, $configuration->getAllFilters($collectionId)));
            //$this->returnJson($results);
        }
        else{
            $this->returnError(404, 'No filters given');
        }
    }
    
    public function reportError($collectionId, $itemId, $errorId){
        $configuration = $this->initConfiguration();
        $errorReports = $configuration->getErrorReports($collectionId);
        
        $errorModel = new ErrorReportsModel();
        !$errorModel->setError($errorReports, $itemId, $errorId) ? $this->returnError(500, 'Could not set error') : $this->returnJson('Error set');
        
        
    }
    
    private function returnJson($data){
        //Create a response instance
        $response = new \Phalcon\Http\Response();
        
        $request = new Phalcon\Http\Request();
        $callback = $request->get('callback');
        
        //Converts single item arrays to object
      /*  if(count($data) == 1){
            $data = $data[0];
        }*/
        try{
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
        catch(Exception $e){
             $this->returnError(404, 'Could not load data: ' . $e);
        }
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