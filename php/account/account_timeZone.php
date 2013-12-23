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
	$thisPage->pageTitle = "Account Control Panel | Update Time Zone";

	//vars
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$timeZone = $thisUser->infoArray["timeZone"];
	
	//on submit
	if($isFormPost){
		$timeZone = fnGetReqVal("timeZone", "", $myRequestVars);
		
		if(!is_numeric($timeZone) ){
			$bolPassed = false;
			$strMessage .= "<br>Invalid entry. Please enter a number only.";
			$timeZone = $thisUser->infoArray["timeZone"];
		}
		if($bolPassed){
		
			//update users email address...
			$strSql = "UPDATE " . TBL_USERS . " SET timeZone = '" . $timeZone . "', modifiedUTC = '" . $dtNow . "'";
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
                   Update Your Time-Zone
                </div>

                <div style='padding:10px;'>
                    
                    <?php if($bolDone ){ ?>
                        <div class='doneDiv'>
                            <b>Time Zone Updated.</b><br/>
                        </div>
                    <?php }?>
                            
					<?php if($strMessage != ""){ ?>
                        <div class='errorDiv'>
                            <?php echo $strMessage;?>
                        </div>
                     <?php } ?>

                    <div style='padding-top:5px;'>
                        <div style='width:225px;float:left;'>Server time:</div>
                        <div style='float:left'><b><?php echo fnMySqlNow("Y-m-d h:i:s A");?></b></div>
                        <div style='clear:both;'></div>
                    </div>
    
                    <div style='padding-top:5px;'>
                        <div style='width:225px;float:left;'>Your time (with offset applied):</div>
                        <div style='float:left'><b><?php echo fnFromUtc($dtNow, $timeZone, "Y-m-d h:i:s A");?></b></div>
                        <div style='clear:both;'></div>
                    </div>
    
                    <div style='padding-top:5px;'>
                        The timezone offset is the number of hours difference between your local time and
                        the servers time. Adjust this number until "your time" reflects your current local time 
                        so dates in the control panel display for your locale.
                    </div>
                    
    
                    <div style='padding-top:10px'>
                        <b>Time Zone Offset</b> (enter a number)<br/>
                        <input type="text" value="<?php echo fnFormOutput($timeZone)?>" name="timeZone" id="timeZone"  maxlength='100' />
                    </div>
                    
                    <div style='padding-top:5px;'>
                        <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                        <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo fnGetSecureURL(APP_URL);?>/account/';">
                    </div>
                        
                        
                
        
                </div>
                
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>

