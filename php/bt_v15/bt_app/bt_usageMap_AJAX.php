<?php   require_once("../../config.php");
	
	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	
	//not logged in...
	if($guid == ""){
		echo "error (1)";
		exit();
	}
	
	//must have an app...
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	
	//User Object
	$thisUser = new User($guid);
	
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
	
	
	//get last 1000 screen requests for this app
	$strSql = "SELECT guid, appGuid, deviceId, dateStampUTC, deviceLatitude, deviceLongitude, deviceModel ";
	$strSql .= " FROM " . TBL_API_REQUESTS;
	$strSql .= " WHERE appGuid = '". $appGuid . "' AND deviceLatitude > 0 ";
	$strSql .= " ORDER BY id DESC ";
	$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	
	if($res){
	
		$numRows = mysql_num_rows($res);
		$cnt = 0;
		$arrayDevices = array();
		$arrayLatitudes = array();
		
			while($row = mysql_fetch_array($res)){
				
				if($cnt < 501){
				
					$tmpLatitude = $row["deviceLatitude"];
					$tmpLongitude = $row["deviceLongitude"];
				
					if(is_numeric($tmpLatitude) && is_numeric($tmpLongitude)){
						if($tmpLatitude != "0" && $tmpLongitude != "0"){
						
							$tmpLatitude = number_format($tmpLatitude, 3);
							$tmpLongitude = number_format($tmpLongitude, 3);
						
							//did we already add this device?
							if(!in_array($tmpLatitude, $arrayLatitudes)){
		
								//increment counter...
								$cnt++;
		
								//add it to the array so we don't add it again
								$arrayLatitudes[] = $tmpLatitude;
								$arrayDevices[] = $row["deviceId"];
							
								echo $row["deviceLatitude"] . ",";
								echo $row["deviceLongitude"] . ",";
								echo fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A") . ",";
								echo $row["deviceModel"];
	
								//end the line
								echo "\n";
								
							}//same location
							
						}//lat/long == 0
					}//is_numeric
				
				}else{
				
					//get out of while...
					break;
				
				}//cnt < 501
			}//end while

	}//if res
	
?>