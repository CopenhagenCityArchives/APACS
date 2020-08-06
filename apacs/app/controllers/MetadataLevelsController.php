<?php

class MetadataLevelsController extends \Phalcon\Mvc\Controller {
	public $configurationLocation = false;
	private $_configuration = false;

	public function getMetadataLevels($collectionId = false, $metadataLevelName = false) {
		if (!is_numeric($collectionId)) {
			throw new Exception('No collection id given');
		}

		$configuration = $this->getConfig();

		if ($metadataLevelName) {
			$this->returnJson($configuration->getMetadataLevels($collectionId, $metadataLevelName));
		} else {
			$this->returnJson($configuration->getMetadataLevels($collectionId));
		}
	}

	private function getConfig() {
		return $this->getDI()->get('collectionsConfiguration');
	}

	public function getCollectionInfoJSON($collectionId = false) {
		$collectionData = $this->getConfig()->getCollection($collectionId, true);

		$this->returnJson($collectionData);
	}

	public function displayCollectionInfo($collectionId = false) {
		if (!$collectionId) {
			return;
		}

		$obj = $this->getCollectionInfo($collectionId);

		require '../../app/templates/info_collection.php';

		die();
	}

	public function displayAllCollectionsInfo()
	{
		$startTime = microtime(true);
		$config = $this->getConfig();
		$collections = Collections::find();
		$cols = [];
		$totals = [];
		$stats = new Stats();
		$totals['displayCount'] = $stats->getCountSince('+1 day');
		$totals['pages'] = 0;
		$totals['public_pages'] = 0;
		$totals['units'] = 0;
		$totals['public_units'] = 0;
		$totals['units_without_pages'] = 0;

		foreach($collections as $col){
			//var_dump($col->id);
			$newCol = $col->toArray();
			if($col->id >= 100){
				$newCol['stats'] = $col->getStats();
			}else{
				$newCol['stats'] = null;
			}
			$newCol['api_documentation_url'] = ConfigurationLoader::getCurrentApiUrl() . 'collections/' . $col->id . '/info';
			$cols[] = $newCol;

			if(is_null($newCol['stats'])){
				continue;
			}

			$totals['pages'] += $newCol['stats']['pages'];
			$totals['public_pages'] += $newCol['stats']['public_pages'];
			$totals['units'] += $newCol['stats']['units'];
			$totals['public_units'] += $newCol['stats']['public_units'];
			$totals['units_without_pages'] += $newCol['stats']['units_without_pages'];
		}

		require '../../app/templates/info_general.php';
			//echo (int)microtime(true) - $startTime;

		die();
	}

	private function getCollectionInfo($collectionId)
	{
		$configuration = $this->getConfig();

		$obj = $configuration->getCollection($collectionId, true)[0];

		$collection = new Collections();
		$collection->id = $collectionId;

		$obj['stats'] = $collection->getStats();

		//Build an array of levels indexed by name
		$levelsByName = [];
		foreach($obj['levels'] as $level){
			$levelsByName[$level['name']] = $level;
		}

		$i = 0;
		foreach ($obj['levels'] as $level) {
			$obj['levels'][$i]['url'] = ConfigurationLoader::getCurrentApiUrl() . 'metadata/' . $obj['id'] . '/' . $level['name'];
			$obj['levels'][$i]['required_levels_url'] = '';
			if ($level['required_levels']) {
				$url = '?';

				foreach ($level['required_levels'] as $req) {
					$value = isset($levelsByName[$req]['example_value']) ? $levelsByName[$req]['example_value'] : ':' . $req . '_value';
					$url = $url . $req . '=' . urlencode($value) . '&';
				}

				$url = substr($url, 0, strlen($url) - 1);
				$obj['levels'][$i]['required_levels_url'] = $url;
			}
			$i++;
		}

		$obj['data_filters'] = $configuration->getAllFilters($collectionId);

		$i = 0;
		$url = ConfigurationLoader::getCurrentApiUrl() . 'data/' . $obj['id'] . '?';
		foreach ($obj['data_filters'] as $level) {
			$obj['data_filters'][$i] = $configuration->getMetadataLevels($collectionId, $level['name']);
			if ($obj['data_filters'][$i] && $level['name'] != 'starbas_id') {
				$value = isset($levelsByName[$level['name']]['example_value']) ? $levelsByName[$level['name']]['example_value'] : ':' . $level['name'];
				$url = $url . $level['name'] . '=' . urlencode($value) . '&';
			}

			$i++;
		}

		$url = substr($url, 0, strlen($url) - 1);

		$obj['data_url'] = $url;

		return $obj;
	}

