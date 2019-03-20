<?php

//Used to create tasks units and tasks pages based on a task id and a collection id
//Should be also support deletion
//TODO: validate if tasks units and tasks pages exists for a given combination of task and collection
class TaskMetadataCreator{

    private $db;
    private $collectionId;
    private $taskId;


    public function __constructor($db, $collectionId, $taskId){
        //new Mysql($this->getDI()->get('config'));
        $this->db = $db;
        $this->collectionId = $collectionId;
        $this->taskId = $taskId;
    }

    // Create task units based on a task and a collection
	public function createTaskUnits($layout_columns, $layout_rows){

		$this->db->begin();

		try{
			echo 'connecting' . PHP_EOL;

			echo 'inserting task units based on task_id ' . $this->taskId . ' and collection_id ' . $this->collectionId . PHP_EOL;

			$this->db->execute(
				"
					INSERT INTO apacs_tasks_units (tasks_id, units_id, pages_done, columns, rows, index_active)
					select ? as tasks_id, apacs_units.id as units_id, 0 as pages_done, ? as columns, ? as rows, 0 as index_active
					from apacs_units
					where apacs_units.collections_id = ?
				",
				[
					$this->taskId,
					$layout_columns,
					$layout_rows,
					$this->collectionId
				]
			);
			
			echo $this->db->affectedRows(), " task units were created";
		}
		catch(Exception $e){
			echo 'something went wrong, commit cancelled. ' . $e->getMessage() . PHP_EOL;
			$this->db->rollback();
		}
	
		$this->db->commit();
	}	

	public function createTaskPages(){
		$this->db->begin();

		try{
			echo 'inserting task pages based on task_id ' . $this->taskId . ' and collection_id ' . $this->collectionId . PHP_EOL;

			$this->db->execute(
				"
					INSERT INTO apacs_tasks_pages (tasks_id, pages_id, units_id, is_done)
					SELECT ? as tasks_id, apacs_pages.id as pages_id, apacs_units.id as units_id, 0 as is_done
					from apacs_units 
					join apacs_pages on apacs_units.id = apacs_pages.unit_id
					
					WHERE apacs_units.collections_id = ?
				",
				[
					$this->taskId,
					$this->collectionId
				]
			);
			
			echo $this->db->affectedRows(), " task pages were created";
		}
		catch(Exception $e){
			echo 'something went wrong, commit cancelled. ' . $e->getMessage() . PHP_EOL;
			$this->db->rollback();
		}

		$this->db->commit();
	}
}