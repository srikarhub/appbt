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
	
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$directoryMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appParentAppGuid = fnGetReqVal("appParentAppGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$bolDeleted = false;
	$appStatus = "";
	$appName = "";
	$dataDir = "";
	$dateStampUTC = "";
	$currentPublishDate = "";
	$p = "";

	$viewCount = "";
	$iconUrl = "";
	$currentPublishVersion = "";
	$iconUrl = "";
	$info = "";
	$screenCount = "";
	
	
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
	
	//if deleting an entire app
	if(isset($_GET['command']) && $appGuid != ""){
		if($_GET['command'] == "confirmDelete"){
			$deleteAppGuid = $appGuid;
			if($deleteAppGuid != ""){
			
				//Some records are removed, some are marked for delete. This allows for some database auditing...
			
				//mark app as deleted...(clear icon values because we will be removing the app's data directory)
				$tmpDel = "UPDATE " . TBL_APPLICATIONS . " SET status = 'deleted', modifiedUTC = '" . $dtNow . "', ";
				$tmpDel .= "iconUrl = '', iconName = '' ";
				$tmpDel .= " WHERE guid = '" . $deleteAppGuid . "'";
				fnExecuteNonQuery($tmpDel, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				
				//remove app objects...
				$tmpDel = "UPDATE " . TBL_BT_ITEMS . " SET status = 'deleted', modifiedUTC = '" . $dtNow . "' ";
				$tmpDel .= "WHERE appGuid = '" . $deleteAppGuid . "'";
				fnExecuteNonQuery($tmpDel, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				
				//remove app files...
				$tmpDel = "UPDATE " . TBL_BT_FILES . " SET status = 'deleted', modifiedUTC = '" . $dtNow . "' ";
				$tmpDel .= "WHERE appGuid = '" . $deleteAppGuid . "'";
				fnExecuteNonQuery($tmpDel, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

				//remove app users...
				$tmpDel = "UPDATE " . TBL_APP_USERS . " SET status = 'deleted', modifiedUTC = '" . $dtNow . "' ";
				$tmpDel .= "WHERE appGuid = '" . $deleteAppGuid . "'";
				fnExecuteNonQuery($tmpDel, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

				//remove api key...
				$tmpApiKey = $objApp->infoArray["apiKey"];
				if($tmpApiKey != ""){
					$tmpDel = "UPDATE " . TBL_API_KEYS . " SET status = 'deleted', modifiedUTC = '" . $dtNow . "' ";
					$tmpDel .= "WHERE apiKey = '" . $tmpApiKey . "'";
					fnExecuteNonQuery($tmpDel, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				}
				
				//remove entire directory structure.
				if($objApp->infoArray["dataDir"] != ""){
					if(is_dir(APP_PHYSICAL_PATH . $objApp->infoArray["dataDir"])){
						fnRemoveDirectory(APP_PHYSICAL_PATH . $objApp->infoArray["dataDir"]);
					}
				}				
				//flag
				$bolDeleted = true;
				
			}
		}//confirm delete	
	}//if deleting

	//need an appguid
	if(!$bolDeleted){
		
		$appName = $objApp->infoArray["name"];
		$dataDir = $objApp->infoArray["dataDir"];
		$appStatus = $objApp->infoArray["status"];
		$currentPublishVersion = $objApp->infoArray["currentPublishVersion"];
		if($currentPublishVersion == "") $currentPublishVersion = "1.0";
		
		$dateStampUTC = $objApp->infoArray["dateStampUTC"];
			if($dateStampUTC != "") $dateStampUTC = fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
			
		$modifiedUTC = $objApp->infoArray["modifiedUTC"];
			if($modifiedUTC != "") $modifiedUTC = fnFromUTC($modifiedUTC, $thisUser->infoArray["timeZone"], "m/d/Y h:i A");

		$currentPublishDate = $objApp->infoArray["currentPublishDate"];
			if($currentPublishDate != "" && $currentPublishDate != "0000-00-00 00:00:00"){
				$currentPublishDate = fnFromUTC($currentPublishDate, $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
			}else{
				$currentPublishDate = $modifiedUTC;
			}
		
		
		$viewCount = $objApp->infoArray["viewCount"];
		$iconUrl = $objApp->infoArray["iconUrl"];
		
		//default icon...
		if($iconUrl == ""){
			$iconUrl = fnGetSecureURL(APP_URL) . "/images/default_app_icon.png";
		}
		
		//icon URL may need to be secure...
		$iconUrl = fnGetSecureURL($iconUrl);
		
		//if appApi key is blank, create a new one
		$appApiKey = $objApp->infoArray["apiKey"];
		if($appApiKey == ""){
			$tmp = "UPDATE " . TBL_APPLICATIONS . " SET apiKey = '" . strtoupper(fnCreateGuid()) . "' WHERE guid = '" . $appGuid . "'";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
		
		//make sure this app has writeable directories...
		$appDirectoryPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/applications/" . $appGuid;
		$folders = array("config", "documents", "images", "audio", "video", "phpscripts", "archives", "source-ios", "source-android", "install-ios", "install-android");
		
		//create this app's individual directory structure if it does not exist...
		if(!is_dir($appDirectoryPath)){
			if(is_writable(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/applications")){
				mkdir($appDirectoryPath);
				chmod($appDirectoryPath, 0755);
			}
		}
			
		//sub-directories for this app...
		if(is_writable($appDirectoryPath)){
			for($x = 0; $x < count($folders); $x++){
				$thisFolder = $appDirectoryPath . "/" . $folders[$x];
				if(!is_dir($thisFolder)){
					mkdir($thisFolder);
					chmod($thisFolder, 0755);
				}
			}//end for
		}
						
		//we have created new directories if they did not exist. Re-check that they are 
		//writeable and show a message if any of them are missing or not-writeable...
		if(!is_dir(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/applications")){
			$directoryMessage .= "<br>The application's data directory on the file system does not exist?";
		}else{
			if(!is_writable(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/applications")){
				$directoryMessage .= "<br>The application's data directory on the file system is not writeable?";
			}
		}
		
		//verify this app's individual directory exists and is writeable...
		if(!is_dir($appDirectoryPath)){
			$directoryMessage .= "<br>This application does not have a data directory on the file system?";
		}else{
			if(!is_writable($appDirectoryPath)){
			$directoryMessage .= "<br>This application's data directory on the file system is not writeable?";
			}
		}		
		
		//very each sub-directory for this application...
		if($directoryMessage == ""){
			for($x = 0; $x < count($folders); $x++){
				$thisFolder = $appDirectoryPath . "/" . $folders[$x];
				if(!is_dir($thisFolder)){
					$directoryMessage .= "<br>This application's \"" . $folders[$x] . "\" directory on the file system does not exist?";
				}else{
					if(!is_writable($thisFolder)){
						$directoryMessage .= "<br>This application's \"" . $folders[$x] . "\" directory on the file system is not writeable?";
					}
				}
			}//end for
		}//directoryMessage = ""

	
		//figure out how many plugins this app is using...
		$screenCount = 0;
		$strSql = "SELECT Count(id) FROM " . TBL_BT_ITEMS;
		$strSql .= " WHERE appGuid = '" . $appGuid . "'";
		$strSql .= " AND controlPanelItemType = 'screen' ";
		$strSql .= " AND status != 'deleted' ";
		$screenCount = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	
	}//bolDeleted
	

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<div class='content'>
        
    <fieldset class='colorLightBg'>

            <!-- left side--> 
            <div class='boxLeft'>
                <div class='contentBox colorDarkBg minHeight' style='min-height:500px;'>
                    <div class='contentBand colorBandBg'>
                        Application Options
                    </div>
                    <div id="leftNavLinkBox" style='padding:10px;padding-bottom:25px;white-space:nowrap;'>
                        
                        <div><a href="<?php echo fnGetSecureURL(APP_URL);?>/account/" title="My Account"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow'/>My Account</a></div>
                        <?php if(strtoupper($thisUser->infoArray["userType"]) == "ADMIN"){ ?>
                            <div><a href="<?php echo fnGetSecureURL(APP_URL);?>/admin/" title="Back to Applications"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow'/>Back to Applications</a></div>
                        <?php } ?>
                        
                        <div><hr></div>
                        
                        <?php if(!$bolDeleted){?>
                            <?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "block", ""); ?>
                    
                            <div><hr></div>
                            <div><a href='bt_usageMap.php?appGuid=<?php echo $appGuid;?>' title='Usage Map'><img src='../../images/arr_right.gif' alt='arrow'/>Usage Map</a></div>
                            <div><a href='bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>' title='Push Notifications'><img src='../../images/arr_right.gif' alt='arrow'/>Push Notifications</a></div>
                            <div><a href='bt_appPackage.php?appGuid=<?php echo $appGuid;?>' title='Prepare Project Download'><img src='../../images/arr_right.gif' alt='arrow'/>Prepare Project Download</a></div>
                            <div><a href='bt_overTheAir.php?appGuid=<?php echo $appGuid;?>' title='Over the Air Distribution'><img src='../../images/arr_right.gif' alt='arrow'/>Over the Air Distribution</a></div>
                            <div><a href='bt_archives.php?appGuid=<?php echo $appGuid;?>' title='Application Archives'><img src='../../images/arr_right.gif' alt='arrow'/>Application Archives</a></div>
                            <div><a href="index.php?appGuid=<?php echo $appGuid;?>&command=delete" title="Permanently Delete App"><img src="../../images/arr_right.gif" alt="arrow"/>Permanently Delete App</a></div>
                        
                        <?php } ?>
                        
                    </div>
                 </div>
            </div>
                    
            <div class='boxRight'>
                
                <div class='contentBox colorLightBg minHeight' style='min-height:500px;'>
                    
                    <div class='contentBand colorBandBg'>
                      	<?php if(!$bolDeleted){?>
                       		<?php echo fnFormOutput($objApp->infoArray["name"]);?> Control Panel
                        <?php } else { ?>
                        	App Deleted
                        <?php } ?>
                    </div>
                        		
                   	<div style='padding:10px;'>

						<?php if($bolDeleted){?>
                            <div class='doneDiv'>
                                <b>Application Deleted</b>
                            </div>
                        <?php } ?>
                            
                            
						<?php if(!$bolDeleted){?>
						
                            <?php if(strtoupper($command) == "DELETE"){ ?>
                                <div class="errorDiv">
                                    <br/><b>Are you sure you want to delete this application?</b>
									<div style='padding-top:5px;'>
										<b><?php echo fnFormOutput($appName);?></b> will be removed along with any and all
                                        data, files, and resources associated with it. This cannot be undone! When you
                                        confirm this operation, all information and content associated with this app will 
                                        be permanently removed and you will not be able to get it back - ever. 
                                    </div>
                                    <div style='padding-top:10px;'>
                                        <a href="index.php?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this application</a>
                                    </div>
                                    <div style='padding-top:10px;'>
                                        <a href="index.php?appGuid=<?php echo $appGuid;?>&command=confirmDelete"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this application</a>
                                    </div>
                                </div>
                            <?php } ?>                                    
                            
                            <?php if(strtoupper($command) != "DELETE"){ ?>
                            
                                <?php if($directoryMessage != ""){ ?>
                                    <div class='errorDiv'>
                                    	<?php echo $directoryMessage;?>
                                	</div>
                                <?php } ?>
                                    
                                <div class='appDiv colorDarkBg'>
                                
                                    <table class='appTable' cellspacing='0' cellpadding='0'>
                                        <tr>
                                            <td style='vertical-align:top;padding:0px;'>
                                                <div class='iconOuter'>
                                                    <div id="iconImage" class="iconImage" style="background:url('<?php echo $iconUrl;?>');background-position:50% 50%;background-repeat:no-repeat;">
                                                        <img src='<?php echo fnGetSecureURL(APP_URL) . "/images/blank.gif";?>' alt="app icon" />
                                                    </div>
                                                    <div id="iconOverlay" class="iconOverlay">
                                                        &nbsp;
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="vertical-align:top;padding:5px;padding-left:10px;padding-top:0px;">
                                                <b><?php echo fnFormOutput($appName);?></b>
                                                <br/>created: <?php echo $dateStampUTC;?> 
                                                <br/>modified: <?php echo $modifiedUTC;?> 
                                                <br/>published: <?php echo $currentPublishDate;?> 
                                                <br/>vers: <?php echo $currentPublishVersion;?> views: <?php echo $viewCount;?>
                                            </td>
                                        </tr>
                                    </table> 
                                </div> 
                                                                
                                <img src="../../images/cp_home.png" alt="sample" style='float:right;margin-left:15px;margin-top:0px;'/>
                                
                                <div style='padding-top:10px;margin-top:100px;'>
                                    <b>Themes, Tabs, Screens and Actions</b> control the applications look, feel, functionality and behavior.
                                    Every screen and action is an individual Plugin. Available plugins are managed by
                                    system administrators using the admin panel.
                                </div>
                                
                              	<div style='padding-top:10px;'>
                                    <b>Every plugin</b> has it's own unique characteristics. Some plugins are simple, some more complex.
                                    It's best to experiment with some simple plugins until you get comfortable with the process.
                                </div>
                                
                                <div style='padding-top:10px;'>
                                    <b>It's best to design and build the app with a simulator or device running.</b> This allows you to see how changes
                                    you make in the control panel affect the application running on the device.
                                </div>
                                    
                                <div style='padding-top:10px;'>
                                   	<b>Uploading Files and Media</b> to the control panel allows you to keep all the file and media associated
                                    with this app organized in a centralized location. 
                                </div>

								<div class="clear"></div>

                                <div class="cpExpandoBox" style='margin-top:10px;margin-left:0px;'>
                                   	<b>About Version, Modified and Published</b> 
                                    <div style='padding-top:5px;'>
                                    	If the Modified and Published values don't match, a red dot will show next to the
                                        <a href='bt_appVersion.php?appGuid=<?php echo $appGuid;?>' title='Publish Changes'>publish changes</a>
                                        option. Update the version when you submit updates to the App Store or Market.
                                    </div>                                   
                                    
                                </div>

 
                            
                            <?php } ?>
                                    
                        
                        
                         <?php } //bolDeleted ?>
                             
                    </div>
                    
                    
                    
            	</div>
            </div>                      
    
    </fieldset>


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
