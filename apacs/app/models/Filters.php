<?php

class Filters extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_filters';
    }

    public function initialize()
    {
  	 	$this->hasMany('id', 'Filterlevels', 'filter_id');
    	$this->belongsTo('collection_id', 'Collection', 'id');
    }
}