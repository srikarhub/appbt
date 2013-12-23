<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//User Object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";	

	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_core_AJAX.php\";";
	
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$jsonVars = "";
	
	//app variables...
	$appName = "";
	$projectName = "";
	$version = "";
	$buzztouchAppId = "";
	$buzztouchAPIKey = "";
	$dataURL = "";
	$reportToCloudURL = "";
	$registerForPushURL = "";
	$startGPS = "";	
	$startAPN = "";
	$allowRotation = "";
	
	//location vars..
	$appAddress = "";
	$appCity = "";
	$appState = "";
	$appZip = "";
	$appLatitude = "";
	$appLongitude = "";
	
	//app object...
	if($appGuid == ""){
	
		echo "invalid request";
		exit();
	
	}else{
		
		//app object...
		$objApp = new App($appGuid);
		$thisPage->pageTitle = $objApp->infoArray["name"] . " Control Panel";

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	}
	
	
	//for default URL's
	$defaultDataURL = fnGetSecureURL(APP_URL) . "/api/app/?command=getAppData&appGuid=" . $appGuid . "&apiKey=" . $objApp->infoArray['apiKey'] . "&apiSecret=" . $objApp->infoArray['appSecret'];
	$defaultCloudURL = fnGetSecureURL(APP_URL) . "/api/app/?command=reportToCloud&appGuid=" . $appGuid . "&apiKey=" . $objApp->infoArray['apiKey'] . "&apiSecret=" . $objApp->infoArray['appSecret'] . "&deviceId=[deviceId]&deviceLatitude=[deviceLatitude]&deviceLongitude=[deviceLongitude]&deviceModel=[deviceModel]&userId=[userId]";
	$defaultRegisterForPushURL = fnGetSecureURL(APP_URL) . "/api/app/?command=registerForPush&appGuid=" . $appGuid . "&apiKey=" . $objApp->infoArray['apiKey'] . "&apiSecret=" . $objApp->infoArray['appSecret'] . "&deviceId=[deviceId]&deviceLatitude=[deviceLatitude]&deviceLongitude=[deviceLongitude]&deviceModel=[deviceModel]&userId=[userId]";

	//vars..
	$appName = $objApp->infoArray["name"];
	$projectName = $objApp->infoArray["projectName"];
	$version = $objApp->infoArray["version"];
	$buzztouchAppId = $objApp->infoArray["guid"];
	$buzztouchAPIKey = $objApp->infoArray["apiKey"];
	$dataURL = $objApp->infoArray["dataURL"];
	$reportToCloudURL = $objApp->infoArray["cloudURL"];
	$registerForPushURL = $objApp->infoArray["registerForPushURL"];
	$startGPS = $objApp->infoArray["startGPS"];
	$startAPN = $objApp->infoArray["startAPN"];
	$allowRotation = $objApp->infoArray["allowRotation"];
	
	$appAddress = $objApp->infoArray["appAddress"];
	$appCity = $objApp->infoArray["appCity"];
	$appState = $objApp->infoArray["appState"];
	$appZip = $objApp->infoArray["appZip"];
	$appLatitude = $objApp->infoArray["appLatitude"];
	$appLongitude = $objApp->infoArray["appLongitude"];
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- app control panel navigation--> 
        <div class='cpNav'>
        	<span style='white-space:nowrap;'><a href="<?php echo fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $appGuid;?>" title="Application Control Panel">Application Home</a></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
			<?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "inline", ""); ?>
        </div>
        
       	<div class='contentBox colorDarkBg minHeight'>
           	
            <div class='contentBand colorBandBg'>
               	Manage Core Properties for <?php echo fnFormOutput($appName, true);?>
           	</div>

            <div style='padding:10px;'>
                    <b>Core Properties</b> allow you to control the app's basic behavior when it launches. You can also modify
                    the app name and the app project name. The app name you enter here will appear under the app's icon
                    on the device. You can change the name that appears under the icon after after downloading the Xcode or
                    Android project. 
           	</div> 
                
            <div class='cpExpandoBox colorLightBg'>
           	<a href='#' onClick="fnShowHide('box_appName');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Application Name</a>
                <div id="box_appName" style="display:none;">
                    <div style='padding-top:10px;'>
                        <b>App Name</b><br/>
                        <input type="text" name="appName" id="appName" value="<?php echo fnFormOutput($appName);?>">
                    </div>
                    <div style='padding-top:5px;'>
                        Enter a name for the application. Enter only letters, numbers, or spaces - no special characters or
                        punctuation allowed. This name is used in the control panel and does not have to be displayed on the
                        device. You can change what is displayed on the device after downloading the project source code.
                    </div>
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('appName');return false;">
                        <div id="submit_appName" class="submit_working">&nbsp;</div>
                    </div>
                </div>
            </div>

            <div class='cpExpandoBox colorLightBg'>
           	<a href='#' onClick="fnShowHide('box_appKey');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Application Id, Control Panel Id / Password</a>
                <div id="box_appKey" style="display:none;">
                
                    <div style='padding-top:10px;width:175px;float:left;vertical-align:middle;'>
                        <b>Application Id:</b>
                    </div>
                   	<div style='padding-top:10px;font-size:12pt;float:left;vertical-align:middle;'>
                        <?php echo $objApp->infoArray["guid"];?>
                    </div>
					<div style='clear:both;'></div>
                   
                   	<div style='padding-top:5px;width:175px;float:left;vertical-align:middle;'>
                        <b>Control Panel Id:</b>
                    </div>
                   	<div style='padding-top:5px;font-size:12pt;float:left;vertical-align:middle;'>
                        <?php echo $objApp->infoArray["apiKey"];?>
                    </div>
   					<div style='clear:both;'></div>

                   	<div style='padding-top:5px;width:175px;float:left;vertical-align:middle;'>
                        <b>Control Panel Password:</b>
                    </div>
                   	<div style='padding-top:5px;font-size:12pt;float:left;vertical-align:middle;'>
                        <?php echo $objApp->infoArray["appSecret"];?>
                    </div>
					<div style='clear:both;'></div>

                    <div style='padding-top:15px;'>
                   		Every application in the control panel uses it's own unique id and Control Panel id / password when making requests
                        to this software for data. There may be PHP scripts in the software that power individual screens. 
                        Example: Maps that pull locations from the software's database. If these scripts use data provided by this software, 
                        they will need to know what id / secret to authenticate with. These values are provided here for reference.
                    </div>
                    <div style='padding-top:5px;margin-bottom:10px;'>
                		You can prevent this application from being able access data on your server by using the Admin > Data Access
                        screen. This is useful in cases where you have already distributed an application but want to prevent it from
                        making backend requests.
                	</div>
                    
                </div>
            </div>


               
            <div class='cpExpandoBox colorLightBg'>
            <a href='#' onClick="fnShowHide('box_projectName');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Project Name</a>
                <div id="box_projectName" style="display:none;">
                    <div style='padding-top:10px;'>
                        <b>Project Name</b><br/>
                        <input type="text" name="projectName" id="projectName" value="<?php echo fnFormOutput(strtolower($projectName));?>">
                    </div>
                    <div style='padding-top:5px;'>
                          Enter a name for the project. This will become the name of the Xcode or Android project when you 
                          download the source code. Letters and numbers only for project names, no spaces or special
                          characters allowed. Your entry will be converted to lower case. 
                    </div>
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('project');return false;">
                        <div id="submit_project" class="submit_working">&nbsp;</div>
                    </div>
                </div>
            </div>
            
            <div class='cpExpandoBox colorLightBg'>
            <a href='#' onClick="fnShowHide('box_dataURL');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Configuration Data URL</a>
            	<div id="box_dataURL" style="display:none;">
                    <div style='padding-top:10px;'>
                        <b>Configuration Data URL</b>
                        &nbsp;&nbsp;                                		
                        <img src='../../images/arr_right.gif' alt="arrow"/><a href='#' onclick="document.forms[0].dataURL.value='<?php echo $defaultDataURL;?>';return false;">Re-set to the default control panel URL</a>
                        &nbsp;&nbsp;                                		
                    </div>
                    <div style='padding-top:2px;'>
                        <input type="text" name="dataURL" id="dataURL" value="<?php echo fnFormOutput($dataURL);?>" style='width:99%;'>
                    </div>
                    
                    <div style='padding-top:5px;'>
                        This is the URL the mobile application downloads it's configuration data from when it needs refreshed.
                        When you donwload the source code for this project the BT_config.txt file in the download will contain
                        the app's latest configuration data.                        
                    </div>
                    
                    <?php if($dataURL != ""){	?> 
                        <div style='padding-top:5px;'>
                            <a href="<?php echo $dataURL;?>" target='_blank' title="Show Config Data"><img src="../../images/arr_right.gif" alt="pointere"/>Open the current config data URL</a>
                        </div>
                    <?php } ?>
                    
                
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('projectName');return false;">
                        <div id="submit_projectName" class="submit_working">&nbsp;</div>
                    </div>
            	</div>
           	</div>                        
            
            <div class='cpExpandoBox colorLightBg'>
            <a href='#' onClick="fnShowHide('box_cloudURL');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Report to Cloud URL</a>
            	<div id="box_cloudURL" style="display:none;">
                    <div style='padding-top:10px;'>
                        <b>Cloud URL</b>
                        &nbsp;&nbsp;
                        <img src='../../images/arr_right.gif' alt="arrow"/><a href='#' onclick="document.forms[0].reportToCloudURL.value='<?php echo $defaultCloudURL;?>';return false;">Re-set to the the default control panel URL</a>
                        <br/>
                        <input type="text" name="reportToCloudURL" id="reportToCloudURL" value="<?php echo fnFormOutput($reportToCloudURL);?>" style='width:99%;'>
                    </div>
                    <div style='padding-top:5px;'>
                        When the app launches it attempts to 
                        "report" to this URL. The primary purpose of this URL is to check for content updates. 
                        If updates are found end-users will be prompted to "refresh."
                        Leave this Report to Cloud URL blank if you don't want to check for updates. 
                    </div>
                    <div style='padding-top:5px;'>
                        <b>URL Merge-Fields</b> 
                    </div>
                    <div style='padding-top:5px;'>
                        You can append some environment variables to this URL if you want. This allows you to capture important
                        information about the application to then save in your backend systems. If you don't use the 
                        default control panel Report To Cloud URL you'll need to write a script in a language such as .PHP, .ASP, Java, etc. 
                        that understands how to process the HTTP request and return the appropriate data.
                    </div>
                    <div style='padding-top:5px;'>
                         <ul>
                            <li>[buzztouchAppId] This is the App Id for your buzztouch control panel</li>
                            <li>[buzztouchAPIKey] This is the app API Key for your buzztouch control panel</li>
                            <li>[userId] The Unique Id of a logged in user (if the app uses login screens)</li>
                            <li>[userEmail] The email address of a logged in user</li>
                            <li>[deviceId] A globally unique string value assigned to the device.</li>
                            <li>[deviceModel] A string value controlled by the device manufacturer.</li>
                            <li>[deviceLatitude] A latitude coordinate value (if the device is reporting it's location).</li>
                            <li>[deviceLongitude] A longitude coordinate value (if the device is reporting it's location).</li>
                        </ul>
                    </div>
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('cloudURL');return false;">
                        <div id="submit_cloudURL" class="submit_working">&nbsp;</div>
                    </div>
                    
                </div>
           	</div>   
    
    
            <div class='cpExpandoBox colorLightBg'>
            <a href='#' onClick="fnShowHide('box_startGPS');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Start Tracking Location (turn on GPS)</a>
            	<div id="box_startGPS" style="display:none;">
                    <div style='padding-top:10px;'>
                        <b>Turn on GPS</b><br/>
                        <select name="startGPS" id="startGPS">
                            <option value="1" <?php echo fnGetSelectedString("1", $startGPS);?>>Yes, start GPS on launch</option>
                            <option value="0" <?php echo fnGetSelectedString("0", $startGPS);?>>No, do not start GPS on launch</option>
                        </select>
                    </div>
                    <div style='padding-top:5px;'>
                        You may want to begin tracking the device's location when the app launches. This is useful in many
                        situations. Be sure to get the device owners consent when the app launches and honor all requests to
                        not track a device location. It's generally best to leave this setting OFF unless you're certain
                        end-users are aware of the purpose of you tracking their devices location. 
                    </div>
                    <div style='color:red;padding-top:5px;'>
                        <b>EVERY APP SHOULD PROVIDE END-USERS WITH A SETTINGS OR OPTIONS PANEL TO TURN OFF GPS TRACKING.</b>
                    </div>
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('gps');return false;">
                        <div id="submit_gps" class="submit_working">&nbsp;</div>
                    </div>
          		</div>                    
    		</div>
            
            <div class='cpExpandoBox colorLightBg'>
            <a href='#' onClick="fnShowHide('box_startAPN');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Push Notification Settings</a>
            	<div id="box_startAPN" style="display:none;">
                    
                    <div style='padding-top:10px;'>
                        <b>Prompt for Push Notifications</b><br/>
                        <select name="startAPN" id="startAPN">
                            <option value="1" <?php echo fnGetSelectedString("1", $startAPN);?>>Yes, prompt for Push Notifications</option>
                            <option value="0" <?php echo fnGetSelectedString("0", $startAPN);?>>No, do not prompt for Push Notifications</option>
                        </select>
                    </div>
                    
                    <div style='padding-top:2px;'>
                        <b>Register Device URL</b>
                        &nbsp;&nbsp;
                        <img src='../../images/arr_right.gif' alt="arrow"/><a href='#' onclick="document.forms[0].registerForPushURL.value='<?php echo $defaultRegisterForPushURL;?>';return false;">Re-set to the the default control panel URL</a>
                        <br/>
                        <input type="text" name="registerForPushURL" id="registerForPushURL" value="<?php echo fnFormOutput($registerForPushURL);?>" style='width:99%;'>
                    </div>
                    
                    
                    <div style='padding-top:5px;'>
                        These settings are used to prompt the app user to allow or disallow Push Notifications. When an app user
                        agrees, the device needs to report it's unique device token to a remote server where it is saved and
                        used when
                        <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>" title="Push Notifications">push notifications</a> are sent.
                    </div>
                    
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('apn');return false;">
                        <div id="submit_apn" class="submit_working">&nbsp;</div>
                    </div>
          		</div>                    
    		</div>
            
            <div class='cpExpandoBox colorLightBg'>
            <a href='#' onClick="fnShowHide('box_supportedOrientation');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Allow / Prevent Rotation (landscape, portrait)</a>
                <div id="box_supportedOrientation" style="display:none;">
                    
                    <div style='padding-top:10px;'>
                        <b>Allow Rotation</b><br/>
                        <select name="allowRotation" id="allowRotation" style='width:350px;'>
                            <option value="allDevices" <?php echo fnGetSelectedString("allDevices", $allowRotation);?>>All devices allow rotations</option>
                            <option value="largeDevicesOnly" <?php echo fnGetSelectedString("largeDevicesOnly", $allowRotation);?>>Only large devices allow rotations</option>
                        </select>
                    </div>
                    <div style='padding-top:5px;'>
                        Lots of app's look and perform better when only portrait orientation is supported. You cannot turn off landscape
                        orientation for large devices (tablets). All tablets support both orientations. 
                    </div>
                    <div style='padding-top:10px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('rotation');return false;">
                        <div id="submit_rotation" class="submit_working">&nbsp;</div>
                    </div>
                </div>
           	</div>                    
               
            <div class='cpExpandoBox colorLightBg'>
            	<a href='#' onClick="fnShowHide('box_address');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Application Location (address, city, state, lat/long)</a>
                <div id="box_address" style="display:none;">
                    <div style='padding-top:5px;'>
                        In most cases, location information associated with individual applications is unused. However, if you have
                        lots of applications configured in the control panel it's sometimes useful to associate a physical location with 
                        an app (think multiple locations for a business or an organization).
                    </div>
                    <div style='padding-top:10px;'>
                        <b>Address</b><br/>
                        <input type="text" name="appAddress" id="appAddress" value="<?php echo fnFormOutput($appAddress);?>" style='width:50%;'>
                    </div>

                    <div>
                        <b>City</b><br/>
                        <input type="text" name="appCity" id="appCity" value="<?php echo fnFormOutput($appCity);?>" style='width:50%;'>
                    </div>

                    <div>
                        <b>State</b><br/>
                        <input type="text" name="appState" id="appState" value="<?php echo fnFormOutput($appState);?>" style='width:50%;'>
                    </div>

                    <div>
                        <b>Zip</b><br/>
                        <input type="text" name="appZip" id="appZip" value="<?php echo fnFormOutput($appZip);?>" style='width:50%;'>
                    </div>

                    <div>
                        <b>Latitude</b><br/>
                        <input type="text" name="appLatitude" id="appLatitude" value="<?php echo fnFormOutput($appLatitude);?>" style='width:50%;'>
                    </div>

                    <div>
                        <b>Longitude</b><br/>
                        <input type="text" name="appLongitude" id="appLongitude" value="<?php echo fnFormOutput($appLongitude);?>" style='width:50%;'>
                    </div>
                    
                    <div style='padding-top:5px;'>
                        <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('address');return false;">
                        <div id="submit_address" class="submit_working">&nbsp;</div>
                    </div>
                    
         		</div>       
         
   			</div>
    	
    	</div>
    </fieldset>
        

<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
