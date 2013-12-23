<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();

	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_configData.js";	
	
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);

	//app object...
	if($appGuid == ""){
	
		echo "invalid request";
		exit();
	
	}else{
		
		//app object...
		$objApp = new App($appGuid);
		$thisPage->pageTitle = $objApp->infoArray["name"] . " Control Panel";
		$appName = $objApp->infoArray["name"];

		//make sure user can manage this app...
		$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	}
	
	//for default URL's
	$apiKey = $objApp->infoArray["apiKey"];
	$appSecret = $objApp->infoArray["appSecret"];
	
	//default dataURL...
	$defaultDataURL = fnGetSecureURL(APP_URL) . "/api/app/?command=getAppData&appGuid=" . $appGuid . "&apiKey=" . $apiKey. "&apiSecret=" . $appSecret;		
	$defaultDataURL = str_replace("[buzztouchAppId]", $appGuid, $defaultDataURL);
	$defaultDataURL = str_replace("[buzztouchAPIKey]", $apiKey, $defaultDataURL);
	
	//live dataURL...
	$liveDataURL = $defaultDataURL . "&currentMode=live";
	
	//design dataURL...
	$designDataURL = $defaultDataURL . "&currentMode=design";
	
	
	//ouput config data...
	$configData = "loading...";
	
	//javascript onLoad function...
	$thisPage->customBody = "onload=\"startConfigDownload('" . $designDataURL . "');\"";
	
	//add the javascript URL variable inline in head section...
	$thisPage->jsInHead = "var getConfigDataURL = \"\";";

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

       		<div class='contentBox colorDarkBg' style='min-height:400px;'>
                <div class='contentBand colorBandBg'>
                	Configuration Data for <?php echo fnFormOutput($appName, true);?>
                </div>

                <div style='padding:10px;'>
                    
                    <div style='margin-bottom:5px;'>
                        <b>A centralized configuration file describes the applications layout and behavior.</b>
                        The configuration data is formatted in 
                        <a href="http://www.JSON.org/" target="_blank">JSON format</a> and must be included in your Xcode or Android project.
                        This is the data the application downloads when it "refreshes". 
                        The purpose of this screen is to allow you to see what the app's configuration data looks like. You can also
                        copy-n-paste it into a 
                        <a href="http://jsonlint.com/" target="_blank">JSON validation tool</a>
                        for testing to make sure it conforms to the JSON standard.
                    
                    	                     
                        <div style='padding-top:10px;'>
                            <a href='#' onclick="startConfigDownload('<?php echo $designDataURL;?>');return false;" title='Show Design Data'><img src='../../images/arr_right.gif' alt='arrow'/>Show Design Mode Data</a>
                            &nbsp;&nbsp;
                            |
                            &nbsp;&nbsp;
                            <a href='#' onclick="startConfigDownload('<?php echo $liveDataURL;?>');return false;" title='Show Design Data'>Live Mode Data</a>
                        </div>
                        

                    </div> 
                    
                    <div id="isLoading" class="cpExpandoBox" style="text-align:center;display:none;">
                        <h2 id="newAppName">Loading configuration data...</h2>
                        <img src="<?php echo APP_URL;?>/images/gif-loading.gif">
                    </div>
                    
                    <div id="doneLoading" style="display:none;">
                    	<div class='cpExpandoBox colorLightBg'>      
                        	<textarea id="configDataResults" name="configDataResults" style='width:100%;height:600px;font-family:monospace;font-size:10pt;border:0px;outline:none;'><?php echo $configData;?></textarea>
                    	</div>
                    </div>
                    
                </div>
        	
            </div>
        
        
    </fieldset>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>






     