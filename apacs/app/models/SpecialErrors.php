<?php

use Phalcon\Mvc\Model\Query;

class SpecialErrors extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'specialerrors';
	}
}
