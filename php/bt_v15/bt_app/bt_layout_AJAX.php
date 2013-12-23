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

		//home screen comes everytime... tab1 value searched for to see if we have "both"
		$homeScreenNickname = fnGetReqVal("homeScreenNickname", "", $myRequestVars);
		$homeScreenItemId = "";
		$tab1_homeScreenNickname = fnGetReqVal("tab1_homeScreenNickname", "", $myRequestVars);

		//validate. We cannot have a homeScreenNickname AND tabs configured...
		if($homeScreenNickname != "" && $tab1_homeScreenNickname != ""){
			$bolPassed = false;
			$strMessage .= "Erase the Home Screen Nickname, or erase all the Tab Screen Nicknames in the Tab Configuration, you cannot have both.";
		}
		if($homeScreenNickname == "" && $tab1_homeScreenNickname == ""){
			$bolPassed = false;
			$strMessage .= "Enter a Home Screen Nickname for a non-tabbed layout, or configure at least one tab.";
		}		

		//if still good, look for the homeScreen itemId
		if($bolPassed & $homeScreenNickname != ""){
			//if we had a homeScreenNickname, find it's guid
			$strSql = "SELECT guid FROM " . TBL_BT_ITEMS;
			$strSql .= " WHERE nickname = '" . $homeScreenNickname . "' AND appGuid = '" . $appGuid . "'";
			$strSql .= " AND controlPanelItemType = 'screen' ";
			$homeScreenItemId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($homeScreenItemId == ""){
				$bolPassed = false;
				$strMessage .= "No screen was found with this nickname? Please try again.";
			}
		}


		if(!$bolPassed){
		
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Changes not saved! " . $strMessage . "</span>";
			exit();
			
		}else{
		
				
			//update
			if($bolPassed){
			
				//start by deleting  previously configured tabs for this app
				$strSql = " DELETE FROM " . TBL_BT_ITEMS;
				$strSql .= " WHERE appGuid = '" . $appGuid . "' AND itemType = 'BT_tab' ";
                fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
      			
				//next, set all the screens for this app to orderIndex=99 
				//so we have "no homescreen" (zero orderIndex is homescreen in non-tabbed apps)
				$strSql = " UPDATE " . TBL_BT_ITEMS . " SET orderIndex = '99' ";
				$strSql .= " WHERE appGuid = '" . $appGuid . "' AND controlPanelItemType = 'screen' ";
                fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				
				//if we have a homeScreenItemId, set that screens orderIndex to zero
				//so it becomes the home screen in the non-tabbed layout.
				if($homeScreenItemId != ""){
					$strSql = " UPDATE " . TBL_BT_ITEMS . " SET orderIndex = '0' ";
					$strSql .= " WHERE appGuid = '" . $appGuid . "' AND guid = '" . $homeScreenItemId . "' ";
					$strSql .= " AND controlPanelItemType = 'screen' ";
                	fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
					
					//app modified date
					$objApp->infoArray["modifiedUTC"] = $dtNow;
					$objApp->fnUpdate();

					//done...
					echo "<img src='../../images/green_dot.png' style='margin-right:5px;'>Saved!";
					exit();		
				}	
				
				//if we did not have a homeScreenItemId, and did have a	tab1_homeScreenNickname, create up to 
				//maxNumberOfTabs tabs - one for each tab configured on the calling screen...
				$maxNumberOfTabs = 5;
				for($x = 0; $x < $maxNumberOfTabs; $x++){
					$i = ($x + 1);
					
					//get form vars...
					$tab_homeScreenNickname = fnGetReqVal("tab" . $i . "_homeScreenNickname", "", $myRequestVars);
					$homeScreenItemId = "";
					$tab_label = fnGetReqVal("tab" . $i . "_label", "", $myRequestVars);
						if($tab_label == "") $tab_label = "label " . $i;
					$tab_iconName = fnGetReqVal("tab" . $i . "_iconName", "", $myRequestVars);
						if($tab_iconName == "") $tab_iconName = "blank.png";
					$tab_soundEffectName = fnGetReqVal("tab" . $i . "_soundEffectName", "", $myRequestVars);
					
					//get the guid of this homescreen..
					
					//create the tab ONLY if we have all the required elements.
					if($tab_homeScreenNickname != ""){
						$strSql = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $tab_homeScreenNickname . "' AND appGuid = '" . $appGuid . "'";
						$strSql .= " AND controlPanelItemType = 'screen' ";
						$homeScreenItemId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						if($homeScreenItemId != ""){
							
							//create an id
							$newItemGuid = strtoupper(fnCreateGuid());
							
							//create the json...
							$jsonVars = "{\"itemId\":\"" . $newItemGuid . "\", ";
							$jsonVars .= "\"itemType\":\"BT_tab\", ";
							$jsonVars .= "\"textLabel\":\"" . $tab_label. "\",  ";
							$jsonVars .= "\"iconName\":\"" . $tab_iconName. "\", ";
							$jsonVars .= "\"soundEffectFileName\":\"" . $tab_soundEffectName . "\", ";
							$jsonVars .= "\"homeScreenItemId\":\"" . $homeScreenItemId . "\"";
							$jsonVars .= "}";
							
							//create new BT_item..
							$objNewItem = new Bt_item();
							$objNewItem -> infoArray["guid"] = fnFormInput($newItemGuid);
							$objNewItem -> infoArray["parentItemGuid"] = "";
							$objNewItem -> infoArray["uniquePluginId"] = "";
							$objNewItem -> infoArray["loadClassOrActionName"] = "";
							$objNewItem -> infoArray["hasChildItems"] = "0";
							$objNewItem -> infoArray["loadItemGuid"] = $homeScreenItemId;
							$objNewItem -> infoArray["appGuid"] = fnFormInput($appGuid);
							$objNewItem -> infoArray["controlPanelItemType"] = "tab";
							$objNewItem -> infoArray["itemType"] = "BT_tab";
							$objNewItem -> infoArray["itemTypeLabel"] = "BT_tab";
							$objNewItem -> infoArray["nickname"] = "tab_ " . ($i - 1);
							$objNewItem -> infoArray["orderIndex"] = "0";
							$objNewItem -> infoArray["jsonVars"] = $jsonVars;
							$objNewItem -> infoArray["status"] = "active";
							$objNewItem -> infoArray["dateStampUTC"] = $dtNow;
							$objNewItem -> infoArray["modifiedUTC"] = $dtNow;
							$objNewItem -> fnInsert();

							//show ajax..
							echo "<span style='color:green;'>Tab " . $i . " Saved.<br/>";
							
						}else{
						
							//show for ajax
							echo "<span style='color:red;'>Tab " . $i . " NOT created - Screen Nickname, Label, and Icon required. You may have entered an invalid nickname</span><br/>";
						
						}
					}
					
				}	
				
				
			
			} //for each tab..
		
			//app modified date
			$objApp->infoArray["modifiedUTC"] = $dtNow;
			$objApp->fnUpdate();
		
		
		}//bolPassed
		
	}//was submitted
	
	//done
	exit();

	
	
	
?>