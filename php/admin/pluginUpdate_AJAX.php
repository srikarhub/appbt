<?php   require_once("../config.php");
		require_once("../includes/zip.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	if($guid == ""){
		echo "<span style='color:red;'>Logged out</span>";
		exit();
	}	
		
	//init user object...
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);

	//vars...
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$pluginGuid = fnGetReqVal("pluginGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$uniquePluginId = fnGetReqVal("uniquePluginId", "", $myRequestVars);
	$webDirectoryName = fnGetReqVal("webDirectoryName", "", $myRequestVars);
	$downloadURL = fnGetReqVal("downloadURL", "", $myRequestVars);
	
	//must have a plugin selected...
	if(strlen($pluginGuid) < 1 && strtoupper($command) == "CHECKFORUPDATES"){
		echo "invalid request";
		exit();
	}	
	
	
	/////////////////////////////////////////////////////////////////////////
	//refresh plugins routine..
	function fnRefreshPlugins($userGuid){
		
		//date...
		$dtNow = fnMySqlNow();
		
		//return this...
		$r = "<div>";

		$pluginFolder = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins";
		if($handle = opendir($pluginFolder)){
			$r .= "<table style='width:100%;margin-top:10px;' cellspacing='0' cellpadding='0'>";
				
				$r .= "\n<tr class=''>";
					$r .= "\n<td class='tdSort' style='padding-left:5px;'>&nbsp;</td>";
					$r .= "\n<td class='tdSort' style='padding-left:5px;'><b>Plugin Name</b></td>";
					$r .= "\n<td class='tdSort' style='padding-left:5px;'><b>Category</b></td>";
					$r .= "\n<td class='tdSort' style='padding-left:5px;'><b>Version</b></td>";
				$r .= "\n</tr>";
		
				$cnt = 0;
				while(false !== ($filename = readdir($handle))){
					
					if(strlen($filename) > 3 && 
						strtoupper(substr($filename, 0, 8)) != "__MACOSX" &&
						strtoupper(substr($filename, 0, 10)) != ".DS_STORE"){
						$result = "";
						$cnt++;
						$css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal";
						
						//fill for each plugin...
						$uniquePluginId = "";
						$result = "";
						$displayAs = "";
						$category = "";
						$versionNumber = "";
						$versionString = "";
						$supportedDevices = "";
						
						//validate each plugin, then get it's info...
						$objPlugin = new Plugin("", "");
						$thisFolder = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins/" . $filename;
						$errors = $objPlugin->fnCheckPluginContents($thisFolder);
						if(count($errors) > 0){
							$result = "<div style='color:red;'><img src='../images/red_dot.png' style='vertical-align:middle;margin-right:3px;'>/plugins/" . $filename . "<br>directory contents are invalid because...</div>";
							foreach($errors as $key => $value){
								$result .= "<div>" . $value . "</div>";
							}
						}else{
							
							//see if this plugin has an index.php page...
							$bolHasIndexPhpFile = false;
							if(is_file(rtrim($thisFolder, "/") . "/index.php")){
								$bolHasIndexPhpFile = true;
							}
							
							
							//get all the field data for this plugin...
							$info = $objPlugin->fnGetPluginInfo($thisFolder);
						
							$uniquePluginId = $info["uniquePluginId"];
							$displayAs = $info["displayAs"];
							$category = $info["category"];
							$loadClassOrActionName = $info["loadClassOrActionName"];
							$hasChildItems = $info["hasChildItems"];
							$supportedDevices = $info["supportedDevices"];
							$versionNumber = $info["versionNumber"];
							$versionString = $info["versionString"];
							$defaultJsonVars = $info["defaultJsonVars"];
							$shortDescription = $info["shortDescription"];
							$updateURL = $info["updateURL"];
							$downloadURL = $info["downloadURL"];
							$authorName = $info["authorName"];
							$authorEmail = $info["authorEmail"];
							$authorBuzztouchURL = $info["authorBuzztouchURL"];
							$authorWebsiteURL = $info["authorWebsiteURL"];
							$authorTwitterURL = $info["authorTwitterURL"];
							$authorFacebookURL = $info["authorFacebookURL"];
							$authorLinkedInURL = $info["authorLinkedInURL"];
							$authorYouTubeURL = $info["authorYouTubeURL"];
							
							//update or insert?
							$tmp = "SELECT guid FROM " . TBL_BT_PLUGINS . " WHERE uniquePluginId = '" . fnFormInput($uniquePluginId) . "' LIMIT 0, 1";
							$existingPluginGuid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
							
							//if plugin is already in the database, update it...
							if($existingPluginGuid != ""){
								
								//update existing plugin...
								$objPlugin = new Plugin($existingPluginGuid);
								$objPlugin->infoArray["category"] = $category;
								$objPlugin->infoArray["displayAs"] = $displayAs;
								$objPlugin->infoArray["versionNumber"] = $versionNumber;
								$objPlugin->infoArray["versionString"] = $versionString;
								$objPlugin->infoArray["loadClassOrActionName"] = $loadClassOrActionName;
								$objPlugin->infoArray["hasChildItems"] = $hasChildItems;
								$objPlugin->infoArray["supportedDevices"] = $supportedDevices;
								$objPlugin->infoArray["defaultJsonVars"] = $defaultJsonVars;
								$objPlugin->infoArray["webDirectoryName"] = "/" . $filename;
								$objPlugin->infoArray["shortDescription"] = $shortDescription;
								$objPlugin->infoArray["authorName"] = $authorName;
								$objPlugin->infoArray["authorEmail"] = $authorEmail;
								$objPlugin->infoArray["authorBuzztouchURL"] = $authorBuzztouchURL;
								$objPlugin->infoArray["authorWebsiteURL"] = $authorWebsiteURL;
								$objPlugin->infoArray["authorTwitterURL"] = $authorTwitterURL;
								$objPlugin->infoArray["authorFacebookURL"] = $authorFacebookURL;
								$objPlugin->infoArray["authorLinkedInURL"] = $authorLinkedInURL;
								$objPlugin->infoArray["authorYouTubeURL"] = $authorYouTubeURL;
								$objPlugin->infoArray["updateURL"] = $updateURL;
								$objPlugin->infoArray["downloadURL"] = $downloadURL;
								$objPlugin->infoArray["modifiedUTC"] = $dtNow;
								$objPlugin->infoArray["modifiedByGuid"] = $userGuid;
								
								//does this plugin have an index.php page...
								if($bolHasIndexPhpFile){
									$objPlugin->infoArray["landingPage"] = "";
								}else{
									$objPlugin->infoArray["landingPage"] = "bt_screen.php";
								}
								
								//execute update...
								$objPlugin->fnUpdate();
								
								
								$result = "<div style='color:green;'><img src='../images/green_dot.png' style='vertical-align:middle;margin-right:3px;'>OK, updated</div>";
								
							}else{
								
								//create a new plugin...
								$objPlugin = new Plugin("");
								$objPlugin->infoArray["guid"] = strtoupper(fnCreateGuid());
								$objPlugin->infoArray["uniquePluginId"] = $uniquePluginId;
								$objPlugin->infoArray["category"] = $category;
								$objPlugin->infoArray["displayAs"] = $displayAs;
								$objPlugin->infoArray["versionNumber"] = $versionNumber;
								$objPlugin->infoArray["versionString"] = $versionString;
								$objPlugin->infoArray["loadClassOrActionName"] = $loadClassOrActionName;
								$objPlugin->infoArray["hasChildItems"] = $hasChildItems;
								$objPlugin->infoArray["supportedDevices"] = $supportedDevices;
								$objPlugin->infoArray["defaultJsonVars"] = $defaultJsonVars;
								$objPlugin->infoArray["webDirectoryName"] = "/" . $filename;
								$objPlugin->infoArray["shortDescription"] = $shortDescription;
								$objPlugin->infoArray["authorName"] = $authorName;
								$objPlugin->infoArray["authorEmail"] = $authorEmail;
								$objPlugin->infoArray["authorBuzztouchURL"] = $authorBuzztouchURL;
								$objPlugin->infoArray["authorWebsiteURL"] = $authorWebsiteURL;
								$objPlugin->infoArray["authorTwitterURL"] = $authorTwitterURL;
								$objPlugin->infoArray["authorFacebookURL"] = $authorFacebookURL;
								$objPlugin->infoArray["authorLinkedInURL"] = $authorLinkedInURL;
								$objPlugin->infoArray["updateURL"] = $updateURL;
								$objPlugin->infoArray["downloadURL"] = $downloadURL;
								$objPlugin->infoArray["dateStampUTC"] = $dtNow;
								$objPlugin->infoArray["modifiedUTC"] = $dtNow;
								$objPlugin->infoArray["modifiedByGuid"] = $userGuid;
								$objPlugin->fnInsert();
					
								$result = "<div style='color:green;'><img src='../images/green_dot.png' style='vertical-align:middle;margin-right:3px;'>OK</div>";
								$displayAs = $displayAs . " <span style='color:green;'><i>added</i></span>";
							
							}//plugin does not exist in the database...

						
						}//no errors
							
						//print results...
						$r .= "\n<tr class='" . $css . "'>";
							$r .= "\n<td class='data' style='padding-left:5px;vertical-align:top;'>" . $result . "</td>";
							$r .= "\n<td class='data' style='padding-left:5px;'>" . $displayAs . "</td>";
							$r .= "\n<td class='data' style='padding-left:5px;'>" . fnFormatProperCase($category) . "</td>";
							$r .= "\n<td class='data' style='padding-left:5px;'>" . $versionString . "</td>";
						$r .= "\n</tr>";
					
					}//not a __MACOSX file...
				}//end while file handle
				
				//finish table...
				$r .= "</table>";
	
				//loop all plugins and remove those that don't have a directory...
				$tmpSql = " SELECT id, uniquePluginId, webDirectoryName FROM " . TBL_BT_PLUGINS;
				$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($res){
					while($row = mysql_fetch_array($res)){
						if(!is_dir(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins/" . ltrim($row["webDirectoryName"], "/"))){
							$tmp = "DELETE FROM " . TBL_BT_PLUGINS . " WHERE id = " . $row["id"];
							fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						}
					}
				}
				
	
		}else{
			$r .= "<br/>There was a problem finding the " . APP_DATA_DIRECTORY . "/plugins directory";
		}
		
		$r .= "</div>";
		
		//return...
		return $r;
	
	}//refresh plugins...
	/////////////////////////////////////////////////////////////////////////
	
	
	
	
	
	///////////////////////////////////////////////////////
	//form posted...
	if($isFormPost || strtoupper($command) == "CHECKFORUPDATES"){
		
		/////////////////////////////////////////////////////////////////////////////
		//refresh plugin list....
		if(strtoupper($command) == "REFRESHPLUGINS"){
		
			//trigger refresh routine...
			$r = fnRefreshPlugins($guid);
			
			//print this...
			echo "<div style='color:black'>";
				echo $r;
			echo "</div>";
			
			//exit..
			exit();
		
		
		}//refreshPlugins
		/////////////////////////////////////////////////////////////////////////////
		
		/////////////////////////////////////////////////////////////////////////////
		//checking for updates....
		if(strtoupper($command) == "CHECKFORUPDATES"){
		
			//plugin id...
			if($pluginGuid == ""){
				$bolPassed = false;
				$strMessage .= "<br>Plugin id required";
			}
	
			//make sure this plugin has an update URL and a downloadURL
			$objPlugin = new Plugin($pluginGuid);
			$updateURL = $objPlugin->infoArray["updateURL"];
			$downloadURL = $objPlugin->infoArray["downloadURL"];
			
			if($updateURL == ""){
				$bolPassed = false;
				$strMessage .= "<br>No update URL provided";
			}
			if($downloadURL == ""){
				$bolPassed = false;
				$strMessage .= "<br>No download URL provided";
			}
	
	
			//good?
			if($bolPassed){
				
				//init a cURL object to the update URL
				$ch = curl_init($updateURL);
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								
				//get result from buzztouch.com API...
				$versionStringOnServer = curl_exec($ch);
						
				if($versionStringOnServer == ""){
					$bolPassed = false;
					$strMessage .= "<br>Error (1)";
				}else{
					//if version string is long....something went wrong (like a 404?)
					if(strlen($versionStringOnServer) > 50){
						$bolPassed = false;
						$strMessage = "Error (3)";
					}else{
						if(strtoupper($versionStringOnServer) != strtoupper($objPlugin->infoArray["versionString"])){
							$strMessage = "<span style='color:red;'>" . $versionStringOnServer . " Available</span>";
							$strMessage .= "<div style='padding-top:3px;'>";
								$strMessage .= "<a href='#' onClick=\"installPlugin('" . $objPlugin->infoArray["guid"] . "', '" . $objPlugin->infoArray["uniquePluginId"] . "', '" . $objPlugin->infoArray["webDirectoryName"] . "', '" . $objPlugin->infoArray["downloadURL"] . "');return false;\" title='install'>download and install</a>";
							$strMessage .= "</div>";
							
						}else{
							$strMessage = "<span style='color:green;'>OK: " . $versionStringOnServer . "</span>";
						}
					}			
				}
						
				//close connection
				curl_close($ch);	
				
			}
	
	
			//update....	
			if(!$bolPassed){
				
				echo "<div style='padding-top:3px;'><img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Error! " . $strMessage . "</span></div>";
				exit();
				
			}else{
		
				//done...
				echo "<div style='padding-top:3px;'><img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'>" . $strMessage . "</div>";
				exit();		
				
			
			}//bolPassed
		
		}//checkForUpdates....
		/////////////////////////////////////////////////////////////////////////////
		
		/////////////////////////////////////////////////////////////////////////////
		//getInstalledPlugins...
		if(strtoupper($command) == "GETINSTALLEDPLUGINS"){
		
			//count the troubles...
			$errors = array();
			$htmlRows = "";
		
			//key/value pairs to send in the request...
			$postVars = "";
			$fields = array(
			
				//needed by the buzztouch.com api to validate this request
				"apiKey" => urlencode(APP_BT_SERVER_API_KEY),
				"apiSecret" => urlencode(APP_BT_SERVER_API_KEY_SECRET),
				"command" => urlencode("getInstalledPlugins"),
					
				//required by "getInstalledPlugins" command...
				"userLoginId" => urlencode(APP_BT_ACCOUNT_USEREMAIL),
				"userPassword" => urlencode(APP_BT_ACCOUNT_USERPASS)
					
			);
			
			//prepare the data for the POST
			foreach($fields as $key => $value){ 
				$postVars .= $key . "=" . $value . "&"; 
			}
			
			//setup api url
			$apiURL = rtrim(APP_BT_SERVER_API_URL, "/");
			
			//init a cURL object, set number of POST vars and the POST data
			$ch = curl_init($apiURL . "/plugins/");
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, count($fields));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
			
			//get JSON result from buzztouch.com  API
			$jsonResult = curl_exec($ch);
			
			//close connection
			curl_close($ch);
			
			//decode json vars
			if($jsonResult != ""){
				$json = new Json; 
				$decoded = $json->unserialize($jsonResult);
				if(is_object($decoded)){

					//must have a result...
					if(array_key_exists("result", $decoded)){
						$results = $decoded -> result;
						
						//must have a status...
						if(array_key_exists("status", $results)){
							$status = $results -> status;
						}else{
							$bolPassed = false;
							$strMessage .= "<br>API result does not contain a required field: status missing.";
						}
						
					}else{
						$bolPassed = false;
						$strMessage .= "<br>API result does not contain a required field: result missing.";
					}
	
					//still no errors?
					if(count($errors) < 1){
					
						//success means we'll get a package URL from the API...
						if(strtoupper($status) == "VALID"){
							
							if(array_key_exists("plugins", $results)){
								
								
								//build arrays for already installed plugins, along with verison info...
								$installed = array();
								$versions = array();
								
								//fetch
								$strSql = " SELECT P.guid, P.category, P.uniquePluginId, P.versionString, P.displayAs, P.loadClassOrActionName, P.webDirectoryName, ";
								$strSql .= " P.dateStampUTC, P.shortDescription ";
								$strSql .= "FROM " . TBL_BT_PLUGINS . " AS P ";
								$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
								if($res){
									while($row = mysql_fetch_array($res)){
										
										$installed[] = $row['uniquePluginId'];
										$versions[] = $row['versionString'];
							
									}//while...
								}//res...
								
								//title...
								$htmlRows .= "<div style='padding-top:10px;color:#000000;'>";
									$htmlRows .= "Installed Plugins for <b>" . APP_BT_ACCOUNT_USEREMAIL . "</b> at buzztouch.com";
									$htmlRows .= "&nbsp;|&nbsp;";
									$htmlRows .= "<a href='#'  onClick=\"downloadPlugins();return false;\">refresh</a>";
								$htmlRows .= "</div>";
								
								
								//start table...
								$htmlRows .= "<table cellspacing='0' cellpadding='0' style='margin-top:10px;'>";

								//counter...
								$cnt = 0;
								
								//loop...
								$pluginList = $results -> plugins;
								for($x = 0; $x < count($pluginList); $x++){
									$cnt++;
									
                                    //style
                                    $css = (($cnt % 2) == 0) ? "rowNormal" : "rowAlt" ;
									
									//installed message...
									$tmpInstalled = "";
									$tmpInstallLink = "";
									if(in_array($pluginList[$x] -> uniquePluginId, $installed)){
										$tmpInstalled = "<span style='color:green;'><i>" . $pluginList[$x] -> versionString . " installed</i></span> | ";
										$tmpInstallLink = "<a href='#' onClick=\"installPlugin('" . $pluginList[$x] -> guid . "', '" . $pluginList[$x] -> uniquePluginId . "', '" . $pluginList[$x] -> directoryName . "', '" . $pluginList[$x] -> downloadURL . "');return false;\" title='update'>update</a>";
									}else{
										$tmpInstalled = "<span style='color:red;'><i>" . $pluginList[$x] -> versionString . " not installed</i></span> | ";
										$tmpInstallLink = "<a href='#' onClick=\"installPlugin('" . $pluginList[$x] -> guid . "', '" . $pluginList[$x] -> uniquePluginId . "', '" . $pluginList[$x] -> directoryName . "', '" . $pluginList[$x] -> downloadURL . "');return false;\" title='install'>install</a>";
									}
									
									
									$pad = "&nbsp;&nbsp;|&nbsp;&nbsp;";
									$htmlRows .=   "\n\n<tr class='" . $css . "'>";
										$htmlRows .=   "\n<td class='data' rowspan='2' style='vertical-align:middle;padding-left:5px;width:50px;height:50px;border-bottom:1px solid #999999;color:#000000;'>";
											$htmlRows .=   "<img src='" . $pluginList[$x] -> iconURL . "' style='height:50px;width:50px;' alt='Plugin icon'/>";
										$htmlRows .=   "</td>";
										$htmlRows .=   "\n<td class='data' style='padding-left:5px;padding-top:5px;border-bottom:1px solid #999999;color:#000000;'>";
											$htmlRows .=   "<a href='" . $pluginList[$x] -> detailsURL . "' target='_blank' title='Details'>" . fnFormOutput($pluginList[$x] -> displayAs) . "</a>";
										$htmlRows .=   "</td>";
										$htmlRows .=   "\n<td rowspan='2'  class='data' style='vertical-align:top;text-align:right;padding-right:5px;padding-top:5px;border-bottom:1px solid #999999;color:#000000;'>";
											$htmlRows .= "<div id='controls_" . $pluginList[$x] -> guid . "' style='display:block;'>";
												$htmlRows .= $tmpInstalled;
												$htmlRows .=  $tmpInstallLink;
											$htmlRows .= "</div>";
											$htmlRows .= "<div id=\"submit_" . $pluginList[$x] -> guid . "\" class=\"submit_working\" style='padding-top:0px;margin-top:0px;'></div>";
										$htmlRows .=   "</td>";
									$htmlRows .=   "\n</tr>";
									$htmlRows .=   "\n\n<tr class='" . $css . "'>";
										$htmlRows .=   "<td class='data' style='padding-top:5px;padding-bottom:5px;white-space:normal;border-bottom:1px solid #999999;color:#000000;'>";
											$htmlRows .= fnFormOutput($pluginList[$x] -> shortDescription);
										$htmlRows .=   "</td>";
									$htmlRows .=   "</tr>";
									
								}//for each plugin...
								
								//end table...
								$htmlRows .= "</table>";
								
							}
							
						}else{
					
							//show the errors...
							if(array_key_exists("errors", $results)){
								$errorList = $results -> errors;
								for($e = 0; $e < count($errorList); $e++){
									$bolPassed = false;
									$strMessage .= "<br/>" . $errorList[$e] -> message;
								}

							}
							
						}//success
					
					}//errors...
					
				}else{
					$bolPassed = false;
					$strMessage .= "<br>buzztouch.com API return invalid JSON";
				}
			}else{
				$bolPassed = false;
				$strMessage .= "<br>buzztouch.com API return invalid JSON";
			}
			
			//update....	
			if(!$bolPassed){
				
				echo "<div style='padding-top:3px;'><img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Error! " . $strMessage . "</span></div>";
				exit();
				
			}else{
		
				//done...
				echo $htmlRows;
				exit();		
				
			
			}//bolPassed
			
			

		}//get installed plugins...
		/////////////////////////////////////////////////////////////////////////////
		
	}//form posted....
	
	
	
	/////////////////////////////////////////////////////////////////////////////
	//install plugins (this is a _GET request)...
	if(strtoupper($command) == "INSTALLPLUGIN"){
		
		//errors...
		$errors = array();
		
		//validate...
		if(strlen($pluginGuid) < 1){
			$bolPassed = false;
			$errros[] = "Plugin id required";
		}
		if(strlen($downloadURL) < 1){
			$bolPassed = false;
			$errros[] = "Download URL required";
		}
		if(strlen($uniquePluginId) < 1){
			$bolPassed = false;
			$errros[] = "Unique plugin id required";
		}
		if(strlen($webDirectoryName) < 1){
			$bolPassed = false;
			$errros[] = "Web directory name required";
		}else{
			//get rid of slashes in directory  name...
			$webDirectoryName = str_replace("/", "", $webDirectoryName);
		}
		
		//setup download url...
		$apiURL = rtrim($downloadURL, "/");
		
		//download URL must be ssl secure!...
		$apiURL = str_replace("http://www.buzztouch.com", "https://www.buzztouch.com", $apiURL);
		
		//append user account info to URL...
		$apiURL .= "&userLoginId=" . urlencode(APP_BT_ACCOUNT_USEREMAIL);
		$apiURL .= "&userPassword=" . urlencode(APP_BT_ACCOUNT_USERPASS);
		
		//directories...
		$plugin_directory = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins"; 
		$temp_directory = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp"; 					
		
		//save as file name..
		$saveAsFolderName = $plugin_directory . "/" . $webDirectoryName;
		$tempSaveAsFileName = $temp_directory . "/" . $webDirectoryName . ".zip";

		//remove previous .zip download from temp...
		if(is_file($tempSaveAsFileName)){
			unlink($tempSaveAsFileName);
		}
		
		//remove possible extracted folder...
		if(is_dir($temp_directory . "/" . $webDirectoryName)){
			fnRemoveDirectory($temp_directory . "/" . $webDirectoryName);
		}


		//file pointer to temp directory...		
		if(!$fh = fopen($tempSaveAsFileName, "w")){
			$bolPassed = false;
			$errros[] = "/temp file not writeable?";
		}
		if(count($errors) < 1){		
		
			//download .zip file...
			$ch = curl_init($apiURL);
			curl_setopt($ch, CURLOPT_FILE, $fh); 
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    		$data = curl_exec($ch);
			
			//close connection
			curl_close($ch);
			
			//close file handle...
			fclose($fh);
			
			//get size of download...
			$userfile_size = 0;
			if(is_file($tempSaveAsFileName)){
				$userfile_size = filesize($tempSaveAsFileName);
			}


			//extract the .zip to the /temp directory..
			$archive = new PclZip($tempSaveAsFileName);
			$list = $archive->extract(PCLZIP_OPT_PATH, $temp_directory . "/" . $webDirectoryName);
			
			/*
				At this point there are two files in the /temp directory. The .zip we uploaded
				and the unzipped version to copy to the /plugins directory.
			*/
			
			//validate the unziped folder contains the necessary parts...
			$objPlugin = new Plugin("", "");
			$extractedFolderName = str_replace(".zip", "", $tempSaveAsFileName); 
			$zipErrors = array();
			
			//installed or updated message...
			$tmpResult = "Installed";
			
			//we should have an extracted folder now...
			if(strlen($extractedFolderName) > 3){
				if(is_dir($extractedFolderName)){
					$zipErrors = $objPlugin->fnCheckPluginContents($extractedFolderName);
				}else{
					$bolPassed = false;
					$errors[] = "<br>Error un-zipping archive (1)";
				}
			}else{
				$bolPassed = false;
				$errors[] = "<br>Error un-zipping archive (2)";
			}

			//if we have zipErrors the contents of the zip are not valid!
			if(count($zipErrors) < 1 && count($errors) < 1){
			
				if(is_writable($plugin_directory)){
					
					//remove possible existing folder...
					if(is_dir($saveAsFolderName)){
						$tmpResult = "Updated";
						fnRemoveDirectory($saveAsFolderName);
					}
					
					//move the uploaded folder...
					if(@rename($extractedFolderName, $saveAsFolderName)){
					
						chmod($saveAsFolderName, 0755);
						fnChmodDirectory($saveAsFolderName, 0755);
						
						//all done!
						if($bolPassed){
							$bolDone = true;
							
							//refresh plugins...
							$r = fnRefreshPlugins($guid);
							
							//print message...							
							$strMessage = "<b>" . fnFormOutput($webDirectoryName . ".zip", true) . "</b><br/>" . fnFormatBytes($userfile_size);
							
							//done...
							echo "<div style='padding-top:0px;'><img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'><span style='color:green;'>" . $tmpResult . "</span></div>";
							echo "<div style='padding-top:3px;'><span style='color:#000000;'>" . $strMessage . "</div>";
							exit();		
							
						}
		
					}//rename...
					
				}else{
				
					$bolPassed = false;
					$errors[] = "<br><b>Plugin folder not writeable";

				}//writeable...
			
			
			}//zipErrors...
			
		}//errors
		
		//errors...
		for($e = 0; $e < count($errors); $e++){
			$bolPassed = false;
			$strMessage .= $errors[$e];
		}
		
		//update....	
		if(!$bolPassed){
			
			echo "<div style='padding-top:3px;'><img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Error! " . $strMessage . "</span></div>";
			exit();
			
		}//bolPassed
			
	}//installPlugin...
	/////////////////////////////////////////////////////////////////////////////


		
	
	
	
?>