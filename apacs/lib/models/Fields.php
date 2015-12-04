<?php

class Fields extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_' . 'fields';
    }

    public function initialize()
    {
    	$this->belongsTo('id', 'FieldsFieldgroup', 'field_id');
    }
}