<?php

class Entries extends \Phalcon\Mvc\Model {

	protected $id;
	protected $pagesId;
	protected $tasksId;
	protected $collectionId;
	protected $usersId;

	public function getSource() {
		return 'apacs_' . 'entries';
	}

	public function initialize() {
		$this->hasMany('id', 'Errors', 'entry_id');
		$this->belongsTo('page_id', 'Pages', 'id');
		$this->belongsTo('task_id', 'Tasks', 'id');
	}
}