	//Should load data from a metadata level, either by query or at once, defined by the filter
	public function getMetadata($collectionId, $metadataLevelName) {
		$metadataLevel = $configuration = $metadataModel = $sql = null;

		$configuration = $this->getConfig();

		$metadataLevel = $configuration->getMetadataLevels($collectionId, $metadataLevelName);

		$metadataModel = new Metadata();

		if ($metadataLevel['data']) {
			$this->returnJson($metadataLevel['data']);
			return;
		}

		$searchParameters = $metadataModel->getMetadataSearchParameters($metadataLevel);

		$sql = $metadataModel->createMetadataSearchQuery($metadataLevel, $searchParameters);

		$this->returnJson($metadataModel->getData($sql));
	}

	public function getObjectData($collectionId) {
		$configuration = $this->getConfig();
		$config = $configuration->getCollection($collectionId);
		$searchableFilters = $configuration->getSearchableFilters($collectionId);

		$objectsModel = new Objects();
		$incomingFilters = $objectsModel->getFilters($searchableFilters, $configuration->getRequiredFilters($collectionId));

		//Filters no set, check id of page or unit
		if (count($incomingFilters) == 0) {
			$request = $this->getDI()->get('request');
			$id = $request->getQuery("id", null, null);
			$unit_id = $request->getQuery("unit_id", null, null);

			if (!is_null($id)) {
				$newFilter = [];
				$newFilter['name'] = $config[0]['primary_table_name'] . '.id';
				$newFilter['value'] = $id;
				$incomingFilters[] = $newFilter;
			}

			//Unit id is assumed to be working only with collections > 50 (the ones from starbas API integration)
			if (!is_null($unit_id)) {
				$newFilter = [];
				//Hence the hardcoded units table name
				$newFilter['name'] = 'apacs_units.id';
				$newFilter['value'] = $unit_id;
				$incomingFilters[] = $newFilter;
			}
		}

		if (count($incomingFilters) > 0) {
			$query = $objectsModel->createObjectQuery($config[0]['objects_query'], $incomingFilters, $this->getDI()->get('db'));

			$results = $objectsModel->getData($query);
			$this->returnJson($objectsModel->convertResultToObjects($results, $configuration->getFilters($collectionId)));
			//$this->returnJson($results);
		} else {
			$this->returnError(400, 'No filters given.');
		}
	}

	public function reportError($collectionId, $itemId, $errorId) {
		$configuration = $this->getConfig();
		$errorReports = $configuration->getErrorReports($collectionId);

		$errorModel = new MetadataErrors();
		!$errorModel->setError($errorReports, $itemId, $errorId) ? $this->returnError(500, 'Could not set error') : $this->returnJson('Error set');

	}

	private function returnJson($data) {
		//Create a response instance
		$response = $this->getDI()->get('response');

		$request = new Phalcon\Http\Request();
		$callback = $request->get('callback');

		//Converts single item arrays to object
		/*  if(count($data) == 1){
	            $data = $data[0];
*/
		try {
			//Set the content of the response
			if ($callback) {
				$response->setContent($callback . '(' . json_encode($data) . ')');
			} else {
				$response->setContent(json_encode($data));
			}
		} catch (Exception $e) {
			$this->returnError(500, 'Could not load data: ' . $e);
		}
	}

	/**
	 * Returns an error
	 * @param int Error code. Defaults to 404 (not found)
	 * @param string Error message. Defaults to blank
	 */
	private function returnError($errorCode = 400, $errorMessage = '') {
		//Getting a response instance
		$response = $this->getDI()->get('response');

		//Set status code
		$response->setStatusCode($errorCode, '');

		//Set the content of the response
		$response->setContent($errorMessage);
	}
}
