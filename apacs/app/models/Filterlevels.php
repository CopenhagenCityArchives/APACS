<?php

class Filterlevels extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_' . 'filterlevels';
    }

    public function initialize()
    {
    	$this->belongsTo('filter_id', 'Filters', 'id');
    }
}