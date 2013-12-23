<?php   require_once("../../config.php");
	
	//API results are always returned text/plain...
	header("Content-Type: text/plain");


	//declare required variables for inc_validateRequest.php...
	$clientVars = array();
	$errors = array();
	
	//include file validates the request and inserts a request record...
	require_once("../inc_validateRequest.php");
	
	
	/*
		required fields for all methods in /api/app folder
		----------------------------------------------------------------------------------				
		apiKey		 			apiKey from API access accounts in control panel
		apiSecret				apiSecret from API access accounts in control panel
		appGuid					tbl_applications.guid
		command					api command to run like "getAppData" or "logIn"
		
		Individual Methods below have additional required fields
		----------------------------------------------------------------------------------				
	*/

	
	//////////////////////////////////////////////////////////////////////////////
	//getAppData
	if(count($errors) < 1 && strtoupper($clientVars["command"]) == "GETAPPDATA"){
	
		//begin jsonResult...
		$jsonResult = "{\"result\":";

	
		//must have appGuid...
		$appGuid = "";
		if(isset($clientVars["appGuid"])) $appGuid = $clientVars["appGuid"];
		if(strlen($appGuid) < 1){
			$bolPassed = false;
			$errors[] = "appGuid required";
		}
		
		//all good?
		if(count($errors) < 1){
	
	
			//Live Mode apps get config file from file system...
			if(strtoupper($clientVars["currentMode"]) == "LIVE"){
				
				$strSql = "SELECT A.dataDir FROM " . TBL_APPLICATIONS . " AS A WHERE A.guid =  '" . $clientVars["appGuid"] . "' AND A.apiKey = '" . $clientVars["apiKey"] . "' AND A.status != 'deleted' ";
				$dataDir = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			
				//make sure config file exists...
				$configFilePath = APP_PHYSICAL_PATH . $dataDir . "/config/BT_config.txt";
				if(is_file($configFilePath)){

					//BT_config.txt holds JSON data from the last time this apps was "published"...
					echo file_get_contents($configFilePath);
					exit();
				
				}else{
				
					//print generic message...
					echo "{\"status\":\"failed\", \"message\":\"No published JSON data found. Press the Save button on the Publish Changes screen.\"}";
					exit();
				
				
				}
			
			}else{
		
				//Design Mode apps get config data from database...
	
				//for tabs...space...This is used so the JSON format looks good when viewed in a browser (view source)
				$tab = "\t";
				$tab2 = "\t\t";
				$tab3 = "\t\t\t";
				$tab4 = "\t\t\t\t";
				$tab5 = "\t\t\t\t\t";
				$tab6 = "\t\t\t\t\t\t";
				
				$line = "\n";
				$line2 = "\n\n";
					
						//begin jsonResult...
						$jsonResult = "{\"BT_appConfig\":";
		
						$jsonResult .= $line . $tab . "{";
						$jsonResult .= $line . $tab . "\"BT_items\":[";
						
						//fill app variables first
						$strSql = "SELECT A.guid, A.apiKey, A.currentPublishVersion, A.name, A.dataURL, A.cloudURL, A.registerForPushURL, A.startGPS, A.startAPN, A.allowRotation, A.modifiedUTC, A.currentPublishDate ";
						$strSql .= "FROM " . TBL_APPLICATIONS . " AS A ";
						$strSql .= "WHERE A.guid =  '" . $clientVars["appGuid"] . "' AND A.apiKey = '" . $clientVars["apiKey"] . "' ";
						$strSql .= "AND A.status != 'deleted' ";
						$strSql .= "LIMIT 0, 1 ";
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
						if($res){
							$row = mysql_fetch_array($res);
	
								$jsonResult .=  $line . $tab2 ."{\"itemId\":\"" . $row['guid'] . "\",";
	
								//gps and apn must be numeric...
								$startGPS = $row['startGPS'];
									if($startGPS == "") $startGPS = "0";
								$startAPN = $row['startAPN'];
									if($startAPN == "") $startAPN = "0";
	
								//current published version cannot be blank...
								$currentPublishVersion = $row['currentPublishVersion'];
									if(!is_numeric($currentPublishVersion)) $currentPublishVersion = "1.0";

								//core app info							
								$jsonResult .= $line . $tab3 . "\"itemType\":\"BT_app\",";
								$jsonResult .= $line . $tab3 . "\"buzztouchAppId\":\"" . $row['guid'] . "\",";
								$jsonResult .= $line . $tab3 . "\"buzztouchAPIKey\":\"" . $row['apiKey'] . "\", ";
								$jsonResult .= $line . $tab3 . "\"dataURL\":\"" . $row['dataURL'] . "\", ";
								$jsonResult .= $line . $tab3 . "\"reportToCloudURL\":\"" . $row['cloudURL'] . "\", ";
								$jsonResult .= $line . $tab3 . "\"registerForPushURL\":\"" . $row['registerForPushURL'] . "\", ";
								$jsonResult .= $line . $tab3 . "\"lastModified\":\"" . date(DATE_RFC2822, strtotime($row['modifiedUTC'])) . "\", ";
								$jsonResult .= $line . $tab3 . "\"lastPublished\":\"" . date(DATE_RFC2822, strtotime($row['currentPublishDate'])) . "\", ";
								$jsonResult .= $line . $tab3 . "\"name\":\"" . $row['name'] . "\", ";
								$jsonResult .= $line . $tab3 . "\"version\":\"" . $currentPublishVersion . "\", ";
								$jsonResult .= $line . $tab3 . "\"currentMode\":\"" . fnFormatProperCase($clientVars["currentMode"]) . "\", ";
								$jsonResult .= $line . $tab3 . "\"startLocationUpdates\":\"" . $startGPS . "\", ";
								$jsonResult .= $line . $tab3 . "\"promptForPushNotifications\":\"" . $startAPN . "\", ";
								$jsonResult .= $line . $tab3 . "\"allowRotation\":\"" . $row['allowRotation'] . "\", ";
							
								//the application item does not end until after themes, tabs, and screens are added
						}//if res
							
							
							//start themes	
							$jsonResult .= $line2 . $tab3 . "\"BT_themes\":[\n";
								$tmpThemes = "";
								$strSql = " SELECT guid, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
								$strSql .= " WHERE appGuid = '" . $clientVars["appGuid"] . "'";
								$strSql .= " AND controlPanelItemType = 'theme' ";
								$strSql .= " AND orderIndex = '0' ";
								$strSql .= "LIMIT 0, 1 ";
								$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
								if($res){
									while($row = mysql_fetch_array($res)){
										$tmpThemes .=  $line . $tab5 . $row['jsonVars'] . ",";
									}
								}
								//remove last comma...
								$tmpThemes = fnRemoveLastChar($tmpThemes, ",");
								$jsonResult .= $tab3 . $tmpThemes;
								
							//end themes
							$jsonResult .= $line . $tab3 . "],";
							
							//start tabs	
							$jsonResult .= $line2 . $tab3 . "\"BT_tabs\":[\n";
								$tmpTabs = "";
								$strSql = " SELECT guid, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
								$strSql .= " WHERE appGuid = '" . $clientVars["appGuid"] . "'";
								$strSql .= " AND controlPanelItemType = 'tab' ";
								$strSql .= " ORDER BY id ASC ";
								$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
								$tabCnt = 0; 
								if($res){
									while($row = mysql_fetch_array($res)){
										$tabCnt = ($tabCnt + 1);
										$tabJSON = $row['jsonVars'];
										$tmpTabs .=  $line . $tab5 . $tabJSON . ",";
									}
								}
								//remove last comma...
								$tmpTabs = fnRemoveLastChar($tmpTabs, ",");
								$jsonResult .= $tmpTabs;
							//end tabs
							$jsonResult .= $line . $tab3 . "],";
								
							//start menus...
							$jsonResult .= $line2 . $tab3 . "\"BT_menus\":[\n";
								$tmpMenus = "";
								$strSql = " SELECT guid, loadClassOrActionName, hasChildItems, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
								$strSql .= " WHERE appGuid = '" . $clientVars["appGuid"] . "'";
								$strSql .= " AND controlPanelItemType = 'menu' ";
								$strSql .= " ORDER BY orderIndex ASC ";
								$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
								$menuCnt = 0;
								if($res){
									while($row = mysql_fetch_array($res)){
										$menuCnt = ($menuCnt + 1);
										
										//screen vars...
										$itemType = $row["itemType"];
										$jsonVars = $row["jsonVars"];
										$hasChildItems = $row["hasChildItems"];
										
										//all menus have child items (this could be an empty list)...
										if($hasChildItems == "1"){
											
											//it's possible that the json in the database has an empty childItems[] array.
											//remove it if it does, we'll recreate it, fill it, then cap it off.
											
											$jsonVars = str_replace(",\"childItems\":[]", "", $jsonVars);
											$jsonVars = str_replace("}", "", $jsonVars);
											
											//begin child items..
											$jsonVars .= ",\n\"childItems\":[\n";
											
												//fetch child items...
												$tmpChildren = "";
												$tmp = " SELECT jsonVars FROM " . TBL_BT_ITEMS  . " WHERE parentItemGuid = '" . $row["guid"] . "' AND itemType = 'BT_menuItem' ";
												$tmp .= " ORDER BY orderIndex ASC ";
												$resChildren = fnDbGetResult($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
												if($resChildren){
													while($childRow = mysql_fetch_array($resChildren)){
														$tmpChildren .= "\n" . $childRow["jsonVars"] . ",";
													}
												}
												
												//remove last comma from child items
												$tmpChildren = fnRemoveLastChar($tmpChildren, ",");
												
											//end child items..												
											$jsonVars .= $tmpChildren . "\n]";
											
											//new cap...
											$jsonVars .= "}";
											
										}//menus with childItems
										
										
										//add to list of menus										
										$tmpMenus .=  $line . $tab5 . $jsonVars . ",";
									}
								}
								//remove last comma...
								$tmpMenus = fnRemoveLastChar($tmpMenus, ",");
								$jsonResult .= $tmpMenus;
							
							//end menus
							$jsonResult .= $line2 . $tab3 . "],";
								
								
							//start screens
							$jsonResult .= $line2 . $tab3 . "\"BT_screens\":[\n";
								$tmpScreens = "";
								$strSql = " SELECT guid, loadClassOrActionName, hasChildItems, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
								$strSql .= " WHERE appGuid = '" . $clientVars["appGuid"] . "'";
								$strSql .= " AND controlPanelItemType = 'screen' ";
								$strSql .= " ORDER BY orderIndex ASC ";
								$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
								$screenCnt = 0;
								if($res){
									while($row = mysql_fetch_array($res)){
										$screenCnt = ($screenCnt + 1);
										
										//screen vars...
										$itemType = $row["itemType"];
										$loadClassOrActionName = $row["loadClassOrActionName"];
										$jsonVars = $row["jsonVars"];
										$hasChildItems = $row["hasChildItems"];
										
										//add screen's itemId, nickname, and nickname as first element in JSON vars
										if($jsonVars != ""){
											$jsonVars = str_replace("{", "{\"itemId\":\"" . $row["guid"] . "\", \"itemType\":\"" . $row["itemType"] . "\", \"itemNickname\":\"" . $row["nickname"] . "\", ", $jsonVars);
										}	
										
										//////////////////////////////////////////////////////////////////
										/* JSON Snippet Keeper Modification */
										if(strtoupper($loadClassOrActionName) == "JSON_SNIPPET_KEEPER"){
											$jsonVars = $row["jsonVars"];
										}
										/* END JSON Snippet Keeper Modification */
										//////////////////////////////////////////////////////////////////
														
											//child items are possible some screen types..
											if($hasChildItems == "1"){
												
												//it's possible that the json in the database has an empty childItems[] array.
												//remove it if it does, we'll recreate it, fill it, then cap it off.
												
												$jsonVars = str_replace(",\"childItems\":[]", "", $jsonVars);
												$jsonVars = str_replace("}", "", $jsonVars);
												
												//begin child items..
												$jsonVars .= ",\n\"childItems\":[\n";
												
													//fetch child items...
													$tmpChildren = "";
													$tmp = " SELECT jsonVars FROM " . TBL_BT_ITEMS  . " WHERE parentItemGuid = '" . $row["guid"] . "'";
													$tmp .= " ORDER BY orderIndex ASC ";
													$resChildren = fnDbGetResult($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
													if($resChildren){
														while($childRow = mysql_fetch_array($resChildren)){
															$tmpChildren .= "\n" . $childRow["jsonVars"] . ",";
														}
													}
													
													//remove last comma from child items
													$tmpChildren = fnRemoveLastChar($tmpChildren, ",");
													
												//end child items..												
												$jsonVars .= $tmpChildren . "\n]";
												
												//new cap...
												$jsonVars .= "}";
												
											}//screens with childItems
										
										
										//add to list of screens									
										$tmpScreens .=  $line . $tab5 . $jsonVars . ",";
									}
								}
								//remove last comma...
								$tmpScreens = fnRemoveLastChar($tmpScreens, ",");
								$jsonResult .= $tmpScreens;
							
							//end screens
							$jsonResult .= $line2 . $tab3 . "]";
								
						//end BT_app item
						$jsonResult .= $line . $tab2 . "}";
					$jsonResult .= $line . $line . $tab2 . "] ";
				$jsonResult .= $line . $line . $tab . "}";
		
		
			}//currentMode...
		
		}else{
		
			$jsonResult .= "{\"status\":\"failed\"}";
		
		}//count of errors...
		
		//cap json result...
		$jsonResult .= "}";
		
		//print json...exit
		echo $jsonResult;
		exit();
		
		
		
		
	}//getAppData
	//////////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////////
	//registereForPush
	if(count($errors) < 1 && strtoupper($clientVars["command"]) == "REGISTERFORPUSH"){

		//assume we did not pass a deviceToken...
		$jsonResult = "{\"result\":{\"status\":\"failed\", \"reason\":\"no device token or type provided\"}}";

		//possible latitude / longitude must be numeric...
		if(!is_numeric($clientVars["deviceLatitude"])){
			$clientVars["deviceLatitude"] = "0";
		}
		if(!is_numeric($clientVars["deviceLongitude"])){
			$clientVars["deviceLongitude"] = "0";
		}

		//currentMode (Live or Design)...
		$currentMode = $clientVars["currentMode"];
		if($currentMode == "") $currentMode = "Live";

		//must have deviceToken...
		$lastRegisteredUTC = $dtNow;
		if($clientVars["appGuid"] != "" && $clientVars["deviceToken"] != "" && $clientVars["deviceType"] != ""){
		
			/*
				devices table...
				guid, appGuid, appUserGuid, deviceType, deviceModel, deviceLatitude, deviceLongitude, deviceToken, dateStampUTC
				
				deviceType: ios, android
				
			*/
			
			$cleanToken = $clientVars["deviceToken"];
			$cleanToken = str_replace(" ", "", $cleanToken);
			$cleanToken = str_replace("<", "", $cleanToken);
			$cleanToken = str_replace(">", "", $cleanToken);

			//return this...
			$lastRegisteredUTC = "";

			//if this is an ANDROID or iOS device, it may be "unregistering" so we'll need to remove it from the list of devices...
			if(isset($clientVars["gcmCommand"]) || isset($clientVars["apnCommand"]) ){
				
				if($clientVars["gcmCommand"] == "unregisterDevice"){
					$lastRegisteredUTC = "unregistered";
					$tmpSql = "DELETE FROM " . TBL_APN_DEVICES . " WHERE deviceToken = '" . fnFormInput($cleanToken) . "' AND deviceType='android' AND deviceMode = '" . $currentMode . "'";
					fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
				}

				if($clientVars["apnCommand"] == "unregisterDevice"){
					$lastRegisteredUTC = "unregistered";
					$tmpSql = "DELETE FROM " . TBL_APN_DEVICES . " WHERE deviceToken = '" . fnFormInput($cleanToken) . "' AND deviceType='ios' AND deviceMode = '" . $currentMode . "' ";
					fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
				}
				
			}else{
			
				//if it exists, return the dateStampUTC value, else insert a new record....
				$tmpSql = "SELECT dateStampUTC FROM " . TBL_APN_DEVICES . " WHERE deviceToken = '" . fnFormInput($cleanToken) . "' ";
				$tmpSql .= "AND appGuid = '" . fnFormInput($clientVars["appGuid"]) . "' ";
				$tmpSql .= "AND deviceMode = '" . fnFormInput($currentMode) . "' ";
				$tmpSql .= " LIMIT 0, 1";
				$lastRegisteredUTC = fnGetOneValue($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		
				//insert new if we don't have one...
				if($lastRegisteredUTC == ""){
					
					$lastRegisteredUTC = $dtNow;
					$tmpSql = "INSERT INTO " . TBL_APN_DEVICES . " (guid, appGuid, appUserGuid, deviceMode, deviceType, deviceModel, deviceLatitude, ";
					$tmpSql .= "deviceLongitude, deviceToken, dateStampUTC) VALUES ( ";
					$tmpSql .= "'" . strtoupper(fnCreateGuid()) . "', '" . fnFormInput($clientVars["appGuid"]) . "', '" . fnFormInput($clientVars["appUserGuid"]) . "', ";
					$tmpSql .= "'" . $currentMode . "', '" . fnFormInput($clientVars["deviceType"]) . "', '" . fnFormInput($clientVars["deviceModel"]) . "', ";
					$tmpSql .= "'" . fnFormInput($clientVars["deviceLatitude"]) . "', '" . fnFormInput($clientVars["deviceLongitude"]) . "', ";
					$tmpSql .= "'" . fnFormInput($cleanToken) . "', '" . $dtNow . "')";
					fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			
				}
				
			}//gcmCommand for unregister device.. 
			
			//update deviceCount for this app...
			$tmpDeviceCount = fnGetOneValue("SELECT Count(*) FROM " . TBL_APN_DEVICES . " WHERE appGuid = '" . fnFormInput($clientVars["appGuid"]) . "'", APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			$tmp = "UPDATE " . TBL_APPLICATIONS . " SET deviceCount = '" . $tmpDeviceCount . "'";
			$tmp .= " WHERE guid = '" . fnFormInput($clientVars["appGuid"]) . "'";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//output
			$jsonResult = "{\"result\":{\"status\":\"valid\", \"lastRegisteredUTC\":\"" . $lastRegisteredUTC . "\"}}";
		
		
		}//appguid, deviceToken, deviceType...

		//print, exit...
		echo $jsonResult;
		exit();		
		
		
	}//registerForPush
	//////////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////////
	//getAppData
	if(count($errors) < 1 && strtoupper($clientVars["command"]) == "REPORTTOCLOUD"){
	
		//update viewCount
		$tmpSql = "UPDATE " . TBL_APPLICATIONS . " SET viewCount = (viewCount + 1) WHERE guid = '" . $clientVars["appGuid"] . "'";
		fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
	
		//if we have a userGuid, update last request, numRequests..
		if($clientVars["userGuid"] != "" && $clientVars["appGuid"] != ""){
			$strSql = " UPDATE " . TBL_APP_USERS . " SET numRequests = (numRequests + 1), lastRequestUTC = '" . $dtNow . "' ";
			$strSql .= " WHERE guid = '" . $clientVars["userGuid"] . "' AND appGuid = '" . $clientVars["appGuid"] . "' ";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
		
		//return the last modifiedUTC or the currentPublishDate...
		$lastModified = "na";
		if($clientVars["currentMode"] == "" || strtoupper($clientVars["currentMode"]) == "DESIGN"){ 
			$tmpSql = "SELECT modifiedUTC FROM " . TBL_APPLICATIONS . " WHERE guid = '" . $clientVars["appGuid"] . "'";
		}else{
			$tmpSql = "SELECT currentPublishDate FROM " . TBL_APPLICATIONS . " WHERE guid = '" . $clientVars["appGuid"] . "'";
		}
		$lastModified = fnGetOneValue($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		if($lastModified != ""){
			$lastModified = date(DATE_RFC2822, strtotime($lastModified));
		}
		
		//output
		$jsonResult = "{\"lastModifiedUTC\":\"" . $lastModified . "\"}";

		//print, exit...
		echo $jsonResult;
		exit();
		
	}
	//reportToCould
	//////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////////////////////
	//getChildItems
	if(count($errors) < 1 && strtoupper($clientVars["command"]) == "GETCHILDITEMS"){

		//must have appGuid...
		$appGuid = "";
		if(isset($clientVars["appGuid"])) $appGuid = $clientVars["appGuid"];
		if(strlen($appGuid) < 1){
			$bolPassed = false;
			$errors[] = "appGuid required";
		}

		//must have screenId...
		$screenId = "";
		if(isset($clientVars["screenId"])) $screenId = $clientVars["screenId"];
		if(strlen($screenId) < 1){
			$bolPassed = false;
			$errors[] = "screenId required";
		}

		//begin jsonResult...
		$jsonResult = "{\"result\":";
		
		//all good?
		if(count($errors) < 1){

			//start child items...
			$jsonResult = "{\"childItems\":[";

				$strSql = "SELECT guid, itemType, jsonVars ";
				$strSql .= "FROM " . TBL_BT_ITEMS;
				$strSql .= " WHERE appGuid = '" . $appGuid . "'";
				$strSql .= " AND parentItemGuid = '" . $screenId . "'";
				$strSql .= " ORDER BY orderIndex ASC";
				$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
				if($res){
					while($row = mysql_fetch_array($res)){
						$jsonResult .=  "\n" . $row['jsonVars'] . ",";
					}
				}//if res

			//remove last comma
			$jsonResult = fnRemoveLastChar($jsonResult, ",");
			$jsonResult .= "\n]}";
				
			
			//output...
			echo $jsonResult;
			exit();		

		}
		
		//if we are here we had error. This script will output the errors at the end...
			

	}
	//getScreenData
	//////////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////////////////////
	//all done processing the requested method...
	if(count($errors) > 0){
		
		//begin jsonResult...
		$jsonResult = "{\"result\":";
		
		//add the array of errors...
		$jsonResult .= "{\"status\":\"invalid\", \"errors\":[";
			
			for($x = 0; $x < count($errors); $x++){
				$jsonResult .= "{\"message\":\"" . $errors[$x] . "\"}, ";
			}
			
		//remove trailing comma...
		$jsonResult = rtrim($jsonResult, ", ");
			
		//cap JSON result...
		$jsonResult .= "]}}";
	
		//output jsonResult
		echo $jsonResult;
		exit();	
	
	}


	//we should not be here! Individual method create then print the JSON then exit(). 
	$jsonResult = "{\"result\":{\"status\":\"invalid\", \"errors\":[{\"message\":\"Invalid api request, no command found?\"}]}}";
	echo $jsonResult;
	exit();


?>