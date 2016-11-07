<?php
/**
 * Handles error reporting
 */
class MetadataErrors extends \Phalcon\Mvc\Model {

	public function setError($errorConfs, $itemId, $errorId) {
		$conf = array();
		foreach ($errorConfs as $report) {
			if ($report['id'] == $errorId) {
				$conf = $report;
				break;
			}
		}

		if (!$conf) {
			return false;
		}

		$conf['sql'] = str_replace(':itemId', $itemId, $conf['sql']);

		try {
			return $this->getDI()->getDatabase()->query($conf['sql']);
		} catch (Exception $e) {
			echo ('Could not execute query: ' . $e);
			return false;
		}

		return false;
	}
}
