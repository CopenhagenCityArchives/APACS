<?php

class CumulusAssetController extends \Phalcon\Mvc\Controller {

	private $host = "https://192.168.20.30";
	private $port = 8443;
	private $user = "CIP-erindringsbilleder";
	private $pass = "***REMOVED***";
	private $location = "CIP-erindringsbilleder";

	public function AssetDownload($assetId) {
		$url = sprintf("%s:%d/%s/", $this->host, $this->port, $this->location);

		// use key 'http' even if you send the request to https://...
		$options = array(
		    'http' => array(
		        'header'  => array(
							sprintf("Authorization: Basic %s", base64_encode(sprintf("%s:%s", $this->user, $this->pass)))),
		        'method'  => 'GET'
		    )
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === FALSE) {
			die("Asset does not exist!");
		} else {
			var_dump($result);
		}
	}
}

?>
