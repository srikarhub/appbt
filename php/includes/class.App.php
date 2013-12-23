<?php 

class App {
	public $infoArray;
		
   function __construct($guid = ""){
   		$tmp = "id, guid, apiKey, ownerGuid, version, currentPublishDate, currentPublishVersion, ";
		$tmp .= "dataDir, dataURL, cloudURL, registerForPushURL, projectName, startGPS, startAPN, allowRotation,";
		$tmp .= "applePushCertDevPassword, applePushCertProdPassword, ";
		$tmp .= "googleProjectId, googleProjectApiKey, scringoAppId, ";
		$tmp .= "name, appSecret, iconUrl, iconName, viewCount, deviceCount, status, dateStampUTC, modifiedUTC, ";
		$tmp .= "appAddress, appCity, appState, appZip, appLatitude, appLongitude ";
		$fields = explode(",", $tmp);
		$this->infoArray = array();

		for($i = 0; $i < count($fields); $i++){
			$field = trim($fields[$i]);
			if($field != "") $this->infoArray[$field] = "";
		}

		//fill infoArray values if possible..
		if($guid != ""){
			$strSql = "SELECT ";
			for($i = 0; $i < count($fields); $i++){
				$field = trim($fields[$i]);
				if($field != "") $strSql .= $field . ", ";
			}
			$strSql = fnRemoveLastChar($strSql, ",");			
			$strSql .= " FROM " . TBL_APPLICATIONS;
			$strSql .= " WHERE guid =  '" . $guid . "' ";
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
			$strSql = "DELETE FROM " . TBL_APPLICATIONS . " WHERE guid = '". $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}
	
	function fnInsert(){
		$strSql = "INSERT INTO " . TBL_APPLICATIONS . " (";
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
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
	}
		
		
	function fnUpdate(){
		if($this->infoArray['guid'] != ""){
			$strSql = "UPDATE " . TBL_APPLICATIONS . " SET ";
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
			//echo $strSql;
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}
		
	function fnNameInUse($name = "", $guid = ""){
		$r = false;
		if($name != ""){
			$strSql = "SELECT id FROM " . TBL_APPLICATIONS;
			$strSql .= " WHERE name =  '" . $name . "' AND status != 'deleted' ";
			if($guid != "") $strSql .= " AND guid != '" . $guid . "'";
			$strSql .= " LIMIT 0, 1";
			$tmp = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($tmp  != "" && $tmp  != "0"){
				$r = true;
			}
		}
	  	return $r;
	}
	
	/*
		Check to make sure user can manage this app...
		This method could get extended to allow a user to manage multiple apps?
	*/
	function fnCanManageApp($userGuid = "", $userType = "", $appGuid = "", $appOwnerGuid = ""){
		
		//all values required...
		if($userGuid == "" || $userType == "" || $appGuid == "" || $appOwnerGuid == ""){
			echo "Unauthorized request";
			exit();
		}
		
		//allow admin or app owner to continue...
		if($userGuid == $appOwnerGuid || strtoupper($userType) == "ADMIN"){
			
		}else{
			echo "Unauthorized request";
			exit();
		}
		
	}
		
		
	//return this app's data directory....
	function fnGetAppDataDirectory($appGuid = ""){
		if($appGuid != ""){
			if(isset($this->infoArray["dataDir"])){
				return rtrim(APP_PHYSICAL_PATH, "/") . "/" . ltrim($this->infoArray["dataDir"], "/");
			}else{
				return rtrim(APP_PHYSICAL_PATH, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/applications/" . $appGuid;
			}
		}
	}
	
	//return a URL to this app's data directory....
	function fnGetAppDataURL($appGuid = ""){
		if($appGuid != ""){
			if(isset($this->infoArray["dataDir"])){
				return rtrim(APP_URL, "/") . "/" . ltrim($this->infoArray["dataDir"], "/");
			}else{
				return rtrim(APP_URL, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/applications/" . $appGuid;
			}
		
		}
	}			
	
} //end class
?>