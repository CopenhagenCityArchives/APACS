<?php

class Subposts extends \Phalcon\Mvc\Model {
    public function getSource() {
		return 'apacs_subposts';
	}
    
	public function initialize() {
		$this->belongsTo('pages_id', 'Pages', 'id');
        $this->belongsTo('posts_id', 'Posts', 'id');
		$this->skipAttributes(['last_update']);
	}
    
	/**
	 * Save a snippet of the image to which the post relates.
	 */
	public function SaveThumbImage() {
		$imageData = $this->getPages()->GetPageImageData();
		$image = imagecreatefromstring($imageData);

		if ($image == FALSE) {
			throw new RuntimeException('Could not find image located at ' . $this->getPages()->GetPageImagePath());
		}

		$imgHeight = imagesy($image);
		$imgWidth = imagesx($image);

		if ($imgHeight == FALSE || $imgWidth == FALSE) {
			throw new RuntimeException('Could not read dimensions of image loaded at ' . $this->getPages()->GetPageImagePath());
		}

		$rect = [];
		$rect['x'] = $imgWidth * $this->x;
		$rect['y'] = $imgHeight * $this->y;
		$rect['height'] = $imgHeight * $this->height;
		$rect['width'] = $imgWidth * $this->width;

		$resizedImage = imagecreatetruecolor($rect['width'], $rect['height']);
		if (!imagecopyresized($resizedImage, $image, 0, 0, $rect['x'], $rect['y'], $rect['width'], $rect['height'], $rect['width'], $rect['height'])) {
			throw new RuntimeException('could not crop image for post id ' . $this->id);
		}

		// free original image data
		imagedestroy($image);

		// capture image data with output buffering
		ob_start();
		$jpegCreated = imagejpeg($resizedImage, NULL, 60);
		$this->image = ob_get_contents();
		ob_end_clean();

		if ($jpegCreated == FALSE) {
			throw new RuntimeException('could not save resized image to temp path');
		}

		// free resized image
		imagedestroy($resizedImage);

		$this->complete = 0;
		if ($this->Save() == false) {
			throw new RuntimeException('could not save image data');
		}

		$this->image = null;
	}
}

?>