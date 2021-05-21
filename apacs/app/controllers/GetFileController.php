<?php

use Aws\S3\S3Client;

class GetFileController extends MainController {

	public function GetFileById($fileId) {
		$starttime = microtime(true);

		$page = Pages::findFirstById($fileId);

		if ($page == NULL) {
			$this->addStat('error_no_result', null, $starttime, $fileId);
			$this->response->setStatusCode(404);
			$this->response->setJsonContent(['error' => 'No file found for file ID ' . $fileId]);
			return;
		}

		$this->OutputS3Page($page, $starttime);
	}

	// TODO: remove when old collections have been migrated
	public function GetFileByPath() {
		$starttime = microtime(true);
		
		$filePath = $this->request->getQuery('path', 'string', null);

		if(is_null($filePath)){
			$this->addStat('error_no_result', null, $starttime, null);
			$this->response->setStatusCode(400);
			$this->response->setJsonContent(['error' => 'path is required']);
			return;
		}
		
		$page = Pages::findFirst([
			'conditions' => 's3_key = :filePath:',
			'bind' => ['filePath' => $filePath]
		]);

		if ($page == NULL) {
			$this->addStat('error_no_result', $filePath, $starttime, null);
			$this->response->setStatusCode(404);
			$this->response->setJsonContent(['error' => 'No file found for file with path ' . $filePath]);
			return;
		}

		$this->OutputS3Page($page, $starttime);
	}

	private function OutputS3Page(Pages $page, $starttime){
		
		if ($page->s3 == 1) {
			$s3Client = new S3Client($this->getDI()->get('s3Config'));

			// TODO: might have to use streamWrapper if this is too slow / memory-consuming
			try {
				$result = $s3Client->getObject([
					'Bucket' => $page->s3_bucket,
					'Key' => $page->s3_key
				]);
				$this->response->setContentType($result['ContentType']);
				$this->response->setContent($result['Body']);
				$this->addStat(null, '/' . $page->s3_key, $starttime, $page->id);

			}
			catch(AwsException $e){
				$this->response->setStatusCode(404);
				$this->response->setJsonContent(['error' => 'S3 returned status code ' . $e->getAwsErrorCode() . ' for fileId ' . $page->id]);
				$this->addStat('error_s3_status_' . $e->getAwsErrorCode() , null, $starttime, $page->id);
			} 
			catch (Exception $e) {
				$this->response->setStatusCode(404);
				$this->response->setJsonContent(['error' => 'General exception: '. $e->getMessage()]);
				$this->addStat('error_no_result', null, $starttime, $page->id);
			}
		} else {
			$this->response->setStatusCode(404);
			$this->response->setJsonContent(['error' => 'Object not marked for S3: ' . $page->id]);
			$this->addStat('error_file_not_marked_s3', null, $starttime, $page->id);
		}
		
	}

	private function addStat($collection, $file, $starttime, $fileId = 'NULL') {
		$loadTime = microtime(true) - $starttime;
		
		try{
			$stats = new Stats();
			$stats->save([
				'collection' => $collection,
				'file' => $file,
				'loadTime' => $loadTime,
				'fileId' => $fileId,
				'ip' => $this->request->getServer('REMOTE_ADDR')
			]);
		}
		catch(Exception $e){
			$exception = new SystemExceptions();
			$exception->save([
				'type' => 'getfile_addStat_error',
				'details' => json_encode(['exception' => $e])
			]);
		}
	}

}

?>