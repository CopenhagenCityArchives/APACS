<?php 

class GenericIndexModel extends \Phalcon\Mvc\Model
{
    private $_entity;



    public function initialize()
    {
        $this->_entity = $this->
        getDI()->
        get('collectionConfigurationLoader')->
        getIndexEntity(
            $this->getDI()->get('currentEntityId')
        );
    }

	public function getSource()
    {
    	return $this->_entity['dbTableName'];
    }

    public function validation(){
        foreach($this->_entity['fields'] as $field){
            if($field['validationRegularExpression'] !== false){
                $this->validate(new \Phalcon\Mvc\Model\Validator\Regex([
                    'field' => $field['name'],
                    'pattern' => $field['validationRegularExpression'],
                    'message' => $field['validationErrorMessage']
                ]));
            }
        }

        if($this->validationHasFailed() == true){
            return false;
        }

        return true;
    }
}  