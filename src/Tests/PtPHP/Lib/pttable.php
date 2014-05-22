<?php
include "PtPHP/Lib/PtTable.php";
include "PtPHP/Lib/PtDb.php";
PtDb::$config['default'] = array(
	'type'=>'mysql',
	'host'=>"localhost",
	'port'=>3306,
	'dbname'=>"game110_dev",
	'dbuser'=>"root",
	'dbpass'=>"root",
	'charset'=>"utf8",
);

$users = new PtTable("users");
$res = $users->row();
echo "<pre>";
print_r($res);
echo "</pre>";
highlight_file(__FILE__);

