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
	$thisPage->pageTitle = "Admin Control Panel | Settings";

	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/admin_settings.js";
	
	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/admin/settings_AJAX.php\";";

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//current settings values...
	$currentSettings = array();
	
	//returns settings value by name...
	function fnGetCurrentSettingsValue($settingName, $currentSettings){
		
		//don't show the API_SECRET...
		$curVal = "";
		if(array_key_exists($settingName, $currentSettings)){
			$curVal = $currentSettings[$settingName];
		}
		if($settingName == "APP_BT_SERVER_API_KEY_SECRET"){
			if($curVal != ""){
				return "xxxxxxxx";
			}else{
				return "";
			}
		}else{
			return $curVal;
		}
		return "";	
	}
	

	//########################################################################
	//existing settings...
	$constArray = get_defined_constants(true);
	foreach($constArray['user'] as $key => $val){
		$currentSettings[$key] = trim($val);
	}
	
	//show server's IP and Host Name
	$hostName = "";
	if(isset($_SERVER["SERVER_NAME"])) $hostName = $_SERVER["SERVER_NAME"];
	$ipAddress = "";
	if(isset($_SERVER["SERVER_ADDR"])) $ipAddress = $_SERVER["SERVER_ADDR"];
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<div class='content'>
<input type="hidden" name="command" id="command" value=""/>
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
                   Server Settings
                </div>

                <div style='padding:10px;'>
                    
					<?php if($strMessage != "" && $bolDone){ ?>
                        <div class='doneDiv' style='margin-top:0px;'>
                            <?php echo $strMessage;?> 
                        </div>
                    <?php } ?>
                       
					<?php if($strMessage != "" && !$bolDone){ ?>
                        <div class='errorDiv' style='margin-top:0px;'>
                        	<br/>The test email was not sent.
                            <?php echo $strMessage;?> 
                        </div>
                    <?php } ?>  
                    
                    <div class='infoDiv'>
                    	<b>Settings are Read Only on this screen.</b>
                        <div style='padding-top:5px;'>
                        	All of the values displayed on this screen are configured in the config.php file in the
                        	root of this softwares directory structure. This means that if you need to change any values
                        	displayed below, you'll need to use a text-editor to make the changes, then re-upload the config.php
                        	file to your remote host.
                    	</div>
                    </div> 
                                          
                    
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btServerInfo');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />buzztouch.com API Key and Secret</a>
                        <div id="box_btServerInfo" style="display:none;">
                            
                            
                            <div style='padding-top:10px;'>
								Visit your <a href="https://www.buzztouch.com/account" target="_blank">account screen at buzztouch.com</a>
                                to obtain an API key. Use this servers IP address and Host Name when configuring the API
                                key in your buzztouch.com account control panel.
                            </div>
                            
                            <?php if(strlen($hostName) > 0 || strlen($ipAddress) > 0) {?>
                                <div style='padding-top:10px;'>
                                	This servers IP ADDRESS is: <b><?php echo $ipAddress;?></b>
                                </div>
								<div style='padding-top:5px;'>
                                	This servers HOST NAME is: <b><?php echo $hostName;?></b>
                                </div>
							<?php } ?>
                            
                            <div style='padding-top:10px;'>
                                <b>buzztouch.com API Key</b> (get this at <a href="https://www.buzztouch.com/account" target="_blank">buzztouch.com</a>)<br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_BT_SERVER_API_KEY", $currentSettings))?>" name="APP_BT_SERVER_API_KEY" id="APP_BT_SERVER_API_KEY"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            	
                            <div>
                                <b>buzztouch.com API Secret</b> (matches the secret you entered at <a href="https://www.buzztouch.com/account" target="_blank">buzztouch.com</a>)<br/>
                                <input type="password" value="<?php echo fnGetCurrentSettingsValue("APP_BT_SERVER_API_KEY_SECRET", $currentSettings);?>" name="APP_BT_SERVER_API_KEY_SECRET" id="APP_BT_SERVER_API_KEY_SECRET"  maxlength="250" style="width:99%;" disabled/>
                            </div>

                            <div>
                                <b>buzztouch.com API URL</b> (provided by <a href="https://www.buzztouch.com/account" target="_blank">buzztouch.com</a>, used by your API Key)<br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_BT_SERVER_API_URL", $currentSettings))?>" name="APP_BT_SERVER_API_URL" id="APP_BT_SERVER_API_URL"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            
                            <div style='padding-top:10px;'>
                                <input type='button' title="validate" value="validate" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('btValidateKey');return false;">
                                <span id="submit_btValidateKey" class="submit_working">&nbsp;</span>
                                <div id="submit_btServerInfo" class="submit_working">&nbsp;</div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btServerNickname');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Server Nickname, Administrator Email</a>
                        <div id="box_btServerNickname" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                                <b>Server Nickname</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_APPLICATION_NAME", $currentSettings))?>" name="APP_APPLICATION_NAME" id="APP_APPLICATION_NAME"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                                <b>Server Administrator Email</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_ADMIN_EMAIL", $currentSettings))?>" name="APP_ADMIN_EMAIL" id="APP_ADMIN_EMAIL"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                            	The administrator email address is the "from address" when the system sends emails.
                            </div>
                            <div>
								The server nickname is the name of the web-application and is used in various routines throughout the app.
                               	This is a common, non-technical name for your self-hosted software and has nothing to do with any mobile
                                apps you create.
                            </div>
                        </div>
                    </div>
                    
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btServerLocation');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Application Directories, File Locations</a>
                        <div id="box_btServerLocation" style="display:none;">
                            
                            
                            <div style='padding-top:10px;'>
                                <b>Application Install Physical Path</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_PHYSICAL_PATH", $currentSettings))?>" name="APP_PHYSICAL_PATH" id="APP_PHYSICAL_PATH"  maxlength="250" style="width:99%;" disabled />
                            </div>
                            
                            <div>
                                <b>Application Root URL</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_URL", $currentSettings))?>" name="APP_URL" id="APP_URL"  maxlength="250" style="width:99%;" disabled />
                            </div>
                            <div>
                                <b>Application Data Directory</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_DATA_DIRECTORY", $currentSettings))?>" name="APP_DATA_DIRECTORY" id="APP_DATA_DIRECTORY"  maxlength="250" style="width:99%;" disabled />
                            </div>
                            
                            <div>
                                <b>Theme Directory Path</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_THEME_PATH", $currentSettings))?>" name="APP_THEME_PATH" id="APP_THEME_PATH"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            
                            <div style='padding-top:5px;'>
								The control panel's layout, style, color-scheme and logo are configured in a theme directory. 
                            	A theme directory has three files: style.css, favicon.png, logo.png. File names are case sensitive.
                                To make your own theme, copy the existing theme then rename the folder. Put the new folder in your 
								<b><?php echo APP_DATA_DIRECTORY;?></b> directory. Adjust the /theme directory value here to activate it.
                                The path value you enter here is relative to the softwares ROOT URL value. 
                            </div>
                            <div style='padding-top:5px;'>
                            	The <b>default theme</b> that ships with the install is located at: <b>/files/theme</b>
                            </div>
                            
                        </div>
                    </div>
                
                    
                    
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btWebPages');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Webpage Meta-Data Defaults</a>
                        <div id="box_btWebPages" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                                <b>Default Web Page Titles</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_DEFAULT_PAGE_TITLE", $currentSettings))?>" name="APP_DEFAULT_PAGE_TITLE" id="APP_DEFAULT_PAGE_TITLE"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                            	Default web page titles are used in HTML &lt;head&gt; sections if a page has no custom title.
                            </div>
                            
                            <div style='padding-top:10px;'>
                                <b>Default Web Page Descriptions</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_DEFAULT_PAGE_DESCRIPTION", $currentSettings))?>" name="APP_DEFAULT_PAGE_DESCRIPTION" id="APP_DEFAULT_PAGE_DESCRIPTION"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                            	Default web page descriptions are used in HTML &lt;head&gt; sections if a page has no custom description.
                            </div>
                            
                            <div style='padding-top:10px;'>
                                <b>Default Web Page Keywords</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_DEFAULT_KEYWORDS", $currentSettings))?>" name="APP_DEFAULT_KEYWORDS" id="APP_DEFAULT_KEYWORDS"  maxlength="250" style="width:99%;" disabled/>
                            </div>
							<div>
                            	Default web page keywords are used in HTML &lt;head&gt; sections if a page has no custom keywords.
                            </div>
                            
                        </div>
                    </div>

                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btErrorReporting');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Error Reporting</a>
                        <div id="box_btErrorReporting" style="display:none;">
                            
                             <div style='padding-top:10px;'>
                                <select name='APP_ERROR_REPORTING' id='APP_ERROR_REPORTING' style='width:99%;' disabled>
                                	<option value='0' <?php echo fnGetSelectedString(fnGetCurrentSettingsValue("APP_ERROR_REPORTING", $currentSettings), "0");?>>Error Reporting is Off. Best in almost all circumstances.</option>
                                	<option value='1' <?php echo fnGetSelectedString(fnGetCurrentSettingsValue("APP_ERROR_REPORTING", $currentSettings), "1");?>>Error Reporting is On. Best in testing environments.</option>
                                </select>
                            </div>
							<div>
                            	Turning on errror reporting may help you diagnose mysterious PHP and mySQL issues. 
                            </div>
                            
                        </div>
                    </div>
                    

                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btCookies');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Session Cookies, Logged In Expiration Time</a>
                        <div id="box_btCookies" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                                <b>Login Cookie Name</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_LOGGEDIN_COOKIE_NAME", $currentSettings))?>" name="APP_LOGGEDIN_COOKIE_NAME" id="APP_LOGGEDIN_COOKIE_NAME"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                                <b>Remember Me Cookie Name</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_REMEMBER_COOKIE_NAME", $currentSettings))?>" name="APP_REMEMBER_COOKIE_NAME" id="APP_REMEMBER_COOKIE_NAME"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                                <b>Login Expires Seconds</b> (1 hour = 360 seconds)<br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_LOGGEDIN_EXPIRES_SECONDS", $currentSettings))?>" name="APP_LOGGEDIN_EXPIRES_SECONDS" id="APP_LOGGEDIN_EXPIRES_SECONDS"  maxlength="250" style="width:99%;" disabled/>
                            </div>

                            <div>
								Cookies are small files passed between browsers and web servers that are used to track 
                                logged in sessions for individual users. No personal information is stored in these cookies.
                                Setting a unique name for each cookie prevents conflicts with other software running on the
                                same server. Not all servers will allow you to modify the session 
                                expiration time. Experiment as needed. 
                                Set this to -1 (negative one) to "stay logged in until logout is clicked."
                            </div>
                            
                        </div>
                    </div>
                    

                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btMailServer');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Outbound Mail Server</a>
                        <div id="box_btMailServer" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                                <b>SMTP Mail Server Name</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_MAIL_SERVER", $currentSettings))?>" name="APP_MAIL_SERVER" id="APP_MAIL_SERVER"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                                <b>SMTP User Name</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_MAIL_SERVER_USER", $currentSettings))?>" name="APP_MAIL_SERVER_USER" id="APP_MAIL_SERVER_USER"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                                <b>SMTP Password</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_MAIL_SERVER_PASS", $currentSettings))?>" name="APP_MAIL_SERVER_PASS" id="APP_MAIL_SERVER_PASS"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                                <b>Use SMTP for Outgoing Mail</b> (enter the word "YES" or "NO" without the quotes)<br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_MAIL_USE_SMTP", $currentSettings))?>" name="APP_MAIL_USE_SMTP" id="APP_MAIL_USE_SMTP"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            
                            <div>
								Routines such as the forgot password process and some notify administrator processes rely on outbound mail services. 
                                The system will attempt to use the built it sendMail() function included in most server installs unless you 
                                configure an SMTP server here then enter "YES" above.
                            </div>
                            
                            <div style='padding-top:0px;'>
                            	<b>Send a test email to...</b> (enter an email address)<br/>
                                <input type="text" name="sendTestToAddress" id="sendTestToAddress"  maxlength="250" style="width:300px;" value="" />
                                <br/>
                                <input type='button' title="send" value="send" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('btSendTestEmail');return false;">
                                <div id="submit_btSendTestEmail" class="submit_working">&nbsp;</div>
                            </div>

                        </div>
                    </div>                    
                    
                    
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_btMaxFileSize');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Maximum File Upload Size, Execution Time</a>
                        <div id="box_btMaxFileSize" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                                <b>Maximum File Upload Size (bytes)</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_MAX_UPLOAD_SIZE", $currentSettings))?>" name="APP_MAX_UPLOAD_SIZE" id="APP_MAX_UPLOAD_SIZE"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            <div>
                            	16mb = 16777216 bytes, 8mb = 8388608 bytes, 50mb = 52428800 bytes
                            </div>

                            <div style='padding-top:10px;'>
                                <b>Maximum Script Execution Time (seconds)</b><br/>
                                <input type="text" value="<?php echo fnFormOutput(fnGetCurrentSettingsValue("APP_MAX_EXECUTION_TIME", $currentSettings))?>" name="APP_MAX_EXECUTION_TIME" id="APP_MAX_EXECUTION_TIME"  maxlength="250" style="width:99%;" disabled/>
                            </div>
                            
                            <div style='padding-top:5px;'>
                            	Many servers, especially on low-cost hosting accounts, will not honor the values you enter here. 
                                If you're usure what this
                                means you'll likely not be able to change your servers behavior anyway. The values here are used to
                                limit file upload sizes IF your backend already supports larger file uploads.
                            </div>
                            
                        </div>
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
