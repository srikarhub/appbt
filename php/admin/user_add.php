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
	$thisPage->pageTitle = "Admin Control Panel | Create New User";

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//########################################################################
	//form submit
	$firstName = fnGetReqVal("firstName","", $myRequestVars);
	$lastName = fnGetReqVal("lastName","", $myRequestVars);
	$email = fnGetReqVal("email","", $myRequestVars);
	$emailConfirm = fnGetReqVal("emailConfirm", "", $myRequestVars);
	$logInId = fnGetReqVal("logInId","", $myRequestVars);
	$logInPassword = fnGetReqVal("logInPassword","", $myRequestVars);
	$confirmPassword = fnGetReqVal("confirmPassword","", $myRequestVars);
	$userType = fnGetReqVal("userType","", $myRequestVars);
	
	//########################################################################
	//form submit
	if($isFormPost){

	
		$newUser = new User(); //init new, empty user
		$bolPassed = true;
		if(strlen($firstName) < 1){
			$bolPassed = false;
			$strMessage .= "<br />First Name Required";
		}
		if(strlen($lastName) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Last Name Required";
		}
		if(!fnIsEmailValid($email) ){
			$bolPassed = false;
			$strMessage .= "<br />Valid Email Required. This needs to be an email address that literally exists and works.";
		}else{
			//make sure emails match
			if(strtoupper($email) != strtoupper($emailConfirm)){
				$bolPassed = false;
				$strMessage .= "<br />Email Addresses Don't Match";
			}else{
				//see if it exists...
				if($newUser->fnIsEmailInUse($email)){
					$bolPassed = false;
					$strMessage .= "<br />Email Address Already Registered. You cannot use this email address";
				}
			}
		}
		$logInId = strtoupper($email);
		
		if(strlen($logInPassword) < 4){
			$bolPassed = false;
			$strMessage .= "<br />Password required (at least 4 characters)";
		}else{
			if(strtoupper($logInPassword) != strtoupper($confirmPassword)){
				$bolPassed = false;
				$strMessage .= "<br />Passwords do not match";
			}
		}
		
		if(strlen($userType) < 1){
			$bolPassed = false;
			$strMessage .= "<br />User Type Required";
		}
		
		
		//if passed
		if($bolPassed){

				//misc vars.
				$dtNow = fnMySqlNow();
				$userGuid = strtoupper(fnCreateGuid());
	
				//create a new user
				$objUser = new User("");
				$objUser->infoArray['guid'] = $userGuid;
				$objUser->infoArray['userType'] = $userType;
				$objUser->infoArray['firstName'] = $firstName;
				$objUser->infoArray['lastName'] = $lastName;
				$objUser->infoArray['email'] = strtolower($email);
				$objUser->infoArray['logInId'] = strtolower($email);
				$objUser->infoArray['logInPassword'] = md5($logInPassword);
				$objUser->infoArray['dateStampUTC'] = $dtNow;
				$objUser->infoArray['modifiedUTC'] = $dtNow;
				$objUser->infoArray['lastPageRequest'] = $dtNow;
				$objUser->infoArray['isLoggedIn'] = "0";
				$objUser->infoArray['timeZone'] = "0";
				$objUser->infoArray['pageRequests'] = "0";
				$objUser->infoArray['status'] = "confirmed";
				$objUser->infoArray['hideFromControlPanel'] = "0";
				$objUser->fnInsert();
				
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
                   Create New User
                </div>

                <div style='padding:10px;'>
                    
                    <?php if($bolDone ){ ?>
                        <div class='doneDiv'>
                            <b>User Created Successfully</b>
                            
                            <div style='padding-top:10px;'>
                                <a href="<?php echo fnGetSecureURL(APP_URL);?>/admin/users.php"><img src="../images/arr_right.gif" alt="arrow"/>Manage Users</a>
                            </div>

                            <div style='padding-top:10px;'>
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
                        
                        <div style='padding:10px;float:left;margin-right:20px;'>                      
                                        
                            <label>First Name</label>
                            <input type="text" value="<?php echo fnFormOutput($firstName)?>"  name="firstName" id="firstName" maxlength='50' style="width:200px;"/>

                            <label>Last Name</label>
                            <input type="text" value="<?php echo fnFormOutput($lastName)?>"  name="lastName" id="lastName" maxlength='50' style="width:200px;"/>

                            <label>Email Address</label>
                            <input type="text" value="<?php echo fnFormOutput($email)?>"  name="email" id="email" maxlength='100' style="width:200px;"/>

                            <label>Re-Enter Email</label>
                            <input type="text" value="<?php echo fnFormOutput($emailConfirm)?>"  name="emailConfirm" id="emailConfirm" maxlength='100' style="width:200px;"/>
                        
                            <div style='padding-top:5px;'>
                                <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                                <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo fnGetSecureURL(APP_URL);?>/admin/users.php';">
                            </div>                                    
                        </div>
                        
                        <div style='padding:10px;float:left;margin-right:20px;'>                      

                            <label>Choose a Password</label>
                            <input type="password" value="<?php echo fnFormOutput($logInPassword)?>"  name="logInPassword" id="logInPassword" maxlength='100' style="width:200px;"/>

                            <label>Confirm Password</label>
                            <input type="password" value="<?php echo fnFormOutput($confirmPassword)?>"  name="confirmPassword" id="confirmPassword" maxlength='100' style="width:200px;"/>

                            <label>User Type</label>
                            <select name="userType" id="userType" style="width:200px;">
                                <option value="">--select--</option>
                                <option value="normal" <?php echo fnGetSelectedString("normal", $userType);?>>Normal</option>
                                <option value="admin" <?php echo fnGetSelectedString("admin", $userType);?>>Admin</option>
                            </select>
                            <div style='padding-top:5px;'>
                                Normal users can only manage their own applications.
                                <br/>
                                Admin users can manage all applications.
                                <br/>
                                Admin users can add and modify users.
                            </div>
                            
                            
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
