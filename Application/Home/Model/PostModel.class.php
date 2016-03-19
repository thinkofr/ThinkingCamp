<?php
namespace Home\Model;

use Think\Model\RelationModel;

class PostModel extends RelationModel{

	public function __construct(){
		parent::__construct();

		echo 'Post Model';
	}
}
?>