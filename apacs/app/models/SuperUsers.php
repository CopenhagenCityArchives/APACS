<?php

class SuperUsers extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'superusers';
	}
}