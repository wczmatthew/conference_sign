<?php
return array(
	//'配置项'=>'配置值'
	//'SERVER_URL' => 'http://mboa.chint.com:8080/cwpu',
	//'SERVER_URL' => 'http://10.1.110.77:8080/cwpu',
	//'SERVER_URL' => 'http://localhost:8080/usercenter',
	//'SERVER_URL' => 'http://testmboa.chint.com:8080/cwpu',
	//'SERVER_URL' => 'http://mboa.chint.com:8080/cwpu',
	//'SERVER_URL' => 'http://localhost:8080/cwpu',
	'SYS_ID' => 'confc_sign',
	'MODULE_ID' =>'confc_sign',
	//'TMPL_EXCEPTION_FILE'   => 'Public/exception.html',
	'TMPL_ACTION_ERROR'=>'Public/error.html',
    	'TMPL_ACTION_SUCCESS'=>'Public/success.html',

	'SESSION_PREFIX' => 'conference_sign',
	'COOKIE_PREFIX'  =>	'conference_sign',

	'LOAD_EXT_CONFIG'       => 'db,log', //加载config文件
	
	'DEFAULT_MODULE'        =>  'Work',  
    'DEFAULT_CONTROLLER'    =>  'Manage', // 默认控制器名称
    'DEFAULT_ACTION'        =>  'index', // 默认操作名称
	
	'LOG_ACCESS' => 'on',//访问日志 on为开启，其它为关闭

	/*---------------------开始业务配置---------------------*/
	'TITLE_NAME'					=> '会议签到',

	//'SITE_URL'	=> 'http://10.1.66.25:9999',
	'SITE_URL'		=> 'http://localhost:9999',

	'APPLICATION_DIR_NAME'	=> 'Conference_Sign',
	/*-----------会议等级---------*/
	'CS_LEVEL' => array(
		'一般',
		'秘密',
		'机密',
		'绝密'
	),

);