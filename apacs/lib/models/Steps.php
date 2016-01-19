<?php
use Phalcon\Mvc\Model\Query;
class Steps extends \Phalcon\Mvc\Model
{
    public function getSource()
    {
        return 'apacs_' . 'steps';
    }

    public function initialize()
    {
  	 	$this->hasMany('id', 'EntitiesFields', 'step_id');
    }
}