<?php
/**
 * Script para la generaci�n de CAPTCHAS
 *
 * @author  Jose Rodriguez <jose.rodriguez@exec.cl>
 * @license GPLv3
 * @link    http://code.google.com/p/cool-php-captcha
 * @package captcha
 * @version 0.3
 *
 */
session_start();
$c = $_GET['c'];
if($c == $_SESSION['captcha']){
	echo 0;
}else{
	echo 1;
}