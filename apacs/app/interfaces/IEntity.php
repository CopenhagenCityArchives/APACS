<?php

interface IEntity {

	public function __construct(Array $entity);
	public function getFields();
	public function UserEntryIsEmpty(Array $entityData);
	public function getChildren();
	public function getEntityByName(string $name);
	public function flattenTree();
	public function isDataValid(Array $data);
	public function GetValidationStatus(): string;
	public function getDenormalizedData(Array $data): Array;
	public function toArray(): Array;
}
