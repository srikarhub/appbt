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
	$currentPublishDate = fnGetReqVal("currentPublishDate", "", $myRequestVars);
	$currentPublishVersion = fnGetReqVal("currentPublishVersion", "", $myRequestVars);
	
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
		
		//version must be numeric...
		if(!is_numeric($currentPublishVersion)){
			$bolPassed = false;
				$strMessage .= "<br>Current Version, numeric required";
		}
		
		
		if(!$bolPassed){
			
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Changes not saved! " . $strMessage . "</span>";
			exit();
			
		}else{
		
			//need apiKey and apiSecret..
			$apiKey = $objApp->infoArray["apiKey"];
			$dataDir = $objApp->infoArray["dataDir"];
			
			if($dataDir != ""){
				$configDirectoryPath = APP_PHYSICAL_PATH . $dataDir . "/config/";
			
				if(is_dir($configDirectoryPath)){
					if(is_writable($configDirectoryPath)){
						
						//build config file from database values....
						$jsonResult = "{\"BT_appConfig\":";
					
						//for tabs...space...This is used so the JSON format looks good when viewed in a browser (view source)
						$tab = "\t";
						$tab2 = "\t\t";
						$tab3 = "\t\t\t";
						$tab4 = "\t\t\t\t";
						$tab5 = "\t\t\t\t\t";
						$tab6 = "\t\t\t\t\t\t";
						
						$line = "\n";
						$line2 = "\n\n";
								
						$jsonResult .= $line . $tab . "{";
						$jsonResult .= $line . $tab . "\"BT_items\":[";
									
								//fill app variables first
								$strSql = "SELECT A.guid, A.apiKey, A.currentPublishVersion, A.name, A.dataURL, A.cloudURL, A.registerForPushURL, A.startGPS, A.startAPN, A.allowRotation, A.modifiedUTC, A.currentPublishDate ";
								$strSql .= "FROM " . TBL_APPLICATIONS . " AS A ";
								$strSql .= "WHERE A.guid =  '" . $appGuid . "' AND A.apiKey = '" . $apiKey . "' ";
								$strSql .= "AND A.status != 'deleted' ";
								$strSql .= "LIMIT 0, 1 ";
								$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
								if($res){
									$row = mysql_fetch_array($res);
			
										//start application object's JSON...
										$jsonResult .=  $line . $tab2 ."{\"itemId\":\"" . $row['guid'] . "\",";
			
										//gps and apn must be numeric...
										$startGPS = $row['startGPS'];
											if($startGPS == "") $startGPS = "0";
										$startAPN = $row['startAPN'];
											if($startAPN == "") $startAPN = "0";
											
										//current published version cannot be blank...
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
										$jsonResult .= $line . $tab3 . "\"currentMode\":\"Live\", ";
										$jsonResult .= $line . $tab3 . "\"startLocationUpdates\":\"" . $startGPS . "\", ";
										$jsonResult .= $line . $tab3 . "\"promptForPushNotifications\":\"" . $startAPN . "\", ";
										$jsonResult .= $line . $tab3 . "\"allowRotation\":\"" . $row['allowRotation'] . "\", ";
										
									
								}//if res
									
									
								//start themes	
								$jsonResult .= $line2 . $tab3 . "\"BT_themes\":[\n";
									$tmpThemes = "";
									$strSql = " SELECT guid, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
									$strSql .= " WHERE appGuid = '" . $appGuid . "'";
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
									$jsonResult .= $tmpThemes;
								//end themes
								$jsonResult .= $line . $tab3 . "],";
			
								//start tabs	
								$jsonResult .= $line2 . $tab3 . "\"BT_tabs\":[\n";
									$tmpTabs = "";
									$strSql = " SELECT guid, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
									$strSql .= " WHERE appGuid = '" . $appGuid . "'";
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
										
								//start screens
								$jsonResult .= $line2 . $tab3 . "\"BT_screens\":[\n";
									$tmpScreens = "";
									$strSql = " SELECT guid, loadClassOrActionName, hasChildItems, jsonVars, itemType, nickname FROM " . TBL_BT_ITEMS ;
									$strSql .= " WHERE appGuid = '" . $appGuid . "'";
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
													$jsonVars .= $tmpChildren . "]\n";
													
													//new cap...
													$jsonVars .= "}";
													
												}//screens with childItems
											
											
											//add to list of screen										
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
		
							//end
							$jsonResult .= $line2 . "}";

						
							//save this text to "config directory"...
							if($fh = fopen($configDirectoryPath . "/BT_config.txt", 'w')){
								fwrite($fh, $jsonResult);
								fclose($fh);		
							}
							
						
					}else{
					
						$bolPassed = false;
						$strMessage .= "<br>App's Config directory is not writable by PHP?";
					}
				}else{
					$bolPassed = false;
					$strMessage .= "<br>App's Config directory does not exist? (1)";
				}
			}else{
				$bolPassed = false;
				$strMessage .= "<br>App's Config directory does not exist? (2)";
			}//dataDir...
		
			//update
			if($bolPassed){
			
				//set the published date and the modified date to the same thing...
				$objApp->infoArray["modifiedUTC"] = $dtNow;
				$objApp->infoArray["currentPublishDate"] = $dtNow;
				$objApp->infoArray["currentPublishVersion"] = $currentPublishVersion;
				$objApp->fnUpdate();
				
				//done...
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'>Saved!";
				exit();		
			
			}
		
		}//bolPassed
		
	}//was submitted
	

	
	
	
?>