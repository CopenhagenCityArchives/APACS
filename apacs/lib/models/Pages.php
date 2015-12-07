<?php

class Pages extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $unitsId;
	protected $collectionId;

    private $status = [];
    static $publicFields = ['id','collection_id','unit_id'];

    const OPERATION_TYPE_CREATE = 'create';
    const OPERATION_TYPE_UPDATE = 'update';

    public function getSource()
    {
        return 'apacs_' . 'pages';
    }

    public function initialize()
    {
        $this->hasMany('id', 'Entries', 'page_id');
        $this->hasMany('id', 'TasksPages', 'page_id');
        $this->belongsTo('unit_id', 'Units', 'id');
    }

    private function getImportCreateSQL()
    {
        return 'INSERT INTO ' . $this->getSource() . ' (concrete_page_id, collection_id, concrete_unit_id, tablename) SELECT :id, :collectionId, :unitId, ":table" FROM :table :conditions';
    }

    private function getImportUpdateSQL()
    {
        return 'UPDATE ' . $this->getSource() . ' LEFT JOIN :table ON ' . $this->getSource() . '.concrete_page_id = :table.:id SET tablename = ":table" :conditions';
    }

    public function Import($type, $collectionId, $idField, $unitIdField, $table, $conditions = NULL)
    {
        if($type == self::OPERATION_TYPE_CREATE && $this->dataAlreadyImported('apacs_pages', $collectionId)){
            $this->status = ['error' => 'pages are already imported (collection and tablename already exists']; 
            return false;
        }

        $sql = ($type == self::OPERATION_TYPE_UPDATE ? $this->getImportUpdateSQL() : $this->getImportCreateSQL());

        $sql = str_replace(':collectionId', $collectionId, $sql);
        $sql = str_replace(':id', $idField, $sql);
        $sql = str_replace(':unitId', $unitIdField, $sql);
        $sql = str_replace(':table', $table, $sql);
        $sql = str_replace(':conditions', $conditions == NULL ? '' : 'WHERE ' . $conditions , $sql);

        return $this->runQueryGetStatus($sql);
    }

    private function runQueryGetStatus($query)
    {
        $connection = $this->getDI()->get('database');
        $success = $connection->execute($query);

        if($success){
            $this->status = ["affected_rows" => $connection->affectedRows()];
        }
        else{
            $this->status = ["status" => "could not execute query", "error_message" => $connection->getErrorInfo()];
        }

        return $success;
    }

    private function dataAlreadyImported($type, $collectionId)
    {
        $sql = 'SELECT * FROM ' . $type . ' WHERE collection_id = \'' . $collectionId . '\' LIMIT 1';
        $resultSet = $this->getDI()->get('database')->query($sql);
        $resultSet->setFetchMode(Phalcon\Db::FETCH_ASSOC);
        $results = $resultSet->fetchAll();

        return count($results) > 0;
    }    

    public function GetStatus()
    {
        return $this->status;
    }    
}