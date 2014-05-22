<?php
define('PATH_PTAPP', realpath('./PtApp'));
define('PATH_ABS',__DIR__);
define('DEBUG', TRUE);
include 'config.php';
include 'PtPHP/App.php';
PtApp::init($config);
PtApp::run();