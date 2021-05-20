<?php

use Aws\S3\S3Client;

class GetFileController extends MainController {

	public function GetFileById($fileId) {
		$starttime = microtime(true);

		$page = Pages::findFirstById($fileId);

		if ($page == NULL) {
			$this->addStat('error_no_result', null, $starttime, $fileId);
			$this->response->setStatusCode(404);
			$this->response->setJsonContent(['error' => 'No file found for file ID ' . $fileID]);
		} else {
			if ($page->s3 == 1) {
				$s3Client = new S3Client($this->getDI()->get('s3Config'));

				// TODO: might have to use streamWrapper if this is too slow / memory-consuming
				$result = $s3Client->getObject([
					'Bucket' => $page->s3_bucket,
					'Key' => $page->s3_key
				]);

				if ($result['StatusCode'] == 200) {
					$this->response->setMimeType($result['ContentType']);
					$this->response->setBody($result['Body']);
					$this->addStat(null, '/' . $page->relative_filename_converted, $starttime, $fileId);
				} else {
					$this->response->setStatusCode($result['StatusCode']);
					$this->response->setJsonContent(['error' => 'S3 returned status code '.$result['StatusCode'].' for ' . $fileID]);
					$this->addStat('error_s3_status_' . $result['StatusCode'], $file, $starttime, $fileId);
				}
			} else {
				$this->response->setStatusCode(404);
				$this->response->setJsonContent(['error' => 'Object not marked for S3: ' . $fileID]);
				$this->addStat('error_file_not_marked_s3', $file, $starttime, $fileId);
			}
		}
	}

	// TODO: remove when old collections have been migrated
	public function GetFileByPath() {
		$starttime = microtime(true);
		
		$filePath = $this->request->getQuery('path', 'str', null);

		if(is_null($filePath)){
			$this->addStat('error_no_result', null, $starttime, null);
			$this->response->setStatusCode(400);
			$this->response->setJsonContent(['error' => 'path is required']);
			return;
		}
		
		$page = Pages::findFirst(['conditions' => 's3_key = ' . $filePath]);

		if ($page == NULL) {
			$this->addStat('error_no_result', null, $starttime, null);
			$this->response->setStatusCode(404);
			$this->response->setJsonContent(['error' => 'No file found for path ' . $filePath]);
			return;
		}

		$this->GetFileById($page->id);
	}

	private function addStat($collection, $file, $starttime, $fileId = 'NULL') {
		$stats = new Stats();
		$stats->save([
			'collection' => $collection,
			'file' => $file,
			'loadTime' => number_format(microtime(true) - $starttime, 6, '.', ''),
			'fileId' => $fileId,
			'ip' => $this->request->getServer('REMOTE_ADDR')
		]);
	}

}

?>