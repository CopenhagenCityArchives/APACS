<?php

class CumulusAssetController extends \Phalcon\Mvc\Controller {

	private $host;
	private $port;
	private $user;
	private $pass;
	private $location;

	public function __construct($host, $port, $location, $user, $pass) {
		$this->host = $host;
		$this->port = $port;
		$this->location = $location;
		$this->user = $user;
		$this->pass = $pass;
	}

	public function AssetDownload($assetId) {
		$url = sprintf("%s:%d/%s/", $this->host, $this->port, $this->location);

		// use key 'http' even if you send the request to https://...
		$options = array(
		    'http' => array(
		        'header'  => array(
							"Content-type: application/x-www-form-urlencoded",
							sprintf("Authorization: Basic %s", base64_encode(sprintf("%s:%s", $this->user, $this->pass)))),
		        'method'  => 'POST'
		    )
		);
		$context  = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		if ($result === FALSE) { /* Handle error */ }

		var_dump($result);
	}
}

?>
