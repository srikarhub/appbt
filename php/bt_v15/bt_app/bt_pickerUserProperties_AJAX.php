<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//end here if not logged in because we are in the "shadowBox" window...
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
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$BT_userId = fnGetReqVal("BT_userId", "", $myRequestVars);
	
	//app object...
	if($appGuid == ""  || $BT_userId == ""){
	
		echo "invalid request";
		exit();
	
	}else{
		
		//app object...
		$objApp = new App($appGuid);
		$thisPage->pageTitle = $objApp->infoArray["name"] . " Control Panel";

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	}
	
	//update's
	$displayName = fnGetReqVal("displayName", "", $myRequestVars);
	$email = fnGetReqVal("email", "", $myRequestVars);
	$password = fnGetReqVal("password", "", $myRequestVars);
	$passwordConfirm = fnGetReqVal("passwordConfirm", "", $myRequestVars);
	$newPassword = "";
	
	///////////////////////////////////////////////////////
	//form posted
	if($isFormPost){
		
		//display name req.
		if($displayName == ""){
			$bolPassed = false;
			$strMessage .= "<br/>Display name required.";
		}else{
			if(!fnIsAlphaNumeric($displayName, true)){
				$bolPassed = false;
				$strMessage .= "<br/>Display name invalid.";
			}
		}
		
		//email address req.
		if(!fnIsEmailValid($email)){
			$bolPassed = false;
			$strMessage .= "<br/>Email address invalid.";
		}
		
		//updating password?
		if($password != ""){
			if(!fnIsAlphaNumeric($password, true)){
				$bolPassed = false;
				$strMessage .= "<br/>Password invalid.";
			}else{
				if(strtolower($password) != strtolower($passwordConfirm)){
					$bolPassed = false;
					$strMessage .= "<br/>Passwords don't match.";
				}else{
					$newPassword = md5($password);
				}
			}
		}		
		
		//if passed, check for duplicate display name, or email address...
		if($bolPassed){
			$tmp = "SELECT Count(id) FROM " . TBL_APP_USERS . " WHERE guid != '" . $BT_userId . "' AND appGuid = '" . $appGuid . "' AND displayName = '" . $displayName . "'";
			$iExists = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($iExists > 0){
				$bolPassed = false;
				$strMessage .= "<br/>Display name not available, please choose another display name.";
			}
		}
		if($bolPassed){
			$tmp = "SELECT Count(id) FROM " . TBL_APP_USERS . " WHERE guid != '" . $BT_userId . "' AND appGuid = '" . $appGuid . "' AND email = '" . $email . "'";
			$iExists = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($iExists > 0){
				$bolPassed = false;
				$strMessage .= "<br/>Email address already in use, please use another email address.";
			}			
		}
		
		if(!$bolPassed){
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Changes not saved! " . $strMessage . "</span>";
			exit();
		}else{
				
			//upate user
			$objUser = new Appuser($BT_userId);
			$objUser->infoArray["displayName"] = $displayName;
			$objUser->infoArray["email"] = $email;
			if($newPassword != ""){
				$objUser->infoArray["encLogInPassword"] = $newPassword;
			}
			$objUser->infoArray["modifiedUTC"] = $dtNow;
			$objUser->fnUpdate();
			
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'>Saved!";
			exit();
				
		}//bolPassed	
			
	}//was submitted
	
	//done
	exit();
	
	
?>

