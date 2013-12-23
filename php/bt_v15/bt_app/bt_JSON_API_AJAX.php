<?php   require_once("../../config.php");

	//who's logged in? loggedInUserGuid will be an empty string if no user is logged in...
	$loggedInUserGuid = "";
	if(isset($_COOKIE[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_COOKIE[APP_LOGGEDIN_COOKIE_NAME]);
	if($loggedInUserGuid == ""){
		if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	}
	
	//plugins table constant is different on self hosted servers..
	if(!defined("TBL_PLUGINS")){
		if(defined("TBL_BT_PLUGINS")){
			define("TBL_PLUGINS", TBL_BT_PLUGINS);
		}
	}
	
	//returns values from array or ""...
	function fnGetPostVal($key, $array){
		return (isset($array[$key]) ? $array[$key] : "");
	}
	
	//return JSON data...
	header("Content-type: text/plain");

	//track errors...
	$errors = array();

	//track all form fields and values...
	$requestVars = array();
	
	//end request if user is not logged in...
	if($loggedInUserGuid == ""){
		$errors[] = "You are not logged in.";
	}	
		
	//request method must be POST...
	if(!isset($_SERVER["REQUEST_METHOD"])){
		$errors[] = "Request method not determined. Must be HTTP POST.";
	}else{
		if(strtoupper($_SERVER['REQUEST_METHOD']) != "POST"){
			$errors[] = "Request method not POST. HTTP Post required.";
		}else{
		
			//get all the form fields and values posted...
			$requestVars = array();
			foreach($_POST as $key => $value){
				$requestVars[$key] = fnFormInput($value);
			}
		
		}
	}
	
		
	//all good?
	if(count($errors) < 1){
	
		//init user object for the logged in user...
		$objLoggedInUser = new User($loggedInUserGuid);
		
	}
	
	//vars...
	$dtNow = fnMySqlNow();
	$jsonSaved = "";
	$command = fnGetPostVal("command", $requestVars);
	$appGuid = fnGetPostVal("appGuid", $requestVars);
	$BT_itemId = fnGetPostVal("BT_itemId", $requestVars);
	$childItemId = fnGetPostVal("childItemId", $requestVars);

	$itemType = "";
	$screenNickname = "";
	$parentItemGuid = "";
	
	//required fields...
	if(strlen($command) < 1) $errors[] = "command required";
	if(strlen($appGuid) < 1) $errors[] = "appGuid required";
	if(strlen($BT_itemId) < 1) $errors[] = "BT_itemId required";
	
	//create an app object...
	if(count($errors) < 1){
		$objApp = new App($appGuid);
		if($objApp->fnCanManageApp($loggedInUserGuid, $objLoggedInUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"])){
			//all good, fnCanManageApp will end execution if invalid..	
		}
	}
	
	
	//create a screen object....
	if(count($errors) < 1){
		
		//workign with a screen or a childItem?...
		if(strlen($childItemId) > 0){
			$objBT_item = new Bt_item($childItemId);
		}else{
			$objBT_item = new Bt_item($BT_itemId);
		}
		$itemType = $objBT_item->infoArray["itemType"];
		$parentItemGuid = $objBT_item->infoArray["parentItemGuid"];
		$screenNickname = $objBT_item->infoArray["nickname"];
	}
	
	//must have an itemType...
	if(strlen($itemType) < 1) $errors[] = "itemType not found?";
	
	//if we don't have a parentItemGuid then we must have a nickname...
	if(strlen($parentItemGuid) < 1){
		if(strlen($screenNickname) < 1) $errors[] = "nickname not found?";
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//saveJSONProperties (save buttons under each property)
	if(strtoupper($command) == "SAVEJSONPROPERTIES" && count($errors) < 1){
		
		//return...
		$ret = "";
		
		//nickname...
		$screenNickname = fnGetPostVal("json_itemNickname", $requestVars);
		if(strlen($parentItemGuid) < 1){
			if(strlen($screenNickname) < 1){
			 	$errors[] = "Nickname required";
			}
		}
		
		//if a form field exists named "json_itemNickname" it cannot be blank...
		if(isset($_POST["json_itemNickname"])){
			if(strlen($_POST["json_itemNickname"]) < 1){
			 	$errors[] = "Nickname required";
			}
		}
		
		//make sure another item does not exist with this nickname...
		if(strlen($parentItemGuid) < 1){
			if(count($errors) < 1){
				$strSql = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $screenNickname . "' ";
				$strSql .= " AND guid != '" . $BT_itemId . "'";
				$strSql .= " AND appGuid = '" . $appGuid . "'";
				$strSql .= " AND status != 'deleted'";
				$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($tmpId != ""){
					$errors[] = "nickname already in use, no duplicates allowed";
				}
			}
		}
		
		
		//right button...
		$navBarRightButtonType = fnGetPostVal("json_navBarRightButtonType", $requestVars);
		$navBarRightButtonTapLoadScreenNickname = fnGetPostVal("json_navBarRightButtonTapLoadScreenNickname", $requestVars);
		$navBarRightButtonTapLoadScreenItemId = "";
		
		//context menu...
		$contextMenuNickname = fnGetPostVal("json_contextMenuNickname", $requestVars);
		$contextMenuItemId = "";
		
		//if we are using a right-button, we need the id of the screen to load from the nickname entered...
		if(count($errors) < 1){
			if(strlen($navBarRightButtonTapLoadScreenNickname) > 1){
				$strSql = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $navBarRightButtonTapLoadScreenNickname . "' ";
				$strSql .= " AND appGuid = '" . $appGuid . "'";
				$strSql .= " AND controlPanelItemType = 'screen' ";
				$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($tmpId == ""){
					$errors[] = "No screen found with the nickname entered for the right nav button";
				}else{
					$navBarRightButtonTapLoadScreenItemId = $tmpId;
				}
			}else{
				$navBarRightButtonTapLoadScreenItemId = "";
			}		
		}
		
		//if right nav button is 'home button' and no load screen nickname was entered.
		if($navBarRightButtonType == "home" && $navBarRightButtonTapLoadScreenItemId == ""){
			$navBarRightButtonTapLoadScreenItemId = "goHome";
		}
		
		
		//if we are using a context menu, we need the id of the screen to load from the nickname entered...
		if(count($errors) < 1){
			if(strlen($contextMenuNickname) > 1){
				$strSql = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $contextMenuNickname . "' ";
				$strSql .= " AND appGuid = '" . $appGuid . "'";
				$strSql .= " AND controlPanelItemType = 'menu' ";
				$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($tmpId == ""){
					$errors[] = "No context menu found with this nickname";
				}else{
					$contextMenuItemId = $tmpId;
				}
			}else{
				$contextMenuItemId = "";
			}			
		}
		
		
		//errors?
		if(count($errors) > 0){
			
			//build list of error messages...
			$tmpErrors = "";
			foreach($errors as $value){
				$tmpErrors .= $value . ", ";
			}
			$tmpErrors = rtrim($tmpErrors, ", ");
			
			$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
			
		}else{
			
			//saved JSON...
			$jsonSaved = "{";
				$jsonSaved .= "\"itemId\":\"" . $BT_itemId . "\", ";
				$jsonSaved .= "\"itemType\":\"" . $itemType . "\", ";
			
			
				//add the rest of the properties...
				foreach($requestVars as $key => $val){
				
					//only look at keys that begin with "json_"...
					if(strtoupper(substr($key, 0, 5)) == "JSON_"){
	
						//if this is the "right button load screen" field, use the id for the screen we found above (on line 88)...
						if($key == "json_navBarRightButtonTapLoadScreenItemId"){
							$val = $navBarRightButtonTapLoadScreenItemId;
						}
	
						//if this is the "context menu" field, use the id for the menu we found above...
						if($key == "json_contextMenuItemId"){
							$val = $contextMenuItemId;
						}
						
						//if we have a value, add it to the jsonVars...
						if($val != ""){
							$jsonSaved .= "\"" . str_replace("json_", "", $key) . "\":\"" . $val  . "\", ";
						}
						
						
					}//end if this form field begins with "json_"					
				}//end for each form field...
				
				//clean up last comma...
				$jsonSaved = rtrim($jsonSaved, ", ");
		
			//cap json...
			$jsonSaved .= "}";
			
			//update screen...
			$objBT_item->infoArray["nickname"] = $screenNickname;
			$objBT_item->infoArray["jsonVars"] = $jsonSaved;
			$objBT_item->infoArray["modifiedUTC"] = $dtNow;
			$objBT_item->fnUpdate();
			
			//update app
			$objApp->infoArray["modifiedUTC"] = $dtNow;
			$objApp->fnUpdate();
			
			//return JSON values...
			$ret = "{\"result\":\"success\", \"savedJSON\":" . fnFormOutput($jsonSaved) . "}";
		
		}
		
		//print the JSON...
		echo $ret;
		exit();
		
	
	}//saveJSONProperties (save buttons under each property)
	///////////////////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////////////////
	//saveJSONScreenData (save button under raw json text area)
	if(strtoupper($command) == "SAVEJSONSCREENDATA" && count($errors) < 1){
		
		//return...
		$ret = "";
		
		//get the raw json data from the textarea...
		$screenJson = $_POST["screenJson"];
		if(strlen($screenJson) < 5){
			$errors[] = "JSON properties required";
		}
		
		//errors? 
		if(count($errors) < 1){
		
			//make sure the textarea had valid json data...
			$json = new Json; 
			$decoded = $json->unserialize($screenJson);
			if(!is_object($decoded)){
				$errors[] = "Value is not valid JSON. Did you use the JSON validator tool?";
			}else{
			
				//itemId required...
				if(array_key_exists("itemId", $decoded)){
					 if($decoded->itemId != $BT_itemId){
						$errors[] = "The itemId property cannot be modified here";
					 }
				}else{
					$errors[] = "The itemId property does not exist in the JSON? This is required";
				}

				//itemType required...
				if(array_key_exists("itemType", $decoded)){
					 if($decoded->itemType != $itemType){
						$errors[] = "The itemType property cannot be modified here";
					 }
				}else{
					$errors[] = "The itemType property does not exist in the JSON? This is required";
				}
				
				//itemNickname required if we DO NOT have a parentItemGuid...
				if(strlen($parentItemGuid) < 1){

					if(array_key_exists("itemNickname", $decoded)){
						 
						//make sure nickname is available...
						$screenNickname = fnFormInput($decoded->itemNickname);
						
						if(count($errors) < 1){
							$strSql = "SELECT id FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $screenNickname . "' ";
							$strSql .= " AND appGuid = '" . $appGuid . "'";
							$strSql .= " AND guid != '" . $BT_itemId . "'";
							$strSql .= " AND controlPanelItemType = 'screen' ";
							$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
							if($tmpId != ""){
								$errors[] = "The itemNickname you entered is already in use. Duplicates not allowed";
							}
						}
						 
					}else{
						$errors[] = "The itemNickname property does not exist in the JSON? This is required";
					}
				} //parentItemGuid...
				
				//childItems are NOT allowed here...
				if(array_key_exists("childItems", $decoded)){
					$errors[] = "childItems cannot be entered or modified here";
				}
				
				
			
			}//valid json..		
		}//errors...

		//errors?
		if(count($errors) > 0){
			
			//build list of error messages...
			$tmpErrors = "";
			foreach($errors as $value){
				$tmpErrors .= $value . ", ";
			}
			$tmpErrors = rtrim($tmpErrors, ", ");
			
			$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
		
		}else{
		
			//update screen...
			$objBT_item->infoArray["nickname"] = $screenNickname;
			$objBT_item->infoArray["jsonVars"] = $screenJson;
			$objBT_item->infoArray["modifiedUTC"] = $dtNow;
			$objBT_item->fnUpdate();
			
			//update app
			$objApp->infoArray["modifiedUTC"] = $dtNow;
			$objApp->fnUpdate();
			
			//return JSON values...
			$ret = "{\"result\":\"success\", \"savedJSON\":" . fnFormOutput($screenJson) . "}";
		
		}//errors...
		
		//return...
		echo $ret;
		exit();
		
	}//saveJSONScreenData (save button under raw json text area)
	///////////////////////////////////////////////////////////////////////////////////////////
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//getPluginOptions (used in drop-down lists when adding childItems)
	if(strtoupper($command) == "GETPLUGINOPTIONS" && count($errors) < 1){
		
		//return data...
		$ret = "";

		$objPlugin = new Plugin();
		$ret = $objPlugin->fnGetPluginOptions($loggedInUserGuid);
		
		//return...
		echo $ret;
		exit();
			

	}//getPluginOptions...
	///////////////////////////////////////////////////////////////////////////////////////////
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//getChildItems (used in child items HTML sections)
	if(strtoupper($command) == "GETCHILDITEMS" && count($errors) < 1){
		
		//return data...
		$ret = "";
		
		//errors?
		if(count($errors) > 0){
			
			//build list of error messages...
			$tmpErrors = "";
			foreach($errors as $value){
				$tmpErrors .= $value . ", ";
			}
			$tmpErrors = rtrim($tmpErrors, ", ");
			
			$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
		
		}else{
		
				//get total number of childItems...
				$strSql = "SELECT Count(*) FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' AND parentItemGuid = '" . $BT_itemId . "'";
				$numRows = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		
				///append total rows...
				$ret .= "\"startIndex\":\"0\", ";
				$ret .= "\"totalNumberOfChildItems\":\"" . $numRows . "\", ";
		
				//start child items...
				$ret .= "\"childItems\":[";
					if($numRows > 0){
					
						$strSql = " SELECT I.id, I.guid, I.jsonVars, I.orderIndex, I2.nickname AS loadScreenNickname  ";
						$strSql .= " FROM " . TBL_BT_ITEMS . " AS I ";
						$strSql .= " LEFT JOIN " . TBL_BT_ITEMS . " AS I2 ON I.loadItemGuid = I2.guid ";
						$strSql .= " WHERE I.appGuid = '" . $appGuid . "' AND I.parentItemGuid = '" . $BT_itemId . "'";
						$strSql .= " AND I.status != 'deleted' ";
						$strSql .= " ORDER BY I.orderIndex ASC";
						$strSql .= " LIMIT  0, 300";
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
						if($res){
							while($row = mysql_fetch_array($res)){
							
								//add the json for this child row, append orderIndex and loadScreenNickname...
								if($row["jsonVars"] != ""){
									
									$json = new Json; 
									$decoded = $json->unserialize($row["jsonVars"]);
									if(is_object($decoded)){
										
										//add orderIndex and "loadScreenNickname" to the existing object...
										$tmp = $json->serialize($decoded);
										
										//if we have a loadScreenNickname...
										if(strlen($row["loadScreenNickname"]) > 0){
											$tmp = str_replace("}", ", \"loadScreenNickname\":\"" . $row["loadScreenNickname"] . "\"}", $tmp);
										}
										
										//if we have an order index...
										if(strlen($row["orderIndex"]) > 0){
											$tmp = str_replace("}", ", \"orderIndex\":\"" . $row["orderIndex"] . "\"}", $tmp);
										}
										
										//append JSON for this childItem...					
										$ret .=  "\n" . $tmp . ",";
								
									}
								
								}
																	
							}
						}//if res
				}//numRows...
				
				//remove last comma
				$ret = fnRemoveLastChar($ret, ",");
				$ret .= "\n]";
			
			//return JSON values...
			$ret = "{\"result\":\"success\", " . $ret . "}";
		
		}//errors...
		
		//return...
		echo $ret;
		exit();
			

	}//getChildItems...
	///////////////////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////////////////
	//addChildItem (used in child items HTML sections)
	if(strtoupper($command) == "ADDCHILDITEM" && count($errors) < 1){
		
		//return data...
		$ret = "";
		
		//must have childItemAddJson...
		$childItemAddJson = "";
		if(isset($_POST["childItemAddJson"])){
			$childItemAddJson = $_POST["childItemAddJson"];
		}else{
			$errors[] = "childItemAddJson required";
		}
		
		//childItemJson must be valid JSON data...
		$json = new Json; 
		$decoded = $json->unserialize($childItemAddJson);
		if(!is_object($decoded)){
			$errors[] = "childItemAddJson is not valid JSON data";
		}		
		
		
		
		/*
			We are either adding a childRow OR a childRow AND a new screen...
			If we are adding a new screen, we will have...
			
			addScreenNickname = the nickname of the screen to add or the name of the new screen.
			addPluginUniqueId = tells us which plugin we are adding.
		*/
		
		//vars to add new item and possible new screen...
		$newItemId = strtoupper(fnCreateGuid());
		$bolCreateNewScreen = false;
		$bolNicknameRequired = false;
		$newScreenItemGuid = "";
		$newItemType = "";
		$newItemTitleText = "";
		$newScreenNickname = "";
		$newScreenPluginType = "";
		$newOrderIndex = 1;
		$newItemJson = "";
		
		//childItemAddJson is valid JSON...
		if(count($errors) < 1){

			//if JSON data uses "addScreenScreenNickname" we validate the nickname...
			if(isset($decoded->addScreenNickname)){
				$bolNicknameRequired = true;
				$newScreenItemGuid = strtoupper(fnCreateGuid());
			}
			
			//childItemAddJson must have an item type...
			if(isset($decoded->itemType)){
				$newItemType = $decoded->itemType;
			}else{
				$errors[] = "itemType required";
			}
			
			//if we are using titleText...
			if(isset($decoded->titleText)){
				$newItemTitleText = $decoded->titleText;
			}else{
				$newItemTitleText = "No title text";
			}
			
			//if we are loading an new screen...
			if(isset($decoded->addScreenNickname)){
				$newScreenNickname = $decoded->addScreenNickname;
			}
			if(strlen($newScreenNickname) < 1 && $bolNicknameRequired){
				$errors[] = "Existing nickname or a new nickname required";
			}
			
			//if we are creating a new screen...
			if(isset($decoded->addPluginType)){
				$newScreenPluginType = $decoded->addPluginType;
			}
			
			//must have a newScreenNickname if adding a new screen...
			if(strlen($newScreenNickname) > 0 && $bolNicknameRequired){
			
				//if we have a newScreenPluginType....
				if(strlen($newScreenPluginType) < 1){
					$bolCreateNewScreen = false;
					
					//selected an existing screen to connect to...
					$tmp = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . fnFormInput($newScreenNickname) . "'";
					$tmp .= " AND appGuid = '" . $appGuid . "'";
					$tmp .= " AND controlPanelItemType = 'screen' ";
					$tmp .= " AND status != 'deleted' ";
					$tmp .= " LIMIT 0, 1";
					$newScreenItemGuid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
					if(strlen($newScreenItemGuid) < 2){
						$errors[] = "No screen found with this nickname. Enter a nickname for an existing screen or choose a plugin type from the drop down list to create a new screen";
					}
					
				}
			
			}
			
			//if we have a newScreenNickname AND a newScreenPluginType....
			if(strlen($newScreenPluginType) > 1){
				$bolCreateNewScreen = true;
			}//creating a new screen...		
			
			//continue....
			if(count($errors) < 1){
				
				//get the next available orderIndex...
				$tmp = "SELECT MAX(orderIndex) FROM " . TBL_BT_ITEMS;
				$tmp .= " WHERE parentItemGuid = '" . $BT_itemId . "'";
				$tmp .= " AND appGuid = '" . $appGuid . "'";
				$tmp .= " AND itemType = '" . $newItemType . "' ";
				$tmp .= " AND status != 'deleted' ";
				$tmp .= " LIMIT 0, 1";
				$newOrderIndex = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if(!is_numeric($newOrderIndex)){
					$newOrderIndex = 1;
				}else{
					$newOrderIndex = ($newOrderIndex + 1);
				}
				
				
				//add the rest of the properties...
				$newItemJson = "{";
				
				//add itemId, itemType and newScreenItemGuid...
				$newItemJson .= "\"itemId\":\"" . $newItemId . "\", ";
				$newItemJson .= "\"itemType\":\"" . $newItemType . "\"";
				if(strlen($newScreenItemGuid) > 0){
					$newItemJson .= ", \"loadScreenWithItemId\":\"" . $newScreenItemGuid . "\"";
				}			
				
				//add the rest of the JSON vars from the row form post...
				foreach($decoded as $key => $val){
				
					//ignore these keys...
					if(strtoupper($key) != "ITEMID" 
						&& strtoupper($key) != "ITEMTYPE" 
						&& strtoupper($key) != "LOADSCREENWITHITEMID"
						&& strtoupper($key) != "ADDSCREENNICKNAME"
						&& strtoupper($key) != "ADDPLUGINTYPE"){
						
						//if the value is NOT an array...(ignore child items)...
						if(!is_array($val)){
							if(strlen($val) > 0){
								$newItemJson .= ", \"" . $key . "\":\"" . $val . "\"";
							}
						}
						
					}
					
				}//for each...
			}//count errors...
			
			//cap the json...
			$newItemJson .= "}";
			
		}else{
			$errors[] = "childItemAddJson is not valid JSON data";
		}
		
		//errors?
		if(count($errors) > 0){
			
			//build list of error messages...
			$tmpErrors = "";
			foreach($errors as $value){
				$tmpErrors .= $value . ", ";
			}
			$tmpErrors = rtrim($tmpErrors, ", ");
			
			$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
		
		}else{
		
			//if NOT adding a new screen...just add the menu item...
			if(!$bolCreateNewScreen){

				//info about the existing screen we are loading...
				$objScreenItem = new Bt_item($newScreenItemGuid);

				//create the new childItem...
				$objChildItem = new Bt_item("");
				$objChildItem->infoArray["guid"] = $newItemId;
				$objChildItem->infoArray["parentItemGuid"] = $BT_itemId;;
				$objChildItem->infoArray["uniquePluginId"] = "";
				$objChildItem->infoArray["loadClassOrActionName"] = $objScreenItem->infoArray["itemType"];
				$objChildItem->infoArray["hasChildItems"] = "0";
				$objChildItem->infoArray["loadItemGuid"] = $objScreenItem->infoArray["guid"];
				$objChildItem->infoArray["appGuid"] = $appGuid;
				$objChildItem->infoArray["controlPanelItemType"] = "childItem";
				$objChildItem->infoArray["itemType"] = $newItemType;
				$objChildItem->infoArray["itemTypeLabel"] = "Child Item";
				$objChildItem->infoArray["nickname"] =  $newItemTitleText;
				$objChildItem->infoArray["orderIndex"] = $newOrderIndex;
				$objChildItem->infoArray["jsonVars"] = $newItemJson;
				$objChildItem->infoArray["status"] = "active";
				$objChildItem->infoArray["dateStampUTC"] = $dtNow;
				$objChildItem->infoArray["modifiedUTC"] = $dtNow;
				$objChildItem->fnInsert();
			
			}
			
			
			//if we're creating a new screen...
			if($bolCreateNewScreen){

				//append a count to the nickname if a screen already exists with this nickname...
				$tmp = "SELECT Count(id) FROM " . TBL_BT_ITEMS;
				$tmp .= " WHERE appGuid = '" . $appGuid . "'";
				$tmp .= " AND uniquePluginId = '" . $newScreenPluginType . "' ";
				$tmp .= " AND nickname = '" . fnFormInput($newScreenNickname) . "' ";
				$existingCount = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($existingCount != "" && $existingCount != "0" && $existingCount != 0){
					$newScreenNickname = $newScreenNickname . " (" . ($existingCount + 1) . ")";
				}
			
				//get info about the plugin we are adding....			
				$tmpSql = "SELECT category, displayAs, loadClassOrActionName, hasChildItems, defaultJsonVars, webDirectoryName ";
				$tmpSql .= " FROM " . TBL_PLUGINS . " WHERE uniquePluginId = '" . $newScreenPluginType . "' LIMIT 0, 1";
				$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($res){
					$row = mysql_fetch_array($res);
					$category = $row["category"];
					$displayAs = $row["displayAs"];
					$loadClassOrActionName = $row["loadClassOrActionName"];
					$hasChildItems = $row["hasChildItems"];
					$defaultJsonVars = $row["defaultJsonVars"];
					$webDirectoryName = $row["webDirectoryName"];
				}
			
				//replace possible "nickname" in defaultJsonVars...
				$defaultJsonVars = str_replace("[itemNickname]", $newScreenNickname, $defaultJsonVars);
				$defaultJsonVars = str_replace("[replaceNickname]", $newScreenNickname, $defaultJsonVars);
				$defaultJsonVars = str_replace("[nickname]", $newScreenNickname, $defaultJsonVars);
				
				//validate...
				if(strlen($category) < 1){
					$errors[] = "This plugin does not contain a category";
				}
				if(strlen($displayAs) < 1){
					$errors[] = "This plugin does not contain a displayAs name";
				}
				if(strlen($loadClassOrActionName) < 1){
					$errors[] = "This plugin does not contain a loadClassOrActionName value";
				}
				if(strlen($defaultJsonVars) < 1){
					$errors[] = "This plugin does not contain any defaultJsonVars";
				}


				if(count($errors) < 1){
				
					//create new BT_item for the screen...
					$objNewScreenItem = new Bt_item("");
					$objNewScreenItem -> infoArray["guid"] = fnFormInput($newScreenItemGuid);
					$objNewScreenItem -> infoArray["parentItemGuid"] = "";
					$objNewScreenItem -> infoArray["uniquePluginId"] = fnFormInput($newScreenPluginType);
					$objNewScreenItem -> infoArray["loadClassOrActionName"] = fnFormInput($loadClassOrActionName);
					$objNewScreenItem -> infoArray["hasChildItems"] = fnFormInput($hasChildItems);
					$objNewScreenItem -> infoArray["loadItemGuid"] = "";
					$objNewScreenItem -> infoArray["appGuid"] = fnFormInput($appGuid);
					$objNewScreenItem -> infoArray["controlPanelItemType"] = "screen";
					$objNewScreenItem -> infoArray["itemType"] = $loadClassOrActionName;
					$objNewScreenItem -> infoArray["itemTypeLabel"] = fnFormInput($displayAs);
					$objNewScreenItem -> infoArray["nickname"] = fnFormInput($newScreenNickname);
					$objNewScreenItem -> infoArray["orderIndex"] = "99";
					$objNewScreenItem -> infoArray["jsonVars"] = $defaultJsonVars;
					$objNewScreenItem -> infoArray["status"] = "active";
					$objNewScreenItem -> infoArray["dateStampUTC"] = $dtNow;
					$objNewScreenItem -> infoArray["modifiedUTC"] = $dtNow;
					$objNewScreenItem -> fnInsert();

					//create a new BT_item for the menu item...
					$objChildItem = new Bt_item("");
					$objChildItem->infoArray["guid"] = $newItemId;
					$objChildItem->infoArray["parentItemGuid"] = $BT_itemId;;
					$objChildItem->infoArray["uniquePluginId"] = "";
					$objChildItem->infoArray["loadClassOrActionName"] = $loadClassOrActionName;
					$objChildItem->infoArray["hasChildItems"] = "0";
					$objChildItem->infoArray["loadItemGuid"] = $newScreenItemGuid;
					$objChildItem->infoArray["appGuid"] = $appGuid;
					$objChildItem->infoArray["controlPanelItemType"] = "childItem";
					$objChildItem->infoArray["itemType"] = $newItemType;
					$objChildItem->infoArray["itemTypeLabel"] = $newItemType;
					$objChildItem->infoArray["nickname"] =  $newItemTitleText;
					$objChildItem->infoArray["orderIndex"] = $newOrderIndex;
					$objChildItem->infoArray["jsonVars"] = $newItemJson;
					$objChildItem->infoArray["status"] = "active";
					$objChildItem->infoArray["dateStampUTC"] = $dtNow;
					$objChildItem->infoArray["modifiedUTC"] = $dtNow;
					$objChildItem->fnInsert();

			
				}//errors...
			}//create new screen...
			
			//update the app's modified date...
			$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			
			//return JSON values...
			$ret = "{\"result\":\"success\"}";
		
		
		}//errors...
		
		//return...
		echo $ret;
		exit();
			

	}//addChildItem...
	///////////////////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////////////////////
	//removeChildItem (used in child items HTML sections)
	if(strtoupper($command) == "REMOVECHILDITEM" && count($errors) < 1){
		
		//return data...
		$ret = "";
		
		//must have childItemId...
		if(strlen($childItemId) < 1){
			$errors[] = "childItemId required";
		}
		
		//child items must also have a parentItemGuid....
		if(strlen($parentItemGuid) < 1){
			$errors[] = "parentItemGuid required";
		}
		
		
		//errors?
		if(count($errors) > 0){
			
			//build list of error messages...
			$tmpErrors = "";
			foreach($errors as $value){
				$tmpErrors .= $value . ", ";
			}
			$tmpErrors = rtrim($tmpErrors, ", ");
			
			$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
		
		}else{
		
			//remove this childItem...
			$strSql = "DELETE FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND guid = '" . $childItemId . "' ";
			$strSql .= " AND parentItemGuid = '" . $parentItemGuid . "' ";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			$bolDeleted = TRUE;
		
			//update the app's modified date...
			$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//return JSON values...
			$ret = "{\"result\":\"success\"}";
		
		}//errors...
		
		//return...
		echo $ret;
		exit();
			

	}//removeChildItem...
	///////////////////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////////////////
	//updateChildItemsOrder (used in child items HTML sections)
	if(strtoupper($command) == "UPDATECHILDITEMSORDER" && count($errors) < 1){
		
		//return data...
		$ret = "";
		
		
		//errors?
		if(count($errors) > 0){
			
			//build list of error messages...
			$tmpErrors = "";
			foreach($errors as $value){
				$tmpErrors .= $value . ", ";
			}
			$tmpErrors = rtrim($tmpErrors, ", ");
			
			$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
		
		}else{
		
		
			//loop each "order_[guid]" element..
			foreach($_POST as $key => $val){
				if(substr($key, 0, 6) == "order_"){
					
					$guid = str_replace("order_", "", $key);
					$orderIndex = fnFormInput($val);
					if(!is_numeric($val)) $val = "0";
					
					$strSql = "UPDATE " . TBL_BT_ITEMS . " SET orderIndex = '" . $orderIndex . "' ";
					$strSql .= " WHERE guid = '" . $guid . "' ";
					$strSql .= " AND appGuid = '" . $appGuid . "' ";
					$strSql .= " AND parentItemGuid = '" . $BT_itemId . "' ";
					fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				}
			}
			
			//update the app's modified date...
			$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
			//return JSON values...
			$ret = "{\"result\":\"success\"}";
		
		}//errors...
		
		//return...
		echo $ret;
		exit();
			

	}//updateChildItemsOrder...
	///////////////////////////////////////////////////////////////////////////////////////////





	//errors...we will not be here if all is well...
	if(count($errors) > 0){
		
		//build list of error messages...
		$tmpErrors = "";
		foreach($errors as $value){
			$tmpErrors .= $value . ", ";
		}
		$tmpErrors = rtrim($tmpErrors, ", ");
		
		$ret = "{\"result\":\"error\", \"errors\":\"" . $tmpErrors . "\"}";
	
		//bail...
		echo $ret;
		exit();
	}

	
	//bail...
	exit();
	
	
?>

