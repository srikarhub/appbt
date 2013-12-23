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
	$thisPage->pageTitle = "Account Control Panel | Update Name";

	//vars...
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();
	$firstName = fnGetReqVal("firstName", "", $myRequestVars);
	$lastName = fnGetReqVal("lastName", "", $myRequestVars);

	//on submit
	if($isFormPost){

		if(strlen($firstName) < 1){
			$bolPassed = false;
			$strMessage .= "<br>First Name Required";
		}
		if(strlen($lastName) < 1){
			$bolPassed = false;
			$strMessage .= "<br>Last Name Required";
		}
		
		if($bolPassed){
		
			$tmp = "UPDATE " . TBL_USERS . " SET ";
			$tmp .= "firstName = '" . $firstName . "', ";
			$tmp .= "lastName = '" . $lastName . "', ";
			$tmp .= "modifiedUTC = '" . $dtNow . "' ";
			$tmp .= "WHERE guid = '" . $guid . "' ";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

			//flag
			$bolDone = true;
						
		}//if passed

	}else{
	
		$firstName = $thisUser->infoArray["firstName"];
		$lastName = $thisUser->infoArray["lastName"];
		

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
                   Update Your Name
                </div>
                            
                <div style='padding:10px;'>
                    
                    <?php if($bolDone ){ ?>
                        <div class='doneDiv'>
                            <b>Your name has been updated</b>
                            
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
                        
                        <label>Your First Name</label>
                        <input type="text" value="<?php echo fnFormOutput($firstName)?>" name="firstName" id="firstName"  maxlength='50' />

                        <label>Your Last Name</label>
                        <input type="text" value="<?php echo fnFormOutput($lastName)?>" name="lastName" id="lastName"  maxlength='50' />

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

