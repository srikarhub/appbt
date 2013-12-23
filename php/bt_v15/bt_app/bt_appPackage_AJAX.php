<?php   require_once("../../config.php");
		require_once("../../includes/zip.php");
	
	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);

	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");
	
	//try to increase max execution time..This is only necessary on painfully slow servers!
	if(!ini_get('safe_mode')){ 
    	@set_time_limit(120);
	} 		
	
	//vars...
	$ownerGuid = fnGetReqVal("guid", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$whatPlatform = fnGetReqVal("whatPlatform", "", $myRequestVars);
	
	//keep track of errors...
	$packageErrors = array();
	
	///////////////////////////////////////////////////////////////
	//selected plugin id's
	$inClauseSQLPlugins = "";
	if(isset($_POST['plugins'])){
		while (list ($key, $val) = each ($_POST['plugins'])) { 
			if($val != ""){
				$inClauseSQLPlugins .= "'" . trim($val) . "',";
			}
		}
		$inClauseSQLPlugins = fnRemoveLastChar($inClauseSQLPlugins, ",");
	}
	
	//selected sdk names...
	$includeSDKPdfs = array();
	if(isset($_POST['includeSDKPdfs'])){
		while (list ($key, $val) = each ($_POST['includeSDKPdfs'])) { 
			if($val != ""){
				$includeSDKPdfs[] = trim($val);
			}
		}
	}
	
	/////////////////////////////////////////////////
	//start functions
	
	//replace in text file function
	function fnReplaceText($filePath, $replaceWhat, $replaceWith){
		if(is_file($filePath)){
			if(is_readable($filePath)){
				$str = file_get_contents($filePath);
				if(strlen($str) > 0){	
					//echo "<br> Replacing text in: " . $filePath;
					$fp = fopen($filePath,"w");
					$str = str_replace($replaceWhat,$replaceWith,$str);
					if(fwrite($fp, $str, strlen($str))){
						//echo " ~ SUCCESS";
					}else{
						//echo " ~ ERROR!";
					}
					 
				}else{
					//echo "<br>This file is empty: " . $filePath;
				}
			}else{
				//echo "<br>File not readable: " . $filePath;
			}
		}else{
			//echo "<br>Not a file: " . $filePath;
		}
	}
	
	//delete a directory and all of it's contents..
	function delete_directory($dirname) {
	   if (is_dir($dirname))
		  $dir_handle = opendir($dirname);
	   if (!$dir_handle)
		  return false;
	   while($file = readdir($dir_handle)) {
		  if ($file != "." && $file != "..") {
			 if (!is_dir($dirname."/".$file))
				unlink($dirname."/".$file);
			 else
				delete_directory($dirname.'/'.$file);    
		  }
	   }
	   closedir($dir_handle);
	   rmdir($dirname);
	   return true;
	}
	
	//copies directory and all it's sub-directories
	function copy_directory($source, $destination){
		if(is_dir($source)){
			@mkdir($destination);
			chmod($destination, 0755);
			$directory = dir($source);
			chmod($directory, 0755);
			
			while(FALSE !== ($readdirectory = $directory->read())){
				if($readdirectory == '.' || $readdirectory == '..'){
					continue;
				}
				$PathDir = $source . '/' . $readdirectory; 
				if(is_dir($PathDir)){
					copy_directory($PathDir, $destination . '/' . $readdirectory );
					chmod($destination . '/' . $readdirectory, 0755);
					continue;
				}
				copy($PathDir, $destination . '/' . $readdirectory);
				chmod($destination . '/' . $readdirectory, 0755);
			}
	 		$directory->close();
		}else {
			copy($source, $destination);
			chmod($destination, 0755);
		}
	}	

	//calls buzztouch.com's API to build project. Returns a download URL for the .zipped up package
	function fnGetBuzztouchPackageUsingAPI($appGuid, $projectName, $appName, $iconURL, $platform, $includeSDKPdfs){
		
		//if we passed in an array of partnerSDKPdfs...
		$tmpPartnerSDKPdfs = "";
		if(is_array($includeSDKPdfs)){
			for($x = 0; $x < count($includeSDKPdfs); $x++){
				$tmpPartnerSDKPdfs .= $includeSDKPdfs[$x] . ",";
			}
		}
		$tmpPartnerSDKPdfs = rtrim($tmpPartnerSDKPdfs, ",");
		
		//we'll be returning a download URL or an errors[] array...
		$errors = array();
		$packageURL = "";
	
		//key/value pairs to send in the request...
		$postVars = "";
		$fields = array(
		
			//needed by the buzztouch.com api to validate this request
			"apiKey" => urlencode(APP_BT_SERVER_API_KEY),
			"apiSecret" => urlencode(APP_BT_SERVER_API_KEY_SECRET),
			"command" => urlencode("packageProject"),
				
			//required by "packageProject" command...
			"partnerSDKs" => urlencode($tmpPartnerSDKPdfs),
			"appGuid" => urlencode($appGuid),
			"projectName" => urlencode($projectName),
			"appName" => urlencode($appName),
			"iconURL" => urlencode($iconURL),
			"platform" => urlencode($platform)
				
		);
		
		//prepare the data for the POST
		foreach($fields as $key => $value){ 
			$postVars .= $key . "=" . $value . "&"; 
		}
		
		//setup api url
		$apiURL = rtrim(APP_BT_SERVER_API_URL, "/");
		
		
		//TEMP TEMP TEMP
		
		//init a cURL object, set number of POST vars and the POST data
		$ch = curl_init($apiURL . "/app/");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
		
		//get JSON result from buzztouch.com API
		$jsonResult = curl_exec($ch);
		
		//close connection
		curl_close($ch);
		
		//decode json vars
		if($jsonResult != ""){
			$json = new Json; 
			$decoded = $json->unserialize($jsonResult);
			if(is_object($decoded)){
				
				//init status and pacakgeURL
				$status = "";
				$packageURL = "";
				
				//must have a result...
				if(array_key_exists("result", $decoded)){
					$results = $decoded -> result;
					
					//must have a status...
					if(array_key_exists("status", $results)){
						$status = $results -> status;
					}else{
			 			$errors[] = "API result does not contain a required field: status missing.";
					}
					
				}else{
			 		$errors[] = "API result does not contain a required field: result missing.";
				}//key exists..

				//still no errors?
				if(count($errors) < 1){
				
					//success means we'll get a package URL from the API...
					if(strtoupper($status) == "SUCCESS"){
						if(array_key_exists("packageURL", $results)){
							$packageURL = $results -> packageURL;
						}
					}else{
				
						//show the errors...
						if(array_key_exists("errors", $results)){
						
							$errorList = $results -> errors;
							for($e = 0; $e < count($errorList); $e++){
								$errors[] = $errorList[$e] -> message;
							}
						
						}else{
			 				$errors[] = "buzztouch.com API returned an error but no details were provided";
						}
						
					}//success
				
				}//errors...
				
			}else{
			 	$errors[] = "buzztouch.com API return invalid JSON";
			}
		}else{
		 	$errors[] = "buzztouch.com API return invalid JSON";
		}
		
		//did we have errors?
		if(strlen($packageURL) > 0){
			return $packageURL;
		}else{
			return $errors;
		}
		
	}//fnPackageUsingAPI
	
	
	//downloads and saves a .zip from buzztouch.com so it can be processed locally.
	function fnDownloadAndSaveZip($packageURL, $saveAsLocalFileName){
	
		//create a file pointer for cURL to save to...
		$fp = fopen($saveAsLocalFileName, 'w');

		//init a cURL session to download the zip
		$ch = curl_init($packageURL);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FILE, $fp);
				 
		$zipData = curl_exec($ch);
				 
		//close cURL and file pointer...
		curl_close($ch);
		fclose($fp);					

		//make sure .zip project was downloaded and saved!
		if(is_file($saveAsLocalFileName)){
			return true;
		}else{
			return false;
		}
	
	}
	
	//builds config data from this server's database...
	function fnGetConfigData($appGuid, $appAPIKey, $appAPISecret, $currentMode = "Design"){
		
		//client vars array...
		$clientVars = array();
		$clientVars["appGuid"] = $appGuid;
		$clientVars["apiKey"] = $appAPIKey;
		$clientVars["apiSecret"] = $appAPISecret;
		
		//errors...
		$errors = array();
		
		//begin jsonResult...
		$jsonResult = "{\"result\":";
	
		//Live Mode apps get config file from file system...
		if(strtoupper($currentMode) == "LIVE"){
			
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
							$jsonResult .= $line . $tab3 . "\"currentMode\":\"" . fnFormatProperCase($currentMode) . "\", ";
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
	
	
		}//currentMode...
		
		//cap json result...
		$jsonResult .= "}";
		
		//return json data...
		return $jsonResult;


	} //fnGetConfigData

	
	/*
		This function was implemented because this server may have
		allow_url_open disabled in the .PHP configuration and we cannot
		use imagecreatefromjpeg( IMAGE URL ). 
	*/
	function fnSaveImageToTemp($imgURL){
		$saveAsImageName = basename($imgURL);
		$fullPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
		$ch = curl_init($imgURL);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
		$rawdata = curl_exec($ch);
		curl_close ($ch);
		if(file_exists($fullPath)){
			unlink($fullPath);
		}
		$fp = fopen($fullPath, "x");
		fwrite($fp, $rawdata);
		fclose($fp);
		return true;
	}	

	//end functions
	/////////////////////////////////////////////////
	
	//vars from Core iOS or Xcode project...
	$originalProjectName = "";
	$originalPackageName = "";

	//init vars for new app...
	$newAppGuid = "";
	$newAppApiKey = "";
	$newAppApiSecret = "";
	$newAppName = "";
	$newProjectName = "";
	$newAppDataDir = "";
	$newAppProjectName = "";
	$newAppDelegateName = "";
	$newAppPackageName = "";
	$newAppIconUrl = "";
	$newAppVersion = "";
	
	//used by both platforms...
	$sourceCodeDir = "";
	$newProjectDir = "";
	$newArchiveName = "";
	$newDownloadURL = "";
	$newDownloadSize = "0";
	
	//downloading vars...
	$saveToPath = "";
	$packageURL = "";
	$rootFolder = "";

	
	//must have platform selected...
	if(strlen($whatPlatform) < 1){
		$packageErrors[] = "Invalid platform";
	}
	
	//must have buzztouch api key...
	if(strlen(APP_BT_SERVER_API_KEY) < 1){
		$packageErrors[] = "buzztouch.com API Key required";
  	}

	//must have buzztouch api secret...
	if(strlen(APP_BT_SERVER_API_KEY_SECRET) < 1){
		$packageErrors[] = "buzztouch.com API Key Secret required";
  	}
	
	//must have buzztouch api url...
	if(strlen(APP_BT_SERVER_API_URL) < 1){
		$packageErrors[] = "buzztouch.com API URL required";
  	}	
	
	
	//must have app guid...
	if(strlen($appGuid) < 3){
		$packageErrors[] = "Invalid app id";
	}else{
		$objApp = new App($appGuid);
  	}


	//all good?
	if(count($packageErrors) < 1){
	
		//can this person manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);
	
	}

	
	//if all good...
	if(count($packageErrors) < 1){

		//new app vars...
		$newAppGuid = $objApp->infoArray["guid"];
		$newAppApiKey = $objApp->infoArray["apiKey"];
		$newAppApiSecret = $objApp->infoArray["appSecret"];
		$newAppName = $objApp->infoArray["name"];
		$newProjectName = $objApp->infoArray["projectName"];
		$newAppDataDir = $objApp->infoArray["dataDir"];
		$newDelegateName = $newProjectName . "_appDelegate";
		$newAppIconUrl = $objApp->infoArray["iconUrl"];
		$newAppVersion = $objApp->infoArray["version"];
		
		if(strlen($newAppGuid) < 3){
			$packageErrors[] = "Invalid app id";
		}
		if(strlen($newAppApiKey) < 3){
			$packageErrors[] = "Invalid app api key";
		}
		if(strlen($newAppApiSecret) < 3){
			$packageErrors[] = "Invalid app api secret";
		}
		if(strlen($newAppName) < 3){
			$packageErrors[] = "Invalid app name";
		}
		if(strlen($newProjectName) < 3){
			$packageErrors[] = "Invalid project name";
		}
		if(strlen($newAppDataDir) < 3){
			$packageErrors[] = "Invalid app data directory";
		}
		if(strlen($newDelegateName) < 3){
			$packageErrors[] = "Invalid app delegate name";
		}
			
	}//count of errors...


	////////////////////////////////////////////////////////
	//download core project from buzztouch.com API
	if(count($packageErrors) < 1){
		
		//temp directory path...
		$temp_directory = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp"; 					

		//remove previous .folders if they are still hanging around from last attempt...
		fnRemoveDirectory(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-iOS-BTv2.0");
		fnRemoveDirectory(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-iOS-BTv3.0");
		fnRemoveDirectory(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-Android-BTv2.0");
		fnRemoveDirectory(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-Android-BTv3.0");

		//remove previous zip's if they are still hanging around from last attempt...
		if(is_file(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-iOS-BTv2.0.zip")){
			@unlink(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-iOS-BTv2.0.zip");
		}
		if(is_file(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-iOS-BTv3.0.zip")){
			@unlink(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-iOS-BTv3.0.zip");
		}
		if(is_file(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-Android-BTv2.0.zip")){
			@unlink(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-Android-BTv2.0.zip");
		}
		if(is_file(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-Android-BTv3.0.zip")){
			@unlink(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $newProjectName . "-Android-BTv3.0.zip");
		}

		//file names for the .zip we will be preparing...
		$saveToFolderName = "";
		if(strtoupper($whatPlatform) == "IOS"){
			$saveToFolderName = $newProjectName . "-iOS-BTv2.0.zip";
			$newDownloadURL = "bt_appDownload.php?appGuid=" . $newAppGuid . "&project=" . $newProjectName . "-iOS-BTv2.0.zip";
		}
		if(strtoupper($whatPlatform) == "IOSLATEST"){
			$saveToFolderName = $newProjectName . "-iOS-BTv3.0.zip";
			$newDownloadURL = "bt_appDownload.php?appGuid=" . $newAppGuid . "&project=" . $newProjectName . "-iOS-BTv3.0.zip";
		}
		if(strtoupper($whatPlatform) == "ANDROID"){
			$saveToFolderName = $newProjectName . "-Android-BTv2.0.zip";
			$newDownloadURL = "bt_appDownload.php?appGuid=" . $newAppGuid . "&project=" . $newProjectName . "-Android-BTv2.0.zip";
		}
		if(strtoupper($whatPlatform) == "ANDROIDLATEST"){
			$saveToFolderName = $newProjectName . "-Android-BTv3.0.zip";
			$newDownloadURL = "bt_appDownload.php?appGuid=" . $newAppGuid . "&project=" . $newProjectName . "-Android-BTv3.0.zip";
		}
		
		//save the folder in the /files/temp directory
		$saveToPath = "../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp/" . $saveToFolderName;

		//root folder is where we are saving the all the unzipped files to...
		$rootFolder = str_replace(".zip", "", $saveToPath);
		
		//make sure we have a writable temp directory...
		if(is_dir("../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp")){
			if(!is_writable("../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp")){
				$packageErrors[] = "The ../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp directory is not writable by PHP. This folder needs write access.";
			}
		}else{
			$packageErrors[] = "The ../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp directory does not exist. This folder needs to exist and PHP needs write access to it. ";
		}

		//good so far...
		if(count($packageErrors) < 1){		
		
			//packageURL comes form the buzztouch.com API
			$packageURL = fnGetBuzztouchPackageUsingAPI($appGuid, $newProjectName, $newAppName, $newAppIconUrl, $whatPlatform, $includeSDKPdfs);
			
			if(is_array($packageURL)){
			
				//add erorrs to array...
				for($e = 0; $e < count($packageURL); $e++){
					$packageErrors[] = $packageURL[$e];
				}
	
			}else{
			
				//all good, download and save the file from the packageURL...
				if(!fnDownloadAndSaveZip($packageURL, $saveToPath)){
					$packageErrors[] =  "<br>An error occurred trying to download a package from the buzztouch.com API";
				}else{
					//extract the .zip to the /temp directory..
					if(is_file($saveToPath)){
						
						$archive = new PclZip($saveToPath);
						$list = $archive->extract(PCLZIP_OPT_PATH, $rootFolder);
						if(count($list) < 5 || !is_dir($rootFolder)){
							$packageErrors[] = "There was a problem unzipping the package delivered by the buzztouch.com API. This can be caused by a few things. Is it possible that this is a Windows Server? This software does not run on Windows powered servers.";
						}				
						
					}else{
						
						$packageErrors[] = "There was a problem finding the downloaded package on this server?";
					}
					
				}
				
			}//is_array errors
		
		}//packageErrors
	}//packageErrors
	//end get core project from buzztouch.com API...
	////////////////////////////////////////////////////////

	
	//at this point we have an unzipped folder downloaded from the buzztouch.com API parked in the /files/temp directory....
	if(count($packageErrors) < 1){
		if(is_dir($rootFolder)){
			
			//get the configuration data for this app (comes from this server)...
			$configDataString = fnGetConfigData($newAppGuid, $newAppApiKey, $newAppApiSecret);
			if(strlen($configDataString) > 10){
			
				//existing files so we don't add the same file to the project more than once...
				$existingFiles = array();

				////////////////////////////////////////////////////////////////////////////////////
				//iOS project...(not the latest)...		
				if(strtoupper($whatPlatform) == "IOS"){
					$originalDelegateName = "BT_appDelegate";
					$originalProjectName = "buzzTouch";
					$newDelegateName = $newProjectName . "_appDelegate";
					
					//build directories if they don't exist already...
					$makeFolders = array("BT_Config", "BT_Art", "BT_Video", "BT_Sound", "BT_Images", "BT_Docs", "BT_Plugins");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/" . $makeFolders[$d]);
						}
					}
					
					//over-write the config data...
					if($fh = fopen($rootFolder . "/BT_Config/BT_config.txt", 'w')){
						fwrite($fh, $configDataString);
						fclose($fh);		
					}
					
					//download / save app's icon using curl...
					fnSaveImageToTemp($newAppIconUrl);
					$saveAsImageName = basename($newAppIconUrl);
					$tmpIconPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
					
					//download app icon from remote server...Could be .jpg or .png
					if(strtolower(substr($newAppIconUrl, -4)) == ".jpg"){
						$icon = @imagecreatefromjpeg($tmpIconPath);
						
					}else{
						if(strtolower(substr($newAppIconUrl, -4)) == ".png"){
							$icon = @imagecreatefrompng($tmpIconPath);
						}
					}
					if($icon){
						
						//get icons original size...
						list($width_orig, $height_orig) = @getimagesize($tmpIconPath);
						if($width_orig > 0 && $height_orig > 0){
		
							//create a few new sizes...
							$sizes = array(57, 72, 114);
							for($s = 0; $s < count($sizes); $s++){
								$thisSize = $sizes[$s];
								$width = $thisSize;
								$height = $thisSize;
									
								$ratio_orig = $width_orig / $height_orig;
								if($width / $height > $ratio_orig){
								   $width = $height * $ratio_orig;
								}else{
								   $height = $width / $ratio_orig;
								}
									
								//resample
								$icon_scaled = @imagecreatetruecolor($width, $height);
								@imagecopyresampled($icon_scaled, $icon, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
									
								//output
								@imagepng($icon_scaled, $rootFolder . "/BT_Art/Icon_" . $thisSize . ".png");
							
							}//end for each size
							
						}//if original width / height > 0
					}//if icon...
					
					//process plugins...
					if($inClauseSQLPlugins != ""){
						$strSql = "SELECT webDirectoryName FROM " . TBL_BT_PLUGINS . " WHERE guid IN (" . $inClauseSQLPlugins . ")";
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						while($row = mysql_fetch_array($res)){
							$tmpPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins/" . ltrim($row["webDirectoryName"], "/");
							if(is_dir($tmpPath)){
								if(is_readable($tmpPath)){
									
									//copy all the files from the source-ios directory into the BT_plugins folder...
									$iosFolder = $tmpPath . "/source-ios-2.0";
									if(!is_dir($iosFolder)){
										$iosFolder = $tmpPath . "/source-ios";
									}
									if(is_dir($iosFolder)){
									
										$newFolder = $rootFolder . "/BT_Plugins/" . ltrim($row["webDirectoryName"], "/");
										@mkdir($newFolder, 0755);
									
										//copy / merge every file in the /source-ios folder to this newly created folder...
										if($handle = opendir($iosFolder)){
											while(false !== ($file = readdir($handle))){
												if(strlen($file) > 5){
													if(is_file($iosFolder . "/" . $file)){
														if($file != ".DS_Store" && $file != "__MACOSX"){
														
															//don't include if it's already been added
															if(!in_array($file, $existingFiles)){
															
																//copy the file...
																copy($iosFolder . "/". $file, $newFolder . "/" . $file);
															
																//if this is a .m or .h file, replace the app delegate name...
																$lastTwoChars = substr($file, strlen($file) - 2, 2);
																if(strtoupper($lastTwoChars) == ".H" || strtoupper($lastTwoChars) == ".M"){
																	fnReplaceText($newFolder . "/" . $file, $originalDelegateName, $newDelegateName);
																}
															
															}//existing files
															
															//remember file...
															$existingFiles[] = $file;
															
														}//not .DS_Store																	
													} //is_file...
												}//strlen(file)...
											}//end while...
										}//if open source-ios...
									}//is_dir /source-ios...
								}//is_readable for this plugin...
							}//is_dir for this plugin...
						}//end while plugins...
					}//inClauseSQLPlugins...
					
				}
				//end iOS
				////////////////////////////////////////////////////////////////////////////////////


				////////////////////////////////////////////////////////////////////////////////////
				//iOS LATEST project...		
				if(strtoupper($whatPlatform) == "IOSLATEST"){
					$originalDelegateName = "BT_appDelegate";
					$originalProjectName = "buzzTouch";
					$newDelegateName = $newProjectName . "_appDelegate";
					
					//build directories if they don't exist already...
					$makeFolders = array("BT_Config", "BT_Art", "BT_Video", "BT_Images", "BT_Docs", "BT_Plugins");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/" . $makeFolders[$d]);
						}
					}
					
					/*
					//copy the image assets directory...
					copy_directory(rtrim($rootFolder, "/") . "/" . $originalProjectName, rtrim($rootFolder, "/") . "/" . $newProjectName);
			
					//remove previous images assets directory...
					delete_directory(rtrim($rootFolder, "/") . "/" . $originalProjectName);
					*/
					
					//over-write the config data...
					if($fh = fopen($rootFolder . "/BT_Config/BT_config.txt", 'w')){
						fwrite($fh, $configDataString);
						fclose($fh);		
					}
					
					//download / save app's icon using curl...
					fnSaveImageToTemp($newAppIconUrl);
					$saveAsImageName = basename($newAppIconUrl);
					$tmpIconPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
					
					//download app icon from remote server...Could be .jpg or .png
					if(strtolower(substr($newAppIconUrl, -4)) == ".jpg"){
						$icon = @imagecreatefromjpeg($tmpIconPath);
						
					}else{
						if(strtolower(substr($newAppIconUrl, -4)) == ".png"){
							$icon = @imagecreatefrompng($tmpIconPath);
						}
					}
					if($icon){
						
						//get icons original size...
						list($width_orig, $height_orig) = @getimagesize($tmpIconPath);
						if($width_orig > 0 && $height_orig > 0){
		
							//create a few new sizes...
							$sizes = array(40, 80, 80, 120, 72, 144, 76, 152, 29, 50, 100, 29, 58, 58, 57, 114);
							$names = array("Icon-40", "Icon-40@2x-1", "Icon-40@2x", "Icon-60@2x", "Icon-72", "Icon-72@2x", "Icon-76", "Icon-76@2x", "Icon-Small-1", "Icon-Small-50", "Icon-Small-50@2x", "Icon-Small", "Icon-Small@2x-1", "Icon-Small@2x", "Icon", "Icon@2x");
							
							for($s = 0; $s < count($sizes); $s++){
								$thisSize = $sizes[$s];
								$width = $thisSize;
								$height = $thisSize;
									
								$ratio_orig = $width_orig / $height_orig;
								if($width / $height > $ratio_orig){
								   $width = $height * $ratio_orig;
								}else{
								   $height = $width / $ratio_orig;
								}
									
								//resample
								$icon_scaled = @imagecreatetruecolor($width, $height);
								@imagecopyresampled($icon_scaled, $icon, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
									
								//output
								@imagepng($icon_scaled, $rootFolder . "/" . $newProjectName . "/Images.xcassets/AppIcon.appiconset/" . $names[$s] . ".png");
								
							
							}//end for each size
							
						}//if original width / height > 0
					}//if icon...
					
					//process plugins...
					if($inClauseSQLPlugins != ""){
						$strSql = "SELECT webDirectoryName FROM " . TBL_BT_PLUGINS . " WHERE guid IN (" . $inClauseSQLPlugins . ")";
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						while($row = mysql_fetch_array($res)){
							$tmpPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins/" . ltrim($row["webDirectoryName"], "/");
							if(is_dir($tmpPath)){
								if(is_readable($tmpPath)){
									
									//copy all the files from the source-ios directory into the BT_plugins folder...
									$iosFolder = $tmpPath . "/source-ios-3.0";
									if(is_dir($iosFolder)){
									
										$newFolder = $rootFolder . "/BT_Plugins/" . ltrim($row["webDirectoryName"], "/");
										@mkdir($newFolder, 0755);
									
										//copy / merge every file in the /source-ios folder to this newly created folder...
										if($handle = opendir($iosFolder)){
											while(false !== ($file = readdir($handle))){
												if(strlen($file) > 5){
													if(is_file($iosFolder . "/" . $file)){
														if($file != ".DS_Store" && $file != "__MACOSX"){
														
															//don't include if it's already been added
															if(!in_array($file, $existingFiles)){
															
																//copy the file...
																copy($iosFolder . "/". $file, $newFolder . "/" . $file);
															
																//if this is a .m or .h file, replace the app delegate name...
																$lastTwoChars = substr($file, strlen($file) - 2, 2);
																if(strtoupper($lastTwoChars) == ".H" || strtoupper($lastTwoChars) == ".M"){
																	fnReplaceText($newFolder . "/" . $file, $originalDelegateName, $newDelegateName);
																}
															
															}//existing files
															
															//remember file...
															$existingFiles[] = $file;
															
														}//not .DS_Store																	
													} //is_file...
												}//strlen(file)...
											}//end while...
										}//if open source-ios...
									}//is_dir /source-ios...
								}//is_readable for this plugin...
							}//is_dir for this plugin...
						}//end while plugins...
					}//inClauseSQLPlugins...
					
				}
				//end iOS LATEST
				////////////////////////////////////////////////////////////////////////////////////




				////////////////////////////////////////////////////////////////////////////////////
				//Android project...(not the latest)...			
				if(strtoupper($whatPlatform) == "ANDROID"){

					$originalDelegateName = "BT_appDelegate";
					$newDelegateName = $newProjectName . "_appDelegate";
					$originalPackageName = "com.buzzTouch";
					$newPackageName = "com." . $newProjectName;
					
					//build directories if they don't exist already...
					$makeFolders = array("assets", "bin", "gen", "libs", "res", "src");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/" . $makeFolders[$d]);
						}
					}
					
					//make /assets sub-directories if they don't exist already...
					$makeFolders = array("BT_Video", "BT_Audio", "BT_Docs");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/assets/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/assets/" . $makeFolders[$d]);
						}
					}

					//make /res sub-directories if they don't exist already...
					$makeFolders = array("anim", "drawable", "drawable-hdpi", "drawable-ldpi", "drawable-mdpi", "layout", "values");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/res/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/res/" . $makeFolders[$d]);
						}
					}

					//make /src sub-directories if they don't exist already...
					if(!is_dir($rootFolder . "/src/com")) @mkdir($rootFolder . "/src/com");
					if(!is_dir($rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName))) @mkdir($rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName));
						
					//over-write (or create) the config data...
					if($fh = fopen($rootFolder . "/assets/BT_config.txt", 'w')){
						fwrite($fh, $configDataString);
						fclose($fh);		
					}
					
					//download / save app's icon using curl...
					fnSaveImageToTemp($newAppIconUrl);
					$saveAsImageName = basename($newAppIconUrl);
					$tmpIconPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
					
					//get app's icon, could be .jpg or .png
					if(strtolower(substr($newAppIconUrl, -4)) == ".jpg"){
						$icon = @imagecreatefromjpeg($tmpIconPath);
					}else{
						if(strtolower(substr($newAppIconUrl, -4)) == ".png"){
							$icon = @imagecreatefrompng($tmpIconPath);
						}
					}
					if($icon){
						
						//get icons original size...
						list($width_orig, $height_orig) = @getimagesize($tmpIconPath);
						if($width_orig > 0 && $height_orig > 0){
				
							//create a few new sizes...
							$sizes = array(36, 48, 57, 72);
							
							//save them here...
							$drawableFolders = array("drawable-ldpi", "drawable-mdpi", "drawable", "drawable-hdpi");
							
							for($s = 0; $s < count($sizes); $s++){
								$thisSize = $sizes[$s];
								$width = $thisSize;
								$height = $thisSize;
									
								$ratio_orig = $width_orig / $height_orig;
								if($width / $height > $ratio_orig){
								   $width = $height * $ratio_orig;
								}else{
								   $height = $width / $ratio_orig;
								}
									
								//resample
								$icon_scaled = @imagecreatetruecolor($width, $height);
								@imagecopyresampled($icon_scaled, $icon, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
									
								//output
								@imagepng($icon_scaled, $rootFolder . "/res/" . $drawableFolders[$s] . "/icon.png");
									
							}//end for each size
						
						}//width / height
					}//if icon...
					
					
					/*
						Android Activitites: The AndroidManifest.xml file has a line that reads:
						<!-- replace this with list of activity includes -->
						We'll end up replacing that line with a list of activity files. 														
					*/
					$activitiesList = "";
					
					/* 
						Using Maps: The AndroidManifest.xml file has a line that reads:
						<!-- <uses-library android:name="com.google.android.maps"/> -->
						We'll end up replacing that line with an uncommented version like this:
						<uses-library android:name="com.google.android.maps"/>
						if the application is using a BT_screen_map plugin.
					*/
					$bolUsingMaps = false;
					
					
					//process plugins...
					if($inClauseSQLPlugins != ""){
						$strSql = "SELECT webDirectoryName FROM " . TBL_BT_PLUGINS . " WHERE guid IN (" . $inClauseSQLPlugins . ")";
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						if($res){
							while($row = mysql_fetch_array($res)){
								$tmpPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $row["webDirectoryName"];
								if(is_dir($tmpPath)){
									if(is_readable($tmpPath)){
										
										//look for source code...For Android we don't make any folders, we just copy the 
										//contents of the source-android to the appropriate directory in the Android project
										
										$audioDir = $rootFolder . "/assets/BT_Audio";
										$videoDir = $rootFolder . "/assets/BT_Video";
										$docsDir = $rootFolder . "/assets/BT_Docs";
										$srcDir = $rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName);
										$layoutDir = $rootFolder . "/res/layout";
										$drawableDir = $rootFolder . "/res/drawable";
										$jarDir = $rootFolder . "/libs";
										
										//copy all the source files from the source-android folder...
										$androidFolder = $tmpPath . "/source-android-2.0";
										if(!is_dir($androidFolder)){
											$androidFolder = $tmpPath . "/source-android-activity";
										}
										if(!is_dir($androidFolder)){
											$androidFolder = $tmpPath . "/source-android";
										}
										if(is_dir($androidFolder)){
										
											//copy every file in the /source-android folder to the proper Android folder...
											if($handle = opendir($androidFolder)){
												while(false !== ($file = readdir($handle))){
													if(strlen($file) > 5){
														if(is_file($androidFolder . "/" . $file)){
															if($file != ".DS_Store" && $file != "__MACOSX"){
															
																//figure out what folder to put it in...
																$newFolder = "";
																$ext = substr(strrchr($file,'.'), 1);
																
																switch (strtoupper($ext)){
																	case "DOC":
																	case "XLS":
																	case "PDF":
																	case "PPT":
																	case "HTML":
																	case "HTM":
																	case "TXT":
																	case "JS":
																	case "CSS":
																		$newFolder = $docsDir;
																		break;
																	case "MOV":
																	case "MPEG":
																	case "MP4":
																		$newFolder = $videoDir;
																		break;
																	case "MP3":
																		$newFolder = $audioDir;
																		break;
																	case "PNG":
																	case "JPG":
																	case "JPEG":
																		$newFolder = $drawableDir;
																		break;
																	case "XML":
																		$newFolder = $layoutDir;
																		break;
																	case "JAVA":
																		$newFolder = $srcDir;
																		break;
																	case "JAR":
																		$newFolder = $jarDir;
																		break;
																}
																
																//if we figured it out...
																if($newFolder != ""){
																	
																	//copy the file if it's not already in the list...
																	if(!in_array($file, $existingFiles)){
																		
																		copy($androidFolder . "/". $file, $newFolder . "/" . $file);
																		
																		//if this is a .java file, replace the app delegate name...
																		$lastFiveChars = substr($file, strlen($file) - 5, 5);
																		if(strtoupper($lastFiveChars) == ".JAVA"){
																			fnReplaceText($newFolder . "/" . $file, $originalDelegateName, $newDelegateName);
																			fnReplaceText($newFolder . "/" . $file, $originalPackageName, $newPackageName);
	
																			//include this activity in our list to add to AndroidManifest.xml
																			$activitiesList .= "\n<activity android:name=\"." . str_replace(".java", "", $file) . "\" android:label=\"@string/app_name\" android:configChanges=\"keyboardHidden|orientation\"></activity>";			
	
																			//are we using maps?
																			if($file == "BT_screen_map.java"){
																				$bolUsingMaps = true;
																			}
	
																		}
																	}
																	
																	//remember file...
																	$existingFiles[] = $file;
																
																}
															
															}//not .DS_Store...																	
														}//is_file...
													}//filename length...
												}//end while on each file in android folder...	
											}//if open source-android...
										}//is_dir /source-android...
									}//is_readable...
								}//is_dir...		
							}//while...
						}//res...
					}//inClauseSQLPlugins...
				
					//merge $activitiesList into AndroidManfest.xml
					if(is_file($rootFolder . "/AndroidManifest.xml")){
						fnReplaceText($rootFolder . "/AndroidManifest.xml", "<!-- replace this with list of activity includes -->", $activitiesList);
					}
					
					//replace commented maps line if needed...
					if($bolUsingMaps){
						fnReplaceText($rootFolder . "/AndroidManifest.xml", "<!-- <uses-library android:name=\"com.google.android.maps\"/> -->", "<uses-library android:name=\"com.google.android.maps\"/>");
					}

				}
				//end android...
				////////////////////////////////////////////////////////////////////////////////////



				////////////////////////////////////////////////////////////////////////////////////
				//Android LATEST project...				
				if(strtoupper($whatPlatform) == "ANDROIDLATEST"){

					$originalDelegateName = "BT_appDelegate";
					$newDelegateName = $newProjectName . "_appDelegate";
					$originalPackageName = "com.buzzTouch";
					$newPackageName = "com." . $newProjectName;
					
					//build directories if they don't exist already...
					$makeFolders = array("assets", "bin", "gen", "libs", "res", "src");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/" . $makeFolders[$d]);
						}
					}
					
					//make /assets sub-directories if they don't exist already...
					$makeFolders = array("BT_Video", "BT_Audio", "BT_Docs");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/assets/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/assets/" . $makeFolders[$d]);
						}
					}

					//make /res sub-directories if they don't exist already...
					$makeFolders = array("anim", "drawable", "drawable-hdpi", "drawable-ldpi", "drawable-mdpi", "layout", "values");
					for($d = 0; $d < count($makeFolders); $d++){
						if(!is_dir($rootFolder . "/res/" . $makeFolders[$d])){
							@mkdir($rootFolder . "/res/" . $makeFolders[$d]);
						}
					}

					//make /src sub-directories if they don't exist already...
					if(!is_dir($rootFolder . "/src/com")) @mkdir($rootFolder . "/src/com");
					if(!is_dir($rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName))) @mkdir($rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName));
						
					//over-write (or create) the config data...
					if($fh = fopen($rootFolder . "/assets/BT_config.txt", 'w')){
						fwrite($fh, $configDataString);
						fclose($fh);		
					}
					
					//download / save app's icon using curl...
					fnSaveImageToTemp($newAppIconUrl);
					$saveAsImageName = basename($newAppIconUrl);
					$tmpIconPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
					
					//get app's icon, could be .jpg or .png
					if(strtolower(substr($newAppIconUrl, -4)) == ".jpg"){
						$icon = @imagecreatefromjpeg($tmpIconPath);
					}else{
						if(strtolower(substr($newAppIconUrl, -4)) == ".png"){
							$icon = @imagecreatefrompng($tmpIconPath);
						}
					}
					if($icon){
						
						//get icons original size...
						list($width_orig, $height_orig) = @getimagesize($tmpIconPath);
						if($width_orig > 0 && $height_orig > 0){
				
							//create a few new sizes...
							$sizes = array(36, 48, 57, 72);
							
							//save them here...
							$drawableFolders = array("drawable-ldpi", "drawable-mdpi", "drawable", "drawable-hdpi");
							
							for($s = 0; $s < count($sizes); $s++){
								$thisSize = $sizes[$s];
								$width = $thisSize;
								$height = $thisSize;
									
								$ratio_orig = $width_orig / $height_orig;
								if($width / $height > $ratio_orig){
								   $width = $height * $ratio_orig;
								}else{
								   $height = $width / $ratio_orig;
								}
									
								//resample
								$icon_scaled = @imagecreatetruecolor($width, $height);
								@imagecopyresampled($icon_scaled, $icon, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
									
								//output
								@imagepng($icon_scaled, $rootFolder . "/res/" . $drawableFolders[$s] . "/icon.png");
									
							}//end for each size
						
						}//width / height
					}//if icon...
					
					//process plugins...
					if($inClauseSQLPlugins != ""){
						$strSql = "SELECT webDirectoryName FROM " . TBL_BT_PLUGINS . " WHERE guid IN (" . $inClauseSQLPlugins . ")";
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						if($res){
							while($row = mysql_fetch_array($res)){
								$tmpPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $row["webDirectoryName"];
								if(is_dir($tmpPath)){
									if(is_readable($tmpPath)){
										
										//directories to scan for this plugin (not including this src folder)...
										$scanDir = array();
										$scanDir[] = "/assets/BT_Audio";
										$scanDir[] = "/assets/BT_Docs";
										$scanDir[] = "/assets/BT_Video";
										$scanDir[] = "/libs";
										$scanDir[] = "/res/anim";
										$scanDir[] = "/res/drawable";
										$scanDir[] = "/res/layout";
										$scanDir[] = "/res/values";
										
										//copy all the source files from the source-android folder...
										$androidFolder = $tmpPath . "/source-android-3.0";
										if(!is_dir($androidFolder)){
											$androidFolder = $tmpPath . "/source-android-fragment";
										}
										
										if(is_dir($androidFolder)){
										
											//process the "src" files first...
											$tmpDir = "/src/com/buzzTouch";
											if(is_dir($androidFolder . $tmpDir)){
												if($handle = opendir($androidFolder . $tmpDir)){
												
													//copy every file in this directory...
													while(false !== ($file = readdir($handle))){
														if(strlen($file) > 5){
															if(is_file($androidFolder . $tmpDir . "/" . $file)){
																if($file != ".DS_Store" && $file != "__MACOSX"){
																
																	//copy the file if it's not already in the list...
																	if(!in_array($file, $existingFiles)){
																		
																		copy($androidFolder . $tmpDir . "/". $file, $rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName) . "/" . $file);
																		
																		//if this is a .java file, replace the app delegate name...
																		$lastFiveChars = substr($file, strlen($file) - 5, 5);
																		if(strtoupper($lastFiveChars) == ".JAVA"){
																			fnReplaceText($rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName) . "/" . $file, $originalDelegateName, $newDelegateName);
																			fnReplaceText($rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName) . "/" . $file, $originalPackageName, $newPackageName);
																		}
																	}
																	
																	//remember file to prevent dups...
																	$existingFiles[] = $file;
																	
																
																}//not .DS_Store...																	
															}//is_file...
														}//filename length...
													}//end while on each file in src folder...	
												}//handle...
											}//is_dir...													
										
										
											//copy all other files from all other directories in plugin package...
											for($d = 0; $d < count($scanDir); $d++){
												$tmpDir = $scanDir[$d];
												if(is_dir($androidFolder . $tmpDir)){
													if($handle = opendir($androidFolder . $tmpDir)){
													
														//copy every file in this directory...
														while(false !== ($file = readdir($handle))){
															if(strlen($file) > 5){
																if(is_file($androidFolder . $tmpDir . "/" . $file)){
																	if($file != ".DS_Store" && $file != "__MACOSX"){
																	
																		//copy the file if it's not already in the list...
																		if(!in_array($file, $existingFiles)){
																			
																			copy($androidFolder . $tmpDir . "/". $file, $rootFolder . $tmpDir . "/" . $file);
																			
																			//if this is a .java file, replace the app delegate name...
																			$lastFiveChars = substr($file, strlen($file) - 5, 5);
																			if(strtoupper($lastFiveChars) == ".JAVA"){
																				fnReplaceText($rootFolder . $tmpDir . "/" . $file, $originalDelegateName, $newDelegateName);
																				fnReplaceText($rootFolder . $tmpDir . "/" . $file, $originalPackageName, $newPackageName);
																			}
																		}
																		
																		//remember file to prevent dups...
																		$existingFiles[] = $file;
																		
																	
																	}//not .DS_Store...																	
																}//is_file...
															}//filename length...
														}//end while on each file in android folder...	
													}//handle...
												}//is_dir...													
											}//for...
											
											
										}//is_dir /source-android-fragment...
									
									}//is_readable...
								}//is_dir...		
							}//while...
						}//res...
					}//inClauseSQLPlugins...
				}
				//end android latest...
				////////////////////////////////////////////////////////////////////////////////////
			
			}else{
				$packageErrors[] =  "There was a problem finding the configuration data for this app.";
			}
			
		}else{
			$packageErrors[] = "There was a problem creating a directory in the /temp folder.";
		}
	}//packageErrors
	
	
	//.zip up the project if all is well...
	if(count($packageErrors) < 1){

		$archiveNew = new PclZip($saveToPath);
		$v_dir = $rootFolder;
		$v_remove = $v_dir;
	
		//next three lines needed to support windows and the C: root  
		if(substr($v_dir, 1, 1) == ':'){
			$v_remove = substr($v_dir, 2);
		}
		
		$v_list = $archiveNew->create($v_dir, PCLZIP_OPT_REMOVE_PATH, $v_remove);
		if($v_list == 0){
		
			//error creating zip...
			$packageErrors[] = "Error creating downloadable .zip after filling with application assets. Is it possible that this is a Windows Server? This software does not run on Windows powered servers.";
		
		}else{
		
			//get size of new .zip...
			$newDownloadSize = filesize($saveToPath);
		
		}
		
		//clean up so we're only left with the new .zip..
		if(is_dir($rootFolder)){
			fnRemoveDirectory($rootFolder);
		}

	}//packageErrors

	//return JSON...
	if(count($packageErrors) > 0){
	
		//return invalid results...
		echo "{";
			echo "\"result\":\"invalid\", ";
			echo "\"errors\":[";
			$tmp = "";
			for($x = 0; $x < count($packageErrors); $x++){
				$tmp .= "\"" . $packageErrors[$x] . "\",";
			} 
			echo rtrim($tmp, ",");
			echo "]";	
		echo "}";
		
		//bail...
		exit();
		
	}else{
	
		//return valid results...
		echo "{";
			echo "\"result\":\"valid\", ";
			echo "\"projectURL\":\"" . $newDownloadURL . "\", ";
			echo "\"projectSize\":\"" . fnFormatBytes($newDownloadSize) . "\"";
		echo "}";
		
		//bail...
		exit();
	
	
	}
	
	//should not ever get here!
	echo "done";
	exit();


	
?>

