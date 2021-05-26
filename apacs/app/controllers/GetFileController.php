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
			$this->addStat('error_path_not_set', null, $starttime, null);
			$this->response->setStatusCode(400);
			$this->response->setJsonContent(['error' => 'path is required']);
			return;
		}
		
		// Create artifical page object, as collections with paths does not have 
		// a representation in apacs_pages
		$page = new Pages();
		$page->id = null;
		$page->s3 = 1;
		$page->s3_bucket = 'kbhkilder';
		$page->s3_key = $filePath;

		$this->OutputS3Page($page, $starttime);
	}

	private function OutputS3Page(Pages $page, $starttime){
		
		$s3Client = new S3Client($this->getDI()->get('s3Config'));

		// Register the stream wrapper from an S3Client object
		$s3Client->registerStreamWrapper();

		// TODO: might have to use streamWrapper if this is too slow / memory-consuming
		try {	
			$BucketPath = 's3://' . $page->s3_bucket . '/' . $page->s3_key;

			// Open a stream in read-only mode
			if ($stream = file_get_contents($BucketPath)) {
				header('Content-Type: image/jpeg');
				header('Access-Control-Allow-Origin: *');
				// While the stream is still open
				while (!feof($stream)) {
					// Read 1024 bytes from the stream
					echo fread($stream, 1024);
				}
				// Be sure to close the stream resource when you're done with it
				fclose($stream);
				$this->addStat(null, '/' . $page->s3_key, $starttime, $page->id);
				die();
			}
			else{
				$this->response->setStatusCode(404);
				$this->response->setJsonContent(['error' => 'General exception: Could not open stream with path ' . $BucketPath]);
				$this->addStat('error_no_result', null, $starttime, $page->id);
				return;
			}
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