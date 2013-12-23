<?php require_once("config.php");

	//User Object
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	
	//user object (may or may not be logged in)...
	$thisUser = new User($guid);
	$thisUser -> fnUpdateLastRequest($guid);
	
	//URL... 
	$tmpPath = rtrim(APP_URL, "/") . "/";

	//meta-tag used for redirecting after logging in...
	$metaRedirect = "";
	
	//we may be arriving here after clicking logout.
	if(isset($_GET["logOut"]) || isset($_GET["timedOut"])){
		$bolDoLogOut = false;
		if(isset($_GET["logOut"])){
			if($_GET["logOut"] == "1") $bolDoLogOut = true;
		}
		if(isset($_GET["timedOut"])){
			if($_GET["timedOut"] == "1") $bolDoLogOut = true;
		}
		if($bolDoLogOut){
			
			//make sure user is "logged out"...
			$thisUser -> fnUpdateLastRequest($guid, "0");
			$thisUser -> infoArray["isLoggedIn"] = "0";
			$thisUser->infoArray["guid"] = "";
			
			//kill cookie and session
			setcookie(APP_LOGGEDIN_COOKIE_NAME, "", time() - 3600);
			$_SESSION[APP_LOGGEDIN_COOKIE_NAME] = "";
			
			//destroy the session.
			session_destroy();	
			
			//erase guid for user...
			$guid = "";		
			
		}
	}//isset($_GET["logOut"])
	
	//init page object
	$thisPage = new Page();

	$strMessage = "";
	$bolDone = false;
	$bolDidLogIn = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();
	$command = fnGetReqVal("command", "", $myRequestVars);
	$logInId = fnGetReqVal("logInId", "", $myRequestVars);
	$logInPassword = fnGetReqVal("logInPassword", "", $myRequestVars);
	$remember = fnGetReqVal("remember", "0", $myRequestVars);
	$email = fnGetReqVal("email", "", $myRequestVars);
	
	//creates a random password
	function fnCreateRandomPassword(){
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ023456789"; 
		srand((double)microtime()*1000000); 
		$i = 0; 
		$pass = ""; 
		while ($i <= 7) { 
			$num = rand() % 33; 
			$tmp = substr($chars, $num, 1); 
			$pass = $pass . $tmp; 
			$i++; 
		} 
		return $pass; 
	} 
	
	
	
	//########################################################################
	//form submit for login...
	if($isFormPost && strtoupper($command) == "LOGIN"){

		if(strlen($logInPassword) < 4 || strlen($logInPassword) > 100 ){
			$bolPassed = false;
			$strMessage .= "<br />Invalid Password";
		}
	
		//if valid
		if($bolPassed){
	
			//user class
			$thisUser = new User();
			$tmpGuid = $thisUser->fnIsValidLogin($logInId, $logInPassword);
			
			if(strlen($tmpGuid) > 2){
			
			
				//remember user's login id for about 90 days if they checked "remember me"
				//Note: DO NOT REMEMBER PASSWORDS IN COOKIES. EVER.
				if($remember == "1"){
					setcookie(APP_REMEMBER_COOKIE_NAME, $tmpGuid, time()+60*60*24*100, "/");
					setcookie(APP_REMEMBER_COOKIE_NAME . "-checked", "1", time()+60*60*24*100, "/");
				}else{
					setcookie(APP_REMEMBER_COOKIE_NAME, "0", time()+60*60*24*100, "/");
					setcookie(APP_REMEMBER_COOKIE_NAME . "-checked", "0", time()+60*60*24*100, "/");
				}				
			
				//set session var for this users guid...
				$_SESSION[APP_LOGGEDIN_COOKIE_NAME] = $tmpGuid;
				
				//set cookie var for this users guid...
				setcookie(APP_LOGGEDIN_COOKIE_NAME, $tmpGuid, time()+60*60*24*100, "/");
				
				//flag user as logged in....
				$thisUser -> infoArray["guid"] = $tmpGuid;
				$thisUser -> infoArray["isLoggedIn"] = "1";
				$thisUser -> fnUpdateLastRequest($tmpGuid, "1");

				//flag as done...
				$bolDone = true;
				$bolDidLogIn = true;
				
				//create the message to display in the HTML with a link to the users account screen...
				$strMessage = "<div class='doneDiv'><b>Login successful.</b></div>";
				$strMessage .= "<div style='padding-top:5px;'>";
					$strMessage .= "<a href='" . trim($tmpPath, "/") . "/account/?id=" . md5($tmpGuid) . "' target='_self'>Use this link</a> to continue to your account if you're not automatically re-directed.";
				$strMessage .= "</div>";
				$strMessage .= "<div style='padding-top:5px;'>";
					$strMessage .= "Be sure to logout when you're done with your session.";
				$strMessage .= "</div>";
				
				//setup the meta-redirect tag (3 second delay)...
				$metaRedirect = "\n<meta http-equiv=\"refresh\" content=\"3;url=" . trim($tmpPath, "/") . "/account/?id=" . md5($tmpGuid) . "\">";

			}else{ //invalid user
			
				$bolDidLogIn = false;
				$strMessage .= "<br />Login failed.";
					
			}
			
		}//if passed
			
	}//form submit for login...
	
	
	//form submit forgot password
	if($isFormPost && strtoupper($command) == "FORGOTPASSWORD"){
		
		
		if(!fnIsEmailValid($email)){
			$bolPassed = false;
			$strMessage .= "<br />Please enter a valid email address";
		}
				
		//used when sending
		$uid = "";
		$ownerGuid = "";
		$firstName = "";
		$lastName = "";
	  
		if($bolPassed){
	
			//fetch account info
			$strSql = "SELECT U.id, U.guid, U.email, U.logInId, U.logInPassword, U.firstName, U.lastName ";
			$strSql .= " FROM " . TBL_USERS . " AS U ";
			$strSql .= " WHERE U.email = '" . $email . "' LIMIT 0,1";
            $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($res){
				$numRows = mysql_num_rows($res);
				if($numRows > 0){
					$row = mysql_fetch_array($res);
					$uid = fnFormOutput($row['id']);
					$ownerGuid = fnFormOutput($row['guid']);
					$firstName = fnFormOutput($row['firstName']);
					$lastName = fnFormOutput($row['lastName']);
				}else{
					$bolPassed = false;
					$strMessage = "<br />Your login information could not be located. Are you sure you entered the correct email address?";
				}//end if rows
			}else{
				$bolPassed = false;
				$strMessage = "<br />Your login information could not be located. Are you sure you entered the correct email address?";
			}//select_db
			
			if(fnIsEmailValid($email) && $ownerGuid != ""){
				
				//create a random, temporary password
				$newPassword = fnCreateRandomPassword();
				
				//update users password to the temporary password..
				$strSql = "UPDATE " . TBL_USERS . " SET logInPassword = '" . md5($newPassword) . "', modifiedUTC = '" . $dtNow . "'";
				$strSql .= " WHERE guid = '" . $ownerGuid . "' ";
				
				//make sure we have an app name...
				$controlPanelName = "Application Name Not setup on Admin > Settings screen";
				if(defined("APP_ADMIN_EMAIL")){
					$controlPanelName = APP_APPLICATION_NAME;
				}


				//build the email message...				
				$emailContent = $controlPanelName;
				$emailContent .= "\n\nA request was made to re-set your password. If you did not make this ";
				$emailContent .= "request please contact a system administrator immediately.";
			
				$emailContent .= "\n\nSystem administrators work hard to protect your privacy and they ";
				$emailContent .= "need to hear from you if this request was made by someone other than you.";
			
				$emailContent .= "\n\nTemporary Password: " . $newPassword;
				$emailContent .= "\n\nLogin here: " . $tmpPath;
			
				$emailContent .= "\n\nPlease keep your password safe to prevent unauthorized access to your account. ";
				$emailContent .= "After logging in, visit your Account Settings to re-set your password. ";
				$emailContent .= "You will need the temporary password contained in this email to complete this process. ";
				
				//send the message...
				if(defined("APP_ADMIN_EMAIL")){
					if(fnIsEmailValid(APP_ADMIN_EMAIL)){
					
					
						//fnSendTextEmail($toAddress, $toName, $fromAddress, $fromName, $subject, $body, $commaSeperatedAttachs = "")
						if(fnSendTextEmail($email, $firstName . " " . $lastName, APP_ADMIN_EMAIL, $controlPanelName, "Re-Set Password", $emailContent, "")){
							
							//flag
							$bolDone = true;
							$strMessage = "<b>Password Reset</b>";
							$strMessage .= "<div style='padding-top:15px;'>Your account password has been re-set to a temporary password. We sent the new password to....</div>";
							$strMessage .= "<div style='padding-top:5px;font-size:13pt;font-weight:bold;'>" . strtolower(fnFormOutput($email)) . "</div>";
							$strMessage .= "<div style='padding-top:5px;'>Be sure to re-set your password after using the temporary password in the email.</div>";
							$strMessage .= "<div style='padding-top:5px;'><a href='#' onclick=\"fnShowLogIn();return false;\" title='OK'><img alt=\"arrow\" src='images/arr_right.gif' />OK, Hide this message</a></div>";    
		
							//execute update statement to set new password...
							fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		
							//flag as done...
							$email = "";
							$bolDone = true;
		
							
						}else{
						
							//flag
							$bolDone = false;
							$strMessage = "<span style='color:red;font-weight:bold;'>There was a problem sending an email to <b>" . strtolower(fnFormOutput($email)) . "</b></span>";
							$strMessage .= "<br />Your account information was found but the system had trouble sending you an email.";
						
						}//end if sent.
				
					//admin email no good
					}else{
						//flag
						$bolDone = false;
						$strMessage = "<span style='color:red;font-weight:bold;'>There was a problem sending an email to <b>" . strtolower(fnFormOutput($email)) . "</b></span>";
						$strMessage .= "<br />The administrtor email on the Admin > Server Settings screen is not a valid email address.";
					}

				//admin email not defined
				}else{
					//flag
					$bolDone = false;
					$strMessage = "<span style='color:red;font-weight:bold;'>There was a problem sending an email to <b>" . strtolower(fnFormOutput($email)) . "</b></span>";
					$strMessage .= "<br />There is no administrtor email setup on the Admin > Server Settings screen.";
				}
	
			}//valid email address.	
		}//if passed				

	}//form submit forgot password
	
	//if form was not submitted...
	if(!$isFormPost){
	
		$tempGuid = "";
		$tempRemember = "";
		
		if(isset($_COOKIE[APP_REMEMBER_COOKIE_NAME])) $tempGuid = $_COOKIE[APP_REMEMBER_COOKIE_NAME];
		if(isset($_COOKIE[APP_REMEMBER_COOKIE_NAME . "-checked"])) $tempRemember = $_COOKIE[APP_REMEMBER_COOKIE_NAME . "-checked"];

		if(strlen($tempGuid) > 1 && $tempRemember == "1"){
			$strSql = " SELECT U.logInId FROM " . TBL_USERS . " AS U WHERE U.guid = '" . $tempGuid . "' LIMIT 0, 1";
			$logInId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if(strlen($logInId) > 1){
				$remember = "1";
			}
		}//strlen(tempGuid)

	}//not submitted.
	

	//the css for each "box" depends on command...
	$cssLogIn = "block";
	$cssForgotPassword = "none";
	if(strtoupper($command) == "LOGIN"){
		$cssLogIn = "block";
		$cssForgotPassword = "none";
	}
	if(strtoupper($command) == "FORGOTPASSWORD"){
		$cssLogIn = "none";
		$cssForgotPassword = "block";
	}


	//if we just logged in, add a meta-redirect...
	if($metaRedirect != ""){
		$thisPage->customHeaders = $metaRedirect;
	}

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>	

