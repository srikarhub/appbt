<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);

	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);
	$userName = fnFormOutput($thisUser->infoArray["firstName"] . " " . $thisUser->infoArray["lastName"]);
	$userSince = fnFromUTC($thisUser->infoArray["dateStampUTC"], $thisUser->infoArray["timeZone"], "m/d/Y");

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Create New Application";
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";	
	
	//from may be admin (for cancel button)
	$from = fnGetReqVal("from", "", $myRequestVars);
	$cancelURL = fnGetSecureURL(APP_URL) . "/account";
	if(strtoupper($from) == "ADMIN") $cancelURL = fnGetSecureURL(APP_URL) . "/admin";

	
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//app vars...
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appName = fnGetReqVal("appName", "", $myRequestVars);
	$confirmCopyright = fnGetReqVal("confirmCopyright", "", $myRequestVars);

	//cleans up project name
	function fnProjectName($theVal){
		$theVal = str_replace(" ", "", $theVal);
		$theVal = strtolower($theVal);
		return $theVal;
	}
	
	//on submit
	if($isFormPost){

		if(strlen($appName) < 3){
			$strMessage .= "<br/>Enter at least 3 characters for the app's name.";
			$bolPassed = false;
		}else{
			if(!fnIsAlphaNumeric($appName, true)){
				$strMessage .= "<br/>Letters and numbers only for app names.";
				$bolPassed = false;
			}
		}
		
		//if good, title must start with a letter
		if($bolPassed){
			$nums = array("0","1","2","3","4","5","6","7","8","9");
			$firstChar = substr($appName, 0, 1);
			if(in_array($firstChar, $nums)){
				$strMessage .= "<br/>App names must begin with a letter.";
				$bolPassed = false;
			}
		}
		
		if($confirmCopyright != "1"){
			$strMessage .= "<br/>Check the \"I understand the copyright laws\" checkbox.";
			$bolPassed = false;
		}
		
		if($bolPassed){
			//check for dups
			$strSql = "SELECT id FROM " . TBL_APPLICATIONS . " WHERE status != 'deleted' AND name = '"  . $appName . "'";
			$iDups = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($iDups != ""){
				$strMessage .= "<br/>Choose a different name for the app, the one you entered is already in use.";
				$bolPassed = false;
			}	
		}	
		
		//applications directory must exist...
		if(!is_dir("../.." . APP_DATA_DIRECTORY . "/applications")){
			$strMessage .= "<br/>The Applications directory does not exist? Contact a system administrator for help.";
			$bolPassed = false;
		}else{
			if(!is_writeable("../.." . APP_DATA_DIRECTORY . "/applications")){
				$strMessage .= "<br/>The Applications directory is not writeable? Contact a system administrator for help.";
				$bolPassed = false;
			}
		}
		
		//create app		
		if($bolPassed){
		
			//do dups
			if($appGuid == ""){
			
				//new app
				$appGuid = "EA" . strtoupper(fnCreateGuid()); //guid's must begin with a letter.
				
				//create an apiKey and secret...ALL APPS need an apiKey to request data....
				$newApiKey = strtoupper(fnCreateGuid());
				$newApiSecret = strtoupper(fnCreateGuid());
				
				//create an app...
				$objApp = new App("");
				$objApp->infoArray['guid'] = $appGuid;
				$objApp->infoArray['ownerGuid'] = $guid;
				$objApp->infoArray['apiKey'] = $newApiKey;
				$objApp->infoArray['appSecret'] = $newApiSecret;
				$objApp->infoArray['version'] = "2.0";
				$objApp->infoArray['currentMode'] = "design";
				$objApp->infoArray['currentPublishVersion'] = "1.0";
				$objApp->infoArray['currentPublishDate'] = $dtNow;
				$objApp->infoArray['name'] = fnFormInput($appName);
				$objApp->infoArray['projectName'] = fnProjectName($appName);
				$objApp->infoArray['dataURL'] = APP_URL . "/api/app/?command=getAppData&appGuid=" . $appGuid . "&apiKey=" . $newApiKey . "&apiSecret=" . $newApiSecret;
				$objApp->infoArray['cloudURL'] = APP_URL . "/api/app/?command=reportToCloud&appGuid=" . $appGuid . "&apiKey=" . $newApiKey . "&apiSecret=" . $newApiSecret . "&deviceId=[deviceId]&deviceLatitude=[deviceLatitude]&deviceLongitude=[deviceLongitude]&deviceModel=[deviceModel]&userId=[userId]";
				$objApp->infoArray['registerForPushURL'] = APP_URL . "/api/app/?command=registerForPush&appGuid=" . $appGuid . "&apiKey=" . $newApiKey . "&apiSecret=" . $newApiSecret . "&deviceId=[deviceId]&deviceLatitude=[deviceLatitude]&deviceLongitude=[deviceLongitude]&deviceModel=[deviceModel]&userId=[userId]";
				$objApp->infoArray['dataDir'] = APP_DATA_DIRECTORY . "/applications/" . $appGuid;
				$objApp->infoArray['startGPS'] = "0";
				$objApp->infoArray['startAPN'] = "0";
				$objApp->infoArray['allowRotation'] = "largeDevicesOnly";
				$objApp->infoArray['iconUrl'] = "";
				$objApp->infoArray['iconName'] == "";
				$objApp->infoArray['viewCount'] = "0";
				$objApp->infoArray['deviceCount'] = "0";
				$objApp->infoArray['status'] = "created";
				$objApp->infoArray['modifiedUTC'] = $dtNow;
				$objApp->infoArray['dateStampUTC'] = $dtNow;
				$objApp->fnInsert();
				
				//create an API key for this app...
				$objBtApiKey = new Bt_apikey("");
				$objBtApiKey->infoArray['guid'] = strtoupper(fnCreateGuid());
				$objBtApiKey->infoArray['apiKey'] = $newApiKey;
				$objBtApiKey->infoArray['apiSecret'] = md5($newApiSecret);
				$objBtApiKey->infoArray['ownerName'] = "App: " . fnFormInput($appName);
				$objBtApiKey->infoArray['email'] = "";
				$objBtApiKey->infoArray['allowedIPAddress'] = "";
				$objBtApiKey->infoArray['expiresDate'] = fnMySqlDate("01/01/2025");
				$objBtApiKey->infoArray['lastRequestUTC'] = "";
				$objBtApiKey->infoArray['requestCount'] = "0";
				$objBtApiKey->infoArray['dateStampUTC'] = $dtNow;
				$objBtApiKey->infoArray['modifiedUTC'] = $dtNow;
				$objBtApiKey->infoArray['status'] = "active";
				$objBtApiKey->fnInsert();
				
				//create a default app user for the owner...
				$objAppUser = new Appuser();
				$objAppUser -> infoArray["guid"] = strtoupper(fnCreateGuid());
				$objAppUser -> infoArray["appGuid"] = fnFormInput($appGuid);
				$objAppUser -> infoArray["userType"] = "owner";
				$objAppUser -> infoArray["displayName"] = $thisUser->infoArray["firstName"] . " " . $thisUser->infoArray["lastName"];
				$objAppUser -> infoArray["email"] = strtolower($thisUser->infoArray["email"]);
				$objAppUser -> infoArray["encLogInPassword"] = md5($thisUser->infoArray["logInPassword"]);
				$objAppUser -> infoArray["status"] = "active";
				$objAppUser -> infoArray["numRequests"] = "0";
				$objAppUser -> infoArray["lastRequestUTC"] = '';
				$objAppUser -> infoArray["lastLoginUTC"] = '';
				$objAppUser -> infoArray["dateStampUTC"] = $dtNow;
				$objAppUser -> infoArray["modifiedUTC"] = $dtNow;
				$objAppUser -> fnInsert();

				//create a default theme...
				$newThemeGuid = strtoupper(fnCreateGuid());
				$objTheme = new Bt_item();
				$objTheme -> infoArray["guid"] = $newThemeGuid;
				$objTheme -> infoArray["parentItemGuid"] = "";
				$objTheme -> infoArray["uniquePluginId"] = "";
				$objTheme -> infoArray["loadClassOrActionName"] = "";
				$objTheme -> infoArray["hasChildItems"] = "0";
				$objTheme -> infoArray["loadItemGuid"] = "";
				$objTheme -> infoArray["appGuid"] = fnFormInput($appGuid);
				$objTheme -> infoArray["controlPanelItemType"] = "theme";
				$objTheme -> infoArray["itemType"] = "BT_theme";
				$objTheme -> infoArray["itemTypeLabel"] = "Global Theme";
				$objTheme -> infoArray["nickname"] = "Default Theme";
				$objTheme -> infoArray["orderIndex"] = "0";
				$objTheme -> infoArray["jsonVars"] = "{\"itemId\":\"" . $newThemeGuid . "\", \"itemType\":\"BT_theme\", \"backgroundColor\":\"#FFFFFF\"}";
				$objTheme -> infoArray["status"] = "active";
				$objTheme -> infoArray["dateStampUTC"] = $dtNow;
				$objTheme -> infoArray["modifiedUTC"] = $dtNow;
				$objTheme -> fnInsert();
					
				//every app needs a writeable directory for files and other assets. This is created on the app's home-screen.
				//this approach allows us to make sure it's directory exists everytime the app's control panel is loaded.
				
				//flag as done.
				$bolDone = true;
				
			
			}else{
				//flag
				$bolDone = true;
			}
		
		}//if passed
	
		
	}else{
	
		
	}//not posted


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<?php if($bolDone){ ?>
	<script type="text/javascript">
		function doneBuilding(){
			document.getElementById("isLoading").style.display = "none";
			document.getElementById("doneLoading").style.display = "block";
		}
		window.setTimeout(doneBuilding, 7000, true);
	</script>
<?php } ?>



