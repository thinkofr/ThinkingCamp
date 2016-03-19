<?php
namespace Home\Model;

use Think\Model\RelationModel;

class UserModel extends RelationModel{
	
	protected $_link = array(
		
			'Project'=>array(
					'mapping_type' => self::HAS_ONE,
					'class_name' =>'Project',
					'foreign_key' => 'owneralias',
			 ),
			'Post' => array(
					'mapping_type' => self::HAS_ONE,
					'class_name' =>'Post',
					'foreign_key' => 'owneralias',
			),
			
			'Team'=>array(
					'mapping_type' => self::MANY_TO_MANY,
					'relation_table' => 'innovation_teamuserrelationship',
					'foreign_key' => 'useralias',
					'relation_foreign_key' => 'teamid',
			 ),
			
			
	);
	
	public function __construct(){
		parent::__construct();
		//echo 'UserModel';
	}
}

?>