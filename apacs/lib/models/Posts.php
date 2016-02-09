<?php

class Posts extends \Phalcon\Mvc\Model {
	public function getSource() {
		return 'apacs_' . 'posts';
	}

	public function initialize() {
		$this->belongsTo('id', 'Pages', 'pages_id');
		$this->hasMany('id', 'TasksPosts', 'tasks_id');
		$this->hasMany('id', 'Entries', 'posts_id');
	}

	public function GetPostsByPage($pageId) {
		$posts = Posts::find(['conditions' => 'pages_id = ' . $pageId]);

		if (count($posts) == 0) {
			//TODO: Calculate posts based on page image size
		}

		return $posts;
	}

	/*private function CalculatePosts($x, $y, $width, $height, $postsX, $postsY)
		{
			$threshold = 0.8;
			//Go right
			while()

	*/
}