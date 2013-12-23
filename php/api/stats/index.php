<?php   require_once("../../config.php");
	
	//API results are always returned text/plain...
	header("Content-Type: text/plain");

	//declare required variables for inc_validateRequest.php...
	$clientVars = array();
	$errors = array();
	
	//include file validates the request and inserts a request record...
	require_once("../inc_validateRequest.php");
	
	//coming soon....
	
	//echo $JSONresult;
	exit();

	
?>










