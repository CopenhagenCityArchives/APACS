<?php

class CumulusAssetController extends \Phalcon\Mvc\Controller {

	public function AssetDownload($assetId) {
		$url = sprintf("%s:%d/%s/asset/download/%s/%d",
			$this->getDI()->get('cipConfig')['host'],
			$this->getDI()->get('cipConfig')['port'],
			$this->getDI()->get('cipConfig')['location'],
			$this->getDI()->get('cipConfig')['catalog'],
			$assetId
		);

		$auth = base64_encode(sprintf("%s:%s",
			$this->getDI()->get('cipConfig')['user'],
			$this->getDI()->get('cipConfig')['pass']
		)));

		// use key 'http' even if you send the request to https://...
		$options = array(
		    'http' => array(
		        'header'  => array(
							sprintf("Authorization: Basic %s", auth),
		        'method'  => 'GET'
		    ),
				'ssl' => array(
					'verify_peer' => false
				)
		);
		$context  = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		if ($result === FALSE) {
			$this->response->setStatusCode(400, "Invalid Asset ID");
		} else {
			header('Content-Type: application/pdf');
			echo $result;
		}
	}

}

?>
