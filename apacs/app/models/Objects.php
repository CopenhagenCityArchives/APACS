<?php
/**
 * Handles loading of metadata from tables as configured
 */
class Objects extends \Phalcon\Mvc\Model {
	/**
	 * Loads and returns the results from the database based on the sql
	 * @param string the sql performing the search
	 * @return array an associative array of results
	 */
	public function getData($sql) {
		try {
			$result = $this->getDI()->get('db')->query($sql);
			$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);

			return $result->fetchAll();
		} catch (Exception $e) {
			die('Could not execute query: ' . $e);
		}
	}

	/**
	 *
	 * @param array Array of possible filters as given in the configuration
	 * @param array Array of required filters as given in the configuration
	 * @return array on success, false if required filters is not set
	 */
	public function getFilters($allFilters, $requiredFilters) {
		$request = new Phalcon\Http\Request();
		$collectedFilters = array();
		$i = 0;

		foreach ($allFilters as $filter) {

			$incommingFilter = $request->getQuery($filter['name'], null, false);

			if ($incommingFilter !== false) {
				$filter['value'] = $incommingFilter;
				$collectedFilters[] = $filter;

				if (count($this->_searchArrayForValue($requiredFilters, 'name', $filter['name'])) > 0) {
					$i++;
				}
			}
		}

		if ($i == count($requiredFilters)) {
			return $collectedFilters;
		} else {
			//throw new Exception('Not all required filters are set!');
			return array();
		}
	}

	private function _searchArrayForValue($array, $key, $val) {
		$results = array();
		foreach ($array as $row) {
			if ($row[$key] == $val) {
				$results[] = $row;
			}
		}

		return $results;
	}

	/**
	 * Creates a object search query based on sql and the search input
	 * @param string SQL for finding the object
	 * @param array Array of inputs
	 * @return string search query
	 */
	public function createObjectQuery($sql, $levels) {
		$searchString = '';
		foreach ($levels as $level) {
			if (isset($level['sql_condition']) && $level['sql_condition']) {
				// $searchString = $searchString . vsprintf($level['sql_condition'],$level['value']);
				$searchString = $searchString . str_replace('%d', $level['value'], $level['sql_condition']) . ' AND ';
				// $searchString = $searchString . str_replace('%s', $level['value'], $level['sql_condition']);
			} else {
				$searchString = $searchString . $level['name'] . ' = \'' . $level['value'] . '\' AND ';
			}
		}

		$searchString = substr($searchString, 0, strlen($searchString) - 5);

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
	public function convertResultToObjects($results, $metadataLevels) {
		$objects = array();
		$i = 0;

		foreach ($results as $curRow) {
			$objects[$i]['id'] = $curRow['id'];

			foreach ($metadataLevels as $curLevel) {
				$objects[$i]['metadata'][$curLevel['name']] = $curRow[$curLevel['name']];
				if (isset($curRow['height']) && isset($curRow['width'])) {
					$objects[$i]['metadata']['height'] = $curRow['height'];
					$objects[$i]['metadata']['width'] = $curRow['width'];
				}
			}
			//$objects[$i]['images'][] = 'https://' . $_SERVER['HTTP_HOST'] . $curRow['imageURL'];
			$objects[$i]['images'][] = 'https://www.kbhkilder.dk' . $curRow['imageURL'];
			$i++;
		}

		return $objects;
	}
}