<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="command" id="command" value="<?php echo $command;?>">



<div class='content'>
<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- left side--> 
        <div class='boxLeft'>
            <div class='contentBox colorDarkBg minHeight'>
                <div class='contentBand colorBandBg'>
                    <?php echo fnFormOutput($userName);?>
                </div>
                <div id="leftNavLinkBox" style='padding:10px;white-space:nowrap;'>
                
                   	<?php if(strtoupper($thisUser->infoArray["userType"]) == "ADMIN"){ ?>
						<div><a href="<?php echo fnGetSecureURL(APP_URL);?>/admin/" title="Admin Home"><img src="../../images/arr_right.gif">Admin Home</a></div>
					<?php } ?>
                
                
					<?php echo $thisPage->fnGetControlPanelLinks("account", "", "block", ""); ?>
                    
				</div>
             </div>
        </div>
        
        <!-- right side--> 
        <div class='boxRight'>
        	<div class='contentBox colorLightBg minHeight'>
                <div class='contentBand colorBandBg'>
                   Create a New Application
                </div>

                <div style='padding:10px;'>

					<?php if(!$bolDone && $strMessage != ""){ ?>
                       	<div class='errorDiv'>
                           	<?php echo $strMessage; ?>
                       	</div>
					<?php } ?>

                        		
                        
					<?php if($bolDone){ ?>
                        
                        <div id="isLoading" class="cpExpandoBox" style="padding:25px;height:250px;text-align:center;display:block;margin-right:auto;margin-left:auto;">
                            <div style='font-size:150%;color:#666666;'>Building "<?php echo fnFormOutput($appName);?>"</div>
                            <img src="<?php echo fnGetSecureURL(APP_URL);?>/images/gif-loading.gif">
                            <br/>
                            <br/>
                        </div>
     
                        <div id="doneLoading" style="display:none;">
                            <div class='doneDiv'>
                                <b>"<?php echo fnFormOutput($appName);?>" Created Successfully</b>
                            </div>
    
                            <div class='infoDiv'>
                                <b>Next steps...</b>
                                <ol>
                                    <li>Visit the apps control panel and upload an icon.</li>
                                    <li>Download the source code for the application and launch it in the simulator.</li>
                                    <li>Add screens and menu's to the application using the control panel.</li>    
                                </ol>
                                <div>
                                    <a href="index.php?appGuid=<?php echo $appGuid;?>" title="app control panel"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt="arrow"/>Continue to this app's control panel</a>
                                </div>
                            </div>
                        </div>                   
     
     
                    <?php } ?>
                    
                    <?php if(!$bolDone){ ?>
    
                            <b>Name the App</b> (letters and numbers only)<br/>
                            <input type="text" value="<?php echo fnFormOutput($appName)?>" name="appName" id="appName"  maxlength='50' />
                            <br/>App names must begin with a letter.

                            <div style='padding-top:10px;padding-left:5px;'>
                                <input type="checkbox" name="confirmCopyright" id="confirmCopyright" value="1" <?php echo fnGetChecked($confirmCopyright, "1");?> />
                                I understand these
                                <a href="http://www.copyright.gov/" target="_blank"/>copyright laws</a>
                            </div>

                            <div style='padding:5px;padding-top:10px;'>
                                <input type="button" id="btnSubmit" value="save" class="buttonSubmit" onclick="document.forms[0].submit();"/>
                                <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo $cancelURL;?>';">
                            </div>
                    
                    <?php } ?>

                        
                        
                    
            	</div>
    		</div>
    	</div>
    
    </fieldset>


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>









