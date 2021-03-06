<?php

class Posts extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_posts';
	}

	public function initialize() {
		$this->belongsTo('pages_id', 'Pages', 'id');
		$this->hasMany('id', 'TasksPosts', 'tasks_id');
		$this->hasMany('id', 'Entries', 'posts_id');
		$this->skipAttributes(['last_update']);
	}

	/**
	 * Checks wheter an approximately post already exists on a page
	 * @param decimal $x
	 * @param decimal $y
	 */
	public function ApproximatePostExists() {
		//Check if there are existing posts for the page that are placed in the same spot
		$existingPosts = Posts::find(['conditions' => 'pages_id = :pagesId: AND ROUND(x,5) = ROUND(:x:,5) AND ROUND(y,5) = ROUND(:y:,5) AND complete = 1 AND id != :id:', 'bind' => [
			'pagesId' => $this->pages_id,
			'y' => $this->y,
			'x' => $this->x,
			'id' => $this->id,
		]]);

		return count($existingPosts) > 0;
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

	//Returns the next possible post for a page, calculated from previous posts for the page
	public function GetNextPossiblePostForPage($pageId, $columns, $rows) {
		$resultSet = Posts::find(['conditions' => 'pages_id = ' . $pageId . ' AND complete = 1', 'columns' => ['id', 'width', 'height', 'x', 'y']]);
		$posts = $resultSet->toArray();

		//No posts found. Return a post based on theoretical layout
		if (count($posts) == 0) {
			return array_merge(['x' => 0, 'y' => 0], $this->GetTheoreticalSize($columns, $rows));
		}

		//All posts set, return false
		if (count($posts) >= $columns * $rows) {
			return false;
		}

		$corners = $this->GetTheoreticalCorners($columns, $rows);

		//We have posts, but there is still unset posts on the page
		if (count($posts) > 0 && count($posts) < $columns * $rows) {

			//Getting distance, height and width from posts to theoretical corners
			$distancesX = [];
			$distancesY = [];
			$heights = [];
			$widths = [];
			foreach ($posts as $post) {
				$this->UnsetNearestCorner($post, $corners);
				$dist = [];
				$distancesX[] = $this->GetDistancesToNearestCorner($post, $corners, 'x')[0];
				$distancesY[] = $this->GetDistancesToNearestCorner($post, $corners, 'y')[0];
				$distances[] = $dist;
				$heights[] = $post['height'];
				$widths[] = $post['width'];
			}

			//Now we only have corners with no matching posts
			//Return the one nearest the global corner (0,0)
			//based on the average height, width and x and y offsets
			$bestGuessNotSetPost = [];
			$bestGuessNotSetPost['x'] = $corners[0]['x'] + (array_sum($distancesX) / count($distancesX));
			$bestGuessNotSetPost['y'] = $corners[0]['y'] + (array_sum($distancesY) / count($distancesY));
			$bestGuessNotSetPost['height'] = array_sum($heights) / count($heights);
			$bestGuessNotSetPost['width'] = array_sum($widths) / count($widths);
			return $bestGuessNotSetPost;
		}
	}

	private function GetDistancesToNearestCorner($post, $corners, $axis) {
		$distances = [];

		//Calculate distance to each corner
		foreach ($corners as $corner) {
			$distances[] = abs($post[$axis] - $corner[$axis]);
		}

		//Return the lowest
		sort($distances);
		return $distances;
	}

	private function UnsetNearestCorner($post, &$corners) {
		//Sortering corners by distance to post
		uasort($corners, function ($a, $b) use ($post) {
			$distanceToA = sqrt(($post['x'] * $post['x']) + ($a['x'] * $a['x']) + ($post['y'] * $post['y']) + ($a['y'] * $a['y']));
			$distanceToB = sqrt(($post['x'] * $post['x']) + ($b['x'] * $b['x']) + ($post['y'] * $post['y']) + ($b['y'] * $b['y']));
			if ($distanceToA == $distanceToB) {
				return 0;
			}

			return $distanceToA > $distanceToB;
		});

		//Unsetting the nearest
		unset($corners[0]);
		$corners = array_values($corners);
	}

	private function GetTheoreticalCorners($columns, $rows) {
		$corners = [];

		$xs = range(0, 1, 1 / $columns);
		$ys = range(0, 1, 1 / $rows);

		$corners = [];

		for ($i = 0; $i < count($xs); $i++) {
			for ($j = 0; $j < count($ys); $j++) {
				$corners[] = ['x' => $xs[$i], 'y' => $ys[$j]];
			}
		}

		return $corners;
	}

	private function GetTheoreticalSize($columns, $rows) {
		$size = [];
		$size['width'] = 1 / $columns;
		$size['height'] = 1 / $rows;

		return $size;
	}
}