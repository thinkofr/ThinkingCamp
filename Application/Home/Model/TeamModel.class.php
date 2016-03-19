<?php
namespace Home\Model;

use Think\Model\RelationModel;

class TeamModel extends RelationModel{
	
	protected $_link = array(
	
         	'User'=>array(
					'mapping_type' => self::MANY_TO_MANY,
					'relation_table' => 'innovation_teamuserrelationship',
					'foreign_key' => 'teamid',
					'relation_foreign_key' => 'useralias',
			),	

			'Project' => array(
					'mapping_type' => self::BELONGS_TO,
					'class_name' =>'Project',
					'foreign_key' => 'projectid',
			
			),
				
	);
	
	public function __construct(){
		parent::__construct();
	
		echo '\home TeamModel';
	}
}
?>
