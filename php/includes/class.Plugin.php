<?php 

class Plugin {
	public $infoArray;
		
   function __construct($guid = "", $uniquePluginId = ""){
   		$tmp = "id, guid, category, uniquePluginId, displayAs, versionNumber, versionString, loadClassOrActionName, ";
		$tmp .= "hasChildItems, defaultJsonVars, webDirectoryName, landingPage, shortDescription, authorName, authorBuzztouchURL,";
		$tmp .= "authorWebsiteURL, authorEmail, authorTwitterURL, authorFacebookURL, authorYouTubeURL, authorLinkedInURL, ";
		$tmp .= " updateURL, downloadURL, dateStampUTC, modifiedUTC, modifiedByGuid, supportedDevices ";
		$fields = explode(",", $tmp);
		$this->infoArray = array();

		for($i = 0; $i < count($fields); $i++){
			$field = trim($fields[$i]);
			if($field != "") $this->infoArray[$field] = "";
		}

		//fill infoArray values if possible..
		if($guid != "" || $uniquePluginId != ""){
			$strSql = "SELECT ";
			for($i = 0; $i < count($fields); $i++){
				$field = trim($fields[$i]);
				if($field != "") $strSql .= $field . ", ";
			}
			$strSql = fnRemoveLastChar($strSql, ",");			
			$strSql .= " FROM " . TBL_BT_PLUGINS;
			$strSql .= " WHERE ";
			if($guid != ""){
				$strSql .= " guid =  '" . $guid . "' ";
			}else{
				$strSql .= " uniquePluginId =  '" . $uniquePluginId . "' ";
			}
			
			$strSql .= " LIMIT 0, 1";
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($res){
				$numRows = mysql_num_rows($res);	
				if($numRows == 1){
					$row = mysql_fetch_array($res);
					//loop fields, add each field-value to info array
					$numFields = mysql_num_fields($res);
					for($i = 0; $i < $numFields; $i++){
						$this->infoArray[mysql_field_name($res, $i)] = $row[$i];
					} 
				}//if num rows
			}//end res
		}//guid = ""
		
		
  	}		
	
