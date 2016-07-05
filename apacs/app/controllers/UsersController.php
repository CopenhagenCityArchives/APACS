<?php

class UsersController extends \MainController {
	private $config;
	private $response;
	private $request;

	public function onConstruct() {
		$this->config = $this->getDI()->get('configuration');
		$this->response = $this->getDI()->get('response');
		$this->request = $this->getDI()->get('request');
	}

	public function GetActiveUsers() {
		$collectionId = $this->request->query('collection_id', 'int', false);
		$unitId = $this->request->query('unit_id', 'int', false);
		$pageId = $this->request->query('page_id', 'int', false);

		if ($collectionId == false && $unitId == false && $pageId == false) {
			$this->SetResponse(400, null, ['collection_id, unit_id or page_id is required']);
			return;
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
			$this->SetResponse(200, null, $results[0]);
		} else {
			$this->SetResponse(200, null, $results);
		}
	}
}