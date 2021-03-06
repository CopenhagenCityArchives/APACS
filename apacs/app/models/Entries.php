<?php

class Entries extends \Phalcon\Mvc\Model {

	public function getSource() {
		return 'apacs_entries';
	}

	public function initialize() {
		$this->hasMany('id', 'Errors', 'entry_id');
		$this->belongsTo('page_id', 'Pages', 'id');
		$this->belongsTo('task_id', 'Tasks', 'id');
	}

	public function GetContext() {
		$query = 'SELECT Collections.id as collection_id, Collections.name as collection_name, Units.id as unit_id, Posts.id as post_id, Units.description as unit_description, Units.pages as unit_pages, Pages.id as page_id, Pages.page_number, Entries.id as entry_id, Entries.created as created, Entries.updated as updated, Users.username as user_name, Users.id as user_id FROM apacs_entries AS Entries LEFT JOIN apacs_posts as Posts ON Entries.posts_id = Posts.id LEFT JOIN apacs_pages as Pages ON Posts.pages_id = Pages.id LEFT JOIN apacs_units as Units ON Pages.unit_id = Units.id LEFT JOIN apacs_collections as Collections ON Units.collections_id = Collections.id LEFT JOIN apacs_users as Users ON Entries.users_id = Users.id WHERE Entries.id = :id';

		$resultSet = $this->getDI()->get('db')->query($query, ['id' => $this->id]);
		$resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);

		$result = $resultSet->fetchAll()[0];

		$result['kildeviser_url'] = 'https://www.kbharkiv.dk/kildeviser/#!?collection=5&item=' . $result['page_id'];

		return $result;
	}
}
