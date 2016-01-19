<?php
namespace TestApp\Shell\Task;

use Cake\Console\Shell;

class ColorsTask extends Shell
{

	public function initialize()
    {
        parent::initialize();
        //$this->loadModel('Colors');
    }

    public function main()
    {
    	$this->loadModel('Colors');
    	$colors = $this->Colors->find('all')->count();
    	if($colors){
    		return 'Colors Exist';
    	}
    	return 'Colors Extinct';
    }
}