<?php

class Fields extends \Phalcon\Mvc\Model
{

	protected $id;

    public function getSource()
    {
        return 'apacs_' . 'feedback';
    }

    public function initialize()
    {
        $this->belongsTo('entries_id', 'Entries', 'id');
        $this->belongsTo('sending_user_id', 'Users', 'id');
        $this->belongsTo('receiving_user_id', 'Users', 'id');
    }
}