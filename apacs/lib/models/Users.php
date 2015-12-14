<?php

class Users extends \Phalcon\Mvc\Model
{
    public static $publicFields = ['id', 'userName', 'imageUrl'];

    public function getSource()
    {
        return 'apacs_' . 'users';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Errors', 'reporting_user_id');
        $this->hasMany('id', 'TasksUsers', 'user_id');
        $this->hasMany('id', 'Entries', 'user_id');
    }
}