<?php
namespace Home\Controller;
use Think\Controller;

class EmptyController extends Controller{

	public function index(){
		echo 'can not find '.CONTROLLER_NAME;
	}
}

?>