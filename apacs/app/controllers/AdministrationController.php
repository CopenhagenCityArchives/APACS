<?php

use Phalcon\Db\Adapter\Pdo\Mysql;


class AdministrationController extends \Phalcon\Mvc\Controller {
	public function createTasksUnits(){
		// Is user admin?
		$this->RequireAccessControl(true);

		if(!in_array($this->auth->getUserId(), [])){
			throw new Exception("Unauthorized! Admin required.");
		}

		// Get required fields
		$input = $this->getAndValidateJSONPostData();
		$this->CheckFields($input, ['collectionId', 'taskId']);

		$creator = new TaskMetadataCreator($this->getDI()->get('db'), $input['collectionId'], $input['taskId']);
		$creator->createTaskUnits();
	}

	public function createTasksPages(){
		// Is user admin?
		$this->RequireAccessControl(true);

		if(!in_array($this->auth->getUserId(), [])){
			throw new Exception("Unauthorized! Admin required.");
		}

		// Get required fields
		$input = $this->getAndValidateJSONPostData();
		$this->CheckFields($input, ['collectionId', 'taskId', 'layout_columns', 'layout_rows']);

		$creator = new TaskMetadataCreator($this->getDI()->get('db'), $input['collectionId'], $inut['taskId']);
		$creator->createTaskPages($input['layout_columns'], $input['layout_rows']);
	}
}