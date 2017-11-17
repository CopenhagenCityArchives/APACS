<?php

class CumulusAssetController extends \Phalcon\Mvc\Controller {

	private $host = "https://192.168.20.30";
	private $port = 8443;
	private $user = "CIP-erindringsbilleder";
	private $pass = "***REMOVED***";
	private $location = "CIP-erindringsbilleder";
	private $catalog = "erindringskatalog";

	public function AssetDownload($assetId) {
		$url = sprintf("%s:%d/%s/asset/download/%s/%d", $this->host, $this->port, $this->location, $this->catalog, $assetId);

		// use key 'http' even if you send the request to https://...
		$options = array(
		    'http' => array(
		        'header'  => array(
							sprintf("Authorization: Basic %s", base64_encode(sprintf("%s:%s", $this->user, $this->pass)))),
		        'method'  => 'GET'
		    ),
				'ssl' => array(
					'verify_peer' => false
				)
		);
		$context  = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		if ($result === FALSE) {
			$this->response->setStatusCode(500, "Invalid asset ID.")
		} else {
			header('Content-Type: application/pdf');
			echo $result;
		}
	}
}

?>
