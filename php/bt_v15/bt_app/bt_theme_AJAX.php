<?php   require_once("../../config.php");

	//who's logged in? loggedInUserGuid will be an empty string if no user is logged in...
	$loggedInUserGuid = "";
	if(isset($_COOKIE[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_COOKIE[APP_LOGGEDIN_COOKIE_NAME]);
	if($loggedInUserGuid == ""){
		if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	}
	
	if($loggedInUserGuid == ""){
		echo "<span style='color:red;'>Logged out</span>";
		exit();
	}	
		
		
	//init user object
	$thisUser = new User($loggedInUserGuid);
	$thisUser -> fnLoggedInReq($loggedInUserGuid);
		
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$BT_itemId = fnGetReqVal("BT_itemId", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appName = "";
	$nickname = "";
	$jsonVars = "";
	
	//app object...(also need BT_itemId)...
	if($appGuid == "" || $BT_itemId == ""){
		echo "invalid request";
		exit();
	}	
	
	//nickname
	$nickname = fnGetReqVal("nickname", "", $myRequestVars);

	//splash screen
	$splashScreenItemId = fnGetReqVal("splashScreenItemId", "", $myRequestVars);
	$splashScreenNickname = fnGetReqVal("splashScreenNickname", "", $myRequestVars);

	//if editing manually...
	$advancedEdit = fnGetReqVal("advancedEdit", "0", $myRequestVars);
	$advancedJSON = fnGetReqVal("advancedJSON", "", $myRequestVars);
	
	
	//init the App object using this app's guid...
	$objApp = new App($appGuid);
	
	//make sure user can manage this app...
	$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);
	
	///////////////////////////////////////////////////////
	//form posted
	if($isFormPost){
		
		//validate
		if($nickname == ""){
			$bolPassed = false;
			$strMessage .= "<br>Nickname required";
		}
		
		//make sure nickname is available
		if($bolPassed){
			$strSql = "SELECT id FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $nickname . "' AND appGuid = '" . $appGuid . "'";
			$strSql .= " AND guid != '" . $BT_itemId . "'";
			$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($tmpId != ""){
				$bolPassed = false;
				$strMessage .= "<br>Duplicate nickname, please choose again.";
			}		
		}
		
		//if a splashScreenItemId, make sure the splashScreenItemId exists...
		if($splashScreenNickname != ""){
			$strSql = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $splashScreenNickname . "' AND appGuid = '" . $appGuid . "'";
			$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($tmpId == ""){
				$bolPassed = false;
				$strMessage .= "<br>Slash screen with this nickname not found?";
			}else{
				$splashScreenItemId = $tmpId;
			}
		}else{
			//no splash screen nickname entered... 
			$splashScreenItemId = "";
		}
		
		
		if(!$bolPassed){
		
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Changes not saved! " . $strMessage . "</span>";
			exit();
			
		}else{
		
			//if we are NOT doing this manually...
			if($advancedEdit != "1"){
			
				//start json
				$jsonVars = "{\"itemId\":\"" . $BT_itemId . "\", ";
				$jsonVars .= "\"itemType\":\"BT_theme\", ";
				$jsonVars .= "\"itemNickname\":\"" . $nickname . "\", ";
				if($splashScreenItemId != ""){
					$jsonVars .= "\"splashScreenItemId\":\"" . $splashScreenItemId  . "\", ";
				}
				//loop through all the form vars that begin with "json_" to build the json..
				foreach($_POST as $key => $val){
					if(substr($key, 0, 5) == "json_"){
					
						//clean up the inputed form field value (prevent SQL injections!)...
						$val = fnFormInput($val);
						
						//if we have a value, add it to the jsonVar...
						if($val != "" && strtoupper($key) != "JSON_NICKNAME"){
							$jsonVars .= "\"" . str_replace("json_", "", $key) . "\":\"" . $val  . "\", ";
						}
						
					}//end if this form field begins with "json_"					
				}//end for each form field...
				
				//remove the last comma then cap the JSON...
				$jsonVars = fnRemoveLastChar($jsonVars, ",");
				$jsonVars .= "}";
			
			}else{
			
			
				//use the manually entered JSON data...
				if(isset($_POST["advanced"])){
					
					$advancedJSON = trim($_POST["advanced"]);
					if(get_magic_quotes_gpc()){
						//json data already escaped...
					}else{
						$advancedJSON = str_replace("'", "\'", $advancedJSON);
					}	
					
					//addend with a comma...
					$jsonVars = $advancedJSON;
								
				}
			
			}//not editing manually...
			
			//must have json!
			if(strlen($jsonVars) < 5){
				$bolPassed = false;
				$strMessage .= "<br>JSON data required.";
			}
			
			
			//update
			if($bolPassed){
			
				//update theme...
				$objBT_item_update = new Bt_item($BT_itemId);
				$objBT_item_update->infoArray["nickname"] = $nickname;
				$objBT_item_update->infoArray["jsonVars"] = $jsonVars;
				$objBT_item_update->infoArray["modifiedUTC"] = $dtNow;
				$objBT_item_update->fnUpdate();
				
				//app modified date
				$objApp->infoArray["modifiedUTC"] = $dtNow;
				$objApp->fnUpdate();
			
				//done....
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'><b>Saved!</b>";
				
				//if we are NOT using the advaned entry...
				if($advancedEdit != "1"){
				
					echo "<div style='padding-top:5px;color:#000000;'>";
						echo "<b>JSON Data for this Theme</b>";
					echo "</div>";
				
					echo "<div style='padding-top:5px;padding-bottom:5px;color:#000000;font-family:monospace;'>";
							echo $jsonVars;
					echo "</div>";
					
					//append the JSON data to the end of the result so we can show it in the "advanced" edit box...
					echo "^";
					echo $jsonVars;
				
				}else{
					
					//no need to print anything else, the "advanced" edit box already contains the user's entries...
				
				}//$advancedEdit != 1
				
			
			}else{
			
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Changes not saved! " . $strMessage . "</span>";
				exit();
			
			
			
			}//bolPassed		
		}//bolPassed		
	}//was submitted
	

	
	
	
?>


