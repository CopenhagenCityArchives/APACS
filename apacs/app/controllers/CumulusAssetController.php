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
		));

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			sprintf("Authorization: Basic %s", $auth)
		]);

		// Write function used to stream curl response data
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data){
			echo $data;
			ob_flush();
			flush();
			return strlen($data);
		});

		$headers = [];

		// this function is called by curl for each header received
		curl_setopt($ch, CURLOPT_HEADERFUNCTION,
		function($ch, $header) use (&$headers)
		{
			$len = strlen($header);
			$header = explode(':', $header, 2);
			if (count($header) < 2) // ignore invalid headers
				return $len;

			$headers[strtolower(trim($header[0]))][] = trim($header[1]);

			if(isset($headers['content-type'])){
				header('Content-Type: ' . $headers['content-type'][0]);
				header("Access-Control-Allow-Origin: *");
			}
			else{
				header('Content-Type: application/pdf');
				header("Access-Control-Allow-Origin: *");
			}

			return $len;
		});

		curl_exec($ch);

		$this->response->setStatusCode(curl_getinfo($ch, CURLINFO_HTTP_CODE), "");
		
		curl_close($ch);
	}
}

?>
