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
	
	//javascript files in <head>...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";

	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_appVersion_AJAX.php\";";
	
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appName = "";
	$BT_itemId = fnGetReqVal("BT_itemId", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);

	//published date, version...
	$currentPublishDate = fnGetReqVal("currentPublishDate", "", $myRequestVars);
	$currentPublishVersion = fnGetReqVal("currentPublishVersion", "", $myRequestVars);

	//app object...
	if($appGuid == ""){
	
		echo "invalid request";
		exit();
	
	}else{
		
		//app object...
		$objApp = new App($appGuid);
		$thisPage->pageTitle = $objApp->infoArray["name"] . " Control Panel";

		$currentPublishDate = $objApp->infoArray["currentPublishDate"];
		$currentPublishVersion = $objApp->infoArray["currentPublishVersion"];
		
		//cannot be empty...
		if($currentPublishVersion == "") $currentPublishVersion = "1.0";

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	}	
	
	//app name...
	$appName = $objApp->infoArray["name"];


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
        
       	<div class='contentBox colorLightBg minHeight'>
           	
            <div class='contentBand colorBandBg'>
                Publish Changes <?php echo fnFormOutput($appName, true);?>
           	</div>

            <div class="cpExpandoBox" style='padding:10px;'>
				<b>Current Version</b>
            	<div style='padding-top:5px;'>
                	When your app runs the "reportToCloud" process it will look to see if you've made
                    any changes in the control panel since the last time it checked. If you have, the device will prompt the user to  
                    Refresh the app's content. You can use the Version value in any way you want. In most cases
                    app owners change the version value each time they publish changes.
                </div>
            	<table>
                	<tr>
                    	<td style="vertical-align:top;">
                		
                            <div style='padding-top:5px;'>
                                <b>Enter a Version</b> (numeric)<br/>
                                <input type="text" name="currentPublishVersion" id="currentPublishVersion" value="<?php echo fnFormOutput($currentPublishVersion);?>">
                            </div>
            
                            <div style='padding-top:5px;'>
                                <input type='button' title="publish" value="publish" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('mode');return false;">
                                <input type="button" value="cancel" class="buttonCancel" onclick="document.location.href='index.php?appGuid=<?php echo $appGuid;?>';return false;" />
                                <div id="submit_mode" class="submit_working">&nbsp;</div>
                            </div>
                            
                            <div style='padding-top:15px;'>
                            	<b>About Live or Design Mode</b>
                                <div style='padding-top:5px;'>
                                	
                                    Change this manually in Xcode or Eclipse.
                                    Set this to "Live" before you publish to the App Store or Market. 
                                    <hr/>
                                    
                                    <b>Design Mode:</b> The device will notice all changes saved in the control panel.
                                    <div style='padding-top:5px;'>
                                		<b>Live Mode:</b> The device will notice changes only after you click publish. 
                                	</div>
                                
                                </div>
                            </div>
                            
							
                        </td>
                    	
                        <td style="vertical-align:top;padding-left:40px;padding-top:10px;">
                        	<b>Config Data in Xcode or Eclipse</b>
                            <hr/>
            				<img src="../../images/current-mode.png" alt="Current Mode"/>
                		</td>
                     </tr>
                </table>
                
                
                
            	<div class="clear"></div>
            </div>  
            
         </div>       
    </fieldset>
        

<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
