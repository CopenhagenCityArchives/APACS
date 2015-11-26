<?php

use Athletic\AthleticEvent;

class IndexingEvent extends AthleticEvent
{
    private $fast;
    private $slow;
    private $data;

    public function setUp()
    {
        $this->fast = new Indexing\FastIndexer();
        $this->slow = new Indexing\SlowIndexer();
        $this->data = array('field' => 'value');
    }


    /**
     * @iterations 1000
     */
    public function fastIndexingAlgo()
    {
        for($i = 0; $i < 100; $i++){
        	$i++;
        }
    }

}