<?php

class Fields extends \Phalcon\Mvc\Model
{

	protected $id;
	protected $name;
	protected $description;
	protected $helpText;
    protected $tableName;
    protected $fieldName;
    protected $normalizationTables;
    protected $validationRules;

    public function getSource()
    {
        return 'apacs_' . 'fields';
    }
}