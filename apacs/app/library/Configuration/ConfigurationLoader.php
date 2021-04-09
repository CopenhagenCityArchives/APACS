<?php

/*
 * Handles the loading of the whole configuration object, as well as specified parts
 */
class ConfigurationLoader {
	private $_configuration;
	private $_configurationLoaded;
	private $_configurationCache;
	private $_configFileLocation;

	public function __construct($filePath) {
		$this->_configFileLocation = $filePath;
		$this->loadConfig(require ($filePath));
	}

	/**
	 * Loads a configuration array
	 * @param array configuration array
	 */
	public function loadConfig($config = false) {
		$this->_configuration = null;
		$this->_configurationLoaded = false;
		if (!$config) {
			throw new Exception('A config input must be given!');
		}

		if (gettype($config) !== 'array') {
			throw new Exception('Could not load configuration: The given configuration is not an array.');
		}

		$this->_configuration = $config;
		$this->_configurationLoaded = true;
	}

	/**
	 * Returns the configuration for a specified collection
	 * @param int collection id
	 * @return array configuration array for the collection
	 */
	public function getCollection($collectionId, $publicData = false) {
		if (!$this->_configurationLoaded) {
			return false;
		}

		$collectionInfo = array();

		//Collections with ids over 50 is from the Starbas integration
		if ($collectionId > 50) {
			$collectionInfo[] = $this->getGenericConfiguration($collectionId);
		}

		$matchAll = !is_numeric($collectionId);

		if (is_numeric($collectionId) && isset($this->_configurationCache[$collectionId])) {
			return $this->_configurationCache[$collectionId];
		}

		foreach ($this->_configuration as $col) {
			if ($col['id'] == $collectionId || $matchAll) {
				if ($publicData) {
					/*$info = array();
						                    $info = $col['info'];
						                    $info['id'] = $col['id'];
					*/
					unset($col['objects_query']);
					$collectionInfo[] = $col;
				} else {
					$collectionInfo[] = $col;
				}
			}
		}

		if (count($collectionInfo) == 0) {
			throw new Exception('Collection empty!');
		}

		for ($i = 0; $i < count($collectionInfo); $i++) {
			$collectionInfo[$i] = $this->setDefaults($collectionInfo[$i]);
		}

		//Let's save the configuration so we dont have to go through all that again!
		if (is_numeric($collectionId)) {
			$this->_configurationCache[$collectionId] = $collectionInfo;
		}

		return $collectionInfo;
	}

