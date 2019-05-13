<?php

use Phalcon\Db\Adapter\Pdo\Mysql;


//Used to create tasks units and tasks pages based on a task id and a unit range
//Should be also support deletion
class TaskMetadataCreator{

    private $db;
	private $startUnitId;
	private $endUnitId;
    private $taskId;


    public function __construct($db, $taskId, $startUnitId, $endUnitId){
		$this->db = $db;

		$this->taskId = $taskId;
		
		$this->startUnitId = $startUnitId;
		$this->endUnitId = $endUnitId;
	}
	
	private function taskPagesExist(){
		return count(TasksPages::find([
			'tasks_id = ' . $this->taskId,
			'unit_id >= ' . $this->startUnitId,
			'unit_id <= ' . $this->endUnitId
		]))>0;
	}

	private function taskUnitsExist(){
		return count(TasksUnits::find([
			'tasks_id = ' . $this->taskId,
			'unit_id >= ' . $this->startUnitId,
			'unit_id <= ' . $this->endUnitId
		]))>0;
	}

	private function taskExists(){
		return count(Tasks::find('id = ' . $this->taskId))>0;
	}

    // Create task units based on a task and a unit range (start unit id and end unit id)
	public function createTaskUnits($layout_columns, $layout_rows){
		
		if(!$this->taskExists()){
			throw new Exception("Task with id " . $this->taskId . " does not exist. Please create it first");
		}

		if($this->taskUnitsExist()){
			throw new Exception("Units for task already exist. Please remove before creating new ones");
		}

        echo 'connecting' . PHP_EOL;
		$this->db->begin();

		try{

			echo 'inserting task units based on task_id ' . $this->taskId . ' and startUnitId, endUnitId ' . $this->startUnitId . ', ' . $this->endUnitId . PHP_EOL;

			$this->db->execute(
				"
					INSERT INTO apacs_tasks_units (tasks_id, units_id, pages_done, columns, rows, index_active)
					select ? as tasks_id, apacs_units.id as units_id, 0 as pages_done, ? as columns, ? as rows, 0 as index_active
					from apacs_units
					where apacs_units.id >= ? and apacs_units.id <= ?
				",
				[
					$this->taskId,
					$layout_columns,
					$layout_rows,
					$this->startUnitId,
					$this->endUnitId
				]
			);
			
			$this->db->commit();

			echo $this->db->affectedRows(), " task units were created";
		}
		catch(Exception $e){
			$this->db->rollback();
			echo 'something went wrong, commit cancelled. ' . $e->getMessage() . PHP_EOL;			
            return;
		}
	
	}	

	public function createTaskPages(){
		
		if(!$this->taskExists()){
			throw new Exception("Task with id " . $this->taskId . " does not exist. Please create it first");
		}

		if($this->taskPagesExist()){
			throw new Exception("Pages for task already exist. Please remove before creating new ones");
		}

		$this->db->begin();

		try{
			echo 'inserting task pages based on task_id ' . $this->taskId . ' and startUnitId, endUnitId ' . $this->startUnitId . ', ' . $this->endUnitId . PHP_EOL;

			$this->db->execute(
				"
					INSERT INTO apacs_tasks_pages (tasks_id, pages_id, units_id, is_done)
					SELECT ? as tasks_id, apacs_pages.id as pages_id, apacs_units.id as units_id, 0 as is_done
					from apacs_units 
					join apacs_pages on apacs_units.id = apacs_pages.unit_id
					
					where apacs_units.id >= ? and apacs_units.id <= ?
				",
				[
					$this->taskId,
					$this->startUnitId,
					$this->endUnitId
				]
			);
			
			$this->db->commit();

			echo $this->db->affectedRows() . " task pages were created";
		}
		catch(Exception $e){
			$this->db->rollback();
			echo 'something went wrong, commit cancelled. ' . $e->getMessage() . PHP_EOL;
		}
	}
}