<?php

class Pages extends \Phalcon\Mvc\Model {

	public $id;
	protected $unitsId;
	protected $collectionId;

	private $status = [];
	static $publicFields = ['id', 'collection_id', 'unit_id'];

	const OPERATION_TYPE_CREATE = 'create';
	const OPERATION_TYPE_UPDATE = 'update';

	public function getSource() {
		return 'apacs_pages';
	}

	public function initialize() {
		$this->hasMany('id', 'Entries', 'page_id');
		$this->hasMany('id', 'TasksPages', 'page_id');
		$this->belongsTo('unit_id', 'Units', 'id');
	}

	public function GetLocalPathToConcreteImage() {

		//Get pageImageLocation to figure out where to get images from
		$pathInfo = $this->getDI()->get('pageImageLocation');

		if ($pathInfo['type'] == 'http') {
			return $pathInfo['path'] . $this->id;
		}

		return $pathInfo['path'] . $this->relative_filename_converted;
	}

	public function GetStatus() {
		return $this->status;
	}
}