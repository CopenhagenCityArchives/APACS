<?php

class AdministrationController extends MainController{
	public function createTasksUnits(){
		// Is user admin?
		$this->RequireAccessControl(true);

		if(!in_array($this->auth->getUserId(), [])){
			//TODO: implement security!	throw new Exception("Unauthorized! Admin required.");
			//throw new Exception("Unauthorized! Admin required.");
		}

		// Get required fields
		$input = $this->getAndValidateJSONPostData();
		$this->CheckFields($input, ['startUnitId', 'endUnitId', 'taskId', 'layout_columns', 'layout_rows']);

		$creator = new TaskMetadataCreator($this->getDI()->get('db'), $input['taskId'],$input['startUnitId'],$input['endUnitId']);
		$creator->createTaskUnits($input['layout_columns'],$input['layout_rows']);
	}

	public function createTasksPages(){
		// Is user admin?
		$this->RequireAccessControl(true);

		if(!in_array($this->auth->getUserId(), [])){
			//TODO: implement security!	throw new Exception("Unauthorized! Admin required.");
			//throw new Exception("Unauthorized! Admin required.");
		}

		// Get required fields
		$input = $this->getAndValidateJSONPostData();
		$this->CheckFields($input, ['startUnitId', 'endUnitId', 'taskId', 'layout_columns', 'layout_rows']);

		$creator = new TaskMetadataCreator($this->getDI()->get('db'), $input['taskId'],$input['startUnitId'],$input['endUnitId']);
		$creator->createTaskPages($input['layout_columns'], $input['layout_rows']);
	}
}