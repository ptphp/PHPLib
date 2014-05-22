PtPHP 文档
====
PtPHP 是一个PHP快速开发框架

##文档目录

	Project/
	  ├── PtAPP/
	  │   ├── Tpl/
	  │   ├── View/
	  │   ├── config.php
	  │   └── init.php
	  ├── PtPHP/
	  │   ├── Lib/
	  │   ├── Ext/
	  │   ├── Tests/
	  │   └── App.php
	  └── Public/
	      ├── static/
	      └── index.php
	      

## nginx 配置

	server {
		listen  80;
		server_name  dev.ptphp.com;
		root   C:\Users\joseph\Documents\GitHub\Core\Public;
		autoindex on;		

		location ~ .*\.(php|php5)?$ {
			fastcgi_pass   127.0.0.1:9000;	            
			fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
	}


## SAE config.yaml

	handle:
	- rewrite: if(is_file() && path ~ "^PtApp/(.*)") goto "/Public/index.php/$1"
	- rewrite: if(is_file() && path ~ "^PtPHP/(.*)") goto "/Public/index.php/$1"
	- rewrite: if(path=="/") goto "/Public/index.php"
	- rewrite: if(!is_dir() && !is_file() && path~"^(.*)$") goto "/Public/index.php/$1"

## Project/Public/index.php

	<?php
	define('PATH_PTAPP', realpath('../PtApp'));
	define('PATH_ABS',__DIR__);
	include PATH_PTAPP.'/init.php';
	PtApp::run();

## Project/PtApp/init.php

	<?php
	defined("PATH_ABS") or die('error:0');
	$config = include PATH_PTAPP.'/config.php';
	define('PATH_LIB_PTPHP', $config['path_lib_app']);
	include PATH_LIB_PTPHP.'/App.php';
	PtApp::init($config)
	
## Project/PtApp/config.php

	<?php
	defined("PATH_ABS") or die('error:0');
	return array(	
			'mode'=>'dev',
			'path_lib_app'=>realpath('../PtPHP'),
			'debug'=>true,
			'charset'=>'utf-8',
			'version'=>'0.0.1',
			'static_host'=>'',
			'view'=>array(				
					'dir'=>__dir__.'/View',
					'tpl'=>__dir__.'/Tpl',
					'theme'=>'default',
			),
			'admin'=>array(
					'username'=>'admin',
					'password'=>'admin888',
			),
			'db'=>array(
					'project'=>array(
							'type'=>'mysql',
							'host'=>'localhost',
							'port'=>'3306',
							'username'=>'root',
							'password'=>'root',
							'dbname'=>'ptphp',
					),
					'default'=>array(
							'type'=>'mysql',
							'host'=>'localhost',
							'port'=>'3306',
							'username'=>'root',
							'password'=>'root',
							'dbname'=>'ptphp',
					),
			),
	);