	function fnDelete($guid = ""){
		if($guid != ""){
			$strSql = "DELETE FROM " . TBL_BT_PLUGINS . " WHERE guid = '". $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}
	
	function fnInsert(){
		if($this->infoArray['guid'] != ""){
			$strSql = "INSERT INTO " . TBL_BT_PLUGINS . " (";
			//loop field names
			foreach ($this->infoArray as $key=>$value){
				if(strtoupper($key) != "ID"){
					$strSql .= $key . ", ";
				}
			}
			$strSql = fnRemoveLastChar($strSql, ",");
			$strSql .= ") VALUES (";
			//loop values
			foreach ($this->infoArray as $key=>$value){
				if(strtoupper($key) != "ID"){

					//apostrophe's may already be escaped.
					$value = str_replace("\'", "'", $value);
					$value = str_replace("'", "\'", $value);
					$strSql .=  "'" . $value . "', ";

				}
			}
			$strSql = fnRemoveLastChar($strSql, ",");
			$strSql .= ")";
			//echo $strSql;
			//exit();
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		} 
	}
		

	function fnUpdate(){
		if($this->infoArray['guid'] != ""){
			$strSql = "UPDATE " . TBL_BT_PLUGINS . " SET ";
			//loop field names
			foreach ($this->infoArray as $key=>$value){
				if(strtoupper($key) != "ID"){
				
					//apostrophe's may already be escaped.
					$value = str_replace("\'", "'", $value);
					$value = str_replace("'", "\'", $value);
					$strSql .= $key . " = '" . $value . "', ";
					
				}
			}
			$strSql = fnRemoveLastChar($strSql, ",");
			$strSql .= " WHERE guid = '" . $this->infoArray['guid'] . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}

	//gets option list of plugins..
	function fnGetPluginOptions(){
		$r = "";
		$actionOptions = "";
		$menuOptions = "";
		$screenOptions = "";
		$settingsOptions = "";
		$splashOptions = "";
		$optSql = "SELECT guid, uniquePluginId, displayAs, category FROM " . TBL_BT_PLUGINS;
		$optRes = fnDbGetResult($optSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		if($optRes){
			while($row = mysql_fetch_array($optRes)){
				$thisOption = "\n<option value=\"" . $row["uniquePluginId"] . "\">&nbsp;&nbsp;&nbsp;" . fnFormOutput($row["displayAs"]) . "</option>";
				switch (strtoupper($row["category"])){
					case "ACTION":
						$actionOptions .= $thisOption;
						break;
					case "MENU":
						$menuOptions .= $thisOption;
						break;
					case "SCREEN":
						$screenOptions .= $thisOption;
						break;
					case "SETTINGS":
						$settingsOptions .= $thisOption;
						break;
					case "SPLASH":
						$splashOptions .= $thisOption;
						break;
				}//end switch
				
				
			}//end while
		}//end res
			
		//assemble all the options...
		$r = "\n<option value=''>&nbsp;&nbsp;</option>";
		$r .= "\n<option value=''>--Menu screens--</option>" . $menuOptions . "\n<option value=''>&nbsp;&nbsp;</option>";
		$r .= "\n<option value=''>--Content Screens--</option>" . $screenOptions. "\n<option value=''>&nbsp;&nbsp;</option>";
		$r .= "\n<option value=''>--Splash Screens--</option>" . $splashOptions. "\n<option value=''>&nbsp;&nbsp;</option>";
		$r .= "\n<option value=''>--Settings Screens--</option>" . $settingsOptions. "\n<option value=''>&nbsp;&nbsp;</option>";
		$r .= "\n<option value=''>--Other Actions--</option>" . $actionOptions. "\n<option value=''>&nbsp;&nbsp;</option>";
		$r .= "\n<option value=''>&nbsp;&nbsp;</option>";
		
		//return 
		return $r;

	}

	//fnCheckPluginContents return array of errors if not valid...
	function fnCheckPluginContents($directoryPath = ""){
		$errors = array();
		$cleanPath = rtrim($directoryPath, "/");
		
		/*
			plugin folders are valid if they contain...
			------------------------
			1) config.txt					configuration file for control panel  
			2) icon.png						icon used in the control panel
			3) readme.txt					readme.txt file to explain plugins usage, purpose, etc.
			4) config_cpt.txt				holds configuration rules for plugin's landing page
				
				* required entries
				
				*uniquePluginId:			unique value across all Buzztouch plugins
				*displayAs:					name to show in control panel
				*category:					options: screen, settings, menu, splash
				*loadClassOrActionName:		name of class file to load or action to perform
				*hasChildItems: 			options: Yes or No.
				*supportedDevices: 			options: ios, android, iosOrAndroid
				*versionNumber:				double / float value like 1.5
				*versionString:				version string like v1.5
				*updateURL:					URL returning most current version and version string in JSON format
				*downloadURL:				URL returning .zip archive for this plugin
				*defaultJONData:			json values to use when plugin added to an app
				*shortDescription:			short description for control panel. Up to 250 chars, no HTML allowed
				
				*authorName:				name of author
				authorEmail:				email of author
				authorBuzztouchURL: 		buzztouch.com URL for plugin author
				authorWebsiteURL: 			URL to plugin author's website
				authorTwitterURL: 			URL to plugin author's Twitter account
				authorFacebookURL: 			URL to plugin author's Facebook account
				authorLinkedInURL: 			URL to plugin author's LinkedIn account
				authorYouTubeURL: 			URL to plugin author's YouTube channel


		*/
		
		if(is_dir($cleanPath)){
		
			if(is_readable($cleanPath)){
			
				//must have config.txt
				if(!is_file($cleanPath . "/config.txt")){
					$errors[] = "config.txt file required";
				}
			
				//must have icon.png
				if(!is_file($cleanPath . "/icon.png")){
					$errors[] = "icon.png file required";
				}
				
				//must have readme.txt
				if(!is_file($cleanPath . "/readme.txt")){
					$errors[] = "readme.txt file required";
				}
				
				//if good so far...
				if(count($errors) < 1){
				
					$uniquePluginId = "";
					$displayAs = "";
					$category = "";
					$loadClassOrActionName = "";
					$hasChildItems = "";
					$supportedDevices = "";
					$versionNumber = "";
					$versionString = "";
					$updateURL = "";
					$downloadURL = "";	
					$defaultJSONData = "";
					$shortDescription = "";
					$authorName = "";

			
					//parse the config.txt file...
					$data = file_get_contents($cleanPath . "/config.txt");
					$lines = explode("\n", $data);
					for($x = 0; $x < count($lines); $x++){
						$thisLine = $lines[$x];

						//uniquePluginId...
						if(substr(trim($thisLine), 0, 15) == "uniquePluginId:"){
							$uniquePluginId = trim(substr($thisLine, 15));
						}
						
						//displayAs...
						if(substr(trim($thisLine), 0, 10) == "displayAs:"){
							$displayAs = trim(substr($thisLine, 10));
						}
						
						//category...
						if(substr(trim($thisLine), 0, 9) == "category:"){
							$category = trim(substr($thisLine, 9));
						}
						
						//loadClassOrActionName...
						if(substr(trim($thisLine), 0, 22) == "loadClassOrActionName:"){
							$loadClassOrActionName = trim(substr($thisLine, 22));
						}
						
						//hasChildItems...
						if(substr(trim($thisLine), 0, 14) == "hasChildItems:"){
							$hasChildItems = trim(substr($thisLine, 14));
						}

						//supportedDevices...
						if(substr(trim($thisLine), 0, 17) == "supportedDevices:"){
							$supportedDevices = trim(substr($thisLine, 17));
						}
	
						//versionNumber...
						if(substr(trim($thisLine), 0, 14) == "versionNumber:"){
							$versionNumber = trim(substr($thisLine, 14));
						}
						
						//versionString...
						if(substr(trim($thisLine), 0, 14) == "versionString:"){
							$versionString = trim(substr($thisLine, 14));
						}

						//updateURL...
						if(substr(trim($thisLine), 0, 10) == "updateURL:"){
							$updateURL = trim(substr($thisLine, 10));
						}
						
						//downloadURL...
						if(substr(trim($thisLine), 0, 12) == "downloadURL:"){
							$downloadURL = trim(substr($thisLine, 12));
						}
	
						//defaultJSONData...
						if(substr(trim($thisLine), 0, 16) == "defaultJSONData:"){
							$jsonStartIndex = strpos($data, "defaultJSONData:") + 16;
							$jsonEndIndex = strpos($data, "}");
							if(!is_numeric($jsonEndIndex)) $jsonEndIndex = 0;
							if($jsonStartIndex > 0 && ($jsonEndIndex > $jsonStartIndex)){
								$defaultJSONData = trim(substr($data, $jsonStartIndex, ($jsonEndIndex - $jsonStartIndex)));
							}
						}
						
						//shortDescription (250 chars max)...
						if(substr(trim($thisLine), 0, 17) == "shortDescription:"){
							$shortDescription = trim(substr($data, strpos($data, "shortDescription:") + 17));
							$shortDescription = substr($shortDescription, 0, 250);
						}
						
						//authorName...
						if(substr(trim($thisLine), 0, 11) == "authorName:"){
							$authorName = trim(substr($thisLine, 11));
						}
	
	
					}//end for each line	
					
					//valid?
					if(strlen($uniquePluginId) < 1){
						$errors[] = "uniquePluginId not found";
					}
					
					if(strlen($displayAs) < 1){
						$errors[] = "displayAs not found";
					}								
					
					if(strlen($category) < 1){
						$errors[] = "category not found";
					}else{
						if(strtoupper($category) != "SCREEN"
							&& strtoupper($category) != "MENU"
							&& strtoupper($category) != "SETTINGS"
							&& strtoupper($category) != "ACTION"
							&& strtoupper($category) != "SPLASH"){
							$errors[] = "category invalid. Screen, Menu, Settings, Action, or Splash required";
						}
					}
					
					if(strlen($loadClassOrActionName) < 1){
						$errors[] = "loadClassOrActionName Name not found";
					}					
					
					if(strlen($hasChildItems) < 1){
						$errors[] = "hasChildItems not found";
					}else{
						if(strtoupper($hasChildItems) != "YES"
							&& strtoupper($hasChildItems) != "NO"){
							$errors[] = "hasChildItems invalid. Yes or No required";
						}					
					}
					
					
					if(strlen($supportedDevices) < 1){
						$errors[] = "supportedDevices not found";
					}else{
						if(strtoupper($supportedDevices) != "IOS"
							&& strtoupper($supportedDevices) != "ANDROID"
							&& strtoupper($supportedDevices) != "IOSANDANDROID"){
							$errors[] = "supportedDevices invalid. ios, Android, or iosAndAndroid required";
						}					
					}
														
					if(strlen($versionNumber) < 1){
						$errors[] = "versionNumber not found";
					}else{
						if(!is_numeric($versionNumber)){
							$errors[] = "versionNumber not numeric";
						}
					}
					
					if(strlen($versionString) < 1){
						$errors[] = "versionString not found";
					}

					if(strlen($updateURL) < 1){
						$errors[] = "updateURL not found";
					}

					if(strlen($downloadURL) < 1){
						$errors[] = "downloadURL not found";
					}

					if(strlen($defaultJSONData) < 1){
						$errors[] = "defaultJSONData not found";
					}
					
					if(strlen($shortDescription) < 1 || strlen($shortDescription) > 250 ){
						$errors[] = "shortDescription length invalid";
					}
					
					if(strlen($authorName) < 1){
						$errors[] = "authorName not found";
					}

				}//count errors < 1
			
			}else{
				$errors[] = "Plugin directory is not readable";
			}					
		}else{
			$errors[] = "Plugin directory does not exist: " . $directoryPath;
		}
		
		//return errors array...if it's not empty then the calling script knows this plugin is invalid...
		return $errors;
	}

	//fnGetPluginInfo returns array of all plugin fields after parsing it's config.txt file...
	function fnGetPluginInfo($directoryPath = ""){
		
		$cleanPath = rtrim($directoryPath, "/");

		$info = array();
		$info["uniquePluginId"] = "";
		$info["displayAs"] = "";
		$info["category"] = "";
		$info["loadClassOrActionName"] = "";
		$info["hasChildItems"] = "";
		$info["supportedDevices"] = "";
		$info["authorName"] = "";
		$info["authorEmail"] = "";
		$info["authorBuzztouchURL"] = "";
		$info["authorWebsiteURL"] = "";		
		$info["authorTwitterURL"] = "";
		$info["authorFacebookURL"] = "";
		$info["authorLinkedInURL"] = "";
		$info["authorYouTubeURL"] = "";
		$info["versionNumber"] = "";
		$info["versionString"] = "";
		$info["updateURL"] = "";
		$info["downloadURL"] = "";
		$info["defaultJSONData"] = "";
		
		//legacy...
		$info["defaultJsonVars"] = "";

		
		$info["shortDescription"] = "";
		
		if(is_dir($cleanPath)){
			if(is_readable($cleanPath)){
				if(is_file($cleanPath . "/config.txt")){
					
					//look at each line in the config.txt file...
					$data = file_get_contents($cleanPath . "/config.txt");
					$lines = explode("\n", $data);
					for($x = 0; $x < count($lines); $x++){
						$thisLine = $lines[$x];
						
						//uniquePluginId...
						if(substr(trim($thisLine), 0, 15) == "uniquePluginId:"){
							$info["uniquePluginId"] = trim(substr($thisLine, 15));
						}
						
						//displayAs...
						if(substr(trim($thisLine), 0, 10) == "displayAs:"){
							$info["displayAs"] = trim(substr($thisLine, 10));
						}
						
						//supportedDevices...
						if(substr(trim($thisLine), 0, 17) == "supportedDevices:"){
							$info["supportedDevices"] = trim(substr($thisLine, 17));
						}
						
						//category...
						if(substr(trim($thisLine), 0, 9) == "category:"){
							$info["category"] = trim(substr($thisLine, 9));
						}
	
						//versionNumber...
						if(substr(trim($thisLine), 0, 14) == "versionNumber:"){
							$info["versionNumber"] = trim(substr($thisLine, 14));
						}
						
						//versionString...
						if(substr(trim($thisLine), 0, 14) == "versionString:"){
							$info["versionString"] = trim(substr($thisLine, 14));
						}
						
						//loadClassOrActionName...
						if(substr(trim($thisLine), 0, 22) == "loadClassOrActionName:"){
							$info["loadClassOrActionName"] = trim(substr($thisLine, 22));
						}
	
						//hasChildItems...
						if(substr(trim($thisLine), 0, 14) == "hasChildItems:"){
							$info["hasChildItems"] = trim(substr($thisLine, 14));
							if(strtoupper($info["hasChildItems"]) == "YES" || strtoupper($info["hasChildItems"]) == "Y"){
								$info["hasChildItems"] = "1";
							}
							if(strtoupper($info["hasChildItems"]) == "NO" || strtoupper($info["hasChildItems"]) == "N"){
								$info["hasChildItems"] = "0";
							}
						}
						if(!is_numeric($info["hasChildItems"])){
							$info["hasChildItems"] = "0";
						}
						
						//authorName...
						if(substr(trim($thisLine), 0, 11) == "authorName:"){
							$info["authorName"] = trim(substr($thisLine, 11));
						}

						//authorEmail...
						if(substr(trim($thisLine), 0, 12) == "authorEmail:"){
							$info["authorEmail"] = trim(substr($thisLine, 12));
						}

						//authorBuzztouchURL...
						if(substr(trim($thisLine), 0, 19) == "authorBuzztouchURL:"){
							$info["authorBuzztouchURL"] = trim(substr($thisLine, 19));
						}

						//authorWebsiteURL...
						if(substr(trim($thisLine), 0, 17) == "authorWebsiteURL:"){
							$info["authorWebsiteURL"] = trim(substr($thisLine, 17));
						}

						//authorTwitterURL...
						if(substr(trim($thisLine), 0, 17) == "authorTwitterURL:"){
							$info["authorTwitterURL"] = trim(substr($thisLine, 17));
						}

						//authorFacebookURL...
						if(substr(trim($thisLine), 0, 18) == "authorFacebookURL:"){
							$info["authorFacebookURL"] = trim(substr($thisLine, 18));
						}

						//authorFacebookURL...
						if(substr(trim($thisLine), 0, 18) == "authorLinkedInURL:"){
							$info["authorLinkedInURL"] = trim(substr($thisLine, 18));
						}
						
						//authorYouTubeURL...
						if(substr(trim($thisLine), 0, 17) == "authorYouTubeURL:"){
							$info["authorYouTubeURL"] = trim(substr($thisLine, 17));
						}
	

						//downloadURL...
						if(substr(trim($thisLine), 0, 12) == "downloadURL:"){
							$info["downloadURL"] = trim(substr($thisLine, 12));
						}

						//updateURL...
						if(substr(trim($thisLine), 0, 10) == "updateURL:"){
							$info["updateURL"] = trim(substr($thisLine, 10));
						}
						
						//defaultJSONData...
						if(substr(trim($thisLine), 0, 16) == "defaultJSONData:"){
							$jsonStartIndex = strpos($data, "defaultJSONData:") + 16;
							$jsonEndIndex = strpos($data, "}");
							if(!is_numeric($jsonEndIndex)) $jsonEndIndex = 0;
							if($jsonStartIndex > 0 && ($jsonEndIndex > $jsonStartIndex)){
								$json = trim(substr($data, $jsonStartIndex, ($jsonEndIndex - $jsonStartIndex) + 1));
								$json = fnNoLineBreaks($json);
								$info["defaultJSONData"] = $json;
								$info["defaultJsonVars"] = $json;
							}
						}
						
						//shortDescription (250 chars max)...
						if(substr(trim($thisLine), 0, 17) == "shortDescription:"){
							$shortDescription = trim(substr($data, strpos($data, "shortDescription:") + 17));
							$info["shortDescription"] = substr($shortDescription, 0, 250);
						}
	
	
					}//end for each line	
				}//isFile config.txt
			}//isReadable
		}//isDir
		
		//return info array...
		return $info;
	}



	  
} //end class
?>