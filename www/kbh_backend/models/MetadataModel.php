<?php
/**
 * Handles loading of metadata from tables as configured
 */
class MetadataModel extends \Phalcon\Mvc\Model
{        
    /**
     * Creates a metadata search query based on the metadata level and the search input
     * @param array metadataLevel configuration
     * @param array Array of inputs
     * @return string search query
     * @throws Exception if number of required inputs does not match the ones actual given
     */
    public function createMetadataSearchQuery($metadataLevel, $searchString){
        //$pattern = "/%[-+]?(?:[ 0]|['].)?[a]?\d*(?:[.]\d*)?[%bcdeEufFgGosxX][^%]/";
        $pattern = "/%d|%s/";

        if(preg_match_all($pattern, $metadataLevel['data_sql']) != count($searchString)){
            throw new Exception('The number of arguments does not match the data_sql!');
        }
        
        $query = '';
        if($metadataLevel['type'] == 'getallbyfilter'){
            //checks if needed arguments match the supplied number
            $query = vsprintf($metadataLevel['data_sql'], $searchString);
            return $query;
        }
        else if($metadataLevel['type'] == 'typeahead'){
            $query = vsprintf($metadataLevel['data_sql'], $searchString) . ' LIMIT 10';
            return $query;
        }
    }
    
    /**
     * Gets the search parameters from the GET request and matches them with the required filters
     * @param array metadata level configuration
     * @return array search parameters
     */
    public function getMetadataSearchParameters($metadataLevel){
        if(!isset($metadataLevel['required_filters'])){
            throw new Exception('The variable required_filters is not set!');
        }
        
        $request = new Phalcon\Http\Request();
        $parameters = array();
        
        //$requiredParameters = count($metadataLevel['required_filters']);
        
        foreach($metadataLevel['required_filters'] as $filter){
            $par = $request->getQuery($filter);
            if(isset($par)){
                $parameters[$filter] = $par;
            }
        }
        
        if(count($metadataLevel['required_filters']) != count($parameters)){
            throw new Exception ('The number of parameters does not match the number of required filters!');
        }
        
        return $parameters;
    }
    
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
            return null;
        }
    }
}
