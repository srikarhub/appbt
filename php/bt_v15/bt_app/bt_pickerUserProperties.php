<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	//include the js file to handle "save" clicks (ajax request)...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";
	
	//tell the "saveScreenData" js method in /app_utilities.js what URL to use...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_pickerUserProperties_AJAX.php\";";

	//add some inline css (in the <head>) for 100% width...
	$inlineCSS = "";
	$inlineCSS .= "html{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= "body{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= ".contentWrapper, .contentWrap{height:100%;width:100%;margin:0px;padding:0px;} ";
	$thisPage->cssInHead = $inlineCSS;


	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$BT_userId = fnGetReqVal("BT_userId", "", $myRequestVars);
	
	//need an appguid
	if($appGuid == "" || $BT_userId == ""){
		echo "invalid request";
		exit();
	}
	
	//user object..
	$objUser = new Appuser($BT_userId);
	$displayName = $objUser->infoArray["displayName"];
	$email = $objUser->infoArray["email"];


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_userId" id="BT_userId" value="<?php echo $BT_userId;?>">


<div class='content'>
        
    <fieldset class='colorLightBg minHeightShadowbox'>
        
       	<div class='contentBox colorLightBg minHeightShadowbox' style='-moz-border-radius:0px;border-radius:0px;'>
        
            <div class="contentBand colorBandBg" style='-moz-border-radius:0px;border-radius:0px;'>
            	User Properties
            </div>
            <div style='padding:10px;'>

                    <table cellspacing='0' cellpadding='0'>
                    	<tr>
                        	<td style="padding:10px;padding-bottom:0px;">

                                   <div style='padding-top:0px;'>
                                       <b>Display Name</b><br/>
                                       <input type="text" name="displayName" id="displayName" value="<?php echo fnFormOutput($displayName);?>" maxlength="50"/>
                                   </div>

                                   <div style='padding-top:0px;'>
                                        <b>Email Address</b><br/>
                                        <input type="text" name="email" id="email" value="<?php echo fnFormOutput($email);?>" maxlength="100"/>
                                    </div>
                                   <div style='padding-top:0px;'>
                                        <b>Reset Password</b>  ~ WRITE IT DOWN!<br/>
                                        <input type="password" name="password" id="password" value="" maxlength="50"/>
                                    </div>

                                   <div style='padding-top:0px;'>
                                        <b>Confirm Password</b><br/>
                                        <input type="password" name="passwordConfirm" id="passwordConfirm" value="" maxlength="50"/>
                                    </div>
                                                
                                <div style='padding-top:10px;'>
                                    <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('userItem');return false;">           
                               	    <div id="submit_userItem" class="submit_working" style='height:100px;'>&nbsp;</div>
                                </div>	
                                
                                	
							</td>
                           	<td style="padding:10px;padding-bottom:0px;">
                                <div style='color:red;padding-top:15px;'>
                                	Letters, numbers, and spaces only.<br/>
                                    No special characters allowed.
                                </div>
                                <div style='padding-top:5px;'>
									<b>Lost passwords cannot be recovered.</b>
                                	All sensitive user data, including passwords, is encrypted before
                                    it's stored in the database. This means you will only be able to reset
                                    passwords and not recover passwords.
                                </div>
							</td>
	
                     	</tr>
                  </table>




                    
    
    		</div>
        </div>
	</fieldset>

</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
