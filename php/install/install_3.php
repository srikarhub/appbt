<?php

	//fnCreateGuid...	
	function fnCreateGuid(){
		$RandomStr_1 = md5(microtime()); 
		$RandomStr_2 = md5(microtime()); 
		$randomString = substr($RandomStr_2, 4, 4) . substr($RandomStr_1, 0, 15) . substr($RandomStr_2, 0, 4); 
		return $randomString;
	}

	//from previous page if we have it...
	$path = "";
	$appURL = "";
	$dbServer = "";
	$dbName = "";
	$dbUser = "";
	$dbPass = "";
	$dbTablePrefix = "";
	$physPath = "";
	
	$cryptoKey = strtoupper(fnCreateGuid());
	$cookieName = strtoupper(fnCreateGuid());
	$rememberMeName = strtoupper(fnCreateGuid());
	
	
	if(isset($_GET["path"])) $path = $_GET["path"];
	if(isset($_GET["appURL"])) $appURL = $_GET["appURL"];
	if(isset($_GET["dbServer"])) $dbServer = $_GET["dbServer"];
	if(isset($_GET["dbName"])) $dbName = $_GET["dbName"];
	if(isset($_GET["dbUser"])) $dbUser = $_GET["dbUser"];
	if(isset($_GET["dbPass"])) $dbPass = $_GET["dbPass"];
	if(isset($_GET["dbTablePrefix"])) $dbTablePrefix = $_GET["dbTablePrefix"];
	if(isset($_GET["path"])) $physPath = $_GET["path"];
	
	//build the config data...
	$configData = "";
	
	$configData .= "\n//################################################################################";
	$configData .= "\n//COPY AND PASTE CONFIG VALUES CREATED DURING THE INSTALL PROCESS";
	
	$configData .= "\n\n/* database server information. Enter your database login credentials here. */";
	$configData .= "\ndefine(\"APP_DB_HOST\", \"" . $dbServer . "\");";
	$configData .= "\ndefine(\"APP_DB_NAME\", \"" . $dbName . "\");";
	$configData .= "\ndefine(\"APP_DB_USER\", \"" . $dbUser . "\");";
	$configData .= "\ndefine(\"APP_DB_PASS\", \"" . $dbPass . "\");";
	
	$configData .= "\n\n/* buzztouch.com User Email Address, Password */";
	$configData .= "\ndefine(\"APP_BT_ACCOUNT_USEREMAIL\", \"YOUR_BUZZTOUCH_ACCOUNT_LOGIN\");";
	$configData .= "\ndefine(\"APP_BT_ACCOUNT_USERPASS\", \"YOUR_BUZZTOUCH_ACCOUNT_PASSWORD\");";

	$configData .= "\n\n/* buzztouch.com API URL, API Key, API Secret. Log in at buzztouch.com then see Account > Self Hosted Servers */";
	$configData .= "\ndefine(\"APP_BT_SERVER_API_URL\", \"YOUR_BUZZTOUCH_API_KEY_URL\");";
	$configData .= "\ndefine(\"APP_BT_SERVER_API_KEY\", \"YOUR_BUZZTOUCH_API_KEY\");";
	$configData .= "\ndefine(\"APP_BT_SERVER_API_KEY_SECRET\", \"YOUR_BUZZTOUCH_API_SECRET\");";

	$configData .= "\n\n/* application URL. Do not enter the trailing slash (/) after any URL. */";
	$configData .= "\ndefine(\"APP_URL\", \"" . $appURL . "\");";

	$configData .= "\n\n/* application physical path on server, data directories. */";
	$configData .= "\n/*These begin with a forward slash (/). Do not enter the trailing slash (/) */";
	$configData .= "\ndefine(\"APP_PHYSICAL_PATH\", \"" . $physPath .  "\");";
	$configData .= "\ndefine(\"APP_DATA_DIRECTORY\", \"/files\");";
	$configData .= "\ndefine(\"APP_THEME_PATH\", \"/files/theme\");";
	
	$configData .= "\n\n/* values used in <head> section of the HTML for many pages in this web based application */";
	$configData .= "\ndefine(\"APP_APPLICATION_NAME\", \"buzztouch\");";
	$configData .= "\ndefine(\"APP_DEFAULT_PAGE_TITLE\", \"buzztouch\");";
	$configData .= "\ndefine(\"APP_DEFAULT_PAGE_DESCRIPTION\", \"Open Source iOS and Android App Platform\");";
	$configData .= "\ndefine(\"APP_DEFAULT_KEYWORDS\", \"buzztouch\");";
	
	$configData .= "\n\n/* outbound email settings. Used by the forgot password routine on the login page */";
	$configData .= "\ndefine(\"APP_ADMIN_EMAIL\", \"no-reply@domain.com\");";
	$configData .= "\ndefine(\"APP_MAIL_SERVER\", \"\");";
	$configData .= "\ndefine(\"APP_MAIL_SERVER_USER\", \"\");";
	$configData .= "\ndefine(\"APP_MAIL_SERVER_PASS\", \"\");";
	$configData .= "\ndefine(\"APP_MAIL_USE_SMTP\", \"0\");";
	
	$configData .= "\n\n/* encryption key for senstive data. Set this once, then DO NOT CHANGE IT. */";
	$configData .= "\n/* letters and numbers only, NO SPACES OR SPECIAL CHARACTERS */";
	$configData .= "\ndefine(\"APP_CRYPTO_KEY\", \"" . $cryptoKey . "\");";
	
	$configData .= "\n\n/* google maps API key for application usage map */";
	$configData .= "\ndefine(\"APP_GOOGLE_MAPS_API_KEY\", \"YOUR_GOOGLE_API_KEY\");";
	
	$configData .= "\n\n/* miscellaneous settings */";
	$configData .= "\ndefine(\"APP_MAX_UPLOAD_SIZE\", \"52428800\");";
	$configData .= "\ndefine(\"APP_MAX_EXECUTION_TIME\", \"360\");";
	$configData .= "\ndefine(\"APP_LOGGEDIN_EXPIRES_SECONDS\", \"180\");";
	$configData .= "\ndefine(\"APP_ERROR_REPORTING\", \"0\");";
	$configData .= "\ndefine(\"APP_CURRENT_VERSION\", \"2.1.9\");";
	
	$configData .= "\n\n/* Cookie names are unique to this installation and must be unique values such as a GUID */";
	$configData .= "\ndefine(\"APP_LOGGEDIN_COOKIE_NAME\", \"" . $cookieName. "\");";
	$configData .= "\ndefine(\"APP_REMEMBER_COOKIE_NAME\", \"" . $rememberMeName . "\");";

	$configData .= "\n\n/* database table names. Do not change these unless you know what you're doing */";
	$configData .= "\ndefine(\"TBL_USERS\", \"" . $dbTablePrefix . "users\");";
	$configData .= "\ndefine(\"TBL_APPLICATIONS\", \"" . $dbTablePrefix . "applications\");";
	$configData .= "\ndefine(\"TBL_APP_USERS\", \"" . $dbTablePrefix . "app_users\");";
	$configData .= "\ndefine(\"TBL_CP_LINKS\", \"" . $dbTablePrefix.  "cp_links\");";
	$configData .= "\ndefine(\"TBL_BT_ITEMS\", \"" . $dbTablePrefix . "items\");";
	$configData .= "\ndefine(\"TBL_BT_FILES\", \"" . $dbTablePrefix . "files\");";
	$configData .= "\ndefine(\"TBL_BT_PLUGINS\", \"" . $dbTablePrefix . "plugins\");";
	$configData .= "\ndefine(\"TBL_API_REQUESTS\", \"" . $dbTablePrefix. "api_requests\");";
	$configData .= "\ndefine(\"TBL_API_KEYS\", \"" . $dbTablePrefix . "api_keys\");";
	$configData .= "\ndefine(\"TBL_APN_DEVICES\", \"" . $dbTablePrefix . "apn_devices\");";
	$configData .= "\ndefine(\"TBL_APN_QUEUE\", \"" . $dbTablePrefix . "apn_queue\");";
	
	$configData .= "\n\n//END COPY AND PASTE VALUES CREATED DURING THE INSTALL PROCESS";
	$configData .= "\n//################################################################################";
	$configData .= "\n\n\n";
	
	
	
	
	
	
	
	

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml" >

