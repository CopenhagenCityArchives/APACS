<?php
use Aws\S3\S3Client;

class Pages extends \Phalcon\Mvc\Model {

	public $id;
	protected $unitsId;
	protected $collectionId;

	private $status = [];
	static $publicFields = ['id', 'collection_id', 'unit_id'];

	const OPERATION_TYPE_CREATE = 'create';
	const OPERATION_TYPE_UPDATE = 'update';

	public function getSource() {
		return 'apacs_pages';
	}

	public function initialize() {
		$this->hasMany('id', 'Entries', 'page_id');
		$this->hasMany('id', 'TasksPages', 'page_id');
		$this->belongsTo('unit_id', 'Units', 'id');
	}

	public function GetPageImagePath() {
		return 's3://' . $this->s3_bucket . '/' . $this->s3_key;
	}

	public function GetPageImageData()
	{
		if(is_null($this->s3_bucket) || is_null($this->s3_key)){
			throw new Exception("Cannot get image data from S3. s3_bucket and s3_key properties are required. Page id: " . $this->id);
		}

		$s3Client = new S3Client($this->getDI()->get('s3Config'));

		// TODO: might have to use streamWrapper if this is too slow / memory-consuming
		try {
			$result = $s3Client->getObject([
				'Bucket' => $this->s3_bucket,
				'Key' => $this->s3_key
			]);
			
			return $result['Body'];
		}
		catch(AwsException $e){
			throw Exception('S3 returned status code ' . $e->getAwsErrorCode() . ' for fileId ' . $page->id);
		} 
		catch (Exception $e) {
			throw new Exception("Could not get image object from S3: " . $e);
		}
	}

	public function GetPageImageDataStream()
	{
		if(is_null($this->s3_bucket) || is_null($this->s3_key)){
			throw new Exception("Cannot get image data stream from S3. s3_bucket and s3_key properties are required. Page id: " . $this->id);
		}

		$s3Client = new S3Client($this->getDI()->get('s3Config'));

		// Register the stream wrapper from an S3Client object
		$s3Client->registerStreamWrapper();

		// Open a stream in read-only mode
		return fopen($this->GetPageImagePath(), 'r');
	}

	public function GetStatus() {
		return $this->status;
	}
}