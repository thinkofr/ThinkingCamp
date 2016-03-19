<?php
return array(
	//'配置项'=>'配置值'

	//mysql Global setting
	'DB_TYPE'=>'mysql',
	'DB_HOST'=>'localhost',
	'DB_USER'=>'root',
	'DB_PWD'=>'123',
	'DB_NAME'=>'innovationprojectdb',
	'DB_PORT'=>'3306',
	'DB_PREFIX'=>'innovation_',

	//Page debug tool
 	'SHOW_PAGE_TRACE'=>true,


	//'ACTION_SUFFIX'=> 'Action',

     //html 过滤
     'DEFAULT_FILTER'=>'htmlspecialchars',

	//set not start session when  start
	//'SESSION_AUTO_START'=>false,

	//for language
	'LANG_SWITCH_ON' => true,
	'LANG_AUTO_DETECT' => true,
	'LANG_LIST' => 'en-us',
	'VAR_LANGUAGE' => 'lang',


		//邮件配置
	// 'THINK_EMAIL' => array(
	// 	'SMTP_HOST'   => '163.184.146.3', //SMTP服务器
	// 	'SMTP_PORT'   => '465', //SMTP服务器端口
	// 	'FROM_EMAIL'  => 'InnovationIncubator@slb.com', //发件人EMAIL
	// 	'FROM_NAME'   => 'ThinkPHP', //发件人名称
	// 	'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）
	// 	'REPLY_NAME'  => '', //回复名称（留空则为发件人名称）
	// ),

);
?>
