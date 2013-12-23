<?php   require_once("../config.php");

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
	$thisPage->pageTitle = "Account Control Panel | Update Password";
	
	//vars...
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$logInPassword = fnGetReqVal("logInPassword", "", $myRequestVars);
	$confirmPassword_1 = fnGetReqVal("confirmPassword_1", "", $myRequestVars);
	$confirmPassword_2 = fnGetReqVal("confirmPassword_2", "", $myRequestVars);

	//on submit
	if($isFormPost){

		if(strlen($confirmPassword_1) < 4){
			$bolPassed = false;
			$strMessage .= "<br>Passwords must be at least 4 characters long";
		}
		
		if($bolPassed){
			//make sure the existing password is valid..
			if($thisUser->infoArray['logInPassword'] != md5($logInPassword)){
				$bolPassed = false;
				$strMessage .= "<br>The current password you entered in incorrect";
			}else{
				if(strtoupper($confirmPassword_1) != strtoupper($confirmPassword_2)){
					$bolPassed = false;
					$strMessage .= "<br>Please enter a new password then confirm it by entering it again, the two passwords you entered do not match";
				}
			}	
			
		}//if passed

		//if still good...
		if($bolPassed){
			$thisUser->fnUpdatePassword($guid, $confirmPassword_1);
			$bolDone = true;
			
		}

	}//not posted
	

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
                    <?php echo fnFormOutput($userName);?>
                </div>
                <div id="leftNavLinkBox" style='padding:10px;white-space:nowrap;'>
					<?php echo $thisPage->fnGetControlPanelLinks("account", "", "block", ""); ?>
				</div>
             </div>
        </div>
        
        <!-- right side--> 
        <div class='boxRight'>
            <div class='contentBox colorLightBg minHeight'>
                
                <div class='contentBand colorBandBg'>
                    Update Your Password
                </div>

                            
                <div style='padding:10px;'>
                            

                    <?php if($bolDone ){ ?>
                        <div class='doneDiv'>
                            <b>Your password has been updated</b><br/>
                            You will need to use the newly created password the next time you login.
                            
                            <div style='padding-top:5px;'>
                                <a href="<?php echo fnGetSecureURL(APP_URL);?>/account/"><img src="../images/arr_right.gif" alt="arrow"/>Account Home</a>
                            </div>
                        
                        </div>
                    <?php } ?>

                    <?php if(!$bolDone) { ?>      
              

                        <?php if($strMessage != ""){ ?>
                          <div class='errorDiv'>
                               <?php echo $strMessage;?>
                           </div>
                        <?php } ?>
                                  
                        <label>Enter your current password</label>          
                        <input type="password" value="<?php echo fnFormOutput($logInPassword)?>" name="logInPassword" id="logInPassword"  maxlength='20'/>

                        <label>Enter a new password</label>
                        <input type="password" value="<?php echo fnFormOutput($confirmPassword_1)?>" name="confirmPassword_1" id="confirmPassword_1" maxlength='20'/>
                    
                        <label>Confirm the new password</label>
                        <input type="password" value="<?php echo fnFormOutput($confirmPassword_2)?>" name="confirmPassword_2" id="confirmPassword_2" maxlength='20'/>

                        <div style='padding-top:5px;'>
                            <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                            <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo fnGetSecureURL(APP_URL);?>/account/';">
                        </div>
                    
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

