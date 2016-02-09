<?php

class SolrCommunicator {
	private $config;

	public function __construct() {
		$this->config = [
			'endpoint' =>
			['localhost' =>
				['host' => '54.194.233.62', 'hostname' => '54.194.233.62', 'port' => 80, 'login' => '', 'path' => '/solr/apacs_core'],
			],
		];
	}

	public function SaveData($data) {
		// create a client instance
		$client = new Solarium\Client($this->config);
		//Create update instance
		$update = $client->createUpdate();
		//Create document
		$doc1 = $update->createDocument();
		//Set data
		$doc1 = $data;

		//Add document and commit data
		$result = $update->addDocuments([$doc1]);
		$update->addCommit();
		$result = $client->update($update);
	}
}