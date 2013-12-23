<?php   require_once("../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Admin Control Panel | Create New Control Panel Id";

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//########################################################################
	//form submit
	$apiKey = fnGetReqVal("apiKey","", $myRequestVars);
	$apiSecret = fnGetReqVal("apiSecret","", $myRequestVars);
	$confirmApiSecret = fnGetReqVal("confirmApiSecret","", $myRequestVars);
	$ownerName = fnGetReqVal("ownerName","", $myRequestVars);
	$email = fnGetReqVal("email","", $myRequestVars);
	$allowedIPAddress = fnGetReqVal("allowedIPAddress", "", $myRequestVars);
	$expiresDate = fnGetReqVal("expiresDate","", $myRequestVars);
	
	//########################################################################
	//form submit
	if($isFormPost){
		$bolPassed = true;

		if(strlen($apiKey) < 1){
			$bolPassed = false;
			$strMessage .= "<br />API Key Required";
		}else{
			if(!fnIsAlphaNumeric($apiKey, false)){
				$bolPassed = false;
				$strMessage .= "<br />Letter and numbers only for Control Panel Id, no spaces";
			}
		}
		if(strlen($apiSecret) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Password Required";
		}else{
			if(!fnIsAlphaNumeric($apiSecret, false)){
				$bolPassed = false;
				$strMessage .= "<br />Letter and numbers only for passwords, no spaces";
			}else{
				if($apiSecret != $confirmApiSecret){
					$bolPassed = false;
					$strMessage .= "<br />Passwords don't match.";
				}
			}
		}

		if(!fnIsValidDate($expiresDate)){
			$bolPassed = false;
			$strMessage .= "<br />Expires date invalid. Required Format: 01/01/2025";
		}


		if(strlen($ownerName) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Owner Name Required";
		}
		if(strlen($email) > 1){
			if(!fnIsEmailValid($email) ){
				$bolPassed = false;
				$strMessage .= "<br />Email invalid";
			}
		}
		
		//quick check to make sure key is available....
		if($bolPassed){
			$tmpKey = new Bt_apikey("");
			if(!$tmpKey -> fnIsKeyAvailable($apiKey)){
				$bolPassed = false;
				$strMessage .= "<br />This Control Panel Id is not available (duplicates are not allowed). Please choose a different Control Panel Id";
			}
		}
		
		//if passed
		if($bolPassed){

				//misc vars.
				$dtNow = fnMySqlNow();
				$apiKeyGuid = strtoupper(fnCreateGuid());

				//insert expires date...
				$insertExpiresDate = fnMySqlDate($expiresDate);
				
				//create a new api key
				$objBtApiKey = new Bt_apikey("");
				$objBtApiKey->infoArray['guid'] = $apiKeyGuid;
				$objBtApiKey->infoArray['apiKey'] = $apiKey;
				$objBtApiKey->infoArray['apiSecret'] = md5($apiSecret);
				$objBtApiKey->infoArray['ownerName'] = $ownerName;
				$objBtApiKey->infoArray['email'] = strtolower($email);
				$objBtApiKey->infoArray['allowedIPAddress'] = $allowedIPAddress;
				$objBtApiKey->infoArray['expiresDate'] = $insertExpiresDate;
				$objBtApiKey->infoArray['lastRequestUTC'] = "";
				$objBtApiKey->infoArray['requestCount'] = "0";
				$objBtApiKey->infoArray['dateStampUTC'] = $dtNow;
				$objBtApiKey->infoArray['modifiedUTC'] = $dtNow;
				$objBtApiKey->infoArray['status'] = "active";
				$objBtApiKey->fnInsert();

				//flag as done
				$bolDone = true;
		}//bolPassed
		
	}//form submit



	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- left side--> 
        <div class='boxLeft'>
            <div class='contentBox colorDarkBg minHeight'>
                <div class='contentBand colorBandBg'>
                    Admin Options
                </div>
                <div id="leftNavLinkBox" style='padding:10px;white-space:nowrap;'>
        			<?php echo $thisPage->fnGetControlPanelLinks("admin", "", "block", ""); ?>
				</div>
             </div>
        </div>
        
        <!-- right side--> 
        <div class='boxRight'>
            <div class='contentBox colorLightBg minHeight'>
                
                <div class='contentBand colorBandBg'>
                   Create New Control Panel Id
                </div>

                <div style='padding:10px;'>
                    
                    <?php if($bolDone ){ ?>
                        <div class='doneDiv'>
                            <b>Control Panel Id Created Successfully</b>
                            
                            <div style='padding-top:10px;'>
                                <a href="<?php echo fnGetSecureURL(APP_URL);?>/admin/keys.php"><img src="../images/arr_right.gif" alt="arrow"/>Manage Data Access</a>
                            </div>

                            
                        </div>
                    <?php }?>
                            
                    <?php if(!$bolDone) { ?>      
    
                        <?php if($strMessage != ""){ ?>
                            <div class='errorDiv'>
                                <?php echo $strMessage;?>
                            </div>
                        <?php } ?>
                        
                        <table style='width:100%;'>
                        	<tr>
                            	<td style='padding:10px;vertical-align:top;width:250px;'>                      
                                        
                                    <label>Control Panel Id</label>
                                    <input type="text" value="<?php echo fnFormOutput($apiKey)?>"  name="apiKey" id="apiKey" maxlength='50' style="width:200px;"/>
                                    
                                    <label>Password</label>
                                    <input type="password" value="<?php echo fnFormOutput($apiSecret)?>"  name="apiSecret" id="apiSecret" maxlength='50' style="width:200px;"/>
                                    
                                    <label>Confirm Password</label>
                                    <input type="password" value="<?php echo fnFormOutput($confirmApiSecret)?>"  name="confirmApiSecret" id="confirmApiSecret" maxlength='50' style="width:200px;"/>
                                    
                                    <label>Expires Date <span style='font-weight:normal;'>01/01/2025</span></label>
                                    <input type="text" value="<?php echo fnFormOutput($expiresDate)?>"  name="expiresDate" id="expiresDate" maxlength='50' style="width:200px;"/>
        
                                
                                </td>
                            	<td style='padding:10px;vertical-align:top;'>                      

                                    <label>Owner Name</label>
                                    <input type="text" value="<?php echo fnFormOutput($ownerName)?>"  name="ownerName" id="ownerName" maxlength='50' style="width:200px;"/>
        
                                    <label>Owner Email Address</label>
                                    <input type="text" value="<?php echo fnFormOutput($email)?>"  name="email" id="email" maxlength='100' style="width:200px;"/>
        
                                    <label>IP Address</label>
                                    <input type="text" value="<?php echo fnFormOutput($allowedIPAddress)?>"  name="allowedIPAddress" id="allowedIPAddress" maxlength='50' style="width:200px;"/>
        
                                   <div style='padding-top:10px;'>
                                        <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                                        <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo fnGetSecureURL(APP_URL);?>/admin/keys.php';">
                                    </div>  
                                    
                             	</td>
                            </tr>
                            <tr>
                            	<td colspan='2'>
                                
                                    <div style='padding-top:5px;'>
                                       <i>Control Panel Id's</i> are usually a version of the owners name, use something like: johnsmith. In cases where you're giving
                                        access to an application, use a version of the app's name (all app's require an Control Panel Id).
                                    </div>
                                    <div style='color:red;'>
                                        Passwords are encrypted in the database and cannot be reverse encrypted. This means you'll never be able to figure out what
                                        it is after you create it. WRITE DOWN the password you enter!
                                    </div>
                                    <div style='padding-top:5px;'>
                                        All requests by this Control Panel Id (app / person) will require both the Control Panel Id and password in the URL or POST variables.
                                    </div>
                                    <div style='padding-top:5px;'>
                                        <i>Expire Dates</i> are used to limit how long into the future this Control Panel Id should work. 
                                        Use a date long into the future to "never expire."
                                    </div>
                                    <div style='padding-top:5px;'>
                                        <i>IP Addresses</i> are used to allow access from that IP address only. Leave this empty in
                                        most cases.
                                    </div>
                                                            
                                    
                                </td>
                            </tr>
                        </table>
                                    
                    <?php } ?>
                        
                
                             
                </div>
                    
                    
                    
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
