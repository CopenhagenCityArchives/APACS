<?php

class Posts extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'posts';
	}

	public function initialize() {
		$this->belongsTo('pages_id', 'Pages', 'id');
		$this->hasMany('id', 'TasksPosts', 'tasks_id');
		$this->hasMany('id', 'Entries', 'posts_id');
		$this->skipAttributes(['last_update']);
	}

	/**
	 * Save a snippet of the image to which the post relates.
	 */
	public function SaveThumbImage() {
		//	unlink('./temp.jpg');

		//	$imageData = file_get_contents($page->image_url);
		//var_dump();
		$image = imagecreatefromjpeg($this->getPages()->GetLocalPathToConcreteImage());

		$imgHeight = imagesy($image);
		$imgWidth = imagesx($image);

		$rect = [];
		$rect['x'] = $imgWidth * $this->x;
		$rect['y'] = $imgHeight * $this->y;
		$rect['height'] = $imgHeight * $this->height;
		$rect['width'] = $imgWidth * $this->width;

		//This only works in PHP > 5.5
		//imagejpeg(imagecrop($image, $rect), './temp.jpg', 60);

		//More usable solution
		$tempPath = './temp.jpg';
		$resizedImage = imagecreatetruecolor($rect['width'], $rect['height']);
		if (!imagecopyresized($resizedImage, $image, 0, 0, $rect['x'], $rect['y'], $rect['width'], $rect['height'], $rect['width'], $rect['height'])) {
			throw new RuntimeException('could not crop image for post id ' . $this->id);
		}

		if (!imagejpeg($resizedImage, $tempPath, 60)) {
			throw new RuntimeException('could not save resized image to temp path');
		}

		$this->image = file_get_contents($tempPath);
		unlink($tempPath);

		$this->complete = 0;
		if ($this->Save() == false) {
			throw new RuntimeException('could not save image data');
		}

		$this->image = null;
	}

	//Returns the next possible post for a page, calculated from previous posts for the page
	public function GetNextPossiblePostForPage($pageId, $columns, $rows) {
		$resultSet = Posts::find(['conditions' => 'pages_id = ' . $pageId, 'columns' => ['id', 'width', 'height', 'x', 'y']]);
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