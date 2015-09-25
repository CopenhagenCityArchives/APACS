<?php 

class GenericIndexModel extends \Phalcon\Mvc\Model
{
	public function getSource()
    {
    	return $this->getDI()->get('tableNameHolder');
        return "insert_table";
    }

    public function setTable($table)
    {
    	$this->_source = $table;
    }

    public function validation(){
        $this->validate(new \Phalcon\Mvc\Model\Validator\Regex(array(
          "field" => 'lastname',
          'pattern' => '/^[0-9]{4}[-\/](0[1-9]|1[12])[-\/](0[1-9]|[12][0-9]|3[01])/'
        )));
         if ($this->validationHasFailed() == true) {
            return false;
        }
    }
}  