<?php

class Units extends \Phalcon\Mvc\Model {

	public $id;

	private $status = [];
	static $publicFields = ['id', 'collection_id', 'pages', 'description'];

	const OPERATION_TYPE_CREATE = 'create';
	const OPERATION_TYPE_UPDATE = 'update';

	public function getSource(): string {
		return 'apacs_units';
	}

	public function initialize() {
		$this->hasMany('id', 'Pages', 'units_id');
		$this->hasMany('id', 'TasksUnits', 'units_id');
		$this->belongsTo('collections_id', 'Collections', 'id');
	}

	public function updatePagesCountByCollection($collectionId)
	{
		$sql = 'UPDATE apacs_units SET pages = (
  					SELECT COUNT(id) FROM apacs_pages
    				WHERE apacs_pages.unit_id = apacs_units.id
				) WHERE collections_id = :id';

		return $this->getDI()->get('db')->query($sql, ['id' => $collectionId]);
	}

	public function updateIsPublicStatusByCollection($collectionId, $isPublic){
		$sql = 'UPDATE apacs_units set is_public = :isPublic WHERE collections_id = :id';

		return $this->getDI()->get('db')->query($sql, ['isPublic' => $isPublic, 'id' => $collectionId]);
	}

	public function GetStatus() {
		return $this->status;
	}
}
