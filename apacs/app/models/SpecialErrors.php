<?php

use Phalcon\Mvc\Model\Query;

class SpecialErrors extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_specialerrors';
	}

	public static function setLabels($errorReports, $taskId)
	{
		$config = ErrorReports::GetConfig();
		for($i = 0; $i < count( $errorReports ); $i++) {
			foreach ($config as $collectionConf) {
				if ($collectionConf['task_id'] != $taskId) {
					continue;
				}
				
				foreach ($collectionConf['error_reports'] as $confRow) {
					if ($confRow['entity'] == $errorReports[$i]['entity']) {
						$errorReports[$i]['label'] = $confRow['label'];
					}
				}
			}
		}

		return $errorReports;
	}
}
