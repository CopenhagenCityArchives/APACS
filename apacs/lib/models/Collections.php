<?php

class Collections extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_' . 'collections';
    }

    public function initialize()
    {
  	 	$this->hasMany('id', 'Tasks', 'collection_id');
    }
}