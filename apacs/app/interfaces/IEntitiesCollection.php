<?php

interface IEntitiesCollection {

	public function __construct(Array $entity);
	public function getEntities();
	public function GetPrimaryEntity();
	public function GetSecondaryEntities();
}
