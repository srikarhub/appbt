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

		
		//btValidateKey...
		if(strtoupper($command) == "BTVALIDATEKEY"){
		
			//api key...
			if(strlen(APP_BT_SERVER_API_KEY) < 1){
				$bolPassed = false;
				$strMessage = "All Enteries Required. See config.php in the /root installation folder.";
			}
			
			//secret...
			if($bolPassed){
				if(strlen(APP_BT_SERVER_API_KEY_SECRET) < 1){
					$bolPassed = false;
					$strMessage = "All Enteries Required. See config.php in the /root installation folder.";
				}
			}
			
			//url
			if($bolPassed){
				if(strlen(APP_BT_SERVER_API_URL) < 1){
					$bolPassed = false;
					$strMessage = "All Enteries Required. See config.php in the /root installation folder.";
				}
			}
					
			//good still?
			if($bolPassed){
			
				//key/value pairs to send in the request...
				$postVars = "";
				$fields = array(
				
						//needed by the buzztouch.com api to validate this request
						"apiKey" => urlencode(APP_BT_SERVER_API_KEY),
						"apiURL" => urlencode(APP_BT_SERVER_API_URL),
						"apiSecret" => urlencode(APP_BT_SERVER_API_KEY_SECRET),
						
						"command" => urlencode("validateRegistration")
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
				
				if($jsonResult != ""){
					$json = new Json; 
					$decoded = $json->unserialize($jsonResult);
					if(is_object($decoded)){
						
						if(array_key_exists("result", $decoded)){
												
							$results = $decoded -> result;
							
							if(array_key_exists("status", $results)){
								$status = $results -> status;

								//valid?
								if(strtoupper($status) == "VALID"){
									$strMessage = $results -> result;
								}else{
									$bolPassed = false;
									//$strMessage = $results -> result;
								}
								
							}else{
								$bolPassed = false;
								$strMessage .= "<br>ERROR! results returned from server do not included \"status\" element";
							}
						}else{
							$bolPassed = false;
							$strMessage .= "<br>ERROR! results returned from server do not included \"result\" element";
						}
						
					}
				
				}else{
				
					$bolPassed = false;
					$strMessage .= "<br>JSON results returned from server are invalid?";
				
				}	
		
				
			}//bolPassed		
		
			if(!$bolPassed){
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Failed! The key / secret / URL you entered could not be validated</span>";
				exit();
			}else{
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'><span style='color:green;'>Success! " . $strMessage . "</span>";
				exit();
			}

		}
		

		//btSendTestEmail
		if(strtoupper($command) == "BTSENDTESTEMAIL"){
			$sendTestToAddress = fnGetReqVal("sendTestToAddress", "", $myRequestVars);
				
			//must have a system administrator email setups...
			if(!defined("APP_ADMIN_EMAIL")){
				$bolPassed = false;
				$strMessage .= "<br>Administrator Email Address required. See config.php in the /root installation folder.";
			}else{
				if(!fnIsEmailValid(APP_ADMIN_EMAIL)){
					$bolPassed = false;
					$strMessage .= "<br>Administrator Email Address is invalid. See config.php in the /root installation folder.";
				}
			}
			if(!fnIsEmailValid($sendTestToAddress)){
				$bolPassed = false;
				$strMessage .= "<br>Please enter a valid email \"test\" address";
			}
	
			//send the test email..
			if($bolPassed){
			
				//build the email message...				
				$emailContent = "Test message from " . APP_APPLICATION_NAME;
				if(fnSendTextEmail($sendTestToAddress, "", APP_ADMIN_EMAIL, APP_APPLICATION_NAME, "Test Email", fnFormOutput($emailContent), "")){
					
					$strMessage .= "<br/>Test message sent to <span style='color:black;font-weight:bold;'>" . $sendTestToAddress . "  </span> from  <span style='color:black;font-weight:bold;'>" . APP_ADMIN_EMAIL . "</span>";
					$strMessage .= "<div style='padding-top:5px;color:black;'><span style='color:red;'>IMPORTANT:</span>";
					$strMessage .= " The only thing this test did was SEND a message to the \"<b>" . $sendTestToAddress . "</b>\" address.";
					$strMessage .= " This software cannot determine if the message actually arrived at the inbox.";
					$strMessage .= " If the message never arrives at the inbox you'll need to diagnose what's wrong with this ";
					$strMessage .= " servers built-in Linux sendMail() configuration or the SMTP service provider you're using.";
					$bolDone = true;
					
				}else{
				
					$strMessage .= "<br>There was a problem sending the email?";
					$bolDone = true;
				
				}
			}
			
			
			//done...
			if(!$bolPassed){
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/red_dot.png' style='margin-right:5px;'><span style='color:red;'>Message not sent! " . $strMessage . "</span>";
				exit();
			}else{
				echo "<img src='" . fnGetSecureURL(APP_URL) . "/images/green_dot.png' style='margin-right:5px;'><span style='color:green;'>OK</span>" . $strMessage;
				exit();
			}			
			
				
		}
		
		


	}//isFormPost..
		
	
?>