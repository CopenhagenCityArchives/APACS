<?php

class Errors extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $entriesId;
	protected $collectionId;
	protected $reportingUser;
	protected $reportedTime;
	protected $toSuperuser;

    public function getSource()
    {
        return 'apacs_' . 'errors';
    }

    public function initialize()
    {
        $this->belongsTo('unit_id', 'Errors', 'id');
        $this->belongsTo('reporting_user_id', 'Users', 'id');
    }
}