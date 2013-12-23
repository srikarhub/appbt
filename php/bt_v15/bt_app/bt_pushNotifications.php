<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//User Object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_apn.js";	

	//javascript in footer
	$thisPage->scriptsInFooter = "bt_v15/bt_scripts/app_apnFooter.js";	

	//form does uploads...
	$thisPage->formEncType = "multipart/form-data";

	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appName = "";
	$queueGuid = fnGetReqVal("queueGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$googleProjectId = fnGetReqVal("googleProjectId", "", $myRequestVars);
	$googleProjectApiKey = fnGetReqVal("googleProjectApiKey", "", $myRequestVars);
	$certificateType = fnGetReqVal("certificateType", "", $myRequestVars);
	
	//apple push certs require passwords...
	$applePushCertDevPassword = fnGetReqVal("applePushCertDevPassword", "", $myRequestVars);
	$applePushCertProdPassword = fnGetReqVal("applePushCertProdPassword", "", $myRequestVars);
	$appleEnteredPassword = fnGetReqVal("appleEnteredPassword", "", $myRequestVars);
	
	//if sending a new message...
	$apnMessage = fnGetReqVal("apnMessage", "", $myRequestVars);
	$apnSoundEffectName = fnGetReqVal("apnSoundEffectName", "", $myRequestVars);
	$apnBadgeNumber = fnGetReqVal("apnBadgeNumber", "", $myRequestVars);

	//send to iOS and / or Android Devices...
	$apnSendToIOS = fnGetReqVal("apnSendToIOS", "0", $myRequestVars);
		if(!is_numeric($apnSendToIOS)) $apnSendToIOS = "0";
	$apnSendToAndroid = fnGetReqVal("apnSendToAndroid", "0", $myRequestVars);
		if(!is_numeric($apnSendToAndroid)) $apnSendToAndroid = "0";

	//iOS Development or Prouction cert...
	$developmentOrProduction = fnGetReqVal("developmentOrProduction", "", $myRequestVars);

	//esscape json...
	function jsonEncode($theData){
		static $start = array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"');
		static $end = array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"');
		return str_replace($start, $end, $theData);
	}	
	
	//app object...
	if($appGuid == ""){
	
		echo "invalid request";
		exit();
	
	}else{
		
		//app object...
		$objApp = new App($appGuid);
		$thisPage->pageTitle = $objApp->infoArray["name"] . " Control Panel";

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	}	
	
	//app name...
	$appName = $objApp->infoArray["name"];

	//paths to this applications files
	$appDataURL = $objApp->fnGetAppDataURL($appGuid);
	$appDataURL = fnGetSecureURL($appDataURL);
	
	$appDataDirectory = $objApp->fnGetAppDataDirectory($appGuid);
	$configDirectory = $appDataDirectory . "/config";
	
	//google cloud messaging API info...
	$googleProjectId = $objApp->infoArray["googleProjectId"];
	$googleProjectApiKey = $objApp->infoArray["googleProjectApiKey"];

	//apple cert passwords...
	$applePushCertDevPassword = fnGetReqVal("applePushCertDevPassword", "", $myRequestVars);
	$applePushCertProdPassword = fnGetReqVal("applePushCertProdPassword", "", $myRequestVars);

	$iosDevCertName = APP_CRYPTO_KEY . "_dev.pem";
	$iosProdCertName = APP_CRYPTO_KEY . "_prod.pem";
	
	//make sure config directory exists and is writeable...
	if(!is_dir($configDirectory)){
		$bolPassed = false;
		$strMessage .= "<br>This app's /config directory does not exist?";
	}else{	
		if(!is_writable($appDataDirectory)){
			$bolPassed = false;
			$strMessage .= "<br>This app's /config directory is not writeable by PHP. This means you cannot upload Push Notification Certificates.";
		}
	}
	
	/////////////////////////////////////////////////////////////////////////////////
	//delete ios Dev cert...
	if(strtoupper($command) == "CONFIRMDELETEIOSDEVCERT"  && $bolPassed){
			
		if(is_file($configDirectory . "/" . $iosDevCertName)){
			@unlink($configDirectory . "/" . $iosDevCertName);
		
			$bolDone = true;
			$command = "";
			$strMessage = "<b>iOS Development Certificate Removed</b>";
			$strMessage .= "<div style='padding-top:10px;'>";
			$strMessage .= "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "' title='OK, hide this message'><img src='../../images/arr_right.gif' alt='arrow' />OK, hide this message</a>";
			$strMessage .= "</div>";
		
		}else{
			$bolPassed = false;
			$strMessage .= "<br>iOS Development Certificate not found?";
		}			
		
	}
	//end delete ios Dev cert.
	/////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////////
	//delete ios Prod cert...
	if(strtoupper($command) == "CONFIRMDELETEIOSPRODCERT"  && $bolPassed){
		
		if(is_file($configDirectory . "/" . $iosProdCertName)){
			@unlink($configDirectory . "/" . $iosProdCertName);
		
			$bolDone = true;
			$command = "";
			$strMessage = "<b>iOS Production Certificate Removed</b>";
			$strMessage .= "<div style='padding-top:10px;'>";
			$strMessage .= "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "' title='OK, hide this message'><img src='../../images/arr_right.gif' alt='arrow' />OK, hide this message</a>";
			$strMessage .= "</div>";
		
		}else{
			$bolPassed = false;
			$strMessage .= "<br>iOS Production Certificate not found?";
		}			
		
	}
	//end delete ios Dev cert.
	/////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////////
	//remove from queue...
	if(strtoupper($command) == "REMOVEFROMQUEUE"  && $bolPassed){
		if($queueGuid != ""){
		
			//remove...
			$tmp = "DELETE FROM " . TBL_APN_QUEUE . " WHERE guid = '" . $queueGuid . "'";
			$tmp .= " AND appGuid = '" . $appGuid . "'";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//flag...
			$bolDone = true;
			$command = "";
			$strMessage = "<b>Push Notification Removed from Queue</b>";
			$strMessage .= "<div style='padding-top:10px;'>";
			$strMessage .= "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "' title='OK, hide this message'><img src='../../images/arr_right.gif' alt='arrow' />OK, hide this message</a>";
			$strMessage .= "</div>";
		}
	}
	//end remove from queue...
	/////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////////
	//save GCM settings...
	if(strtoupper($command) == "SAVEGCM"  && $bolPassed){
	
		//google cloud messaging API info...
		$googleProjectId = fnGetReqVal("googleProjectId", "", $myRequestVars);
		$googleProjectApiKey = fnGetReqVal("googleProjectApiKey", "", $myRequestVars);
	
		//blank OK for GCM settings...
		
		//if passed
		if($bolPassed){
		
			//update this app...
			$objApp->infoArray["googleProjectId"] = $googleProjectId;
			$objApp->infoArray["googleProjectApiKey"] = $googleProjectApiKey;
			$objApp->infoArray["modifiedUTC"] = $dtNow;
			$objApp->fnUpdate();
		
			$bolDone = true;
			$command = "";
			$strMessage = "<b>Google Cloud Messaging settings saved successfully</b>";
			$strMessage .= "<div style='padding-top:10px;'>";
			$strMessage .= "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "' title='OK, hide this message'><img src='../../images/arr_right.gif' alt='arrow' />OK, hide this message</a>";
			$strMessage .= "</div>";
		}
	
	}
	//End saveGCM...
	/////////////////////////////////////////////////////////////////////////////////
	
	/////////////////////////////////////////////////////////////////////////////////
	//addToQueue...
	if(strtoupper($command) == "ADDTOQUEUE"  && $bolPassed){
	
		//send flags...
		$sendToDevices = "";
		$bolSendApple = false;
		$bolSendGoogle = false;
	
		//comma separated list of ios and / or android devices to send to...
		//this will be iosDev, iosProd, or android (like "iosDev, android")...
		$sendToiOSList = "";
		$sendToAndroidList = "";
		$deviceCount = 0;
		$iosDeviceCount = 0;
		$androidDeviceCount = 0;
	
	
		//must have selected what types of devices to send to...
		if($apnSendToIOS != "1" && $apnSendToAndroid != "1"){
			$bolPassed = false;
			$strMessage .= "<br/>Please choose the type(s) of devices to send this message to (iOS and / or Android).";
		}
		
		//must have selected "live" or "design" mode devices...
		if($developmentOrProduction == ""){
			$bolPassed = false;
			$strMessage .= "<br/>Please choose \"Live\" or \"Design\" mode devices (Development or Production).";
		}
		
		
		//message length...
		if(strlen($apnMessage) < 1 || strlen($apnMessage) > 200){
			$bolPassed = false;
			$strMessage .= "<br/>Invalid message. Please enter up to 200 characters.";
		}
		
		//check for development or production cert...
		if($apnSendToIOS == "1" && $developmentOrProduction  != ""){
			if(strtoupper($developmentOrProduction) == "DESIGN"){
				if(!is_file($appDataDirectory . "/config/" . $iosDevCertName) || strlen($objApp->infoArray["applePushCertDevPassword"]) < 1){
					$bolSendApple = false;
					$bolPassed = false;
					$strMessage .= "<br/>You cannot send to iOS Development Devices without first uploading an iOS Development Certificate";
				}else{
					$bolSendApple = true;
					$sendToDevices .= "iosDev,";
				}
			}
			if(strtoupper($developmentOrProduction) == "LIVE"){
				if(!is_file($appDataDirectory . "/config/" . $iosProdCertName) || strlen($objApp->infoArray["applePushCertProdPassword"]) < 1){
					$bolSendApple = false;
					$bolPassed = false;
					$strMessage .= "<br/>You cannot send to iOS Production Devices without first uploading an iOS Production Certificate";
				}else{
					$bolSendApple = true;
					$sendToDevices .= "iosProd,";
				}
			}
		}

		//check for google credentials...
		if($apnSendToAndroid == "1"){
			if(strlen($objApp->infoArray["googleProjectId"]) < 1 || strlen($objApp->infoArray["googleProjectApiKey"]) < 1){
				$bolSendGoogle = false;
				$bolPassed = false;
				$strMessage .= "<br/>You cannot send to Android Devices without first entering Google GCM Credentials";
			}else{
				$bolSendGoogle = true;
				$sendToDevices .= "android,";
			}
		}
		
		//apnBadgeNumber must be numeric of blank...
		if(strlen($apnBadgeNumber) > 0){
			if(!is_numeric($apnBadgeNumber)){
				$bolPassed = false;
				$strMessage .= "<br/>Badge number invalid. Numbers only.";
			}
		}

		//passed...
		if($bolPassed){
		
			//possible sound effect...
			$soundEffect = "default";
			if(strlen($apnSoundEffectName) > 1){
				$soundEffect = $apnSoundEffectName;
			}
			
			//possible badge number...
			$badgeNum = "";
			if(is_numeric($apnBadgeNumber)){
				if($apnBadgeNumber > 0){
					$badgeNum = $apnBadgeNumber;
				}
			}
		
			//remove last command from sendToDevices from form selection...
			$sendToDevices = fnRemoveLastChar($sendToDevices, ",");
		
			//get registered device list to send to (must have at least one)...
			$strSql = "SELECT deviceMode, deviceToken, deviceType FROM " . TBL_APN_DEVICES;
			$strSql .= " WHERE appGuid = '" . $appGuid . "' ";
			if(strtoupper($developmentOrProduction) == "DESIGN"){
				$strSql .= " AND deviceMode = 'Design' ";
			}
			if(strtoupper($developmentOrProduction) == "LIVE"){
				$strSql .= " AND deviceMode = 'Live' ";
			}			
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($res){
				$numRows = mysql_num_rows($res);
				while($row = mysql_fetch_array($res)){
					$deviceCount++;
					
					//ios devices...
					if(($apnSendToIOS == "1") && strtoupper($row["deviceType"]) == "IOS"){
						$sendToiOSList .= $row["deviceToken"] . ",";
						$iosDeviceCount++;
					}

					//android devices...
					if($apnSendToAndroid == "1"  && strtoupper($row["deviceType"]) == "ANDROID"){
						$sendToAndroidList .= $row["deviceToken"] . ",";
						$androidDeviceCount++;
					}
					
				}//while...
			}//res...
			
			//remove last comma from each list...
			$sendToiOSList = fnRemoveLastChar($sendToiOSList, ",");
			$sendToAndroidList = fnRemoveLastChar($sendToAndroidList, ",");
		
		}//bolPassed..
		
		
		//if we are sending to iOS device, we must have some registered...
		if($apnSendToIOS == "1"){
			if($iosDeviceCount < 1){	
				$bolPassed = false;
				$strMessage .= "<br/>There are no iOS Devices registered. You can't send messages until devices are registered.";
				$strMessage .= "<br/>Devices need to register as \"Live\" or \"Design\", depending on which mode you selected.";
			}
		}	


		if($bolSendGoogle == "1"){
			if($androidDeviceCount < 1){	
				$bolPassed = false;
				$strMessage .= "<br/>There are no Android Devices registered. You can't send messages until devices are registered.";
				$strMessage .= "<br/>Devices need to register as \"Live\" or \"Design\", depending on which mode you selected.";
			}
		}	
		
		
		//still good?
		if($bolPassed){
			
			//insert into queue...
			if($sendToiOSList != "" || $sendToAndroidList != ""){
				$tmp = "INSERT INTO " . TBL_APN_QUEUE . " (guid, appGuid, message, sound, badge, dateStampUTC, sendToDevices, ";
				$tmp .= "iosDeviceTokens, androidDeviceTokens, iosNumTokens, androidNumTokens, status) VALUES ('" . strtoupper(fnCreateGuid()) . "', '" . $appGuid . "', ";
				$tmp .= "'" . $apnMessage . "', '" . $apnSoundEffectName . "','" . $apnBadgeNumber . "', '" . $dtNow . "', ";
				$tmp .= "'" . fnFormInput($sendToDevices) . "', '" . $sendToiOSList . "', '" . $sendToAndroidList . "', ";
				$tmp .= "'" . $iosDeviceCount . "', '" . $androidDeviceCount . "', 'pending')";
				fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			}
			
			//clear form...
			$apnMessage = "";
			$apnBadgeNumber = "";
			$apnSoundEffectName = "";
					
			//if we didn't find any registered devices...
			if($deviceCount < 1){
				$bolPassed = false;
				$strMessage .= "<br/>No registered devices found. You cannot send push notifications without registering at least one device.";
			}

		}//passed...
		
		//passed?
		if($bolPassed){
			
			$bolDone = true;
			$command = "";
			$strMessage = "<b>Push Notification added to queue successfully</b>";
			$strMessage .= "<div style='padding-top:10px;'>";
			$strMessage .= "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "' title='OK, hide this message'><img src='../../images/arr_right.gif' alt='arrow' />OK, hide this message</a>";
			$strMessage .= "</div>";
			
		}
		
	}
	//End addToQueue...
	/////////////////////////////////////////////////////////////////////////////////

	
	/////////////////////////////////////////////////////////////////////////////////
	//upload submit...
	if(strtoupper($command) == "UPLOADFILE"  && $bolPassed){
		
		//Get the file information
		$userfile_name = $_FILES['fileUpload']['name'];
		$userfile_tmp = $_FILES['fileUpload']['tmp_name'];
		$userfile_size = $_FILES['fileUpload']['size'];
		$userfile_type = $_FILES['fileUpload']['type'];
		$filename = basename($_FILES['fileUpload']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
		
		//saveAsFileName will depend on what type of cert is being uploaded... 
		$saveAsFileName = "";
						
		//only process if the file is acceptable and below the allowed file size limit constant...
		if((!empty($_FILES["fileUpload"])) && ($_FILES['fileUpload']['error'] == 0)) {
				
			//mime-type and extenstion must be allowed...
			if(strtolower($userfile_type) != "application/octet-stream" || strtolower($file_ext) != "pem"){
				$bolPassed = false;
				$strMessage = "<br/><b>Invalid File Type</b>. .pem files only. ";
				$strMessage .= "<br/>You tried to upload a file named <b>" . $filename . "</b>";
			}
			
		}else{
			$bolPassed = false;
			$strMessage .= "<br/>Please select a file before clicking upload";
		}
		
		//passphrase required...
		if(strlen($appleEnteredPassword) < 1){
			$bolPassed = false;
			$strMessage .= "<br/>Certificate passphrase required";
		}
		
		
		//if passed...
		if($bolPassed){
		
			//must have a certificateType selected...
			if(strlen($certificateType) < 3){
				$bolPassed = false;
				$strMessage .= "<br/>Please select the type of certificate you're uploading";
			}else{
			
				//encrypt passphrase with key so we can reverse them later...
				$appleEnteredPassword = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, APP_CRYPTO_KEY, $appleEnteredPassword, MCRYPT_MODE_ECB);
				
				//mcrypt returns binary data, convert for database...
				$appleEnteredPassword = base64_encode($appleEnteredPassword);
			
				//ios development cert...
				if(strtolower($certificateType) == "iosdevelopment"){
					$saveAsFileName = $appDataDirectory . "/config/" . $iosDevCertName;
					$applePushCertDevPassword = $appleEnteredPassword;
					$applePushCertProdPassword = $objApp->infoArray["applePushCertProdPassword"];
				}
				
				//ios production cert...
				if(strtolower($certificateType) == "iosproduction"){
					$saveAsFileName = $appDataDirectory . "/config/" . $iosProdCertName;
					$applePushCertDevPassword = $objApp->infoArray["applePushCertDevPassword"];
					$applePushCertProdPassword = $appleEnteredPassword;
				}
				
			}
		
			//must have a password entered for certificate file...
			if(strlen($appleEnteredPassword) < 3){
				$bolPassed = false;
				$strMessage .= "<br/>Certificate passphrase required";
			}
			
		
			//must have saveAsFileName...
			if(strlen($saveAsFileName) < 5){
				$bolPassed = false;
			}
			
			//check if the file size is above the allowed limit...
			if($bolPassed){
				if ($userfile_size > APP_MAX_UPLOAD_SIZE) {
					$bolPassed = false;
					$strMessage .= "<br/>Uploaded file is too large. The maximum allowed size is " . fnFormatBytes(APP_MAX_UPLOAD_SIZE) . " and you ";
					$strMessage .= "<br/>tried to upload a file that is " . fnFormatBytes(userfile_size);
				}
			}		

		}//bolPassed...

		//move file from temp. upload folder to app files folder..
		if($bolPassed){
			if(!move_uploaded_file($userfile_tmp, $saveAsFileName)){
				$bolPassed = false;
				$strMessage .= "<br/><b>Error Saving File</b>. The file uploaded OK but it could not be saved to the file system?";
			}else{
				chmod($saveAsFileName, 0777);
			}
		}
		
		//if passed
		if($bolPassed){
		
			//update this app...
			$objApp->infoArray["applePushCertDevPassword"] = $applePushCertDevPassword;
			$objApp->infoArray["applePushCertProdPassword"] = $applePushCertProdPassword;
			$objApp->infoArray["modifiedUTC"] = $dtNow;
			$objApp->fnUpdate();
		
			$bolDone = true;
			$command = "";
			$certificateType = "";
			$strMessage = "<b>" . fnFormOutput($filename, true) . "</b> (" . fnFormatBytes($userfile_size) . ") ";
			$strMessage .= "uploaded successfully.";
			$strMessage .= "<div style='padding-top:10px;'>";
			$strMessage .= "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "' title='OK, hide this message'><img src='../../images/arr_right.gif' alt='arrow' />OK, hide this message</a>";
			$strMessage .= "</div>";
			
		}
	
	}	
	//done uploading
	/////////////////////////////////////////////////////////////////////////////////
	
	//red / gree certificate dots...
	$iosDevCertDot = "<img src='" . fnGetSecureURL(rtrim(APP_URL, "/")) . "/images/red_dot.png' style='vertical-align:middle;margin:0px;margin-bottom:2px;margin-left:5px;'>";
	$iosProdCertDot = "<img src='" . fnGetSecureURL(rtrim(APP_URL, "/")) . "/images/red_dot.png' style='vertical-align:middle;margin:0px;margin-bottom:2px;margin-left:5px;'>";
                                    
    //does ios dev. cert exist...
	if(is_file($configDirectory . "/" . $iosDevCertName)){
		$iosDevCertDot = "<img src='" . fnGetSecureURL(rtrim(APP_URL, "/")) . "/images/green_dot.png' style='vertical-align:middle;margin:0px;margin-bottom:2px;margin-left:5px;'>";
		$iosDevCertDot .= "&nbsp;&nbsp;<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "&command=removeIosDevCert' title='Remove'>remove</a>";
	}

	//does ios dev. cert exist...
	if(is_file($configDirectory . "/" . $iosProdCertName)){
		$iosProdCertDot = "<img src='" . fnGetSecureURL(rtrim(APP_URL, "/")) . "/images/green_dot.png' style='vertical-align:middle;margin:0px;margin-bottom:2px;margin-left:5px;'>";
		$iosProdCertDot .= "&nbsp;&nbsp;<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "&command=removeIosProdCert' title='Remove'>remove</a>";
	}
	
	//////////////////////////////////////////
	//device count stats...
	$iosDeviceCount = fnGetOneValue("SELECT Count(*) FROM " . TBL_APN_DEVICES . " WHERE appGuid = '" . $appGuid . "' AND deviceType = 'ios'", APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	$androidDeviceCount = fnGetOneValue("SELECT Count(*) FROM " . TBL_APN_DEVICES . " WHERE appGuid = '" . $appGuid . "' AND deviceType = 'android'", APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>


<!--load the jQuery files using the Google API -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

<script>

	//html elements...
	var isSending = false;
	var controlsDiv;
	var loadingDiv;

	//fix up output when dom is ready...
 	$(document).ready(function(){
   
    });

	//start sending...
	function fnStartSending(queueId){
		if(!isSending){
			document.getElementById("confirmRemove_" + queueId).style.display = "none";
			$("#confirmSend_" + queueId).show('fast');
		}
	}
	
	//remove from queue...
	function fnRemoveFromQueue(queueId){
		if(!isSending){
			document.getElementById("confirmSend_" + queueId).style.display = "none";
  			$("#confirmRemove_" + queueId).show('fast');
		}
	}
	
	//trigger send...
	function fnConfirmSend(queueId){
		
		if(!isSending){
			isSending = true;
		
			//hide controls...
			controlsDiv = document.getElementById("controls_" + queueId);
			controlsDiv.style.display = "none";
	
			//loading...
			loadingDiv = document.getElementById("loading_" + queueId);
			$("#loading_" + queueId).show('fast');
			
			//url to post to...
			var theURL = "bt_pushNotifications_AJAX.php";
			
			//setup post
			http_request = false;
			if(theURL != ""){
				//post the request		
				if(window.XMLHttpRequest){ // Mozilla, Safari,...
					http_request = new XMLHttpRequest();
					if(http_request.overrideMimeType){
						http_request.overrideMimeType('text/html');
					}
				}else if(window.ActiveXObject) { // IE
					try{
						http_request = new ActiveXObject("Msxml2.XMLHTTP");
					}catch(e){
						try{
						   http_request = new ActiveXObject("Microsoft.XMLHTTP");
						}catch(e){}
					}
				}
				if(!http_request) {
					theDiv.innerHTML = "error saving?";
					return false;
				}
			}
			
			//submit
			var parameters = "appGuid=<?php echo $appGuid;?>";
			parameters += "&queueGuid=" + queueId;
	
			http_request.onreadystatechange = handleAJAXResult;
			http_request.open('POST', theURL, true);
			http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			http_request.send(parameters);
		
		}//isSending...
		
	}
	
	//processes results
	function handleAJAXResult(){
		if(http_request.readyState == 4) {
			if(http_request.status == 200) {
				result = http_request.responseText;
				
				//alert(result);
	
				//hide loading...
				loadingDiv.style.display = "none";

				//set data in controlsDiv...	
				controlsDiv.innerHTML = result;
				controlsDiv.style.display = "block";
				
				//flag...
				isSending = false;
		
				
			}else{
				
				//hide loading...
				loadingDiv.style.display = "none";

				//set data in controls...
				controlsDiv.innerHTML = "Error sending push notifications?";  
				controlsDiv.style.display = "block";
				
				//flag...
				isSending = false;
			
			}
		}
	}



	//cancel send...
	function fnCancelSend(queueId){
		if(!isSending){
  			$("#confirmSend_" + queueId).hide('fast');
		}
	}

	//cancel remove...
	function fnCancelRemove(queueId){
  		if(!isSending){
			$("#confirmRemove_" + queueId).hide('fast');
		}
	}
	
	
	
</script>



<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="queueGuid" id="queueGuid" value="<?php echo $queueGuid;?>">
<input type="hidden" name="command" id="command" value="<?php echo $command;?>">


<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- app control panel navigation--> 
        <div class='cpNav'>
        	<span style='white-space:nowrap;'><a href="<?php echo fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $appGuid;?>" title="Application Control Panel">Application Home</a></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
			<?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "inline", ""); ?>
        </div>
        
       	<div class='contentBox colorLightBg minHeight'>
           	
            <div class='contentBand colorBandBg'>
                Push Notifications for <?php echo fnFormOutput($appName, true);?>
           	</div>


             <div id="dataForm" style='padding:10px;visibility:visible;'>

				<?php if($strMessage != "" && !$bolDone) { ?>
					<div class="errorDiv">
                    	<?php echo $strMessage;?>
                        
                        <div style='padding-top:10px;'>
                            <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                        </div>
                        
                    </div>
				<?php } ?>

				<?php if($strMessage != "" && $bolDone) { ?>
					<div class="doneDiv">
                    	<?php echo $strMessage;?>
                    </div>
				<?php } ?>
                
				<?php if($command == "removeIosDevCert"){ ?>
                    
                    <div class="errorDiv">
                        <br/>
                        <b>Are you sure you want to delete this Development Certificate? This cannot be undone.</b>
                        <div style='padding-top:10px;'>
                            <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this certificate</a>
                        </div>
                        <div style='padding-top:10px;'>
                            <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>&command=confirmDeleteIosDevCert"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this certificate</a>
                        </div>
                    </div>
                
                <?php } ?>
                
				<?php if($command == "removeIosProdCert"){ ?>
                    
                    <div class="errorDiv">
                        <br/>
                        <b>Are you sure you want to delete this Production Certificate? This cannot be undone.</b>
                        <div style='padding-top:10px;'>
                            <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this certificate</a>
                        </div>
                        <div style='padding-top:10px;'>
                            <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>&command=confirmDeleteIosProdCert"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this certificate</a>
                        </div>
                    </div>
                
                <?php } ?>
                
                
                
                <table>
                	<td>
                    	<td style="vertical-align:top;width:75%;">
                        	
                            <div class="cpExpandoBox" style="min-width:500px;">
							
                                <div style='padding-top:5px;'>
                                    <b>Send to Devices</b><br/>
                                    
                                    <div style='padding-top:10px;vertical-align:middle;white-space:nowrap;'>
                                    	
                                        <input type="checkbox" name="apnSendToAndroid" id="apnSendToAndroid" value="1" <?php echo fnGetChecked($apnSendToAndroid, "1");?> style='vertical-align:middle;display:inline;'/>
                                        Android Devices
                                    	
                                        &nbsp;&nbsp;
                                    	<input type="checkbox" name="apnSendToIOS" id="apnSendToIOS" value="1" <?php echo fnGetChecked($apnSendToIOS, "1");?> style='vertical-align:middle;display:inline;'/>
                                        iOS Devices
                                    	
                                        &nbsp;&nbsp;
                                    	<select name="developmentOrProduction" id="developmentOrProduction">
                                        	<option value="">--select Live or Design devices</option>
                                            <option value="design" <?php echo fnGetSelectedString("design", $developmentOrProduction);?>>Send to devices in Design Mode</option>
                                            <option value="live" <?php echo fnGetSelectedString("live", $developmentOrProduction);?>>Send to devices in Live Mode</option>
                                        </select>
                                    
                                    </div>
                                    
                                </div>
                            
                                <div style='padding-top:10px;'>
                                    <b>Message Text</b> (200 chars max)<br/>
                                    <input type="text" id="apnMessage" name="apnMessage" value="<?php echo fnFormOutput($apnMessage);?> " style="width:475px;" />
                                </div>
    
                                <div style='padding-top:0px;float:left;'>
                                    <b>Sound Effect</b> (ios only)
                                        &nbsp;&nbsp;
                                        <img src='../../images/arr_right.gif' alt='arrow'/>
                                        <a href="<?php echo fnGetSecureURL(APP_URL);?>/bt_v15/bt_app/bt_pickerFile.php?appGuid=<?php echo $appGuid;?>&formEl=apnSoundEffectName&fileNameOrURL=fileName&searchFolder=/audio" rel="shadowbox;height=550;width=950">Select</a>
                                    <br/>
                                    <input type="text" name="apnSoundEffectName" id="apnSoundEffectName" value="<?php echo $apnSoundEffectName;?>" style="width:225px;"/>
                                    <div style='margin:0px;padding:0px;font-size:9pt;'>
                                        <i>audio file in Xcode project</i>
                                    </div>
                                </div>
    
                                <div style='padding-top:0px;float:left;margin-left:20px;'>
                                    <b>Badge Number</b> (ios only)
                                    <br/>
                                    <input type="text" name="apnBadgeNumber" id="apnBadgeNumber" value="<?php echo $apnBadgeNumber;?>" style="width:225px;"/>
                                    <div style='margin:0px;padding:0px;font-size:9pt;'>
                                        <i>Must be numeric or blank</i>
                                    </div>
                                </div>
                                <div class="clear"></div>
                                
                                <div style='padding-top:10px;'>
                                    <input type='button' title="save" value="save" id="addToQueue" name="addToQueue" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnAddToQueue();return false;">
                                    <div style='padding:5px;padding-left:0px;font-size:9pt;'>
                                        Save will add this message to the Push Notification queue. 
                                    </div>
                                </div>

							</div>
                            
                            <div class="cpExpandoBox">
                            	<b>Push Notification Queue</b>
                            	<table cellspacing='0' cellpadding='0' style="width:100%;">
                            	<?php 
									
									//queue data...
									$strSql = "SELECT guid, appGuid, message, badge, sound, dateStampUTC, sendToDevices, status, ";
									$strSql .= "iosNumTokens, androidNumTokens, iosDateSentUTC, androidDateSentUTC ";
									$strSql .= " FROM " . TBL_APN_QUEUE;
									$strSql .= " WHERE appGuid = '" . $appGuid . "' AND status != 'done' ";
									$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
									$cnt = 0;
									if($res){
										$numRows = mysql_num_rows($res);
										$cnt = 0;
										
										while($row = mysql_fetch_array($res)){
											$cnt++;
															
											//style
											$css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
											
											//if sending to iOS, determine which certificate to show...
											$certType = "";
											if($row["iosNumTokens"] > 0){
												if(substr($row["sendToDevices"], 0, 6) == "iosDev"){
													$certType = " (send to devices in Design Mode using Development Certificate) ";
												}
												if(substr($row["sendToDevices"], 0, 7) == "iosProd"){
													$certType = " (send to devices in Live Mode using Production Certificate) ";
												}
											}
											
											
											//message...
											$sound = $row["sound"];
												if($sound == "") $sound = "default";
											$badge = $row["badge"];
												if(!is_numeric($badge)) $badge = "0";
												
											//build payload...
											$payload = "{\"apps\":{\"alert\":\"" . fnFormOutput($row["message"]) . "\", \"badge\":" . $badge . ", \"sound\":\"" . fnFormOutput($sound) . "\"}}";
											
											//date created....
                                            $createdDate = fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
											
											//start link...
											$startLink = "<img src='../../images/arr_right.gif' alt='arrow'/><a href='#' onclick=\"fnStartSending('" . $row["guid"] . "');return false;\" title='Begin Sending'>begin sending</a>";
											
											//delete link...
											$deleteLink = "<a href='#' onclick=\"fnRemoveFromQueue('" . $row["guid"] . "');return false;\" title='Remove from Queue'>remove from queue</a>";
											
											echo "\n\n<tr>";
												echo "<td style='vertical-align:top;padding-top:10px;border-top:1px solid gray;width:300px;'>";
													
													echo "\n<div style='white-space:nowrap;'>";
														echo "<u>Created:</u> " . $createdDate;
													echo "\n</div>";
													
													echo "\n<div style='padding-top:3px;'>";
														echo "<u>Payload:</u> " . $payload;
													echo "\n</div>";

													echo "\n<div style='padding-top:3px;'>";
														echo "<u>Send To:</u> <span style='font-weight:bold;'>" . number_format($row["iosNumTokens"], 0, "", ",")  . "</span> iOS devices " . $certType;
														echo "<span style='font-weight:bold;'>" . number_format($row["androidNumTokens"], 0, "", ",")  . "</span> Android devices";
													echo "\n</div>";
													
												echo "</td>";
												echo "<td style='vertical-align:top;padding:10px;text-align:right;background-color:#FFFFFF;border-top:1px solid gray;height:75px;'>";
												
													echo "\n<div id=\"loading_" . $row["guid"] . "\" style='display:none;color:red;font-size:9pt;float:right;text-align:center;'>";
														echo "<img src=\"../../images/gif-loading-small.gif\" alt='loading' />";
														echo "<br/>sending...";
													echo "</div>";
													
													echo "\n<div id=\"controls_" . $row["guid"] . "\">";
														
														//if progress is "pending"...(bt_pushNotifications_AJAX.php updates the status)...
														if(strtoupper($row["status"]) == "PENDING"){
														
															echo $startLink;
															echo "&nbsp;|&nbsp;";
															echo $deleteLink;
														
															//send confirm links hidden...		
															echo "\n\n<div id=\"confirmSend_" . $row["guid"] . "\" style=\"text-align:right;padding-top:5px;display:none;\">";
																echo "<a href=\"#\" onclick=\"fnConfirmSend('" . $row["guid"] . "');return false;\" title='Confirm'>confirm</a>";
																echo "&nbsp;|&nbsp;";
																echo "<a href=\"#\" onclick=\"fnCancelSend('" . $row["guid"] . "');return false;\" title='Cancel'>cancel</a>";
															echo "\n</div>";
		
															//cancel confirm links hidden...		
															echo "\n\n<div id=\"confirmRemove_" . $row["guid"] . "\" style=\"text-align:right;padding-top:5px;display:none;\">";
																echo "<a href='bt_pushNotifications.php?appGuid=" . $appGuid . "&queueGuid=" . $row["guid"] . "&command=removeFromQueue' title='Confirm'>confirm</a>";
																echo "&nbsp;|&nbsp;";
																echo "<a href=\"#\" onclick=\"fnCancelRemove('" . $row["guid"] . "');return false;\" title='Cancel'>cancel</a>";
															echo "\n</div>";
														
														}else{
															
															//in progress or completed...
															echo "<i><span style='color:red;'>sending in progress</span></i>";
															echo "&nbsp;|&nbsp;";
															echo "<a href=\"bt_pushNotifications.php?appGuid=" . $appGuid . "\" title='Confirm'>refresh</a>";
														
														}
														
														
														
													//end controls...
													echo "\n\n</div>";
												
														
												echo "</td>";
											echo "\n</tr>";
											
                           				}//while
									}//res
									
									//no records...
									if($cnt < 1){
										echo "<tr>";
											echo "<td colspan='2' style='padding-top:5px;'><i>No messages in the APN queue</i></td>";
										echo "</tr>";
									}	
								
								
								?>
                            	</table>
                            </div>

                        </td>
                    	<td style="vertical-align:top;">

							<div class="cpExpandoBox">
                            	
                                <b>Registered Devices</b>
                                &nbsp;|&nbsp;
                                <a href="bt_pushDevices.php?appGuid=<?php echo $appGuid;?>">Show Devices</a>
                                <hr/>
                                
                                <div>
                                	
                                	<div style='float:left;padding-top:0px;width:100px;'>iOS</div>
                                	<div style='float:left;padding-top:0px;'><b><?php echo $iosDeviceCount;?></b></div>
                                    <div class="clear"></div>
                                
                                	<div style='float:left;padding-top:0px;width:100px;'>Android</div>
                                	<div style='float:left;padding-top:0px;'><b><?php echo $androidDeviceCount;?></b></div>
                                    <div class="clear"></div>
                                
                                
                                </div>

							</div>
                            
							<div class="cpExpandoBox">
                            	
                                <b>Google Cloud Messaging (GCM)</b>
                                &nbsp;|&nbsp;
                                <a href="http://developer.android.com/google/gcm/index.html" target="_blank">About</a>
                                <hr/>
                            	
                                <div style='padding-top:0px;'>
                        			GCM Project Number:<br/>
                                    <input type="text" name="googleProjectId" id="googleProjectId" value="<?php echo fnFormOutput($googleProjectId);?>">
                                </div>
                                
                                <div style='padding-top:0px;'>
                                	GCM API Key:<br/>
                                    <input type="text" name="googleProjectApiKey" id="googleProjectApiKey" value="<?php echo fnFormOutput($googleProjectApiKey);?>">
                                </div>
                                
                                <div style='padding-top:0px;'>
                                    <input type='button' title="save" value="save" id="saveGoogle" name="saveGoogle" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnSaveGCM();return false;">
								</div>
                                
                                
                                <div style='padding-top:20px;'>
                                	<b>Apple Push Certificates</b>
                                    &nbsp;|&nbsp;
                                    <a href="http://developer.apple.com/library/mac/#documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Introduction/Introduction.html#//apple_ref/doc/uid/TP40008194-CH1-SW1" target="_blank">About</a>
                                    <hr/>
                            	</div>
                                
                            	<div style='padding-top:0px;'>
                                	Development Cert.<?php echo $iosDevCertDot;?>
                                </div>
                                
                                <div style='padding-top:5px;'>
                                	Production Cert.<?php echo $iosProdCertDot;?>
                                </div>
                                
                                <div style='padding-top:5px;'>
                                    <select id="certificateType" name="certificateType" style='vertical-align:middle;width:150px;' align='absmiddle'>
                                        <option value="">...choose cert. type</option>
                                        <option value="iosDevelopment" <?php echo fnGetSelectedString("iosDevelopment", $certificateType);?>>Apple: Development</option>
                                        <option value="iosProduction" <?php echo fnGetSelectedString("iosProduction", $certificateType);?>>Apple: Production</option>
                                    </select>
                                </div>

                                <div style='padding-top:5px;'>
									.PEM Certificate Passphrase<br/>
                                    <input type="password" name="appleEnteredPassword" id="appleEnteredPassword" value="">
                                </div>
                                
                                .PEM Certificate File<br/>
                                <div class="fileinputs">
                                    <input type="file" id="fileUpload" name="fileUpload" class="file"/>
                                    <div class="fakefile">
                                        <input id="fileUploadValue" name="fileUploadValue" style="width:115px;height:18px;display:inline;vertical-align:middle;"/>
                                        <img src="../../images/plus.png" alt="select" style='display:inline;vertical-align:middle;margin-top:-8px;cursor:pointer;'/>
                                    </div>
                                </div>                                
                    
                                <div style='padding-top:5px;'>
                                    <input type='button' title="upload" value="upload" id="uploadButton" name="uploadButton" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnUploadBT_item();return false;">
                                </div>
                                <div id="isLoadingImage" class="cpExpandoBox" style='margin-left:0px;float:left;visibility:hidden;'>
                                    <img src="../../images/gif-loading-small.gif" style="height:40px;width:40px;margin-top:5px;">
                                </div>
                                <div id="isLoadingText" style='margin-top:15px;font-size:9pt;color:red;visibility:hidden;'>
                                    uploading...
                                    <br/>
                                    please wait...
                                </div>
                                <div class="clear"></div>
                      		
                            
                            </div>
                            
                        </td>
            		</td>
            	</table>
                 
            </div> 
            
             
         </div>       
    </fieldset>
    


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
