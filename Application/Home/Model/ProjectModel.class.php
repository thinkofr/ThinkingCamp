<?php
namespace Home\Model;

use Think\Model\RelationModel;

class ProjectModel extends RelationModel{
	
	protected $_link = array(
			'Team' => array(
					'mapping_type' => self::HAS_ONE,
					'class_name' =>'Team',
					'foreign_key' => 'projectid',
		
	          ),
			
			'Post'=> array(
					'mapping_type' => self::HAS_ONE,
					'class_name' =>'Feature',
					'foreign_key' => 'projectid',
					
			)
	);
	
	public function __construct(){
		parent::__construct();
	
		echo 'Project Model';
	}
}

?>