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


	//plugins table constant is different on self hosted servers..
	if(!defined("TBL_PLUGINS")){
		if(defined("TBL_BT_PLUGINS")){
			define("TBL_PLUGINS", TBL_BT_PLUGINS);
		}
	}
	
	//init page object
	$thisPage = new Page();
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_menus.js";
	
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
	
	///////////////////////////////////////////////////////////////////////////////
	//childItem logic...
	$BT_childItemId = fnGetReqVal("BT_childItemId", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$scriptName = "bt_menu.php";
	$dtNow = fnMySqlNow();
	$bolDeleted = false;
	$bolDone = false;
	$bolPassed = true;
	$strMessage = "";

	//child item properties...
	$addMenuItemText = fnGetReqVal("addMenuItemText", "", $myRequestVars);
	$addScreenNickname = fnGetReqVal("addScreenNickname", "", $myRequestVars);
	$addPluginUniqueId = fnGetReqVal("addPluginUniqueId", "", $myRequestVars);
	$addLoadScreenWithNickname = fnGetReqVal("addLoadScreenWithNickname", "", $myRequestVars);

	//for creating new child items...
	$newMenuItemGuid = strtoupper(fnCreateGuid());
	$newScreenItemGuid = strtoupper(fnCreateGuid());	
	
	
	//if deleting a child item
	if($appGuid != "" && $BT_itemId != "" && $BT_childItemId != "" && $command == "confirmDelete"){
		
		$strSql = "DELETE FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' ";
		$strSql .= " AND guid = '" . $BT_childItemId . "' AND itemType = 'BT_menuItem' ";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		$bolDeleted = TRUE;
	
		//update the app's modified date...
		$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

	}//if deleting
	
	//if re-ordering the childItems 
	if($appGuid != "" && $BT_itemId != "" && strtoupper($command) == "UPDATEORDERINDEX"){
		
		//loop each "order_[guid]" element..
		foreach($_POST as $key => $val){
			if(substr($key, 0, 6) == "order_"){
				$id = str_replace("order_", "", $key);
				$val = fnFormInput($val);
				if(!is_numeric($val)) $val = "0";
				$strSql = "UPDATE " . TBL_BT_ITEMS . " SET orderIndex = '" . $val . "' WHERE id = '" . $id . "' AND itemType = 'BT_menuItem' ";
				fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				$bolDone = true;
				$strMessage = "orderUpdated";
			}
		}
		
		//update the app's modified date...
		$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
					
	}//if reordering...

	//if adding a new child items
	if(strtoupper($command) == "ADDITEM"){

		//validate
		if(strlen($addMenuItemText) < 1){
			$bolPassed = false;
			$strMessage .= "<br>Menu Item title required.";
		}
		if(strlen($addScreenNickname) < 1){
			$bolPassed = false;
			$strMessage .= "<br>Existing nickname or a new nickname required.";
		}
		
		
		//loop each "order_[guid]" element to find the order index of the next child item...
		$nextOrderIndex = 1;
		foreach($_POST as $key => $val){
			if(substr($key, 0, 6) == "order_"){
				$nextOrderIndex ++;
			}
		}	
		if($nextOrderIndex == 0) $nextOrderIndex = 1;		
		
		//new screen or existing screen?
		$existingScreenGuid = "";
		if($bolPassed){
			if(strlen($addScreenNickname) > 1 && strlen($addPluginUniqueId) < 1){
				
				//selected an existing screen to connect to...
				$tmp = "SELECT guid FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $addScreenNickname . "'";
				$tmp .= " AND appGuid = '" . $appGuid . "'";
				$tmp .= " AND controlPanelItemType = 'screen' ";
				$tmp .= " LIMIT 0, 1";
            	$existingScreenGuid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

				if(strlen($existingScreenGuid) < 2){
					$bolPassed = false;
					$strMessage .= "<br>No screens were found with the nickname you entered. Enter a nickname for an existing screen, or, choose ";
					$strMessage .= " something from the list to create a new screen. ";
				}
				
				//good so far...
				if($bolPassed){
					
					//get details about the screen we are connecting to this menu item....
					$objScreenItem = new Bt_item($existingScreenGuid);

					//new child item's jsonVars
					$menuItemJsonVars = "{";
					$menuItemJsonVars . "\"itemId\":\"" . $newMenuItemGuid . "\", ";
					$menuItemJsonVars .= "\"itemType\":\"BT_menuItem\", ";
					$menuItemJsonVars .= "\"titleText\":\"" . $addMenuItemText . "\", ";
					$menuItemJsonVars .= "\"loadScreenWithItemId\":\"" . $objScreenItem->infoArray["guid"] . "\"";
					$menuItemJsonVars .= "}";
	
					//create a new BT_item for the menu item...
					$objChildItem = new Bt_item($existingScreenGuid);
					$objChildItem->infoArray["guid"] = $newMenuItemGuid;
					$objChildItem->infoArray["parentItemGuid"] = $BT_itemId;;
					$objChildItem->infoArray["uniquePluginId"] = "";
					$objChildItem->infoArray["loadClassOrActionName"] = $objScreenItem->infoArray["itemType"];
					$objChildItem->infoArray["hasChildItems"] = "0";
					$objChildItem->infoArray["loadItemGuid"] = $objScreenItem->infoArray["guid"];
					$objChildItem->infoArray["appGuid"] = $appGuid;
					$objChildItem->infoArray["controlPanelItemType"] = "menuItem";
					$objChildItem->infoArray["itemType"] = "BT_menuItem";
					$objChildItem->infoArray["itemTypeLabel"] = "Menu Item";
					$objChildItem->infoArray["nickname"] =  $addMenuItemText;
					$objChildItem->infoArray["orderIndex"] = $nextOrderIndex;
					$objChildItem->infoArray["jsonVars"] = $menuItemJsonVars;
					$objChildItem->infoArray["status"] = "active";
					$objChildItem->infoArray["dateStampUTC"] = $dtNow;
					$objChildItem->infoArray["modifiedUTC"] = $dtNow;
					$objChildItem->fnInsert();
					
					//flag
					$bolDone = true;
					$strMessage = "done";
					
					//clear form
					$addMenuItemText = "";
					$addScreenNickname = "";
					$addPluginUniqueId = "";
					$addLoadScreenWithNickname = "";

					//update the app's modified date...
					$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
					fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				
				}//bolPassed

			}else{
		
				//append a count to the nickname if a screen already exists with this nickname...
				$tmp = "SELECT Count(id) FROM " . TBL_BT_ITEMS;
				$tmp .= " WHERE appGuid = '" . $appGuid . "'";
				$tmp .= " AND uniquePluginId = '" . $addPluginUniqueId . "' ";
				$tmp .= " AND nickname = '" . $addScreenNickname . "' ";
				$existingCount = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($existingCount != "" && $existingCount != "0" && $existingCount != 0){
					$addScreenNickname = $addScreenNickname . " (" . ($existingCount + 1) . ")";
				}
				
				//get info about the plugin we are adding....			
				$tmpSql = "SELECT category, displayAs, loadClassOrActionName, hasChildItems, defaultJsonVars, webDirectoryName ";
				$tmpSql .= " FROM " . TBL_PLUGINS . " WHERE uniquePluginId = '" . $addPluginUniqueId . "' LIMIT 0, 1";
				$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($res){
					$row = mysql_fetch_array($res);
					$category = $row["category"];
					$displayAs = $row["displayAs"];
					$loadClassOrActionName = $row["loadClassOrActionName"];
					$hasChildItems = $row["hasChildItems"];
					$defaultJsonVars = $row["defaultJsonVars"];
					$webDirectoryName = $row["webDirectoryName"];
				}
			
				//replace possible "nickname" in defaultJsonVars...
				$defaultJsonVars = str_replace("[itemNickname]", $addScreenNickname, $defaultJsonVars);
				$defaultJsonVars = str_replace("[replaceNickname]", $addScreenNickname, $defaultJsonVars);
				$defaultJsonVars = str_replace("[nickname]", $addScreenNickname, $defaultJsonVars);
				
				//validate...
				if(strlen($category) < 1){
					$bolPassed = false;
					$strMessage .= "<br>The plugin does not contain a category";
				}
				if(strlen($displayAs) < 1){
					$bolPassed = false;
					$strMessage .= "<br>The plugin does not contain a displayAs value";
				}
				if(strlen($loadClassOrActionName) < 1){
					$bolPassed = false;
					$strMessage .= "<br>The plugin does not contain a loadClassOrActionName value";
				}
				if(strlen($defaultJsonVars) < 1){
					$bolPassed = false;
					$strMessage .= "<br>The plugin does not contain any defaultJsonVars values";
				}
					
				if($bolPassed){
				
					//create new BT_item for the screen.
					$objNewScreenItem = new Bt_item();
					$objNewScreenItem -> infoArray["guid"] = fnFormInput($newScreenItemGuid);
					$objNewScreenItem -> infoArray["parentItemGuid"] = "";
					$objNewScreenItem -> infoArray["uniquePluginId"] = fnFormInput($addPluginUniqueId);
					$objNewScreenItem -> infoArray["loadClassOrActionName"] = fnFormInput($loadClassOrActionName);
					$objNewScreenItem -> infoArray["hasChildItems"] = fnFormInput($hasChildItems);
					$objNewScreenItem -> infoArray["loadItemGuid"] = "";
					$objNewScreenItem -> infoArray["appGuid"] = fnFormInput($appGuid);
					$objNewScreenItem -> infoArray["controlPanelItemType"] = "screen";
					$objNewScreenItem -> infoArray["itemType"] = $loadClassOrActionName;
					$objNewScreenItem -> infoArray["itemTypeLabel"] = fnFormInput($displayAs);
					$objNewScreenItem -> infoArray["nickname"] = fnFormInput($addScreenNickname);
					$objNewScreenItem -> infoArray["orderIndex"] = "99";
					$objNewScreenItem -> infoArray["jsonVars"] = $defaultJsonVars;
					$objNewScreenItem -> infoArray["status"] = "active";
					$objNewScreenItem -> infoArray["dateStampUTC"] = $dtNow;
					$objNewScreenItem -> infoArray["modifiedUTC"] = $dtNow;
					$objNewScreenItem -> fnInsert();
				
					//new menu item's jsonVars
					$menuItemJsonVars = "{";
					$menuItemJsonVars .= "\"itemId\":\"" . $newMenuItemGuid . "\", ";
					$menuItemJsonVars .= "\"itemType\":\"BT_menuItem\", ";
					$menuItemJsonVars .= "\"titleText\":\"" . $addMenuItemText . "\"";
					$menuItemJsonVars .= "\"loadScreenWithItemId\":\"" . $newScreenItemGuid . "\"";
					$menuItemJsonVars .= "}";


					//create a new BT_item for the menu item...
					$objChildItem = new Bt_item($existingScreenGuid);
					$objChildItem->infoArray["guid"] = $newMenuItemGuid;
					$objChildItem->infoArray["parentItemGuid"] = $BT_itemId;;
					$objChildItem->infoArray["uniquePluginId"] = "";
					$objChildItem->infoArray["loadClassOrActionName"] = $loadClassOrActionName;
					$objChildItem->infoArray["hasChildItems"] = "0";
					$objChildItem->infoArray["loadItemGuid"] = $newScreenItemGuid;
					$objChildItem->infoArray["appGuid"] = $appGuid;
					$objChildItem->infoArray["controlPanelItemType"] = "menuItem";
					$objChildItem->infoArray["itemType"] = "BT_menuItem";
					$objChildItem->infoArray["itemTypeLabel"] = "Menu Item";
					$objChildItem->infoArray["nickname"] =  $addMenuItemText;
					$objChildItem->infoArray["orderIndex"] = $nextOrderIndex;
					$objChildItem->infoArray["jsonVars"] = $menuItemJsonVars;
					$objChildItem->infoArray["status"] = "active";
					$objChildItem->infoArray["dateStampUTC"] = $dtNow;
					$objChildItem->infoArray["modifiedUTC"] = $dtNow;
					$objChildItem->fnInsert();
					
					//flag
					$bolDone = true;
					$strMessage = "done";
					
					//clear form
					$addMenuItemText = "";
					$addScreenNickname = "";
					$addPluginUniqueId = "";
					$addLoadScreenWithNickname = "";

					//update the app's modified date...
					$strSql = " UPDATE " . TBL_APPLICATIONS . " SET modifiedUTC = '" . $dtNow . "' WHERE guid = '" . $appGuid . "'";
					fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				
				
				
				}else{
					$strMessage .= "<br>There was a problem creating a screen with this plugin type?";
				
				}
				
				
			}//new or existing screen
		}//bolPassed			
	}//if adding a new child BT_item

	//childItem logic...
	///////////////////////////////////////////////////////////////////////////////
	
	
	
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
		
		
	//menu object
	$objBT_item = new Bt_item($BT_itemId);
	$nickname = $objBT_item->infoArray["nickname"];
	$jsonVars = $objBT_item->infoArray["jsonVars"];

	//fill plugins options list for adding new screens...
	$objPlugin = new Plugin();
	$pluginOptions = $objPlugin->fnGetPluginOptions($loggedInUserGuid);

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
<input type="hidden" name="command" id="command" value="" />


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
                	<?php echo fnFormOutput($nickname, true);?> for <?php echo fnFormOutput($appName, true);?>
            </div>
             	
            <div style='padding:10px;'>
                
                <div style='margin-bottom:5px;'>
                    <a href="bt_menus.php?appGuid=<?php echo $appGuid . $qVars;?>" title="Menus"><img src="../../images/arr_right.gif" alt="pointer"/>Back to Menus</a>
                </div>
                    
                    
                <div class='cpExpandoBox colorLightBg'>
                    <a href='#' onClick="fnExpandCollapse('box_nickname');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Menu Nickname</a>
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
                            
                <!-- ################################################# -->                   
				<!-- ############### child items property ############-->
                <div class='cpExpandoBox colorLightBg'>
                    <a href='#' onClick="fnExpandCollapse('box_childItems');return false;"><img src='<?php echo APP_URL;?>/images/arr_right.gif' alt='arrow' />Menu Item Items</a>
                    <div id="box_childItems" style="display:block;">

						<?php if(strtoupper($command) == "DELETE"){ ?>
                            <div class="errorDiv" style="margin-top:10px;">
                                <br/>
                                <b>Delete this Menu Item?</b>
                                <div style='padding-top:5px;'>
                                    Are you sure you want to do this? This cannot be undone!
                                </div>
                                <div style='padding-top:5px;'>
                                    <a href="<?php $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>&BT_itemId=<?php echo $BT_itemId;?>"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt='arrow'/>No, do not delete this menu item</a>
                                </div>
                                <div style='padding-top:5px;'>
                                    <a href="<?php $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>&BT_itemId=<?php echo $BT_itemId;?>&BT_childItemId=<?php echo $BT_childItemId;?>&command=confirmDelete"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt='arrow'/>Yes, permanently delete this menu item</a>
                                </div>
                            </div>
                        <?php } ?>
                            
                        <?php if($bolDeleted){ ?>
                            <div class="doneDiv" style="margin-top:10px;">
                                <b>Menu Item Deleted.</b>
                                <div style='padding-top:5px;'>
                                    <a href="<?php $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>&BT_itemId=<?php echo $BT_itemId;?>"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                </div>
                             </div>
                        <?php } ?> 
        
                        <?php if($strMessage != "" && !$bolDone){ ?>
                            <div class='errorDiv' style="margin-top:10px;">
                                <?php echo $strMessage;?>                                
                            </div>
                        <?php } ?> 
                        
                        <?php if($strMessage == "done" && $bolDone){ ?>
                            <div class='doneDiv' style="margin-top:10px;">
                                <b>Success</b>. 
                                The menu item was added to the list.
                                <div style='padding-top:5px;'>
                                    <a href="<?php $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>&BT_itemId=<?php echo $BT_itemId;?>"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                </div>
                            </div>
                        <?php } ?>
                        
                        <?php if($strMessage == "orderUpdated" && $bolDone){ ?>
                            <div class='doneDiv' style="margin-top:10px;">
                                <b>Order Updated</b>. 
                                <div style='padding-top:5px;'>
                                    <a href="<?php $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>&BT_itemId=<?php echo $BT_itemId;?>"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                </div>
                            </div>
                        <?php } ?>
                        
               
                
                        <!--list of existing items -->
                        <table cellspacing='0' cellpadding='0' style='width:100%;margin-top:10px;'>
                    
							<?php
                                
                                //fetch existing rows
                                $cnt = 0;
                                $strSql = " SELECT I.id, I.guid, I.parentItemGuid, I.loadItemGuid, I.appGuid, I.itemType, I.itemTypeLabel, I.nickname, I.orderIndex, ";
                                $strSql .= "I.jsonVars, I.status, I.dateStampUTC, I.modifiedUTC, ";
                                $strSql .= "I2.itemTypeLabel AS loadScreenType, I2.nickname AS tapScreenNickname,  ";
                                $strSql .= "P.uniquePluginId, P.webDirectoryName ";
                                $strSql .= " FROM " . TBL_BT_ITEMS . " AS I ";
                                $strSql .= " LEFT JOIN " . TBL_BT_ITEMS . " AS I2 ON I.loadItemGuid = I2.guid ";
                                $strSql .= " LEFT JOIN " . TBL_PLUGINS . " AS  P ON I2.uniquePluginId = P.uniquePluginId ";
                                $strSql .= " WHERE I.appGuid = '" . $appGuid . "' AND I.parentItemGuid = '" . $BT_itemId . "'";
                                $strSql .= " ORDER BY I.orderIndex ASC";
                                $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                                if($res){
                                    $numRows = mysql_num_rows($res);
                                    
                                        //header...
                                        if($numRows > 0){
                                            echo "<tr>";
                                                echo "<td style='border-bottom:1px solid gray;padding:5px;padding-left:0px;'><b>Menu Item title...</b></td>";
                                                echo "<td style='border-bottom:1px solid gray;padding:5px;padding-left:10px;'><b>Tapping this menu item loads...</b></td>";
                                                echo "<td style='border-bottom:1px solid gray;padding:5px;text-align:center;width:70px;'><b>order</b></td>";
                                                echo "<td style='border-bottom:1px solid gray;padding:5px;'>&nbsp;</td>";
                                            echo "</tr>";
                                        }
                                    
                                        while($row = mysql_fetch_array($res)){
                                            $cnt++;
                                                        
                                            //style
                                            $css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
        
                                            //for ech childItem...
                                            $BT_childItemId = $row['guid'];
                                            $thisTitle = "";
                                            $thisScreenTypeLabel = "";
                                            $thisTapLoadScreenWithItemId = $row["loadItemGuid"];
                                            $loadScreenPluginDirectory = $row['webDirectoryName'];
                                            $tapScreenNickname = $row["tapScreenNickname"];
                                            $thisScreenTypeLabel = "";
                                        
                                            //figure out what link / label to show
                                            if($row["jsonVars"] != ""){
                                                $json = new Json; 
                                                $decoded = $json->unserialize($row["jsonVars"]);
                                                if(is_object($decoded)){
                                                    if(array_key_exists("titleText", $decoded)) $thisTitle = $decoded->titleText;
                                                }
                                            } 
                                            
                                            //loading screen.....
                                            if($thisTapLoadScreenWithItemId != ""){
                                                $pluginScreenURL = rtrim(APP_URL, "/") . "/" . rtrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($loadScreenPluginDirectory, "/") . "/?appGuid=" . $appGuid . "&BT_itemId=" . $thisTapLoadScreenWithItemId . "&BT_previousScreenId=" . $BT_itemId . "&BT_previousScreenNickname=" . urlencode(fnFormOutput($nickname)) . $qVars;
                                                $thisScreenTypeLabel = "<a href='" . $pluginScreenURL . "' title='properties'>" . $tapScreenNickname . "</a>";
                                            }
                                            
                                            //long title?
                                            if(strlen($thisTitle) > 65){
                                                $thisTitle = substr($thisTitle , 0, 65) . "...";
                                            }
                                                                                                
                                            echo "\n\n<tr id='i_" . $row["guid"] . "' class='" . $css . "'>";
                                                echo "\n<td class='data' style='vertical-align:middle;padding-left:0px;'>";
                                                    echo fnFormOutput($thisTitle);
                                                echo "</td>";
                                                echo "\n<td class='data' style='vertical-align:middle;padding-left:10px;vertical-align:middle;'>";
                                                    echo $thisScreenTypeLabel;
                                                echo "</td>";
                                                echo "\n<td class='data' style='vertical-align:middle;text-align:center;vertical-align:middle;'>";
                                                    echo "<input type='text' name='order_" . $row["id"] . "' id='order_" . $row["id"] . "' value='" . $row['orderIndex'] . "' style='margin:0px;width:70px;text-align:center;font-size:8pt;' />";
                                                echo "</td>";
                                                echo "\n<td class='data' style='vertical-align:middle;text-align:right;padding-right:10px;'>";
                                                    echo "<a href='" . $scriptName . "?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . $qVars . "&BT_childItemId=" . $BT_childItemId . "&command=delete'>delete</a>";
                                                echo "</td>";
                                            echo "\n</tr>";
                                        
                                        }//end while
                                    }//no res
                             
                            ?>     
                            <?php if($cnt > 1){?>
                                <tr>
                                    <td style='padding:5px;padding-left:0px;border-top:1px solid gray;'>
                                        <?php echo $cnt;?> Menu Items
                                    </td>
                                    <td style='border-top:1px solid gray;text-align:center;padding-top:3px;'>&nbsp;
                                        
                                    </td>
                                   <td style='border-top:1px solid gray;text-align:center;padding-top:3px;'>
                                        <input type='button' title="update" value="update" id="saveButton" name="saveButton" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="document.forms[0].command.value='updateOrderIndex';document.forms[0].submit();return false;">
                                    </td>
                                    <td style='border-top:1px solid gray;text-align:center;padding:5px;'>&nbsp;
                                        
                                    </td>
                                </tr>
                            <?php } ?>
        
                        </table>                


						<!-- add new child item -->
                        <table cellspacing='0' cellpadding='0' style='margin:10px;margin-left:0px;'>
                            <tr>
                                <td style="vertical-align:top;padding-left:0px;">
                                    
                                    <div style='padding-top:5px;'>
                                        <b>Menu Item Title</b><br/>
                                        <input name='addMenuItemText' id='addMenuItemText' type='text' value="<?php echo fnFormOutput($addMenuItemText, true);?>" />
        							</div>
                                    
                                    <div style='padding-top:5px;'>    
                                        <b>New or Existing Screen</b>         
                                        &nbsp;&nbsp;
                                        <img src="<?php echo APP_URL;?>/images/arr_right.gif" alt="arrow"/>
                                        <a href="bt_pickerScreen.php?appGuid=<?php echo $appGuid;?>&formElVal=addScreenNicknameId&formElLabel=addScreenNickname" rel="shadowbox;height=750;width=950">Select</a>
                                        <br/>
                                        <input type="text" name="addScreenNickname" id="addScreenNickname" value="<?php echo fnFormOutput($addScreenNickname, true);?>">
                                        <input type="hidden" name="addScreenNicknameId" id="addScreenNicknameId" value="">
                                    </div>
                                    
                                    <div style='padding-top:5px;'>
                                        <b>Choose if adding a screen</b><br/>
                                        <select name="addPluginUniqueId" id="addPluginUniqueId" style="margin:0px;width:250px;">
                                            <option value="">I entered an existing screen above...</option>
                                            <?php echo $pluginOptions;?>
                                        </select>
                                    </div>
                                        
                                    <div style="margin:10px;margin-top:15px;margin-left:0px;">
                                        <input type='button' id="addButton" title="add" value="add" align='absmiddle' class="buttonSubmit" onClick="document.forms[0].command.value='addItem';document.forms[0].submit();return false;">
                                    </div>   

                                </td>
                                    
        
                                </td>
                                <td style='vertical-align:top;padding-left:15px;'>
                                    <div style='margin:10px;'>      
                                        <b>Create Menu Items</b> to show when this menu is displayed. In most cases this menu will be
                                        used as a context menu for an individual screen.
                                        For each row, select which screen loads by entering (or selecting) it's nickname in the
                                        "New or Existing" screen box.
                                    </div>
                                    <div style='margin:10px;'>      
                                        <b>Android Context Menus:</b>
                                        Users are familiar with the omni-present "action bar" used by many Android Apps. When a
                                        plugin is setup to use a context menu the menu items appear in the Android Action Bar. 
									</div>
                                    <div style='margin:10px;'>      
                                        <b>iOS Context Menus:</b>
                                        iOS does not use a standard "context menu" like Android does (using the Android Action Bar). 
                                        When a plugin is setup to use a context menu an icon will appear in the top navigation bar. 
                                        Tapping this icon will expose the context menu.
									</div>
                                    <div style='margin:10px;'>      
                                        <b>Editing Existing Menu Items:</b>
                                        Existing menu items are not editiable. Use the delete option to remove items then 
                                        re-create them if necessary.
									</div>

                               </td>

                                    
                            </tr>
                        </table>
                    </div>    
                </div>
				<!-- ############### end child items properties ########## -->
                <!-- ##################################################### -->                   

                
                <div style="height:100px;"></div>
    	
       		
            </div>
       </div> 
    </fieldset>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
