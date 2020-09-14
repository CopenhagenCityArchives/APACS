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

		return $this->curl_get_contents($url);
		
		$auth = base64_encode(sprintf("%s:%s",
			$this->getDI()->get('cipConfig')['user'],
			$this->getDI()->get('cipConfig')['pass']
		));

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => array(sprintf("Authorization: Basic %s", $auth)),
				'method'  => 'GET'
			),
			'ssl' => array(
				'verify_peer' => false
			)
		);
		$context  = stream_context_create($options);
		$result = @file_get_contents($url, false, $context);
		if ($result === false) {
			$this->response->setStatusCode(400, "Invalid Asset ID");
		} else {
			header('Content-Type: application/pdf');
			echo $result;
		}
	}

	private function curl_get_contents($url)
	{
	  	$auth = base64_encode(sprintf("%s:%s",
			$this->getDI()->get('cipConfig')['user'],
			$this->getDI()->get('cipConfig')['pass']
		));

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			sprintf("Authorization: Basic %s", $auth)
		]);

		$data = curl_exec($ch);
		var_dump($data);

		curl_close($ch);
	  	return $data;
	}

}

?>