	/**
	 * Returns a generic configuration used to display collections added
	 * via the Starbas integration
	 * @param  int $collectionId Id of the collection
	 * @return array A configuration array for a given collection
	 */
	private function getGenericConfiguration($collectionId) {
		$collection = Collections::findFirstById($collectionId);

		if (!$collection) {
			throw new Exception('Collection with id ' . $collectionId . ' does not exist');
		}

		$conf = [];
		$conf['id'] = $collectionId;
		$conf['info'] = $collection->info;
		$conf['link'] = $collection->link;

		$conf['short_name'] = $collection->name;
		$conf['long_name'] = $collection->name;

		$conf['image_type'] = 'image';

		$conf['primary_table_name'] = 'apacs_pages';

		$conf['objects_query'] = 'select apacs_pages.id as id, apacs_collections.name, apacs_units.id as units_id, apacs_units.level1_value, apacs_units.level2_value, apacs_units.level3_value, 
			
			IF(s3 = 1, apacs_pages.image_url, CONCAT(\'https://api.kbharkiv.dk/file/\', apacs_pages.id)) as imageURL,
			
			apacs_units.id as starbas_id, apacs_pages.s3

            FROM apacs_pages
            LEFT JOIN apacs_units ON apacs_pages.unit_id = apacs_units.id
            LEFT JOIN apacs_collections ON apacs_units.collections_id = apacs_collections.id
            WHERE apacs_collections.id = ' . $collectionId . ' AND :query AND apacs_units.is_public = 1 ORDER BY apacs_pages.page_number';
		//apacs_collections.is_public = 1 AND

		$conf['api_documentation_url'] = self::getCurrentApiUrl() . 'collections/' . $collectionId . '/info';

		$conf['levels_type'] = 'hierarchy';

		$conf['levels'] = [];

		for ($i = 1; $i <= $collection->num_of_filters; $i++) {
			$requiredLevels = [];
			$conditions = '';
			$order = '';
			foreach ($conf['levels'] as $level) {
				$requiredLevels[] = $level['name'];
				$conditions .= ' AND ' . $level['name'] . ' = \'%s\'';
				$order .= ' ' . str_replace('value', 'order', $level['name']) . ',';
			}

			$order = ' ORDER BY' . $order . ' level' . $i . '_order';

		//	$conditions = substr($conditions, 0, strlen($conditions)-3);

			//TODO: Use this instead, when PHP version is updated to >= 5.5
			//$requiredLevels = array_merge(array_column($conf['levels'], 'name'));

			$conf['levels'][] = [
				'order' => $i,
				'gui_name' => $collection->{'level' . $i . '_name'},
				'gui_description' => $collection->{'level' . $i . '_info'},
				'gui_info_link' => false,
				'name' => 'level' . $i . '_value',
				'example_value' => $collection->{'level' . $i . '_example_value'},
				'gui_type' => 'typeahead',
				'data_sql' => 'SELECT DISTINCT level' . $i . '_value as id, level' . $i . '_value as text, level' . $i . '_order FROM apacs_units WHERE collections_id = ' . $collectionId . $conditions . $order,
				'data' => false,
				'gui_hide_name' => true,
				'gui_hide_value' => false,
				//One filter is always required
				'required' => $i < 2 ? true : false,
				'searchable' => true,
				'required_levels' => $requiredLevels,
			];
		}

		//Starbas-reference, ikke søgebar
		$conf['levels'][] = [
			'order' => $collection->num_of_filters + 1,
			'gui_name' => 'starbas_id',
			'gui_description' => '',
			'gui_info_link' => false,
			'name' => 'starbas_id',
			'gui_type' => 'preset',
			'data_sql' => 'sesf',
			'data' => false,
			'gui_hide_name' => true,
			'gui_hide_value' => true,
			'required' => false,
			'searchable' => false,
			'required_levels' => false,
		];

		return $conf;
	}

	/**
	 * Set defaults in configuration arrays.
	 * TODO: Not implemented. Suggests a new structure of the over complicated configuration structure
	 *
	 * @param Array A collection configuration
	 */
	private function setDefaults($collectionConfig) {
		$DefaultInfo = array(
			//Id of the collection. Main entrance for API requests
			'id' => -1,
			//Description of the collection (NOT USED, see info instead)
			//    'description' => false,
			'info' => false,
			//Is the collection in test or public?
			'test' => true,
			//Image type (image or tile)
			'image_type' => 'image',
			//Link for further information about the collection
			'link' => false,
			//Short name of collection
			'short_name' => false,
			//Long name of collection
			'long_name' => false,
			//Link to API documentation
			'api_documentation_url' => '',
			//Name of the collection
			'primary_table_name' => 'primary_table_name',
			//Starbs field name, if any
			'starbas_field_name' => false,
			//Type of levels. Can be flat or hierarkic
			'levels_type' => false,
			//Query for loading objects. Should at least include the field "image"
			'objects_query' => false,
			//Textual description of the required fields needed for object search
			'gui_required_fields_text' => false,
			//An array of levels of metadata
			'levels' => array(),
			//Indexes are used to configure the indexing of the collection
			'indexes' => [],
			//Text used to introduce the error reporting
			'error_intro' => '',
			//Text presented to the user when an error report is submitted
			'error_confirm' => '',
			//An array of possible error reports
			'error_reports' => array(),
		);

		$DefaultMetadataLevel = array(
			//Ordering of levels in hierarkic metadata structures. Also used in GUI for form field ordering
			'order' => -1,
			//Name in GUI
			'gui_name' => false,
			//Description in GUI
			'gui_description' => false,
			//Description in API
			'api_description' => false,
			//Link to further information, GUI
			'gui_info_link' => false,
			//Internal name, also used in requests
			'name' => false,
			//GUI type, preset, getallbyfilter, typehead
			'gui_type' => false,
			//Query for receiving data for this field (for example adresses). Digits written as %d, strings as %s
			//(Example: SELECT id, name WHERE id = %d AND name LIKE %s)
			'data_sql' => false,
			//Data for the field. Required if no data_sql is given. Format: array(id, text)
			'data' => false,
			//Wheter or not the field name should be visible in the metadata info when displaying images
			'gui_hide_name' => false,
			//Wheter or not the data should be visible in the metadata info when displaying images
			'gui_hide_value' => false,
			//Is this a required field when searching objects?
			'required' => false,
			//Is this a searchable field when searching objects?
			'searchable' => true,
			//Other levels required to get data from this level
			'required_levels' => array(),
		);

		$collectionConfig = array_merge($DefaultInfo, $collectionConfig);

		$i = 0;
		foreach ($collectionConfig['levels'] as $metadataLevel) {
			$collectionConfig['levels'][$i] = array_merge($DefaultMetadataLevel, $metadataLevel);

			//Logic validation

			//Either data or data_sql has to be filled out
			if (!$collectionConfig['levels'][$i]['data'] && !$collectionConfig['levels'][$i]['data_sql']) {
				throw new Exception('Invalid configuration format. Either data or data_sql should be set.');
			}

			//If gui_type is preset, the data field has to by filled
			//if ($collectionConfig['levels'][$i]['gui_type'] == 'preset' && (!is_countable($collectionConfig['levels'][$i]['data']) || count($collectionConfig['levels'][$i]['data']) == 0)) {
			//	throw new Exception('Invalid configuration format. GUI type \'preset\' requires data to have content.');
			//}

			$collectionConfig['api_documentation_url'] =  self::getCurrentApiUrl()  . 'collections/' . $collectionConfig['id'] . '/info';
			$i++;
		}

		$DefaultErrorConfig = array(
			'id' => -1,
			'name' => '',
			'sql' => false,
			'order' => -1,
		);

		$i = 0;
		foreach ($collectionConfig['error_reports'] as $errorReport) {
			$collectionConfig['error_reports'][$i] = array_merge($DefaultErrorConfig, $errorReport);
			$i++;
		}

		$defaultIndexConfig = [
			'id' => -1,
			'name' => '',
			'description' => '',
			'layout_columns' => 1,
			'layout_rows' => 1,
			'entities' => [],
		];

		$defaultEntityConfig = [
			'id' => -1,
			'name' => '',
			'required' => '',
			'dbTableName' => '',
			'isMarkable' => true,
			'countPerEntry' => 'one',
			'serviceUrl' => 'https://' . $_SERVER['HTTP_HOST'] . '/api/indexing/',
			'fields' => [],
		];

		$defaultEntityField = [
			'id' => -1,
			'name' => '',
			'guiName' => '',
			'defaultValue' => null,
			'placeholder' => '',
			'helpText' => '',
			'helpLink' => '',
			'dbFieldName' => '',
			'type' => '1',
			'required' => false,
			'validationRegularExpression' => false,
			'validationErrorMessage' => '',
		];

		$i = 0;
		foreach ($collectionConfig['indexes'] as $index) {
			$collectionConfig['indexes'][$i] = array_merge($defaultIndexConfig, $index);

			$j = 0;
			foreach ($index['entities'] as $entity) {

				$collectionConfig['indexes'][$i]['entities'][$j] = array_merge($defaultEntityConfig, $entity);
				$collectionConfig['indexes'][$i]['entities'][$j]['serviceUrl'] .= $entity['id'];
				//If marking is required, add marking parameters as ordinary parameters
				if ($collectionConfig['indexes'][$i]['entities'][$j]['isMarkable'] == true) {
					$entity['fields'][] = [
						'name' => 'entity_topleft',
						'validationRegularExpression' => '/^0(\.\d{0,10})$/',
						'validationErrorMessage' => 'entity_topleft required',
						'required' => true,
						'dbFieldName' => 'entity_topleft',

					];
					$entity['fields'][] = [
						'name' => 'entity_bottomleft',
						'validationRegularExpression' => '/^0(\.\d{0,10})$/',
						'validationErrorMessage' => 'entity_bottomleft required',
						'required' => true,
						'dbFieldName' => 'entity_bottomleft',
					];
					$entity['fields'][] = [
						'name' => 'entity_topright',
						'validationRegularExpression' => '/^0(\.\d{0,10})$/',
						'validationErrorMessage' => 'entity_topright required',
						'required' => true,
						'dbFieldName' => 'entity_topright',
					];
					$entity['fields'][] = [
						'name' => 'entity_bottomright',
						'validationRegularExpression' => '/^0(\.\d{0,10})$/',
						'validationErrorMessage' => 'entity_bottomright required',
						'required' => true,
						'dbFieldName' => 'entity_bottomright',
					];
				}

				//Adding an id field. Needed for updating existing posts
				$entity['fields'][] = [
					'name' => 'id',
					'validationRegularExpression' => '/^\d{0,}$/',
					'required' => false,
					'dbFieldName' => 'id',
				];

				$k = 0;
				foreach ($entity['fields'] as $field) {
					$collectionConfig['indexes'][$i]['entities'][$j]['fields'][$k] = array_merge($defaultEntityField, $field);
					$k++;
				}

				$j++;
			}

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
	public function getMetadataLevels($collectionId, $metadataLevelName = false) {
		$config = $this->getCollection($collectionId);

		if ($metadataLevelName) {
			foreach ($config[0]['levels'] as $level) {
				if ($level['name'] == $metadataLevelName) {
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
	public function getErrorReports($collectionId) {
		$config = $this->getCollection($collectionId);
		return $config[0]['error_reports'];
	}

	/**
	 * Gets data level for the given id
	 * @param int id of the collection
	 * @return array data level for the collection
	 *//*
	public function getDataLevel($collectionId){
	$config = $this->getCollection($collectionId);

	return $config[0]['dataLevel'];
	}    */

	/**
	 * Gets all possible filters for collection
	 * @param int id of the collection
	 * @return array all filters for the collection
	 */
	public function getAllFilters($collectionId) {
		$config = $this->getCollection($collectionId);
		$filters = array();

		foreach ($config[0]['levels'] as $curLevel) {
			$filters[] = $curLevel;
		}

		return $filters;
	}

	public function getSearchableFilters($collectionId) {
		$config = $this->getCollection($collectionId);
		$filters = array();

		foreach ($config[0]['levels'] as $curLevel) {
			if ($curLevel['searchable'] == true) {
				$filters[] = $curLevel;
			}
		}

		return $filters;
	}

	public function getRequiredFilters($collectionId) {
		$config = $this->getCollection($collectionId);
		$filters = array();

		foreach ($config[0]['levels'] as $curLevel) {
			if ($curLevel['required']) {
				$filters[] = $curLevel;
			}
		}

		return $filters;
	}

	/**
	 *
	 * Generic search for filters. Gets filters by key and value
	 * If no value is given, all filters are returned
	 * Example (get all required filters): getFilters(2, 'required', true)
	 *
	 * @param int The id of the collection
	 * @param string The key in which to search for a value
	 * @param string The value of the key. If not given, all filters are returned.
	 */
	public function getFilters($collectionId, $key = null, $value = null) {
		$config = $this->getCollection($collectionId);
		$filters = array();

		if ($key == null && $value == null) {
			foreach ($config[0]['levels'] as $curLevel) {
				$filters[] = $curLevel;
			}
		} else {
			foreach ($config[0]['levels'] as $curLevel) {
				if ($curLevel[$key] == $value) {
					$filters[] = $curLevel;
				}
			}
		}

		return $filters;
	}

	/**
	 * Returns a configuration for a specific entity.
	 * All entities are indentified by unique ids, which are used
	 * to retrieve them.
	 * @param  int $entityId The id of the entity
	 * @return array Returns an array containing the entity configuration
	 */
	public function getIndexEntity($entityId) {

		foreach ($this->_configuration as $collection) {
			foreach ($collection['indexes'] as $index) {
				foreach ($index['entities'] as $entity) {
					if ($entity['id'] == $entityId) {
						return $entity;
					}

				}
			}
		}

		throw new Exception('Could not load configuration for entity id ' . $entityId);
	}

	/**
	*	Returns the API base url for the current location
	*/
	public static function getCurrentApiUrl(){
		$protocol = 'https://';
		$subDir = str_replace('public/', '', str_replace('index.php', '', $_SERVER['PHP_SELF']));

		return $protocol . $_SERVER['HTTP_HOST'] . $subDir;
	}
}