<script type="text/javascript">

	//sumbit on enter (password field)
	function onEnter( evt,frm){
		var keyCode = null;
	if(evt.which){
		keyCode = evt.which;
	}else if(evt.keyCode){
		keyCode = evt.keyCode;
	}
	if(13 == keyCode){
		frm.btnLogin.click();
		return false;
	}
		return true;
	}	
	
	//focus
	document.body.onload = function(){
		var theForm = document.forms[0];
		try{
			if(theForm.logInId.value != "" && theForm.logInPassword.value != ""){
				theForm.btnLogin.focus();
			}else{
				document.forms[0].logInId.focus();
				document.forms[0].logInId.select();
			}
		}catch(er){
		
		}
	}


	function fnShowForgotPassword(){
		document.getElementById("messageBox").style.display = "none";
		document.getElementById("forgotPasswordInfo").style.display = "block";
		document.getElementById("logInInfo").style.display = "none";
	}
	
	function fnShowLogIn(){
		document.getElementById("messageBox").style.display = "none";
		document.getElementById("forgotPasswordInfo").style.display = "none";
		document.getElementById("logInInfo").style.display = "block";
	}
	
	function fnSubmit(theCommand){
		document.forms[0].command.value = theCommand;
		document.forms[0].submit();
	
	}
	
