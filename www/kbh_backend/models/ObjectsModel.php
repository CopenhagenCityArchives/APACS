<?php
/**
 * Handles loading of metadata from tables as configured
 */
class ObjectsModel extends \Phalcon\Mvc\Model
{       
    /**
     * Loads and returns the results from the database based on the sql
     * @param string the sql performing the search
     * @return array an associative array of results
     */
    public function getData($sql)
    {
        try{
            $result = $this->getDI()->getDatabase()->query($sql);
            $result->setFetchMode(Phalcon\Db::FETCH_ASSOC);

            return $result->fetchAll();
        }
        catch(Exception $e){
            die('Could not execute query: ' . $e);
        }
    }
    
    /**
     * 
     * @param array Array of possible filters as given in the configuration
     * @param array Array of required filters as given in the configuration
     * @return array on success, false if required filters is not set
     */
    public function getFilters($allFilters, $requiredFilters){
        $request = new Phalcon\Http\Request();
        $collectedFilters = array();
        $i = 0;
        
        foreach($allFilters as $filter){
            
            $incommingFilter = $request->getQuery($filter);
            
            if($incommingFilter){
                $collectedFilters[$filter] = $incommingFilter;

                if(in_array($filter, $requiredFilters)){
                    $i++;
                }   
            }         
        }
        
        if($i == count($requiredFilters)){
            return $collectedFilters;
        }/*
        else{
            throw new Exception('Not all required filters are set!');
        }*/
    }
    
    /**
     * Creates a object search query based on sql and the search input
     * @param string SQL for finding the object
     * @param array Array of inputs
     * @return string search query
     */
    public function createObjectQuery($sql, $parameters){
        $searchString = '';
        foreach($parameters as $name => $value){
            $searchString = $searchString . $name . ' = \'' . $value . '\' AND ';
        }
        
        $searchString = substr($searchString, 0, strlen($searchString)-5);

        //Replaces :query with search string
        return str_replace(':query', $searchString, $sql);
    }    
    
    /**
     * This function converts a two dimensional array of data level informations
     * and images. Those informations is often in a one-to-many relationship (many images to one object),
     * and the conversion changes the state of the result array to a multidimensional array.
     * Notice that this function could be a bottleneck in the system, as it has to traverse through
     * thousands of row per request
     * 
     * @param array Array of results from database
     * @param array Array of metadata levels
     * @return array Returns array of objects
     */
    public function convertResultToObjects($results, $metadataLevels){
        $objects = array();
        $i = 0;
        foreach($results as $curRow){
            $objects[$i]['id'] = $curRow['id'];

                
            foreach($metadataLevels as $curLevel){
                $objects[$i]['metadata'][$curLevel] = $curRow[$curLevel];
                if(isset($curRow['height']) && isset($curRow['width'])){
                    $objects[$i]['metadata']['height'] = $curRow['height'];
                    $objects[$i]['metadata']['width'] = $curRow['width'];
                }
            }
            //$objects[$i]['images'][] = 'http://' . $_SERVER['HTTP_HOST'] . $curRow['imageURL'];
            $objects[$i]['images'][] = 'http://www.kbhkilder.dk' . $curRow['imageURL'];
            $i++;
        }
        
        return $objects;
    }
}
