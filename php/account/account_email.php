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
	$thisPage->pageTitle = "Account Control Panel | Update Email Address";

	//vars
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$email = fnGetReqVal("email", "", $myRequestVars);
	$emailConfirm = fnGetReqVal("emailConfirm", "", $myRequestVars);

	//on submit
	if($isFormPost){

		if(!fnIsEmailValid($email) ){
			$bolPassed = false;
			$strMessage .= "<br>Valid Email Required";
		}else{
			//confirm
			if(strtoupper($email) != strtoupper($emailConfirm)){
				$bolPassed = false;
				$strMessage .= "<br/>Email addresses do not match";
			}else{
				
				//see if another user is already using this address...
				$strSql = "SELECT email FROM " . TBL_USERS;
				$strSql .= " WHERE guid != '" . $guid . "' AND (email =  '" . $email . "' OR logInId = '" . $email . "') ";
				$strSql .= " LIMIT 0, 1";
				$tmp = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
				if($tmp != ""){
					$bolPassed = false;
					$strMessage .= "<br>Email already in use. You cannot use this email address, please enter another address and try again.";
				}
				
				
			}
		}
		if($bolPassed){
		
			//update users email address...
			$strSql = "UPDATE " . TBL_USERS . " SET email = '" . strtolower($email) . "', logInId = '" . strtolower($email) . "', modifiedUTC = '" . $dtNow . "'";
			$strSql .= " WHERE guid = '" . $guid . "' ";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 

			//flag as done
			$bolDone = true;
			
		}//if passed

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
                   Update Email Address. 
                </div>
                            
                <div style='padding:10px;'>
                    
                    <?php if($bolDone ){ ?>
                    
                        <div class='doneDiv'>
                            <b>Email Address Updated.</b><br/>
                            <div style='padding-top:5px;'>
                                You will need to use <b><?php echo strtolower($email);?></b> when logging in next time.
                            </div>
                            <div style='padding-top:5px;'>
                                <a href="<?php echo fnGetSecureURL(APP_URL);?>/account/"><img src="../images/arr_right.gif" alt="arrow"/>Account Home</a>
                            </div>
                        </div>
                    <?php }?>
                            
                            
                    <?php if(!$bolDone) { ?>      
     
                        <?php if($strMessage != ""){ ?>
                            <div class='errorDiv'>
                                <?php echo $strMessage;?>
                            </div>
                         <?php } ?>

                        <label>Enter your new email address</label>
                        <input type="text" value="<?php echo fnFormOutput($email)?>" name="email" id="email"  maxlength='100' />

                        <label>Re-enter the same email address</label>
                        <input type="text" value="<?php echo fnFormOutput($emailConfirm)?>" name="emailConfirm" id="emailConfirm"  maxlength='100' />

        
                        <div style='padding-top:5px;'>
                            <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                            <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo fnGetSecureURL(APP_URL);?>/account/';">
                        </div>
                        
                        <div class='infoDiv' style='margin-top:30px;'>
                        	<b>Email Addresses</b> are used as Login Id's. This means if you change your email address you will need to use that
                            address when login on your next visit.
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

