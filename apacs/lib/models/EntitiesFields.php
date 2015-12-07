<?php

class EntitiesFields extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_' . 'entities_fields';
    }

    public function initialize()
    {
    	$this->belongsTo('field_id', 'Fields', 'id');
    	$this->belongsTo('entity_id', 'Entities', 'id');
    }
}