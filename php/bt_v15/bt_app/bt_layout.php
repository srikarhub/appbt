<?php  require_once("../../config.php");

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
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";	

	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_layout_AJAX.php\";";
	
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$jsonVars = "";
	$appName = "";
	
	//for non-tabbed apps
	$homeScreenNickname = fnGetReqVal("homeScreenNickname", "", $myRequestVars);
	$homeScreenId = fnGetReqVal("homeScreenId", "", $myRequestVars);
	
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
	
	//app vars...		
	$appName = $objApp->infoArray["name"];
	
	//if the app does not use tabs, show it's homeScreen nickname...
	$strSql = " SELECT Count(id) ";
	$strSql .= "FROM " . TBL_BT_ITEMS;
	$strSql .= " WHERE appGuid = '" . $appGuid . "' AND itemType = 'BT_tab'";
	$tabCount = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	if($tabCount > 0){
		//OK..
	}else{

		//if we had a homeScreenNickname, find it's guid
		$strSql = "SELECT nickname FROM " . TBL_BT_ITEMS . " WHERE controlPanelItemType = 'screen' AND orderIndex = 0 AND appGuid = '" . $appGuid . "'";
		$homeScreenNickname = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
	}
	
	
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
            
            <div class="contentBand colorBandBg">
                	Layout Properties for <?php echo fnFormOutput($appName, true);?>
            </div>
            <div style='padding:10px;'>

                
                <div style='margin-bottom:5px;'>
                    <div style='margin-top:0px;'>
                        <b>Non-Tabbed layout</b>
                        are best for simple apps with minimal content.
                        Non-tabbed app's use a menu as the home-screen. Menu items can lead to deeper menu's to create
                        navigational depth.
                    </div>
                    <div style='padding-top:5px;'>
                        <b>Tabbed layouts</b> are best for apps that have lots of
                        content. Light, simple, less content-centric apps are usually better with a single home screen that
                        serves as the main-menu for the app. Items in the home screen menu often lead to other sub-menus
                        to create navigational depth.
                    </div>
                </div>
                
                <div>&nbsp;</div>
                
                <div class='cpExpandoBox colorLightBg'>
               		<a href='#' onClick="fnShowHide('box_appHomeScreen');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Non-Tabbed Layout</a>
                    	<div id="box_appHomeScreen" style="display:none;">
                    		<div style='padding-top:5px;'>
                        		<b>Home Screen Nickname</b>
                        		&nbsp;&nbsp;
                        		<img src="../../images/arr_right.gif" alt="arrow"/>
                        		<a href="<?php echo fnGetSecureURL(APP_URL);?>/bt_v15/bt_app/bt_pickerScreen.php?appGuid=<?php echo $appGuid;?>&formElVal=homeScreenId&formElLabel=homeScreenNickname&screenType=homeScreen" rel="shadowbox;height=550;width=950">Select</a>
                         		<br/>
                        		<input type="text" name="homeScreenNickname" id="homeScreenNickname" value="<?php echo fnFormOutput($homeScreenNickname);?>">
                        		<input type="hidden" name="homeScreenId" id="homeScreenId" value="<?php echo fnFormOutput($homeScreenId);?>">
                    		</div>
                    		<div style='padding-top:5px;'>
                        		Enter the nickname of the screen you want to use as your Home Screen. Leave this 
                        		blank if you are using a Tabbed Layout.
                    		</div>
                        	<div style="padding-top:10px;">
                        		<input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('home');return false;">
                        	<div id="submit_home" class="submit_working">&nbsp;</div>
                    	</div>
                	</div>
               </div>
           
    
               <div class='cpExpandoBox colorLightBg'>
                    <a href='#' onClick="fnShowHide('box_appTabs');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Tabbed Layout</a>
                    <div id="box_appTabs" style="display:none;">
                   
                   <?php 
                        /* show html for up to 5 tabs. Some may exist already, some may not, show
                            empty tabs for anything less than 5 configured tabs
                        */
                        
                        //this function builds the tab html..
                        function fnCreateTabHTML($appGuid, $tabIndex, $tabHomeScreenNickname, $tabLabel, $tabIconName, $tabSoundEffectName){
                            $r = "";
                            $r .= "\n\n<!-- tab " . $tabIndex . " -->";
                            $r .= "\n<div style='white-space:nowrap;'>";
                                $r .= "\n<div class='cpLeft'>";
                                    $r .= "\n<b>Tab " . $tabIndex . " Screen Nickname</b>";
                                    $r .= "\n&nbsp;&nbsp;";
                                    $r .= "\n<img src='../../images/arr_right.gif' alt='arrow'/>";
                                    $r .= "\n<a href='" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_pickerScreen.php?appGuid=" . $appGuid . "&formElVal=tab" . $tabIndex . "_homeScreenItemId&formElLabel=tab" . $tabIndex . "_homeScreenNickname&screenType=homeScreen' rel='shadowbox;height=550;width=950'>Select</a>";
									$r .= "\n<br/>";
                                    $r .= "\n<input type='text' name='tab" . $tabIndex. "_homeScreenNickname' id='tab" . $tabIndex . "_homeScreenNickname' value=\"" . fnFormOutput($tabHomeScreenNickname) . "\" onkeyup=\"document.forms[0].homeScreenNickname.value='';\">";
                                    $r .= "\n<input type='hidden' name='tab" . $tabIndex . "_homeScreenItemId' id='tab" . $tabIndex . "_homeScreenItemId' value=''>";
                                $r .= "\n</div>";
                                $r .= "\n<div class='cpLeft' style='width:175px;'>";
                                    $r .= "\n<b>Label</b>";
                                    $r .= "\n<br/>";
                                    $r .= "\n<input type='text' name='tab" . $tabIndex . "_label' id='tab" . $tabIndex . "_label' value=\"" . fnFormOutput($tabLabel) . "\" style='width:150px;'>";
                                $r .= "\n</div>";
                                $r .= "\n<div class='cpLeft' style='width:175px;'>";
                                    $r .= "\n<b>Icon</b>";
                                    $r .= "\n&nbsp;&nbsp;";
                                    $r .= "\n<img src='../../images/arr_right.gif' alt='arrow'/>";
                                    $r .= "<a href='" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_pickerFile.php?appGuid=" . $appGuid . "&formEl=tab" . $tabIndex . "_iconName&fileNameOrURL=fileName&searchFolder=/images' rel='shadowbox;height=550;width=950'>Select</a>";
									$r .= "\n<br/>";
                                    $r .= "\n<input type='text' name='tab" . $tabIndex . "_iconName' id='tab" . $tabIndex . "_iconName' value='" . fnFormOutput($tabIconName) . "' style='width:150px;'>";
                                    $r .= "\n<input type='hidden' name='tab" . $tabIndex . "_iconId' id='tab" . $tabIndex . "_iconId' value=''>";
                                $r .= "\n</div>";
                                $r .= "\n<div class='cpLeft' style='width:175px;'>";
                                    $r .= "\n<b>Sound Effect</b>";
                                    $r .= "\n&nbsp;&nbsp;";
                                    $r .= "\n<img src='../../images/arr_right.gif' alt='arrow'/>";
                                    $r .= "<a href='" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/bt_pickerFile.php?appGuid=" . $appGuid . "&formEl=tab" . $tabIndex . "_soundEffectName&fileNameOrURL=fileName&searchFolder=/audio' rel='shadowbox;height=550;width=950'>Select</a>";
                                    $r .= "\n<br/>";
                                    $r .= "\n<input type='text' name='tab" . $tabIndex . "_soundEffectName' id='tab" . $tabIndex . "_soundEffectName' value='" . fnFormOutput($tabSoundEffectName) . "' style='width:150px;'>";
                                    $r .= "\n<input type='hidden' name='tab" . $tabIndex . "_soundEffectId' id='tab" . $tabIndex . "_soundEffectId' value=''>";
                                $r .= "\n</div>";                
                                
                            
                            $r .= "</div>";
                            $r .= "<div style='clear:both;'></div>";
                            
                            //return
                            return $r;
                        
                        
                        }
                        
                        //vars for each tab
                        $tab_homeScreenItemId = "";
                        $tab_label = "";
                        $tab_iconName = "";
                        $tab_soundEffectName = "";
                        
                        $html = "";
                        $jsonVars = "";
						$maxNumberOfTabs = 5;
                        
                        $strSql = " SELECT I.guid, I.jsonVars ";
                        $strSql .= "FROM " . TBL_BT_ITEMS . " AS I ";
                        $strSql .= "WHERE I.appGuid = '" . $appGuid . "' AND I.itemType = 'BT_tab' ";
                        $strSql .= " ORDER BY id ASC ";
                        $strSql .= " LIMIT 0, " . $maxNumberOfTabs;
                        $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                        $numTabs = 0;
                        $cnt = 0;
                        if($res){
                            $numTabs = mysql_num_rows($res);
                            while($row = mysql_fetch_array($res)){
                                $cnt++;
								
								//json vars for this tab...
								$jsonVars = $row["jsonVars"];
	
								//possible json properties...
								$tab_label = "";
								$tab_iconName = "";
								$tab_homeScreenItemId = "";
								$tab_soundEffectName = "";
								$tab_homeScreenNickname = "";
										
                                //if we have json, we have a tab!
                                if($jsonVars != ""){
									
									//fill vars with the JSON values..
                                    $json = new Json; 
                                    $decoded = $json->unserialize($jsonVars);
                                    if(is_object($decoded)){
                                    
										if(array_key_exists("textLabel", $decoded)) $tab_label = $decoded->textLabel;
										if(array_key_exists("iconName", $decoded)) $tab_iconName = $decoded->iconName;
										if(array_key_exists("homeScreenItemId", $decoded)) $tab_homeScreenItemId = $decoded->homeScreenItemId;
										if(array_key_exists("soundEffectFileName", $decoded)) $tab_soundEffectName = $decoded->soundEffectFileName;
										
                                    }//if json object
                                    
                                }//if json vars
								
                                        
								//we need the nickname of the screen with this itemId..
								if($tab_homeScreenItemId != ""){
									//if we had a homeScreenNickname, find it's guid
									$strSql = "SELECT nickname FROM " . TBL_BT_ITEMS . " WHERE guid = '" . $tab_homeScreenItemId . "' AND appGuid = '" . $appGuid . "'";
									$tab_homeScreenNickname = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
								}
								if($tab_homeScreenNickname != ""){
									//create the html...
									$html .= fnCreateTabHTML($appGuid, $cnt, $tab_homeScreenNickname, $tab_label, $tab_iconName, $tab_soundEffectName);
								}else{
									$cnt = ($cnt - 1);
								}
                                        
								
								
                                
                            }//end while
                            
                            //done with existing tabs... do we need to create some blank ones?
                            $diff = $cnt + 1;
                            for($i = 0; $i < ($maxNumberOfTabs - $cnt); $i++){
                                $html .= fnCreateTabHTML($appGuid, ($i + $diff), "", "", "", "");
                            }
                            
                        }//res
                        
                        echo $html;
                        
                   
                   ?>
                   
                        <div style='padding-top:10px;'>
                            <b>To Remove a Tab</b>, clear the value in the Home Screen Nickname box, then Save.
                        </div>
                        
                        <div style='color:red;padding-top:5px;'>
                        	Tab home screens should be menu's or documents. Features like "Call Us" and "Email Us" are not screens 
                            (they are actions) and cannot be set as tab home screens.
                            Also, home-screens for tabs cannot be password protected. If you need to password protect
                            a tab's homescreen, create a menu to use for that tabs home then password protect the menu items.
                        </div>
                   
                        
                        <div style="padding-top:10px;">
                            <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('tabs');return false;">
                            <div id="submit_tabs" class="submit_working">&nbsp;</div>
                        </div>
                    
                  </div>  
               	</div>
            
            </div>
       
       </div> 
    </fieldset>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
