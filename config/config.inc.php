<?php
	global $DEVELOP_ENVIRONMENT,$MY_CLASS,$MY_CONFIG,$MY_TEMPLATE, $MY_FILE, $MY_ROOT, $AZURE_ID, $API_CALENDAR;

	$DEVELOP_ENVIRONMENT = true;
	$MY_ROOT = $_SERVER["DOCUMENT_ROOT"] ."/";	
	$URL = "https://sigesproph.com.co/";
	
	$MY_CONFIG = $MY_ROOT ."config/";
	$MY_CLASS = $MY_ROOT ."class/";
	$MY_TEMPLATE = $MY_ROOT ."templates/";
	$MY_FILE = $MY_ROOT;
	$MY_XML = $MY_ROOT ."xml/";
	
	$AZURE_ID = '7c3ac2889b024778950387993c400298';  
	$AZURE_ID = '7c3ac2889b024778950387993c400298';  
	$AZURE_GRUPO ='personalproh2020'; 
	$API_CALENDAR ='126c242451661a36bd2c823e6efbb77dbaf366e5fde5d4578a588141fe7b0716'; 

	 
	
	date_default_timezone_set("America/Bogota");
	
	error_reporting(E_ALL);
	
?>