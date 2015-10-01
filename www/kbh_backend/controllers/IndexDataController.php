<?php

class IndexDataController extends \Phalcon\Mvc\Controller
{
	private $config;

	public function onConstruct()
	{
		$this->config = $this->getDI()->get('collectionConfigurationLoader');
	}
	
	public function insert($entityId)
	{
				/*
		Flow:
			Check user access rights (authorize and authenticate)
			Get configuration for the current collection and volume
			Validate input
			Build insert statement
			Save data
			Update stats
			Return response
		 */
		
		//if(!$user->hasAccess)
		//	throw new Exeception("You don't have access to this action");
		//	
		//if(!$user->isAuthorized)
		//	throw new Exception("Unauthorized access!");

        $this->getDI()->set('currentEntityId', function() use ($entityId){
            return $entityId;
        });

		$entity = new GenericIndexModel();
		$dataReceiver = new DataReceiver(new Phalcon\Http\Request());

		$valuesFieldsMap = $dataReceiver->GetDataFromFields('POST', $this->config->getIndexEntity($entityId)['fields']);

		$errorMessage = [];

		if(!$entity->save($valuesFieldsMap)){
			foreach($entity->getMessages() as $message){
				$errorMessage[] = $message;
			}

			if(count($errorMessage) > 0){
				array_unshift($errorMessage, 'Validateringsfejl!');
			}

			return false;
		}

		return true;
	}
}