<?php

class EntriesModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $pageId;
	protected $taskId;
	protected $collectionId;

    public function initialize()
    {
        $this->hasMany('id', 'Errors', 'entries_id');
        $this->belongsTo('pages_id', 'Pages', 'id');
        $this->belongsTo('tasks_id', 'Tasks', 'id');
    }
}