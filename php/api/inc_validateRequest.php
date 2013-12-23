<?php   require_once("../../config.php");
	
	//date reference..
	$dtNow = fnMySqlNow();


	//the api script that includes this file must have these variables
	//declared before including this validateRequest.php file
	if(!isset($clientVars)) $clientVars  = array();
	if(!isset($errors)) $errors  = array();
	$errorMessage = "";
	$errorCode = "";
	
	//add time stamp to clientVars array...
	$clientVars["timeStamp"] = fnMySqlNow();
	
	//details about the device making this API request...
	if(isset($_SERVER["REMOTE_ADDR"])) $clientVars["clientIPAddress"] = $_SERVER["REMOTE_ADDR"];
	if(isset($_SERVER["HTTP_USER_AGENT"])) $clientVars["clientAgentString"] = $_SERVER["HTTP_USER_AGENT"];
	
	//$_POST or $_GET variables sent with the request... 
	if(isset($_SERVER["REQUEST_METHOD"])){
		switch ($_SERVER["REQUEST_METHOD"]){
			case "POST":
				$clientVars["requestMethod"] = "POST";
				foreach($_POST as $key => $value){
					$clientVars[$key] = $value;
				}
			break;
			case "GET":
				$clientVars["requestMethod"] = "POST";
				foreach($_GET as $key => $value){
					$clientVars[$key] = $value;
				}
			break;
		}
	}
	//done getting client variables passed to the API request
	//////////////////////////////////////////////////////////////////////////////
	
	//figure out what directory we are in...
	$directoryName = "";
	if(isset($_SERVER["SCRIPT_FILENAME"])){
		$directoryName = $_SERVER["SCRIPT_FILENAME"];
		$directoryName = substr($directoryName, strpos($directoryName, "/api"));
		$directoryName = str_replace("/index.php", "", $directoryName);
	}
	
    //validate the request...
	if(!isset($clientVars["apiKey"])){
		$clientVars["apiKey"] = "";
		$errors[] = "Invalid API Credentials (1)";
		$errorCode = "1";
		$errorMessage .="API key not set. ";
	}
	if(!isset($clientVars["apiSecret"])){
		$clientVars["apiSecret"] = "";
		$errors[] = "Invalid API Credentials (2)";
		$errorCode = "2";
		$errorMessage .="API secret not set. ";
	}
	if(!isset($clientVars["command"])){
		$clientVars["command"] = "";
		$errors[] = "Invalid API command (3)";
		$errorCode = "3";
		$errorMessage .="Command secret not set. ";
	}
	if(!isset($clientVars["requestMethod"])){
		$clientVars["requestMethod"] = "";
		$errors[] = "Invalid request method (4)";
		$errorCode = "4";
		$errorMessage .="Invalid request method. ";
	}
	
	//appGuid required for all /app API requests...
	if(strtoupper($directoryName) == "/API/APP"){
		if(!isset($clientVars["appGuid"])){
			$clientVars["appGuid"] = "";
			$errors[] = "Invalid app id (5)";
			$errorCode = "5";
			$errorMessage .="App guid not set. ";
		}	
	}
	
	//don't have to have these...
	if(!isset($clientVars["clientIPAddress"])){
		$clientVars["clientIPAddress"] = "";
	}
	if(!isset($clientVars["clientAgentString"])){
		$clientVars["clientAgentString"] = "";
	}
	if(!isset($clientVars["userGuid"])){
		$clientVars["userGuid"] = "";
	}
	
	
	//fill user / device vars if not set (not required)...
	if(!isset($clientVars["appUserGuid"])){
		$clientVars["appUserGuid"] = "";
	}
	if(!isset($clientVars["deviceId"])){
		$clientVars["deviceId"] = "";
	}
	if(strtoupper($clientVars["deviceId"]) == "[DEVICEID]"){
		$clientVars["deviceId"] = "";
	}
	if(!isset($clientVars["deviceModel"])){
		$clientVars["deviceModel"] = "";
	}	
	if(strtoupper($clientVars["deviceModel"]) == "[DEVICEMODEL]"){
		$clientVars["deviceModel"] = "";
	}
	if(!isset($clientVars["deviceLatitude"])){
		$clientVars["deviceLatitude"] = "0";
	}else{
		if(!is_numeric($clientVars["deviceLatitude"])){
			$clientVars["deviceLatitude"] = "0";
		}
	}
	if(!isset($clientVars["deviceLongitude"])){
		$clientVars["deviceLongitude"] = "0";
	}else{
		if(!is_numeric($clientVars["deviceLongitude"])){
			$clientVars["deviceLongitude"] = "0";
		}
	}
	
	//deviceToken is used when registering for push notifications...
	if(!isset($clientVars["deviceToken"])){
		$clientVars["deviceToken"] = "";
	}	
	//deviceType is used when registering for push notifications...
	if(!isset($clientVars["deviceType"])){
		$clientVars["deviceType"] = "";
	}
	//currentMode is "live" or "design", defaults to "design"...
	if(!isset($clientVars["currentMode"])){
		$clientVars["currentMode"] = "design";
	}

	
	//valid so far?
	if(count($errors) < 1){	
		
		//get "rules" associated with this apiKey...
		$objBt_apiKey = new Bt_apikey("", $clientVars["apiKey"]);
		$apiSecret = $objBt_apiKey -> infoArray["apiSecret"]; //this md5
		$requestCount = $objBt_apiKey -> infoArray["requestCount"];
			if(!is_numeric($requestCount)) $requestCount = 0;
		$allowedIPAddress = $objBt_apiKey -> infoArray["allowedIPAddress"];
		$expiresDate = $objBt_apiKey -> infoArray["expiresDate"];
		$keyStatus = $objBt_apiKey -> infoArray["status"];

		//does secret match...Some may arrive encypted, others not..
		if(md5($clientVars["apiSecret"]) != $apiSecret && $clientVars["apiSecret"] != $apiSecret){
			$errors[] = "Invalid API Credentials (6)";
			$errorCode = "6";
			$errorMessage .="API credentials invalid. ";
		}

		//has the apiKey expired...
		if(strlen($expiresDate) > 5){
			$todays_date = date("Y-m-d");
			$today = strtotime($todays_date);
			$expiration_date = strtotime($expiresDate);
			if($expiration_date > $today){
				 //all good
			}else{
				$errors[] = "Invalid API Credentials (7)";
				$errorCode = "7";
				$errorMessage .="API credentials expired. ";
			}			
		}

		//valid client ip addrerss...
		if(strlen($allowedIPAddress) > 1){
			if(!isset($clientVars["clientIPAddress"])){
				$errors[] = "Invalid API Credentials (8)";
				$errorCode = "8";
				$errorMessage .="API IP address not set. ";
			}else{
				
				//compare the client's IP address with the "allowedIPAddress"
				if(strtoupper($clientVars["clientIPAddress"]) != strtoupper($allowedIPAddress)){
					$errors[] = "Requests not allowed from this IP address";
					$errorCode = "9";
					$errorMessage .="Invalid API IP address. ";
				}
				
			}
		}
		
		//is the key active...
		if(strtoupper($keyStatus) != "ACTIVE"){
			$errors[] = "Invalid API Credentials (10)";
			$errorCode = "10";
			$errorMessage .="API key not active. ";
		}
		
		
	}
	
	//insert an API request record...
	
	$objBt_apiRequest = new Bt_apirequest("");
	$objBt_apiRequest -> infoArray["guid"] = strtoupper(fnCreateGuid());
	$objBt_apiRequest -> infoArray["appGuid"] = fnFormInput($clientVars["appGuid"]);
	$objBt_apiRequest -> infoArray["clientApiKey"] = fnFormInput($clientVars["apiKey"]);
	$objBt_apiRequest -> infoArray["clientRemoteAddress"] = fnFormInput($clientVars["clientIPAddress"]);
	$objBt_apiRequest -> infoArray["clientUserAgent"] = fnFormInput($clientVars["clientAgentString"]);
	$objBt_apiRequest -> infoArray["requestDirectory"] = $directoryName;
	$objBt_apiRequest -> infoArray["requestCommand"] = fnFormInput($clientVars["command"]);
	$objBt_apiRequest -> infoArray["requestStatus"] = "valid";
		if(count($errors) > 0) $objBt_apiRequest -> infoArray["requestStatus"] = "invalid";
	$objBt_apiRequest -> infoArray["errorMessage"] = fnFormInput($errorMessage);
	$objBt_apiRequest -> infoArray["errorCode"] = fnFormInput($errorCode);
	$objBt_apiRequest -> infoArray["requestMethod"] = fnFormInput($clientVars["requestMethod"]);
	$objBt_apiRequest -> infoArray["appUserGuid"] = fnFormInput($clientVars["appUserGuid"]);
	$objBt_apiRequest -> infoArray["dateStampUTC"] = fnFormInput($clientVars["timeStamp"]);
	$objBt_apiRequest -> infoArray["deviceId"] = fnFormInput($clientVars["deviceId"]);
	$objBt_apiRequest -> infoArray["deviceModel"] = fnFormInput($clientVars["deviceModel"]);
	$objBt_apiRequest -> infoArray["deviceLatitude"] = fnFormInput($clientVars["deviceLatitude"]);
	$objBt_apiRequest -> infoArray["deviceLongitude"] = fnFormInput($clientVars["deviceLongitude"]);
	$objBt_apiRequest -> fnInsert();
	
	
	//if we have an apiKey update that key's "numberOfRequests"
	if(isset($clientVars["apiKey"])){
		if(strlen($clientVars["apiKey"]) > 0){
			$tmp = "UPDATE " . TBL_API_KEYS . " SET requestCount = (requestCount + 1), ";
			$tmp .= " lastRequestUTC = '" . $dtNow . "', modifiedUTC = '" . $dtNow . "' ";
			$tmp .= " WHERE apiKey = '" . fnFormInput($clientVars["apiKey"]) . "'";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		}
	}
	
	
	//bail if request is not valid...
	if(count($errors) > 0){
		
		//every api request gets a jsonResult...
		$jsonResult = "{\"result\":";
		
			//add the array of errors...
			$jsonResult .= "{\"status\":\"invalid\", \"errors\":[";
			
				for($x = 0; $x < count($errors); $x++){
					$jsonResult .= "{\"message\":\"" . $errors[$x] . "\"}, ";
				}
			
			//remove trailing comma
			$jsonResult = rtrim($jsonResult, ", ");
			
			//cap the errors
			$jsonResult .= "]}";

		//cap the result...
		$jsonResult .= "}";

		//print, exit...
		echo $jsonResult;
		exit();
		
	}
	
	
?>