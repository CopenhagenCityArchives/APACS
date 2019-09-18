<?php

class Collections extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'collections';
	}

	public function initialize() {
		$this->hasMany('id', 'Tasks', 'collection_id');
		$this->hasMany('id', 'Units', 'collections_id');
	}

	public function GetStats(){
		$stats = [];

		$stats['units'] = 0;
		$stats['public_units'] = 0;
		$stats['units_without_pages'] = 0;

		$stats['pages'] = 0;
		$stats['public_pages'] = 0;

		$units = $this->getUnits()->toArray();

		foreach($units as $unit){
			$stats['units']++;
			$stats['pages'] += $unit['pages'];

			//Increment if public
			if($unit['is_public'] == 1){
				$stats['public_units']++;
				$stats['public_pages'] += $unit['pages'];
			}

			if($unit['pages'] == 0){
				$stats['units_without_pages']++;
			}
		}

		if($stats['units'] == 0){
			return null;
		}

		return $stats;
	}
}
