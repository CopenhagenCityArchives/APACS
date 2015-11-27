<?php

class UnitsModel extends \Phalcon\Mvc\Model
{
	public function GetUnits($collectionId)
	{
		$sql = $this->getDI()->get('configuration')->getCollection($collectionId)['unit_sql_list'];

	    return $this->getDI()->get('db')->query($sql)->toArray();
	}

	public function GetUnit($collectionId, $protocolId)
	{
		$sql = $this->getDI('configuration')->getCollection($collectionId)['unit_sql_single'];
		$sql = $sql . $protocolId;

		return $this->getDI()->get('db')->query($sql)->toArray();
	}
}