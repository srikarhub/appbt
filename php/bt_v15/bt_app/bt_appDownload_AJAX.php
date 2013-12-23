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
	
	//project vars posted in AJAX request...
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$whatPlatform = fnGetReqVal("whatPlatform", "", $myRequestVars);
	$saveToPath = "";
	$packageURL = "";
	
	//init some variables for the json result...		
	$bolPassed = true;
	$strMessage = "";
	
	//must have an app id...
	if(strlen($appGuid) < 5 || strlen($guid) < 5){
		$bolPassed = false;
		$strMessage .= "<br>No app id found";
	}		

	//must have a platoform selected...
	if(strlen($whatPlatform) < 1){
		$bolPassed = false;
		$strMessage .= "<br>There was a problem figuring out what platform to package the project for?";
	}		
	
	//create an app object so we can get the project name...
	if($bolPassed){

		//ceate an app object...
		$objApp = new App($appGuid);

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);
	
		//appAPIKey and project name comes from objApp
		$appAPIKey = $objApp->infoArray["apiKey"];
		$appAPISecret = $objApp->infoArray["appSecret"];
		$appName = $objApp->infoArray["name"];
		$projectName = $objApp->infoArray["projectName"];
		$projectName = fnCleanProjectName($projectName);
		$projectName = strtolower($projectName);
		$appIconURL = $objApp->infoArray["iconUrl"];
		$appVersion = $objApp->infoArray["version"];
	
	
	}//bolPassed



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
	function fnGetBuzztouchPackageUsingAPI($appGuid, $projectName, $appName, $iconURL, $platform, $version){
		
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
			"appGuid" => urlencode($appGuid),
			"projectName" => urlencode($projectName),
			"appName" => urlencode($appName),
			"iconURL" => urlencode($iconURL),
			"platform" => urlencode($platform),
			"version" => urlencode($version)
				
		);
		
		//prepare the data for the POST
		foreach($fields as $key => $value){ 
			$postVars .= $key . "=" . $value . "&"; 
		}
		
		//setup api url
		$apiURL = rtrim(APP_BT_SERVER_API_URL, "/");
		
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
	
	//gets config data from this server's API
	function fnGetConfigData($appGuid, $appAPIKey, $appAPISecret){
		
		//post vars to using CURL to make an API call to the same box.
		$postVars = "";
		$fields = array(
			"apiKey" => urlencode($appAPIKey),
			"apiSecret" => urlencode($appAPISecret),
			"command" => urlencode("getAppData"),
			"appGuid" => urlencode($appGuid)
			);
		
		//prepare the data for the POST
		foreach($fields as $key => $value){ 
			$postVars .= $key . "=" . $value . "&"; 
		}
					
		//init a cURL object to this UR
		$ch = curl_init(fnGetSecureURL(APP_URL) . "/api/app/");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);

		//get JSON result from buzztouch.com API
		$configDataString = curl_exec($ch);

		//close connection
		curl_close($ch);	
		
		return $configDataString;
	}
	
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
	
	
	//validate...
	if($bolPassed){
		
		if(strlen($appAPIKey) < 1){
			$bolPassed = false;
			$strMessage .= "<br>No API key found for this project?";
		}
		if(strlen($appAPISecret) < 1){
			$bolPassed = false;
			$strMessage .= "<br>No App Secret found for this project?";
		}
		
		if(strlen($appName) < 1){
			$bolPassed = false;
			$strMessage .= "<br>No application name found for this project?";
		}
		
		if(strlen($projectName) < 1){
			$bolPassed = false;
			$strMessage .= "<br>No project name found for this project?";
		}			
	
		if(strlen($appIconURL) < 1){
			$bolPassed = false;
			$strMessage .= "<br>No Icon URL found for this project?";
		}
		
		if(strlen($appVersion) < 1){
			$bolPassed = false;
			$strMessage .= "<br>No version found for this project?";
		}		
				
	}//bolPassed
	
	
	
	//if we have an API key then fetch project from buzztouch.com's API
	if($bolPassed){
		
		//temp directory path...
		$temp_directory = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp"; 					

		//remove previous .folders if they are still hanging around from last attempt...
		fnRemoveDirectory(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $projectName . "-iOS-BTv2.0-" . $appGuid);
		fnRemoveDirectory(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $projectName . "-Android-BTv2.0-" . $appGuid);

		//remove previous zip's if they are still hanging around from last attempt...
		if(is_file(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $projectName . "-iOS-BTv2.0-" . $appGuid . ".zip")){
			@unlink(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $projectName . "-iOS-BTv2.0-" . $appGuid . ".zip");
		}
		if(is_file(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $projectName . "-Android-BTv2.0-" . $appGuid . ".zip")){
			@unlink(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $projectName . "-Android-BTv2.0-" . $appGuid . ".zip");
		}

		//file names for the .zip we will be preparing...
		if(strtoupper($whatPlatform) == "IOS"){
			$saveToFolderName = $projectName . "-iOS-BTv2.0-" . $appGuid . ".zip";
		}
		if(strtoupper($whatPlatform) == "ANDROID"){
			$saveToFolderName = $projectName . "-Android-BTv2.0-" . $appGuid . ".zip";
		}
		
		//save the folder in the /files/temp directory
		$saveToPath = "../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp/" . $saveToFolderName;

		//root folder is where we are saving the all the unzipped files to...
		$rootFolder = str_replace(".zip", "", $saveToPath);

		//make sure we have a writable temp directory...
		if(is_dir("../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp")){
			if(!is_writable("../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp")){
				$bolPassed = false;
				$strMessage .= "The ../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp directory is not writable by PHP. This folder needs write access.";
			}
		}else{
			$bolPassed = false;
			$strMessage .= "The ../../" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp directory does not exist. This folder needs to exist and PHP needs write access to it. ";
		}

		//good so far...
		if($bolPassed){
		
			//packageURL comes form the buzztouch.com API
			$packageURL = fnGetBuzztouchPackageUsingAPI($appGuid, $projectName, $appName, $appIconURL, $whatPlatform, $appVersion);
			
			if(is_array($packageURL)){
			
				//flag as no good
				$bolPassed = false;
				
				//show the errors...
				for($e = 0; $e < count($packageURL); $e++){
					$strMessage .= $packageURL[$e];
				}
	
			}else{
			
				//all good, download and save the file from the packageURL...
				if(!fnDownloadAndSaveZip($packageURL, $saveToPath)){
					$bolPassed = false;
					$strMessage .= "<br>An error occurred trying to download a package from the buzztouch.com API";
				}else{
					//extract the .zip to the /temp directory..
					if(is_file($saveToPath)){
						
						$archive = new PclZip($saveToPath);
						$list = $archive->extract(PCLZIP_OPT_PATH, $rootFolder);
						if(count($list) < 5 || !is_dir($rootFolder)){
							$bolPassed = false;
							$strMessage .= "There was a problem unzipping the package delivered by the buzztouch.com API. This can be caused by a few things. Is it possible that this is a Windows Server? This software does not run on Windows powered servers.";
						}				
						
					}else{
						
						$bolPassed = false;
						$strMessage .= "<br>There was a problem finding the downloaded package";
					}
					
				}
				
			}//is_array errors
		
		}//bolPassed
		
		//at this point we should have a folder to use to create our new project...It may already be full of the 
		//contents from the project returned by the buzztouch.com API...
		if($bolPassed){
			
			if(is_dir($rootFolder)){
			
				//array holds all the files we've already added to the project - no duplicates allowed!
				$existingFiles = array();
			
				//get the BT_config.txt data from this server's API...
				$configDataString = fnGetConfigData($appGuid, $appAPIKey, $appAPISecret);
				if(strlen($configDataString) > 10){
					
					////////////////////////////////////////////////////////////////////////////////////
					//iOS project...				
					if(strtoupper($whatPlatform) == "IOS"){
						$originalDelegateName = "BT_appDelegate";
						$newDelegateName = $projectName . "_appDelegate";
						
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
						fnSaveImageToTemp($appIconURL);
						$saveAsImageName = basename($appIconURL);
						$tmpIconPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
						
						//download app icon from remote server...Could be .jpg or .png
						if(strtolower(substr($appIconURL, -4)) == ".jpg"){
							$icon = @imagecreatefromjpeg($tmpIconPath);
							
						}else{
							if(strtolower(substr($appIconURL, -4)) == ".png"){
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
						$strSql = " SELECT webDirectoryName FROM " . TBL_BT_PLUGINS;
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						while($row = mysql_fetch_array($res)){
							$tmpPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $row["webDirectoryName"];
							if(is_dir($tmpPath)){
								if(is_readable($tmpPath)){
								
									//copy all the files from the source-ios directory into the BT_plugins folder...
									$iosFolder = $tmpPath . "/source-ios";
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
													}
												}
											}//end while	
										}//if open source-ios
									}//is_dir /source-ios
								}//is_readable for this plugin...
							}//is_dir for this plugin...
						}//end while plugins..
					}
					//end iOS
					////////////////////////////////////////////////////////////////////////////////////
					
					////////////////////////////////////////////////////////////////////////////////////
					//Android project...				
					if(strtoupper($whatPlatform) == "ANDROID"){
						$originalDelegateName = "BT_appDelegate";
						$newDelegateName = $projectName . "_appDelegate";
						$originalPackageName = "com.buzzTouch";
						$newPackageName = "com." . $projectName;
						
						//build directories if they don't exist already...
						$makeFolders = array("assets", "bin", "gen", "jar", "res", "src");
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
						fnSaveImageToTemp($appIconURL);
						$saveAsImageName = basename($appIconURL);
						$tmpIconPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp/" . $saveAsImageName;
						
						//get app's icon, could be .jpg or .png
						if(strtolower(substr($appIconURL, -4)) == ".jpg"){
							$icon = @imagecreatefromjpeg($tmpIconPath);
						}else{
							if(strtolower(substr($appIconURL, -4)) == ".png"){
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
							We'll end up replacing that line with a list of activity files included in 
							the project. It's important that each .java class in the Plugin be an Android Activity.	
							If another .java class is needed to support the plugin it needs to be a child-class
							within the main activity class. In other words, it's assumed that all .java class
							files found in the source-android folder are Android Activities. 														
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
						
						
						//process all the plugins...
						$strSql = " SELECT webDirectoryName FROM " . TBL_BT_PLUGINS;
						$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						if($res){
							while($row = mysql_fetch_array($res)){
								$tmpPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $row["webDirectoryName"];
								if(is_dir($tmpPath)){
									if(is_readable($tmpPath)){
										
										//look for source code...For Android we don't make any folder, we just copy the 
										//contents of the source-android to the appropriate directory in the Android project
										
										$audioDir = $rootFolder . "/assets/BT_Audio";
										$videoDir = $rootFolder . "/assets/BT_Video";
										$docsDir = $rootFolder . "/assets/BT_Docs";
										$srcDir = $rootFolder . "/src/com/" . str_replace("com.", "", $newPackageName);
										$layoutDir = $rootFolder . "/res/layout";
										$drawableDir = $rootFolder . "/res/drawable";
										
										$androidFolder = $tmpPath . "/source-android";
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
															
															}//not .DS_Store																	
														}//is_file...
													}//filename length...
												}//end while on each file in android folder..	
											}//if open source-android
										}//is_dir /source-android
									}//is_readable
								}//is_dir		
							}//while
						}//res
					
						//merge $activitiesList into AndroidManfest.xml
						if(is_file($rootFolder . "/AndroidManifest.xml")){
							fnReplaceText($rootFolder . "/AndroidManifest.xml", "<!-- replace this with list of activity includes -->", $activitiesList);
						}
						
						//replace commented maps line if needed...
						if($bolUsingMaps){
							fnReplaceText($rootFolder . "/AndroidManifest.xml", "<!-- <uses-library android:name=\"com.google.android.maps\"/> -->", "<uses-library android:name=\"com.google.android.maps\"/>");
						}
						
						
					}
					//end Android
					////////////////////////////////////////////////////////////////////////////////////
					
					
					//create .zip version of the rootFolder...
					if($bolPassed){
					
						$archiveNew = new PclZip($saveToPath);
						$v_dir = $rootFolder;
						$v_remove = $v_dir;
		
						//next three lines needed to support windows and the C: root  
						if(substr($v_dir, 1, 1) == ':'){
							$v_remove = substr($v_dir, 2);
						}
						
						$v_list = $archiveNew->create($v_dir, PCLZIP_OPT_REMOVE_PATH, $v_remove);
						if($v_list == 0){
						
							//error creating zip
							$bolPassed = false;
							$strMessage .= "<br>Error creating downloadable .zip after filling with application assets. Is it possible that this is a Windows Server? This software does not run on Windows powered servers.";
							//$strMessage .= $archive->errorInfo(true);
						
						}
						
						//clean up so we're only left with the new .zip..
						if(is_dir($rootFolder)){
							fnRemoveDirectory($rootFolder);
						}
						
						//done!
											
					}
					
				}else{
					$bolPassed = false;
					$strMessage .= "<br>There was a problem finding the configuration data for this app.";
				}
			}else{
				$bolPassed = false;
				$strMessage .= "<br>There was a problem creating a directory in the /temp folder.";
			}	
		}//bolPassed
	}//bolPassed
	



	//prepare JSON result...
	$jsonResult = "{\"result\":";
	
	//passed or not?
	if($bolPassed){
		$jsonResult .= "{\"status\":\"success\", \"message\":\"The project was packaged successfully.\"}";
	}else{
		$jsonResult .= "{\"status\":\"error\", \"message\":\"<br>" . $strMessage . "\"}";
	}
	
	//end JSON result...
	$jsonResult .= "}";

	//print the json...
	echo $jsonResult;
	exit();


	
?>

