<?php

interface IEntity {

	public function __construct(Array $entity);
	public function getFields();
	public function AllEntityFieldsAreEmpty(Array $entityData);
}
