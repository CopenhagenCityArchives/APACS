<?php

interface IEntitiesInfo {

	public function __construct(Array $entity);
	public function GetPrimaryEntity(Array $entities);
	public function GetSecondaryEntities(Array $entities);
	public function getFields();
}