<head>
	<title>Buzztouch v2.1.9</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta http-equiv="imagetoolbar" content="false" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body style="background:url(../images/texture.png);">

<input type="hidden" name="step" id="step" value="3"/>

	<div class='contentWrapper'>
		<div class='contentWrap'>
			<div class="content">
            	
                <div style='text-align:middle;margin-left:0px;'>
                	<img src="../images/logo.png" alt="logo"/>
                </div>	
				<div class='contentBox colorLightBg minHeight'>
        			<div class='contentBand colorBandBg'>
            			Buzztouch v2.1.9 Installation.
               		</div>
                
                	<div style='padding:10px;'>
            			
                        <div style='font-size:12pt;margin-top:10px;vertical-align:middle;'>
                        	<img id="step_1" src="../images/num_4.png" style='vertical-align:middle;margin-right:20px;'/>
                    	</div>
                        <div style='margin-left:80px;margin-top:-45px;font-size:9pt;'>
                            <b>Our part is done, you have four more things to do <span style='color:red;'>before you leave this screen...</span></b>
                            
                            <table>
                            	<tr>
                                	<td colspan='2' style='vertical-align:top;'>
                                    	
                                            <div style='padding-top:10px;'>
                                               <b>1) First:</b>
                                                  	You need to 
                                                   	make some changes to the <b>/config.php</b> file in the root of your install. This file
                                                    is the main configuration file for the entire application. You'll need to replace the values
                                                    found on lines 23 - 89 with the values in the CONFIG DATA TEXT-AREA below. After updating the values
                                                    in the config.txt file, re-upload it to your server.
                                            </div>   
                                            
                                            <div style='padding-top:10px;'>
                                               <b>2) Second:</b>
                                                    AFTER DOING STEP 1 ABOVE, you need to 
                                					<a href="../index.php" target="_blank">click this link</a> to make sure the
                                                    login screen looks like you would expect. If an error shows it means the software could not
                                                    find the files it expected. This happens when the PATH or ROOT URL you entered on the previous screen
                                                    are incorrect. You'll need to
                                                    <a href="index.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>">go back and re-enter these values</a>.
                                         	</div>


                                            <div style='padding-top:10px;'>
                                               <b>3) Third</b>
                                                    You need to remove the <b>/install</b> directory from the file system on the webserver.
                                                   	Use the same FTP program you used to transfer the files to remove the directory. Delete it, 
                                                   	throw it away, it's no longer needed and you don't want anyone to find it.
                                         	</div>

                                            <div style='padding-top:10px;'>
                                               <b>4) Fourth</b>
													After veryfing the install, and making sure you can login. You'll need to
                                                    re-visit the /config.php file and enter your buzztouch.com API Key and your Buzztouch Account 
                                                    information. Update these values after veryfing the install.
                                         	</div>

                                            
										</td>
                                 </tr>    
                                 <tr>
                                	<td style='vertical-align:top;padding-top:10px;'>
                                    	<img src="../images/config.png" alt="config file"/>
                                    </td>

                                	<td style='vertical-align:top;padding-top:10px;text-align:right;'>
									   <img src="../images/files.png" alt="files"/>
                                       
                                    </td>
                                </tr>
                            </table>

							<div>
                            	<label>CONFIG DATA (copy and paste this into config.php)</label>
                                <div style='padding-top:5px;'>
                                	<textarea name="configData" id="configData" style="width:97%;height:200px;padding:10px;"><?php echo $configData;?></textarea>
                            	</div>
                            </div>



                            <div style='padding-top:15px;margin-left:auto;margin-right:auto;width:100%;margin-bottom:50px;'>
                                <div style='float:left;'>
                                    <input type="button" name="back" value="< back" class="buttonSubmit" onclick="document.location.href='index.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>';return false" />
                                </div>
                                <div style='float:right;margin-right:10px;'>
                                    <input type="button" name="finish" value="finish >" class="buttonSubmit" onclick="document.location.href='../index.php';return false" />
                                </div>
                                <div style='clear:both;'></div>
                            </div>
                                         



                         </div>   
                    </div>
                        
              </div>
            	<br/><br/><br/><br/>


            </div>
        	
    	</div>
    </div>

</body>






