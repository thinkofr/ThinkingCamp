<?php
namespace Home\Model;

use Think\Model\RelationModel;

class FeatureModel extends RelationModel{

	public function __construct(){
		parent::__construct();

		echo 'Feature Model';
	}
}
?>