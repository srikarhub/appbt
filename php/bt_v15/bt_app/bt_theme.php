<?php   require_once("../../config.php");

	//who's logged in? loggedInUserGuid will be an empty string if no user is logged in...
	$loggedInUserGuid = "";
	if(isset($_COOKIE[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_COOKIE[APP_LOGGEDIN_COOKIE_NAME]);
	if($loggedInUserGuid == ""){
		if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	}
		
	//init user object
	$thisUser = new User($loggedInUserGuid);
	$thisUser -> fnLoggedInReq($loggedInUserGuid);
	$thisUser -> fnUpdateLastRequest($loggedInUserGuid);

	//init page object
	$thisPage = new Page();
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_themes.js";
	
	
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$BT_itemId = fnGetReqVal("BT_itemId", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appName = "";
	$nickname = "";
	$jsonVars = "";
	
	//previous screen paging / filtering..
	$status = fnGetReqVal("status", "", $myRequestVars);
	$searchInput = fnGetReqVal("searchInput", "", $myRequestVars);
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
	$sortUpDown = fnGetReqVal("sortUpDown", "", $myRequestVars);
	$currentPage = fnGetReqVal("currentPage", "", $myRequestVars);
	
	//querystring for back-links on this page so previous page sorts / pages
	$qVars = "&status=" . $status . "&searchInput=" . fnFormOutput($searchInput);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	
	//app object...
	if($appGuid == ""){
	
		echo "invalid request";
		exit();
	
	}
		
	//app object...
	$objApp = new App($appGuid);
	$thisPage->pageTitle = $objApp->infoArray["name"] . " Control Panel";

	//make sure user can manage this app...
	$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);

	//app name...
	$appName = $objApp->infoArray["name"];
		
	/////////////////////////////////////////////////////////////
	//get BT_itemId for default theme if we did not select one...
	if($BT_itemId == ""){
	
			$strSql = "SELECT guid FROM " . TBL_BT_ITEMS;
			$strSql .= " WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND controlPanelItemType = 'theme' ";
			$strSql .= " AND orderIndex = '0' ";
			$strSql .= " LIMIT 0, 1 ";
			$BT_itemId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

	}	
	//End get BT_itemId for default theme...
	/////////////////////////////////////////////////////////////
		
		
	//theme object
	$objBT_item = new Bt_item($BT_itemId);
	$nickname = $objBT_item->infoArray["nickname"];
	$jsonVars = $objBT_item->infoArray["jsonVars"];

	//if this theme's json has a splashScreenItemId we need the name of the screen....	
	$splashScreenNickname = "";
	$splashScreenItemId = fnGetJsonProperyValue("splashScreenItemId", $jsonVars);
	if($splashScreenItemId != ""){
		$strSql = "SELECT nickname FROM " . TBL_BT_ITEMS . " WHERE guid = '" . $splashScreenItemId . "' AND appGuid = '" . $appGuid . "'";
		$splashScreenNickname = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	}
	
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>



<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_itemId" id="BT_itemId" value="<?php echo $BT_itemId;?>">
<input type="hidden" name="status" id="status" value="<?php echo $status;?>">
<input type="hidden" name="searchInput" id="searchInput" value="<?php echo fnFormOutput($searchInput);?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="advancedEdit" id="advancedEdit" value="">


<!--load the jQuery files using the Google API -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>


<div class='content'>
       
    <fieldset class='colorLightBg'>
           
        <!-- app control panel navigation--> 
        <div class='cpNav'>
            <span style='white-space:nowrap;'><a href="<?php echo fnGetSecureURL(APP_URL);?>/account/" title="Applications"><img src="../../images/arr_right.gif" alt="pointer" class="pointer"/>Applications</a></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
			<?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "inline", ""); ?>
        </div>
        
       	<div class='contentBox colorDarkBg' style='min-height:400px;'>
            
            <div class="contentBand colorBandBg">
                	Theme for <?php echo fnFormOutput($appName, true);?>
            </div>
             	
            <div style='padding:10px;'>
                <div style='margin-bottom:25px;'>
                   
                    <a href="bt_themes.php?appGuid=<?php echo $appGuid;?>" title="Global Themes"><img src="../../images/arr_right.gif" alt="pointer"/>Manage Multiple Themes</a>

                    <div style='padding-top:10px;'>
                       <b>Adjust the properties of this theme</b> to change the layout, look and style of this app.
						These properties are global and will apply to all the plugins in your app. However, not all plugins support all 
                        the theme options. It's up to the Plugin Developer to support, or not support these global theme values.
                    </div>

                    <div style='padding-top:10px;'>
                       <b>Additional style and layout properties</b> are available for each plugin. Plugin devlopers
                       determine these additional options. The options in this global theme will not apply if a) A 
                       plugin overrides the value. b) The plugin developer did not accomodate for the global setting.
                        
                    </div>
                    
                </div>
                    
                    
                	<div class='cpExpandoBox colorLightBg'>
                        <a href='#' onClick="fnExpandCollapse('box_nickname');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Theme Nickname</a>
                        <div id="box_nickname" style="display:none;">
                            <div style='padding-top:5px;'>
                                <b>Nickname</b><br/>
                                <input type="text" name="nickname" id="nickname" value="<?php echo fnFormOutput($nickname);?>">
                            	<div style='padding-top:5px;'>
                                    <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveAdvancedProperty('saveResult_nickname');return false;">
                                    <div id="saveResult_nickname" class="submit_working">&nbsp;</div>
                                </div>
                            </div>
                        </div>
                     </div>
                            
                	<div class='cpExpandoBox colorLightBg'>
                        <a href='#' onClick="fnExpandCollapse('box_splash');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Splash Screen</a>
                        <div id="box_splash" style="display:none;">
                            <div style='padding-top:5px;'>
    
                                <b>Splash Screen Nickname</b>
                                &nbsp;&nbsp;
                                <img src="../../images/arr_right.gif" alt="arrow"/>
                                <a href="bt_pickerScreen.php?appGuid=<?php echo $appGuid;?>&formElVal=splashScreenItemId&formElLabel=splashScreenNickname&screenType=splashScreen" rel="shadowbox;height=550;width=950">Select</a>
                                <br/>
                                <input type="text" name="splashScreenNickname" id="splashScreenNickname" value="<?php echo fnFormOutput($splashScreenNickname);?>">
                                <input type="hidden" name="splashScreenItemId" id="splashScreenItemId" value="<?php echo fnFormOutput($splashScreenItemId);?>">
                                
                                <br/>
                                To show a splash-screen when the app launches, enter the nickname of the splash-screen you already configured in the
                                <a href='bt_screens.php?appGuid=<?php echo $appGuid;?>'>Manage Screens and Features</a> list.
                                Leave this blank if this theme does not use a splash screen.
    
                            </div>
                            <div style='padding-top:5px;'>
                                <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveAdvancedProperty('saveResult_splash');return false;">
                                <div id="saveResult_splash" class="submit_working">&nbsp;</div>
                            </div>
                        </div>
                    </div>
                          
                	<div class='cpExpandoBox colorLightBg'>
                    <a href='#' onClick="fnExpandCollapse('box_navBar');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Navigation Bar / Status Bar</a>
                        <div id="box_navBar" style="display:none;padding-top:5px;">
                        
                            <div style='padding-top:5px;'>
                               
                               	<table cellspacing='0' cellpadding='0'>
                                	<tr>
                                    	<td style='vertical-align:top;'>
                                        
                                        
                                            <b>Nav. Bar Background Color</b>
                                            &nbsp;&nbsp;
                                            <img src="../../images/arr_right.gif" alt="arrow"/>
                                            <a href="bt_pickerColor.php?formElVal=json_navBarBackgroundColor" rel="shadowbox;height=550;width=950">Select</a>
                                            <br/>
                                            <input type="text" name="json_navBarBackgroundColor" id="json_navBarBackgroundColor" value="<?php echo fnFormOutput(fnGetJsonProperyValue("navBarBackgroundColor", $jsonVars));?>">
                                            
                                            <br/><b>Navigation Bar Style</b><br/>
                                            <select name="json_navBarStyle" id="json_navBarStyle" style='width:250px;'>
                                                <option value="solid" <?php echo fnGetSelectedString("solid", fnGetJsonProperyValue("navBarStyle", $jsonVars));?>>Solid</option>
                                                <option value="transparent" <?php echo fnGetSelectedString("transparent", fnGetJsonProperyValue("navBarStyle", $jsonVars));?>>Translucent</option>
                                                <option value="hidden" <?php echo fnGetSelectedString("hidden", fnGetJsonProperyValue("navBarStyle", $jsonVars));?>>Hide the navigation bar</option>
                                            </select>
                                            <br/>
                                        
                                        </td>
                                    	<td style='vertical-align:top;padding-left:10px;'>
                                            
                                            <b>Status Bar Style</b><br/>
                                            <select name="json_statusBarStyle" id="json_statusBarStyle" style='width:250px;'>
                                                <option value="default" <?php echo fnGetSelectedString("default", fnGetJsonProperyValue("statusBarStyle", $jsonVars));?>>Default</option>
                                                <option value="solid" <?php echo fnGetSelectedString("solid", fnGetJsonProperyValue("statusBarStyle", $jsonVars));?>>Solid Black</option>
                                                <option value="transparent" <?php echo fnGetSelectedString("transparent", fnGetJsonProperyValue("statusBarStyle", $jsonVars));?>>Transparent Black</option>
                                                <option value="hidden" <?php echo fnGetSelectedString("hidden", fnGetJsonProperyValue("statusBarStyle", $jsonVars));?>>Hide the status bar</option>
                                            </select>
                                            
                                            <br/><b>Bottom Tool Bar Style</b><br/>
                                            <select name="toolbarStyle" id="toolbarStyle" style='width:250px;'>
                                                <option value="solid" <?php echo fnGetSelectedString("solid", fnGetJsonProperyValue("toolbarStyle", $jsonVars));?>>Solid</option>
                                                <option value="transparent" <?php echo fnGetSelectedString("transparent", fnGetJsonProperyValue("toolbarStyle", $jsonVars));?>>Translucent</option>
                                            </select>
                    
                            			</td>
                                   	</tr>
                                </table>

                            
                            </div>
                            <div style='padding-top:5px;'>
                                <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveAdvancedProperty('saveResult_navigationBar');return false;">
                                <div id="saveResult_navigationBar" class="submit_working">&nbsp;</div>
                            </div>
                        </div>
                    </div>                  
         
         			
                    <div class='cpExpandoBox colorLightBg'>
                        <a href='#' onClick="fnExpandCollapse('box_backgroundColor');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Screen Background Color</a>
                        <div id="box_backgroundColor" style="display:none;">
                            
                           <table style='padding-top:15px;'>
                
                                <tr>
                                    <td style='vertical-align:top;padding-left:0px;'>
                                        Enter "clear" for a transparent color. Enter "stripes" for a native iOS background effect (not applicable
                                        on Android).
                                        All other colors should be entered in hex format, include the # character
                                        like: #FFCC66.
                                    </td>                
                                </tr>
                                <tr>
                                    <td style='vertical-align:top;padding-left:0px;padding-top:5px;'>
                                         
                                        <b>Color</b>
                                        &nbsp;&nbsp;
                                        <img src="../../images/arr_right.gif" alt="arrow"/>
                                        <a href="bt_pickerColor.php?formElVal=json_backgroundColor" rel="shadowbox;height=550;width=950">Select</a>
                                        <br/>
                                        <input type="text" name="json_backgroundColor" id="json_backgroundColor" value="<?php echo fnFormOutput(fnGetJsonProperyValue("backgroundColor", $jsonVars));?>">
                        
                                    </td>
                                </tr>
                            </table>
                
                            <div style='padding-top:5px;padding-left:0px;'>
                                <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveAdvancedProperty('saveResult_backgroundColor');return false;">
                                <div id="saveResult_backgroundColor" class="submit_working">&nbsp;</div>
                            </div>
                            
                        </div>
                     </div>
                     
                    <div class='cpExpandoBox colorLightBg'>
                        <a href='#' onClick="fnExpandCollapse('box_backgroundImage');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Screen Background Image</a>
                        <div id="box_backgroundImage" style="display:none;">
                                
                            <table style='padding-top:10px;'>
                                
                                <tr>
                                    <td colspan='3' style='vertical-align:top;padding-left:0px;'>
                                        Image File Names or Image URL (web address)?
                                        <hr>
                                        Use an Image File Name or an Image URL - NOT BOTH.
                                        If you choose to use an Image File Name (and not a URL), you'll need to add the image to
                                        the Xcode or Eclipse project after you download the code for your app. The Image File Name value you
                                        enter in the control panel must match the file name of the image in your
                                        project. Example: mybackground.png. Do not use image file names that contain spaces or special characters.
                                        <hr>
                                        If you use a URL (and not an Image File Name), the image will be downloaded from the URL then stored on the device
                                        for offline use. The Image URL should end with the name of the image file itself. 
                                        Example: www.mysite.com/images/mybackground.png.  You'll need to figure out whether or not
                                        it's best to include them in the project or use URL's, both approaches make sense, depending on your
                                        design goals.
                                    </td>                
                                </tr>
                                
                                
                                <tr>	
                                    <td class='tdSort' style='padding-left:0px;font-weight:bold;padding-top:10px;'>Small Device</td>
                                    <td class='tdSort' style='padding-left:25px;font-weight:bold;padding-top:10px;'>Large Device</td>
                                    <td class='tdSort' style='padding-left:25px;font-weight:bold;padding-top:10px;'>Extras</td>
                                </tr>
                                <tr>
                                    <td style='vertical-align:top;padding-left:0px;padding-top:15px;'>
                                    
                                       <b>Image File Name</b>
                                        &nbsp;&nbsp;
                                        <img src="../../images/arr_right.gif" alt="arrow"/>
                                        <a href="bt_pickerFile.php?appGuid=<?php echo $appGuid;?>&formEl=json_backgroundImageNameSmallDevice&fileNameOrURL=fileName&searchFolder=/images" rel="shadowbox;height=550;width=950">Select</a>
                                        <br/>
                                        <input type="text" name="json_backgroundImageNameSmallDevice" id="json_backgroundImageNameSmallDevice" value="<?php echo fnFormOutput(fnGetJsonProperyValue("backgroundImageNameSmallDevice", $jsonVars));?>">
                                        
                                        <br/><b>Image URL</b>
                                        &nbsp;&nbsp;
                                        <img src="../../images/arr_right.gif" alt="arrow"/>
                                        <a href="bt_pickerFile.php?appGuid=<?php echo $appGuid;?>&formEl=json_backgroundImageURLSmallDevice&fileNameOrURL=URL&searchFolder=/images" rel="shadowbox;height=550;width=950">Select</a>
                                        <br/>
                                        <input type="text" name="json_backgroundImageURLSmallDevice" id="json_backgroundImageURLSmallDevice" value="<?php echo fnFormOutput(fnGetJsonProperyValue("backgroundImageURLSmallDevice", $jsonVars));?>">
                                    </td>
                                    <td style='vertical-align:top;padding-left:25px;padding-top:15px;'>
                                       
                                       <b>Image File Name</b>
                                        &nbsp;&nbsp;
                                        <img src="../../images/arr_right.gif" alt="arrow"/>
                                        <a href="bt_pickerFile.php?appGuid=<?php echo $appGuid;?>&formEl=json_backgroundImageNameLargeDevice&fileNameOrURL=fileName&searchFolder=/images" rel="shadowbox;height=550;width=950">Select</a>
                                        <br/>
                                        <input type="text" name="json_backgroundImageNameLargeDevice" id="json_backgroundImageNameLargeDevice" value="<?php echo fnFormOutput(fnGetJsonProperyValue("backgroundImageNameLargeDevice", $jsonVars));?>">
                                        
                                        <br/><b>Image URL</b>
                                        &nbsp;&nbsp;
                                        <img src="../../images/arr_right.gif" alt="arrow"/>
                                        <a href="bt_pickerFile.php?appGuid=<?php echo $appGuid;?>&formEl=json_backgroundImageURLLargeDevice&fileNameOrURL=URL&searchFolder=/images" rel="shadowbox;height=550;width=950">Select</a>
                                        <br/>
                                        <input type="text" name="json_backgroundImageURLLargeDevice" id="json_backgroundImageURLLargeDevice" value="<?php echo fnFormOutput(fnGetJsonProperyValue("backgroundImageURLLargeDevice", $jsonVars));?>">
                                     </td>
                                    <td style='vertical-align:top;padding-left:25px;padding-top:15px;'>
                                        
                                        <b>Scale / Position</b><br/>
                                        <select name="json_backgroundImageScale" id="json_backgroundImageScale" style="width:150px;">
                                                <option value="" <?php echo fnGetSelectedString("", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>--select--</option>
                                                <option value="center" <?php echo fnGetSelectedString("center", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>center</option>
                                                <option value="fullScreen" <?php echo fnGetSelectedString("fullScreen", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Full Screen</option>
                                                <option value="fullScreenPreserve" <?php echo fnGetSelectedString("fullScreenPreserve", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Full Screen, Preserve Ratio</option>
                                                <option value="top" <?php echo fnGetSelectedString("top", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Top Middle</option>
                                                <option value="bottom" <?php echo fnGetSelectedString("bottom", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Bottom Middle</option>
                                                <option value="topLeft" <?php echo fnGetSelectedString("topLeft", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Top Left</option>
                                                <option value="topRight" <?php echo fnGetSelectedString("topRight", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Top Right</option>
                                                <option value="bottomLeft" <?php echo fnGetSelectedString("bottomLeft", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Bottom Left</option>
                                                <option value="bottomRight" <?php echo fnGetSelectedString("bottomRight", fnGetJsonProperyValue("backgroundImageScale", $jsonVars));?>>Bottom Right</option>
                                        </select>
                                    </td>
                                      
                                </tr>
                            </table>
                                
                            <div style='padding-top:5px;padding-left:0px;'>
                                <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveAdvancedProperty('saveResult_backgroundImage');return false;">
                                <div id="saveResult_backgroundImage" class="submit_working">&nbsp;</div>
                            </div>
                            
                        </div>
                      </div>

                    
                    <div class='cpExpandoBox colorLightBg'>
                        <a href='#' onClick="fnExpandCollapse('box_advanced');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Advanced, Manually Edit JSON</a>
                        <div id="box_advanced" style="display:none;">
                                

							<div style='padding-top:5px;'>
                            	<b>Do not change the values in this box unless you know what you're doing</b>
                            </div>

							<div style='padding-top:5px;'>
                            	<b>Manually editing</b> this data allows you to include arbitrary properties in the global theme.
                                This could be used for many purposes and plugin developers can take advantage of 
                                this idea in many ways. In most cases you won't be entering any of these values without first
                                understanding how a plugin will use them.
                            </div>
                            
                            <div style='padding-top:5px;'>
                                <textarea name="advanced" id="advanced" class="large" style="font-family:monospace;width:100%;height:200px;"><?php echo fnFormOutput($jsonVars);?></textarea>
                            </div>
                                
                            <div style='padding-top:5px;padding-left:0px;'>
                                <input type='button' title="save" value="save" align='absmiddle' class="buttonSubmit" onClick="saveAdvancedProperty('saveResult_advanced');return false;">
                                <div id="saveResult_advanced" class="submit_working">&nbsp;</div>
                            </div>
                            
                        </div>
                      </div>
                    
                    
                    
                    
                </div>
                
                <div style="height:100px;"></div>
    	
        
       </div> 
    </fieldset>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
