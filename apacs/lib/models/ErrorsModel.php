<?php

class ErrorsModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $entriesId;
	protected $collectionId;
	protected $reportingUser;
	protected $reportedTime;
	protected $toSuperuser;

    public function initialize()
    {
        $this->belongsTo('unit_id', 'Errors', 'id');
        $this->belongsTo('reporting_user_id', 'Users', 'id');
    }
}