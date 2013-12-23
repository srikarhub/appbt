<?php   require_once("../../config.php");
		require_once("../../includes/zip.php");
	
	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);

	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");
	
	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtNow = fnMySqlNow();
	
	//app vars...
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$project = fnGetReqVal("project", "", $myRequestVars);
	
	//app object...
	if(strlen($appGuid) < 3 || strlen($project) < 3){
		echo "invalid request (3)";
		exit();
	}else{
		
		//app object...
		$objApp = new App($appGuid);
		
		//can this person manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

  	}
	
	/*
		DOWNLOAD packages are streamed to the browser so we can prevent linking directly to the .zip's URL...
	*/
	
	//do we have the .zip file?
	$projectDownloadPath = rtrim(APP_PHYSICAL_PATH, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp/" . $project; 
	
	if(is_file($projectDownloadPath)){
		
		//output...
		header("Content-disposition: attachment; filename=" . $project);
		header("Content-type: application/zip"); 
		readfile($projectDownloadPath);
		
		//bail...
		exit();
		
	}else{
	
		echo "Invalid download request (6)";
		exit();
	
	}
	

?>