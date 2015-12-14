<?php

class Fields extends \Phalcon\Mvc\Model
{
	public static $publicFields = ['id', 'name', 'formType', 'defaultValue', 'placeholder', 'helpText', 'dbFieldName', 'required'];

    public function getSource()
    {
        return 'apacs_' . 'fields';
    }

    public function initialize()
    {
    	$this->belongsTo('id', 'FieldsFieldgroup', 'field_id');
    }
}