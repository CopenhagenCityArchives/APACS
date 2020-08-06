<?php

use \Auth0\SDK\API\Authentication;
use \Auth0\SDK\API\Management;

class UsersController extends \Phalcon\Mvc\Controller {
	private $config;
	private $response;
	private $request;

	public function onConstruct() {
		$this->auth0Config = $this->getDI()->get('auth0Config');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	public function GetActiveUsers() {
		$collectionId = $this->request->query('collection_id', 'int', false);
		$unitId = $this->request->query('unit_id', 'int', false);
		$pageId = $this->request->query('page_id', 'int', false);

		if ($collectionId == false && $unitId == false && $pageId == false) {
			$this->response->setStatusCode('400', 'Wrong parameter');
			$this->response->setJsonContent(['error_message' => 'collection_id, unit_id or page_id is required']);
		}

		$conditions = '';
		if ($collectionId !== false) {
			$conditions = 'e.collection_id = ' . $collectionId;
		} else if ($unitId !== false) {
			$conditions = 'e.unit_id = ' . $unitId;
		} else {
			$conditions = 'e.page_id  = ' . $pageId;
		}

		//When is a user active? Right now it's 15*60 seconds = 15 minutes
		$activeSessionDuration = time() - (15 * 60);

		$conditions = $conditions . ' AND timestamp < ' . $activeSessionDuration;

		$query = $this->modelsManager->createQuery('SELECT DISTINCT u.id, u.userName, u.profileImageUrl, p.page_number FROM Users as u LEFT JOIN Entries as e ON u.id = e.user_id LEFT JOIN Pages p ON e.page_id = p.id WHERE ' . $conditions);

		$results = $query->execute();
		if (count($results) == 1) {
			$this->response->setJsonContent($results[0]);
		} else {
			$this->response->setJsonContent($results);
		}
	}

	public function UpdateUserProfile() {
		$auth0_api = new Authentication(
			$this->auth0Config['domain'],
			$this->auth0Config['client_id']
		);

		$config = [
			'client_secret' => $this->auth0Config['client_secret'],
			'client_id' => $this->auth0Config['client_id'],
			'audience' => $this->auth0Config['mgmt_audience'],
		];

		try {
			$result = $auth0_api->client_credentials($config);
			echo '<pre>'.print_r($result, true).'</pre>';
			die();
		} catch (Exception $e) {
			die( $e->getMessage() );
		}
	}
}