</script>


<input type="hidden" name="command" id="command" value="<?php echo $command;?>"/>
    
    
<div class='content'>
        
    <fieldset class='colorLightBg'>
           
    	<div class='contentBox colorLightBg minHeight'>
        	<div class='contentBand colorBandBg'>
            	Welcome
            </div>

                <div style='padding:15px;'>
        
                    <!--show message if arriving here while logged in -->
                    <?php if(strlen($thisUser->infoArray["guid"]) > 1 && !$bolDidLogIn){ ?>

                        <div class='doneDiv'>
                            You are logged in as <b><?php echo fnFormOutput($thisUser->infoArray["firstName"] . " " . $thisUser->infoArray["lastName"]);?></b>
                        </div>
                        
                        <div style='padding-top:5px;'>
                            <a href="<?php echo $tmpPath . "/account";?>" title='Account'><img src="images/arr_right.gif" alt="arrow">Show my account control panel</a>
                        </div>
                        
                        <div style='padding-top:5px;'>
                            <a href="<?php echo $tmpPath . "/?logOut=1";?>" title='Logout'><img src="images/arr_right.gif" alt="arrow">Logout</a>
                        </div>
                        
                    <?php } ?>
        
                    <!--show message if we just logged in, else show login form -->
                    <?php if($bolDidLogIn) { ?>
                            <?php echo $strMessage;?>
                            <div class="cpExpandoBox" style='margin-left:auto;margin-right:auto;width:55px;'>
                                <img src='images/gif-loading-small.gif' alt='loading'/>
                            </div>
                    <?php } ?>
                    
                    <!--show login form if we are not logged in -->
                    <?php if(strlen($thisUser->infoArray["guid"]) < 1){?>
                    
                        <div id="messageBox" name="messageBox">
                            
                            <?php if($strMessage != "" && !$bolDone){ ?>
                                <div class='errorDiv'>
                                    <?php echo $strMessage;?>
                                </div>
                            <?php } ?>
                            
                            <?php if($strMessage != "" && $bolDone){ ?>
                                <div class='doneDiv'>
                                    <?php echo $strMessage;?>
                                </div>
                            <?php } ?>
                            
                        </div>
                        
                        <?php if(isset($_GET["timedOut"])){ ?>
                            <?php if($_GET["timedOut"] == "1"){ ?>
                                <div class='errorDiv'>
                                    <br/>Your logged in session has ended.
                                </div>
                                <br/>
                            <?php } ?>
                        <?php } ?>
                        
                        <?php if(isset($_GET["logOut"])){ ?>
                            <?php if($_GET["logOut"] == "1"){ ?>
                                <div class='doneDiv'>
                                    You have been logged out.
                                </div>
                                <br/>
                            <?php } ?>
                        <?php } ?>  
                        
                        
                        <div id="logInInfo" style="display:<?php echo $cssLogIn;?>">
                            
                            <label>Login Id &nbsp;&nbsp;<span style='font-weight:normal;'><i>usually your email address</i></span></label>
                            <input type="text" value="<?php echo strtolower(fnFormOutput($logInId));?>" name="logInId" id="logInId"  maxlength="75" />
                            
                            <label>Password</label>
                            <input type="password" value="<?php echo fnFormOutput($logInPassword);?>" name="logInPassword" id="logInPassword" maxlength="20" onkeypress="return onEnter(event,this.form);" />
                             
                            <div class="pcheckbox">        
                                <input type="checkbox" value="1" <?php echo fnGetChecked($remember, "1");?> name="remember" id="remember" />
                                Remember My Login Id
                            </div>
                            
                            <div class='pcheckbox' style='padding-top:5px;;'>
                            	<input type="button" id="btnLogin" class="buttonSubmit" value="login" onclick="fnSubmit('logIn');return false;" />
                            </div>
                                                        
                            <div style='padding-top:10px;'>
                               <a href='#' onclick="fnShowForgotPassword();return false;" title='Forgot password'><img alt="arrow" src='images/arr_right.gif' />Forgot password?</a>
                            </div>
                                
                        </div>
         
                        <div id="forgotPasswordInfo" style="display:<?php echo $cssForgotPassword;?>">
                            
                            <?php if(!$bolDone){ ?>
                            
                                <label>Enter your Email Address</label>
                                <input type="text" value="<?php echo strtolower(fnFormOutput($email));?>" name="email" id="email"  maxlength="150" />
                                
                                <div style='padding-top:5px;;'>
                                	<input type="button" id="btnSubmitForgot" class="buttonSubmit" value="submit" onclick="fnSubmit('forgotPassword');return false;" />
                                </div>
                                                            
                                <div style='padding-top:10px;'>
                                   <a href='#' onclick="fnShowLogIn();return false;" title='Cancel'><img alt="arrow" src='images/arr_right.gif' />Cancel, Show Login</a>
                                </div>    
                            
                            <?php } ?>
                            
                        </div>
                        
                        
        
                    <?php } ?>
                
                </div>
         
         </div>       
    </fieldset>


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>