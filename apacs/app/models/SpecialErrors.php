<?php

use Phalcon\Mvc\Model\Query;

class SpecialErrors extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_' . 'specialerrors';
	}

	public static function setLabels($errorReports, $taskId)
	{
		$config = json_decode(ErrorReports::GetConfig(), true);
		for($i = 0; $i < count( $errorReports ); $i++){
			foreach($config[$taskId]['error_reports'] as $confRow){
				if($confRow['entity'] == $errorReports[$i]['entity']){
					$errorReports[$i]['label'] = $confRow['label'];
				}
			}
		}

		return $errorReports;
	}
}
