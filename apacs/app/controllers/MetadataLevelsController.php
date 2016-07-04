<?php

class MetadataLevelsController extends \MainController {
	public function getMetadataLevels($collectionId = false, $metadataLevelName = false) {
		if (!is_numeric($collectionId)) {
			throw new Exception('No collection id given');
		}

		if ($metadataLevelName) {
			$this->returnJson($this->getConfig()->getMetadataLevels($collectionId, $metadataLevelName));
		} else {
			$this->returnJson($this->getConfig()->getMetadataLevels($collectionId));
		}
	}

	private function getConfig() {
		return $this->getDI()->get('configuration');
	}

	public function getCollectionInfo($collectionId = false) {
		$collectionData = $this->getConfig()->getCollection($collectionId, true);

		$this->returnJson($collectionData);
	}

	public function displayInfo($collectionId = false) {
		if ($collectionId) {
			$obj = $this->getConfig()->getCollection($collectionId, true)[0];

			$i = 0;
			foreach ($obj['levels'] as $level) {
				$obj['levels'][$i]['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/api/metadata/' . $obj['id'] . '/' . $level['name'];
				$obj['levels'][$i]['required_levels_url'] = '';
				if ($level['required_levels']) {
					$url = '?';

					foreach ($level['required_levels'] as $req) {
						$url = $url . $req . '=:' . $req . '&';
					}
					$url = substr($url, 0, strlen($url) - 1);
					$obj['levels'][$i]['required_levels_url'] = $url;
				}
				$i++;
			}

			$obj['data_filters'] = $this->getConfig()->getAllFilters($collectionId);

			$i = 0;
			$url = 'http://' . $_SERVER['HTTP_HOST'] . '/api/data/' . $obj['id'] . '?';
			foreach ($obj['data_filters'] as $level) {
				$obj['data_filters'][$i] = $this->getConfig()->getMetadataLevels($collectionId, $level['name']);
				if ($obj['data_filters'][$i]['required']) {
					$url = $url . $level['name'] . '=:' . $level['name'] . '&';
				}

				$i++;
			}

			$url = substr($url, 0, strlen($url) - 1);

			$obj['data_url'] = $url;

			require '../../app/templates/info.php';

			die();
		}
	}

	public function displayAllInfo() {
		require '../../app/templates/info.php';
	}

	//Should load data from a metadata level, either by query or at once, defined by the filter
	public function getMetadata($collectionId, $metadataLevelName) {
		$metadataLevel = $metadataModel = $sql = null;

		$metadataLevel = $this->getConfig()->getMetadataLevels($collectionId, $metadataLevelName);

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
		$config = $this->getConfig()->getCollection($collectionId);
		$searchableFilters = $this->getConfig()->getSearchableFilters($collectionId);

		$objectsModel = new Objects();
		$incomingFilters = $objectsModel->getFilters($searchableFilters, $this->getConfig()->getRequiredFilters($collectionId));

		//Filters no set, id filter assumed
		if (count($incomingFilters) == 0) {
			$incomingFilters = $objectsModel->getFilters(array(array('name' => 'id')), array(array('name' => 'id')));
			if (count($incomingFilters) > 0 && $incomingFilters[0]['name'] == 'id') {
				$newFilter = array();
				$newFilter['name'] = $config[0]['primary_table_name'] . '.id';
				$newFilter['value'] = $incomingFilters[0]['value'];

				//$incomingFilters[][$config[0]['primary_table_name'] .'.id'] = $incomingFilters[0]['value'];
				unset($incomingFilters[0]);
				$incomingFilters[] = $newFilter;
			} else {
				$this->returnError(400, 'No filters given');
			}
		}

		if (count($incomingFilters) > 0) {
			$query = $objectsModel->createObjectQuery($config[0]['objects_query'], $incomingFilters);
			$results = $objectsModel->getData($query);
			$this->returnJson($objectsModel->convertResultToObjects($results, $this->getConfig()->getFilters($collectionId)));
			//$this->returnJson($results);
		} else {
			$this->returnError(400, 'No filters given');
		}
	}

	public function reportError($collectionId, $itemId, $errorId) {
		$errorReports = $this->getConfig()->getErrorReports($collectionId);

		$errorModel = new MetadataErrors();
		!$errorModel->setError($errorReports, $itemId, $errorId) ? $this->returnError(500, 'Could not set error') : $this->returnJson('Error set');

	}
}