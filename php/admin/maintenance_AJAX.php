<?php   require_once("../config.php");

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
	$command = fnGetReqVal("command", "", $myRequestVars);


	///////////////////////////////////////////////////////
	//form posted updating settings
	if($isFormPost){
		
		///////////////////////////////////////
		//empty directory...
		if(strtoupper($command) == "TEMPFILES"){
			$strMessage == "";
			$removedCount = 0;
			$tmpPath = rtrim(APP_PHYSICAL_PATH, "/")  . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp"; 
			if(is_dir($tmpPath)){
				if(is_writable($tmpPath)){
					
					//remove all the files...
					if($handle = opendir($tmpPath)){
    					while(false !== ($file = readdir($handle))){
							if(strlen($file) > 3){
								if(is_dir($tmpPath . "/" . $file)){
									if(is_writable($tmpPath . "/" . $file)){
										fnRemoveDirectory($tmpPath . "/" . $file);
										$removedCount++;
									}
								}else{
									if(is_file($tmpPath . "/" . $file)){
										unlink($tmpPath . "/" . $file);
										$removedCount++;
									}
								}
							}
						}					
					}
					
					$bolPassed = true;
					$bolDone = true;
					$strMessage .= "<br/>Removed <b>" . $removedCount . "</b> files and directories from the " . APP_DATA_DIRECTORY . "/temp directory";
				}else{
					$bolPassed = false;
					$strMessage .= "<br/>" . APP_DATA_DIRECTORY . "/temp could not be emptied. It's not \"writable\"?";
				}
			}else{
				$bolPassed = false;
				$strMessage .= "<br/>" . APP_DATA_DIRECTORY . "/temp could not be found?";
			}
		}
		//emptyDirectory
		///////////////////////////////////////

		
		///////////////////////////////////////
		//check for updates?
		if(strtoupper($command) == "CHECKFORUPDATES"){
			$strMessage = "";
			$bolPassed = true;
			
			//Buzztouch api key...
			if(strlen(APP_BT_SERVER_API_KEY) < 1){
				$bolPassed = false;
				$strMessage .= "<br>This feature requires a valid buzztouch.com API key on the Admin > Settings screen";
			}

			//Buzztouch api secret...
			if(strlen(APP_BT_SERVER_API_KEY_SECRET) < 1){
				$bolPassed = false;
				$strMessage .= "<br>This feature requires a valid buzztouch.com API Secret on the Admin > Settings screen";
			}
			
			//Buzztouch api URL...
			if(strlen(APP_BT_SERVER_API_URL) < 1){
				$bolPassed = false;
				$strMessage .= "<br>This feature requires a valid buzztouch.com API URL on the Admin > Settings screen";
			}
			
			//good still?
			if($bolPassed){
			
				//key/value pairs to send in the request...
				$postVars = "";
				$fields = array(
				
						//needed by the buzztouch.com api to validate this request
						"apiKey" => urlencode(APP_BT_SERVER_API_KEY),
						"apiSecret" => urlencode(APP_BT_SERVER_API_KEY_SECRET),
						"command" => urlencode("checkForUpdates"),
						
						//required by "packageProject" command...
						"version" => urlencode(APP_CURRENT_VERSION)
						
					);
				
				//prepare the data for the POST
				foreach($fields as $key => $value){ 
					$postVars .= $key . "=" . $value . "&"; 
				}
				
				//remove the trailing ampersand...
				$postVars = rtrim($postVars, "&");
		
				//setup api url
				$apiURL = rtrim(APP_BT_SERVER_API_URL, "/");
				
				//init a cURL object, set number of POST vars and the POST data
				$ch = curl_init($apiURL . "/updates/");
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
						
						$results = $decoded -> result;
						$status = $results -> status;
						
						if(strtoupper($status) == "SUCCESS"){
						
							$latestVersion = $results -> latestVersion;
							if(strtoupper($latestVersion) == strtoupper(APP_CURRENT_VERSION)){
								$strMessage .= "<br/>The most current version available at buzztouch.com is v" . $latestVersion;
								$strMessage .= "<br/>You are running v" . $latestVersion . " (no updates are necessary).";
							}else{
								$strMessage .= "<div class='errorDiv' style='color:#000000;'>";
								$strMessage .= "</br><b>This software needs to be updated</b>";
								$strMessage .= "<br/>Your server is running v" . APP_CURRENT_VERSION;
								$strMessage .= " and the most current version available is v" . $latestVersion;
								$strMessage .= "<div style='padding-top:5px;'>Download the most current version from <a href='http://www.buzztouch.com' target='_blank'>buzztouch.com</a> then upload it ";
								$strMessage .=" to your website. You will be overwriting all directories and files <b>EXCEPT the /files directory</b>. ";
								$strMessage .= "After uploading the new version you will need to go through the installation process again. ";
								$strMessage .= "The installation URL will be http://www.yourdomain.com/BT-server/install/. The installation process will not ";
								$strMessage .= " overwrite existing values in your database or existing files in the files directory.";
								$strMessage .= "</div>";
							}
						}else{
							$bolPassed = false;
							$strMessage = "<br>Could not establish connection to the buzztouch.com API. Check your API credentials?";
						}
					
					}
				}else{
				
					$bolPassed = false;
					$strMessage .= "<br>JSON results returned from server are invalid?";
				
				}	
				
			}//bolPassed		
		}
		//check for updates...
		///////////////////////////////////////
		
		
		//all good?....	
		if(!$bolPassed){
		
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Error! " . $strMessage . "</span>";
			exit();
			
		}else{
	
			//done...
			echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'>Done " . $strMessage;
			exit();
		
		}//bolPassed
		
	}//was submitted when updating settings...
	

	
	
	
?>