<?php

class DatalistEvents extends \Phalcon\Mvc\Model {

    public function getSource() {
            return 'apacs_' . 'datalist_events';
    }
    
    protected $id;
    protected $users_id;
    protected $datasource_id;
    protected $event_type;
    protected $old_value;
    protected $new_value;
    protected $timestamp;

    public function beforeSave() {
		$this->timestamp = date('Y-m-d H:i:s');
	}

    public function getId() {
        return $this->id;
    }

    public function setUsersId($id) {
        $this->users_id = $id;
    }
    
    public function getDatasourceId() {
        return $this->datasource_id;
    }

    public function setDatasourceId($id) {
        $this->datasource_id = $id;
    }

    public function getEventType() {
        return $this->event_type;
    }

    public function setEventType($type) {
        $this->event_type = $type;
    }
    
    public function getOldValue() {
        return $this->old_value;
    }

    public function setOldValue($value) {
        $this->old_value = $value;
    }

    public function getNewValue() {
        return $this->new_value;
    }

    public function setNewValue($value) {
        $this->new_value = $value;
    }
    

}