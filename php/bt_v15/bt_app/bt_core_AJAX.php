<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	if($guid == ""){
		echo "<span style='color:red;'>Logged out</span>";
		exit();
	}	
		
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);

	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$jsonVars = "";
	
	//need an appguid
	if($appGuid == ""){
		echo "invalid request";
		exit();
	}
	
	//vars from previous screen	
	$appName = fnGetReqVal("appName", "", $myRequestVars);
	$projectName = fnGetReqVal("projectName", "", $myRequestVars);
	$dataURL = fnGetReqVal("dataURL", "", $myRequestVars);
	$reportToCloudURL = fnGetReqVal("reportToCloudURL", "", $myRequestVars);
	$registerForPushURL = fnGetReqVal("registerForPushURL", "", $myRequestVars);
	$startGPS = fnGetReqVal("startGPS", "", $myRequestVars);
	$startAPN = fnGetReqVal("startAPN", "", $myRequestVars);
	$allowRotation = fnGetReqVal("allowRotation", "", $myRequestVars);
	$appAddress = fnGetReqVal("appAddress", "", $myRequestVars);
	$appCity = fnGetReqVal("appCity", "", $myRequestVars);
	$appState = fnGetReqVal("appState", "", $myRequestVars);
	$appZip = fnGetReqVal("appZip", "", $myRequestVars);
	$appLatitude = fnGetReqVal("appLatitude", "", $myRequestVars);
	$appLongitude = fnGetReqVal("appLongitude", "", $myRequestVars);
	
	//app object...
	if($appGuid == ""){
	
		echo "invalid request";
		exit();
	
	}else{
		
		//app object...
		$objApp = new App($appGuid);

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	}
	
	///////////////////////////////////////////////////////
	//form posted
	if($isFormPost){
		
		//validate
		if($appName == ""){
			$bolPassed = false;
			$strMessage .= "<br>App name required";
		}else{
			if(!fnIsAlphaNumeric($appName, true)){
				$bolPassed = false;
				$strMessage .= "<br>Application name invalid";
			}		
		}

		//project name		
		if(!fnIsAlphaNumeric($projectName, false)){
			$bolPassed = false;
			$strMessage .= "<br>Project name invalid";
		}		
		
		//first character of the project name cannot be a number
		if($bolPassed){
			$firstChar = substr($projectName, 0, 1);
			$letters = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
			if(!in_array(strtolower($firstChar), $letters)) {
				$bolPassed = false;
				echo "Project name invalid";
				exit();
			}			
		}
				
		//GPS, APN..
		if(!is_numeric($startGPS)) $startGPS = "0";
		if(!is_numeric($startAPN)) $startAPN = "0";
		
		//make sure app name is available
		if($bolPassed){
			$strSql = "SELECT id FROM " . TBL_APPLICATIONS;
			$strSql .= " WHERE name = '" . $appName . "' AND guid != '" . $appGuid . "' AND status != 'deleted'";
			$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($tmpId != ""){
				$bolPassed = false;
				$strMessage .= "<br>This control panel name is already in use, please choose again.";
			}		
		}
		
		//latitude && longitude required...
		if(strlen($appLatitude) > 0){
			if(!is_numeric($appLatitude)){
				$bolPassed = false;
					$strMessage .= "<br>Numbers only for latitude";
			}
		}else{
			$appLatitude = 0;
		}
		
		if(strlen($appLongitude) > 0){
			if(!is_numeric($appLongitude)){
				$bolPassed = false;
					$strMessage .= "<br>Numbers only for longitude";
			}
		}else{
			$appLongitude = 0;
		}
		
		
		if(!$bolPassed){
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Changes not saved! " . $strMessage . "</span>";
			exit();
			
		}else{
		
				
				//update
			if($bolPassed){
			
				//obj app
				$objApp->infoArray["name"] = $appName;
				$objApp->infoArray["projectName"] = $projectName;
				$objApp->infoArray["dataURL"] = $dataURL;
				$objApp->infoArray["cloudURL"] = $reportToCloudURL;
				$objApp->infoArray["registerForPushURL"] = $registerForPushURL;
				$objApp->infoArray["startGPS"] = $startGPS;
				$objApp->infoArray["startAPN"] = $startAPN;
				$objApp->infoArray["appAddress"] = $appAddress;
				$objApp->infoArray["appCity"] = $appCity;
				$objApp->infoArray["appState"] = $appState;
				$objApp->infoArray["appZip"] = $appZip;
				$objApp->infoArray["appLatitude"] = $appLatitude;
				$objApp->infoArray["appLongitude"] = $appLongitude;
				$objApp->infoArray["allowRotation"] = $allowRotation;
				$objApp->infoArray["modifiedUTC"] = $dtNow;
				$objApp->fnUpdate();
				
				//update the API key's owner name...
				$tmp = "UPDATE " . TBL_API_KEYS . " SET ownerName = 'App: " . fnFormInput($appName) . "' WHERE apiKey = '" . $objApp->infoArray["apiKey"] . "'";
				fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

				//done...
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'>Saved!";
				exit();		
			
			}
		
		}//bolPassed
		
	}//was submitted
	

	
	
	
?>

