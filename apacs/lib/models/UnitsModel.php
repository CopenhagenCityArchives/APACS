<?php

class UnitsModel extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $numOfPages;
	protected $collectionId;

    public function getSource()
    {
        return 'apacs_' . 'units';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Pages', 'units_id');
        $this->hasMany('id', 'TasksUnits', 'units_id');
    }
}