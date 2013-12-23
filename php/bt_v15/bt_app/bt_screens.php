<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//User Object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);
	
	//returns context var...
	function fnGetCurrentContextVar($jsonContext, $varName){
		if(array_key_exists($varName, $jsonContext)){
			return $jsonContext->{$varName};
		}else{
			return "";
		}
	}
	
	//init page object
	$thisPage = new Page();
	
	//javascript files in <head>...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_screens.js";

	//javascript files in footer...
	$thisPage->scriptsInFooter = "bt_v15/bt_scripts/app_screensFooter.js";

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
	$doWhat = fnGetReqVal("doWhat", "", $myRequestVars);
	
	$createdNickname = "";
	$nicknameHint = "Nickname...";
	$newItemGuid = "";
	$addPluginBoxDisplay = "none";
	$existingDataDisplay = "block";
	
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
	
	//app name...
	$appName = $objApp->infoArray["name"];

	//get current context vars (sort, page, filter, etc)...
	$defaultContextVars = "{\"appGuid\":\"" . $appGuid . "\", \"scViewStyle\":\"gridView\", \"scSortUD\":\"DESC\", \"scSortCol\":\"I.modifiedUTC\", \"scCurPg\":\"1\", \"scSearch\":\"search...\", \"scSearchPluginType\":\"\"}";
	$contextVars = $thisUser->infoArray["contextVars"];
	$json = new Json; 
	if($contextVars == ""){
		$contextVars = $defaultContextVars;
	}
	
	//convert JSON string to JSON object...
	$decodedContextVars = $json->unserialize($contextVars);
	
	//if appGuid in contextVars does not match this appGuid, erase them all...
	if(array_key_exists("appGuid", $decodedContextVars)){
		if($decodedContextVars->appGuid != $appGuid){
			$contextVars = $defaultContextVars;
			$decodedContextVars = $json->unserialize($contextVars);
		}	
	}
	




	///////////////////////////////////////////////////////////////
	//selected id's
	$selectedIds = array();
	$inClauseSQL = "";
	if(isset($_POST['selected'])){
		while (list ($key, $val) = each ($_POST['selected'])) { 
			if($val != ""){
				$selectedIds[] = trim($val);
				$inClauseSQL .= "'" . trim($val) . "',";
			}
		}
		$inClauseSQL = fnRemoveLastChar($inClauseSQL, ",");
	}
	
	//pre-selects checked items
	function fnIsChecked($theId){
		global $selectedIds;
		if(in_array($theId, $selectedIds)){
			return "checked";
		}else{
			return "";
		}	
	}
	//end selected id's
	///////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////
	//deleting BT_item(s)...
	if($appGuid != "" && strtoupper($doWhat) == "DELETEITEMS" && count($selectedIds) > 0){
		
		//show the confirmation message on step one...
		$command = "delete";
		
	}
	if($appGuid != "" && strtoupper($command) == "CONFIRMDELETE" && count($selectedIds) > 0){
		
		//delete child-objects if this screen has any...
		$strSql = "DELETE FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' ";
		$strSql .= " AND parentItemGuid IN (" . $inClauseSQL . ")";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
		//delete any menu items, buttons, map items, etc. that may load the screen we are deleting..			
		$strSql = "DELETE FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' ";
		$strSql .= " AND loadItemGuid IN (" . $inClauseSQL . ")";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

		//finally, delete the item itself				
		$strSql = "DELETE FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' ";
		$strSql .= " AND guid IN (" . $inClauseSQL . ")";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
		//flag...
		$bolDeleted = TRUE;
		$command = "";

	}	
	//end change app owner
	///////////////////////////////////////////////////////////////

	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if we are adding a new BT_item...
	$addNickname = fnGetReqVal("addNickname", $nicknameHint, $myRequestVars);
	$addPluginUniqueId = fnGetReqVal("addPluginUniqueId", "", $myRequestVars);
	if(strtoupper($command) == "ADDITEM"){
		if(strlen($addNickname) < 1 || strtoupper($addNickname) == strtoupper($nicknameHint)){
			$bolPassed = false;
		}
		if(strlen($addPluginUniqueId) < 1){
			$bolPassed = false;
		}	
		
		//no good...
		if(!$bolPassed){
			$strMessage = "<br>Nothing was added. To add a new screen or action, start by entering a nickname for the screen or action, ";
			$strMessage .= "then choosing something from the drop-down list, then clicking the \"add\" button.";
		}
		
		//plugin info...
		$category = "";
		$displayAs = "";
		$loadClassOrActionName = "";
		$hasChildItems = "0";
		$defaultJsonVars = ""; 
		$webDirectoryName = "";
			
		//if good so far...
		if($bolPassed){
			
			//if this nickname is being used, append a count to prevent dups.
			if($bolPassed){
				$strSql = "SELECT Count(id) FROM " . TBL_BT_ITEMS;
				$strSql .= " WHERE appGuid = '" . $appGuid . "'";
				$strSql .= " AND uniquePluginId = '" . $addPluginUniqueId . "' ";
				$strSql .= " AND nickname = '" . $addNickname . "' ";
				$existingCount = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($existingCount != "" && $existingCount != "0" && $existingCount != 0){
					$addNickname = $addNickname . " (" . ($existingCount + 1) . ")";
				}		
			}
		
			//get plugins info...			
			$tmpSql = "SELECT category, displayAs, loadClassOrActionName, hasChildItems, defaultJsonVars, webDirectoryName ";
			$tmpSql .= " FROM " . TBL_BT_PLUGINS . " WHERE uniquePluginId = '" . $addPluginUniqueId . "' LIMIT 0, 1";
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
			$defaultJsonVars = str_replace("[itemNickname]", $addNickname, $defaultJsonVars);
			$defaultJsonVars = str_replace("[replaceNickname]", $addNickname, $defaultJsonVars);
			$defaultJsonVars = str_replace("[nickname]", $addNickname, $defaultJsonVars);
			
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

		}
		
		//if good so far...
		if($bolPassed){
			
			//itemType, itemTypeLabel and json values depend on the screen type
			$newItemGuid = strtoupper(fnCreateGuid());
			
			//create new BT_item for the new screen..
			$objNewItem = new Bt_item();
			$objNewItem -> infoArray["guid"] = fnFormInput($newItemGuid);
			$objNewItem -> infoArray["parentItemGuid"] = "";
			$objNewItem -> infoArray["uniquePluginId"] = fnFormInput($addPluginUniqueId);
			$objNewItem -> infoArray["loadClassOrActionName"] = fnFormInput($loadClassOrActionName);
			$objNewItem -> infoArray["hasChildItems"] = fnFormInput($hasChildItems);
			$objNewItem -> infoArray["loadItemGuid"] = "";
			$objNewItem -> infoArray["appGuid"] = fnFormInput($appGuid);
			$objNewItem -> infoArray["controlPanelItemType"] = "screen";
			$objNewItem -> infoArray["itemType"] = $loadClassOrActionName;
			$objNewItem -> infoArray["itemTypeLabel"] = fnFormInput($displayAs);
			$objNewItem -> infoArray["nickname"] = fnFormInput($addNickname);
			$objNewItem -> infoArray["orderIndex"] = "1";
			$objNewItem -> infoArray["jsonVars"] = $defaultJsonVars;
			$objNewItem -> infoArray["status"] = "active";
			$objNewItem -> infoArray["dateStampUTC"] = $dtNow;
			$objNewItem -> infoArray["modifiedUTC"] = $dtNow;
			$objNewItem -> fnInsert();
			
			//flag, reset
			$createdNickname = $addNickname;
			$addNickname = $nicknameHint;
			$bolDone = true;
			$search = "";
			$addPluginBoxDisplay = "block";
			$existingDataDisplay = "none";
		
		}//bolPassed
		
			
	}
	//done adding a new BT_item
	/////////////////////////////////////////////////////////////////////////////////
	
	//list vars
	$search = fnGetReqVal("searchInput", fnGetCurrentContextVar($decodedContextVars, "scSearch"), $myRequestVars);
	$searchPluginTypeUniqueId = fnGetReqVal("searchPluginTypeUniqueId", fnGetCurrentContextVar($decodedContextVars, "scSearchPluginType"), $myRequestVars);
		if(strtoupper($searchPluginTypeUniqueId) == "SEARCH...") $searchPluginTypeUniqueId = "";
	$scriptName = "bt_screens.php";
	$totalRecs = 0;
	$recsPerPage = 100;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$colCount = 4;

	//viewStyle...
	$viewStyle = fnGetReqVal("viewStyle", fnGetCurrentContextVar($decodedContextVars, "scViewStyle"), $myRequestVars);
	$nextViewStyle = fnGetReqVal("nextViewStyle", "", $myRequestVars);
		if($nextViewStyle != "") $viewStyle = $nextViewStyle;
	//sort up / down...
	$sortUpDown = fnGetReqVal("sortUpDown", fnGetCurrentContextVar($decodedContextVars, "scSortUD"), $myRequestVars);
		$sortUpDown = fnGetReqVal("nextUpDown", $sortUpDown, $myRequestVars);
	//sort column...
	$sortColumn = fnGetReqVal("sortColumn", fnGetCurrentContextVar($decodedContextVars, "scSortCol"), $myRequestVars);
		$sortColumn = fnGetReqVal("nextCol", $sortColumn, $myRequestVars);
		if($sortColumn == "") $sortColumn = "I.modifiedUTC";
	//current page...
	$currentPage = fnGetReqVal("currentPage", fnGetCurrentContextVar($decodedContextVars, "scCurPg"), $myRequestVars);
		$nextPage = fnGetReqVal("nextPage", "", $myRequestVars);
			if(is_numeric($nextPage)) $currentPage = $nextPage;
				if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE I.appGuid = '" . $appGuid . "'";
	$whereClause .= " AND controlPanelItemType = 'screen' ";
	$whereClause .= " AND I.status != 'Deleted' ";
	
	//if searching...
	$searchHint = "";
	$typeHint = "";
	
	//if searching for plugin type...
	if($searchPluginTypeUniqueId != ""){
		$whereClause .= " AND I.uniquePluginId = '" . $searchPluginTypeUniqueId . "' ";
		$searchHint = "<span style='color:red;'>You are searching for screens with the <span style='color:black;font-weight:bold;'>selected plugin type</span></span>";
	}
	
	//nickname...	
	if((strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "")){
		$whereClause .= " AND I.nickname LIKE '%" . $search . "%' ";
		if($searchHint == ""){
			$searchHint = "<span style='color:red;'>You are searching for screens where the <span style='color:black;font-weight:bold;'>Nickname</span> contains</span> <b>\"" . fnFormOutput($search) . "\"</b> ";
		}else{
			$searchHint = "<span style='color:red;'>You are searching for screens with the <span style='color:black;font-weight:bold;'>selected type</span> where <span style='color:black;font-weight:bold;'>Nickname</span> contains</span> <b>\"" . fnFormOutput($search) . "\"</b> ";
		}
		
		//if we are searching while looking at "groupView" we are searching plugins "displayAs" not screen nickname...
		if(strtoupper($viewStyle) == "GROUPVIEW"){
			
			if($searchHint == ""){
				$searchHint = "<span style='color:red;'>You are searching where Plugin name contains</span> <b>\"" . fnFormOutput($search) . "\"</b> ";
			}else{
				$searchHint = "<span style='color:red;'>You are searching for screens with the <span style='color:black;font-weight:bold;'>selected type</span> where the <span style='color:black;font-weight:bold;'>Plugin name</span> contains</span> <b>\"" . fnFormOutput($search) . "\"</b> ";
			}
		}
	
	}
	
	//get total recs...
	$totalSql = "  SELECT Count(I.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_BT_ITEMS . " AS I ";

	//append where
	$totalSql .= $whereClause;
	
	//get total count of records that meet search criteria
	$totalRecs = fnGetOneValue($totalSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

	//calculate firstRec
	$firstRec = ($currentPage - 1) * $recsPerPage;
		if($firstRec < 0) $firstRec = 0;
		
	//calculate total pages
	if($totalRecs > $recsPerPage) $totalPages = ceil($totalRecs / $recsPerPage);
	
	//re-calculate lastRec
	$lastRec = $currentPage * $recsPerPage;
	if($lastRec > $totalRecs) $lastRec = $totalRecs;
		
	//fetch
    $strSql = " SELECT I.guid, I.parentItemGuid, I.uniquePluginId, I.hasChildItems, I.loadItemGuid, ";
	$strSql .= "I.appGuid, I.controlPanelItemType, I.itemType, I.itemTypeLabel, I.nickname, I.orderIndex, ";
	$strSql .= "I.jsonVars, I.status, I.dateStampUTC, I.modifiedUTC, ";
	$strSql .= "P.uniquePluginId, P.webDirectoryName, P.landingPage ";
	$strSql .= "FROM " . TBL_BT_ITEMS . " AS I ";
	$strSql .= " INNER JOIN " . TBL_BT_PLUGINS . " AS P ON I.uniquePluginId = P.uniquePluginId ";
	$strSql .= $whereClause;
	$strSql .= " ORDER BY " . $sortColumn . " " . $sortUpDown;
	$strSql .= " LIMIT " . $firstRec . "," . $recsPerPage;
	
	//shows sort arrow.
	$tmpSort = ($sortUpDown == "ASC") ? 'DESC' : 'ASC' ;
	
	//paging links
	$prevPageLink = "\n<a href='" . $scriptName . "?appGuid=" . $appGuid . "&nextPage=" . ($currentPage - 1) . "' title='Previous Page' target='_self'>< Previous Page</a><span>&nbsp;</span>";
	$nextPageLink = "\n<a href='" . $scriptName . "?appGuid=" . $appGuid . "&nextPage=" . ($currentPage + 1) . "' title='Next Page' target='_self'>Next Page ></a><span>&nbsp;</span>";
	if($firstRec < $recsPerPage) $prevPageLink = "";
	if(($firstRec + $recsPerPage) >= $totalRecs) $nextPageLink = "";
	
	//fix up search
	$tmpSearch = $search;
	if($search == "" || strtoupper($search) == "ALL") $search = "search...";

	//fix up add-nickname
	if($addNickname == "") $addNickname = $nicknameHint;
	
	//fill plugins options list for searching and for adding...
	$objPlugin = new Plugin();
	$pluginOptions = $objPlugin->fnGetPluginOptions();

	//if nothing was selected...show the "blankScreen" option as the first choice...
	if($addPluginUniqueId == "") $addPluginUniqueId = "blankScreen";

	//create contextVars JSON for current context...
	$contextVars = "{\"appGuid\":\"" . $appGuid . "\", \"scViewStyle\":\"" . $viewStyle . "\", \"scSortUD\":\"" . $sortUpDown . "\", \"scSortCol\":\"" . $sortColumn . "\", \"scCurPg\":\"" . $currentPage . "\", \"scSearch\":\"" . $search . "\", \"scSearchPluginType\":\"" . $searchPluginTypeUniqueId . "\"}";
	
	//update this user's context vars (remember filter, sort, page, etc)...
	$tmpSql = "UPDATE " . TBL_USERS . " SET contextVars = '" . $contextVars . "' WHERE guid = '" . $thisUser->infoArray["guid"] . "'";
	fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

	//querystring for links
	$qVars = "&appGuid=" . $appGuid . "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	$qVars .= "&viewStyle=" . $viewStyle . "&searchPluginTypeUniqueId=" . $searchPluginTypeUniqueId;

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>


<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_itemId" id="BT_itemId" value="<?php echo $BT_itemId;?>">
<input type="hidden" name="recsPerPage" id="recsPerPage" value="<?php echo $recsPerPage;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="totalPages" id="totalPages" value="<?php echo $totalPages;?>">
<input type="hidden" name="firstRec" id="firstRec" value="<?php echo $firstRec;?>">
<input type="hidden" name="lastRec" id="lastRec" value="<?php echo $lastRec;?>">
<input type="hidden" name="totalRecs" id="totalRecs" value="<?php echo $totalRecs;?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">
<input type="hidden" name="command" id="command" value="">
<input type="hidden" name="viewStyle" id="viewStyle" value="<?php echo $viewStyle;?>">


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
               	Manage Screens and Actions for <?php echo fnFormOutput($appName, true);?>
           	</div>

            <div style='padding:10px;'>

			   <?php if($strMessage != "" || $bolDeleted || $bolDone || strtoupper($command) == "DELETE") { ?>
                   
				   
				   <?php if(strtoupper($command) == "DELETE"){ ?>
                        <div class="errorDiv">
                            <br/>
                            <b>Confirmation Required: </b>
							<?php 
                                if(count($selectedIds) > 1){
                                    echo "Delete " . count($selectedIds) . " screen";
                                }else{
                                    echo "Delete " . count($selectedIds) . " screens";
                                }
                            ?>

                            <div style='padding-top:5px;'>
                            
                                Are you sure you want to do this? This cannot be undone! When you
                                confirm this operation, all information and content associated with 
                                
                                <?php 
									if(count($selectedIds) > 1){
										echo "<b>" . count($selectedIds) . " screen</b>";
									}else{
										echo "<b>" . count($selectedIds) . " screens</b>";
									}
								?>
                                
                                
                                will be permanently removed and you
                                will not be able to get it back - ever. 
                                
                            </div>
                            <div style='padding-top:10px;'>
                                <a href="#" onclick="document.forms[0].doWhat.value='';document.forms[0].submit();return false;"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete</a>
                            </div>
                            <div style='padding-top:10px;'>
                                <a href="#" onclick="document.forms[0].command.value='confirmDelete';document.forms[0].submit();return false;"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete</a>
                            </div>
                        </div>
                    <?php } ?>                                    
    
                    
					
					<?php if($bolDeleted){ ?>
                        <div class="doneDiv">
                            <b>Delete Successful</b>.
                            <div style='padding-top:10px;'>
                                <a href="<?php echo $scriptName . "?appGuid=" . $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                            </div>
                         </div>
                    <?php } ?> 
                    
                    
                    
                    <?php if($strMessage != "" && !$bolDone){ ?>
                        <div class='errorDiv'>
                            <?php echo $strMessage;?>                                
                        </div>
                    <?php } ?> 
                    
                <?php } ?>                   
                       
                <div id="headerBox" style="display:<?php echo $existingDataDisplay;?>;">

					<?php if(strtoupper($command) != "DELETE") { ?>
                        <table cellspacing='0' cellpadding='0' width="99%" style='margin-left:10px;margin-bottom:10px;'>
                            <tr>
                                <td style='vertical-align:middle;'>
            
                                    <a href="#" onClick="fnShowHide();return false;" style='vertical-align:middle;white-space:nowrap;'><img src="../../images/plus.png" alt="Add New" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add New</a>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <a href="<?php echo $scriptName . "?appGuid=" . $appGuid;?>&nextViewStyle=listView" title="List View" style='vertical-align:middle;white-space:nowrap;'><img src="../../images/list.png" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>List View</a>
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                    <a href="<?php echo $scriptName . "?appGuid=" . $appGuid;?>&nextViewStyle=gridView" title="Grid View" style='vertical-align:middle;white-space:nowrap;'><img src="../../images/grid.png" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Grid View</a>
                                
                                </td>
                                <td nowrap style='padding-left:10px;'>
                                    <div style='float:right;margin:0px;'>
                                        &nbsp;
                                        <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                    </div>
                                    <div style='float:right;margin:0px;'>
                                        <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('search...',this);" type='text' value="<?php echo fnFormOutput($search, true);?>" class='searchBox' style='margin:0px;display:inline;vertical-align:middle;overflow:hidden;' onkeyup="document.forms[0].currentPage.value='1';">
                                        <select name="searchPluginTypeUniqueId" id="searchPluginTypeUniqueId" onchange="document.forms[0].currentPage.value='1';document.forms[0].submit();" style="width:200px;margin:0px;display:inline;vertical-align:middle;overflow:hidden;">
                                            <option value="">Search all plugin types...</option>
                                            <?php echo $pluginOptions;?>
                                        </select>
                                    </div>
                                </td>
                            </tr>
                        </table>

						<?php if(strtoupper($searchHint) != ""){ //show message?>
                            <div style='margin-bottom:15px;'>
                               <?php echo $searchHint;?>
                            </div>
                        <?php } ?>

                    <?php } ?>
                    
                    
                </div>                   
                
                <!-- add new screen -->
               	<div id="addPluginBox" style='padding-bottom:25px;margin-bottom:25px;display:<?php echo $addPluginBoxDisplay;?>;'>
					<div style='padding:5px;padding-left:10px;'>
						
                        
						<?php if($bolDone){ ?>
                            <div class='doneDiv' style='margin-bottom:20px;'>
                                <b><?php echo fnFormOutput($createdNickname, true);?></b> created successfully. 
                                This screen or action was created with some generic properties that may need additional configuration.
                                The types of properties you need to configure depend on the type of plugin you selected.
                                You can add another screen or hide this message.
                                <div style='padding-top:10px;'>
                                    <a href="<?php echo $scriptName . "?appGuid=" . $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                </div>
                            </div>
                        <?php } ?>                     
                        
                        
                        <table style='width:100%;' cellspacing='0' cellpadding='0'>
                        	<tr>
                            	<td style='vertical-align:top;'>
                                    <div style='padding-top:0px;'>
                                        <b>Enter a nickname for the new item</b><br/>
                                        <input type="text" name="addNickname" id="addNickname" value="<?php echo fnFormOutput($addNickname);?>" style="width:250px;" onFocus="top.fnClearSearch('Nickname...',this);"/>
                                    </div>
                                    
                                    <div style='padding-top:5px;'>
                                        <b>Choose a plugin type</b><br/>
                                        <select name="addPluginUniqueId" id="addPluginUniqueId" style="margin:0px;width:250px;">
                                            <option value="">Select a plugin type...</option>
                                            <?php echo $pluginOptions;?>
                                        </select>
                                    </div>
                                    
                                    <div style='padding-top:15px;'>
                                        <input type='button' title="add" value="add" align='absmiddle' class="buttonSubmit" onClick="fnAddScreen();return false;" />
                                        <input type='button' title="cancel" value="cancel" align='absmiddle' class="buttonCancel" onClick="fnShowHide();return false;" />
                                    </div>
                                    <div id='addScreenMessage' style='padding-top:5px;font-size:9pt;color:red;visibility:hidden;'>
                                    
                                    </div>
                            	</td>
                            	<td style='vertical-align:top;'>
                                    <div style='float:left;margin-left:35px;'>
                                        <div class='infoDiv' style='margin-top:0px;'>
                                            <b>Adding Screens and Actions</b> 
                                            <div style='padding-top:5px;'>
                                            	<b>1)</b> Enter a nickname for the screen or action. Nicknames help you identify screens and actions.
                                            </div>
                                            <div style='padding-top:5px;'>
                                            	<b>2)</b> Choose a Screen, Menu, or Action from the drop-down list. The choices in the list are an alphabetical
                                                listing of all the plugins installed in the control panel. The number
                                                next to the plugin type shows how many of these are used in this app.
                                            </div>
                                            <div style='padding-top:5px;'>
                                            	<b>3)</b> Click "Add" to save your choices. 
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div style='padding-top:0px;'>&nbsp;</div>
                        		
                        <!-- plugin info -->        
                        <table style='width:100%;border-top:1px solid #999999;' cellspacing='0' cellpadding='0'>
						<?php
                        
							$tmp = "SELECT P.guid, P.uniquePluginId, P.displayAs, P.category, P.webDirectoryName, P.versionString, ";
							$tmp .= "P.shortDescription, ";
							$tmp .= "(SELECT Count(id) AS count FROM " . TBL_BT_ITEMS . " AS I ";
								$tmp .= "WHERE I.uniquePluginId = P.uniquePluginId AND I.appGuid = '" . $appGuid . "' ) AS countOfScreens ";
							$tmp .= "FROM " . TBL_BT_PLUGINS . " AS P ";
							$tmp .= "LEFT JOIN " . TBL_BT_ITEMS . " AS I ON P.uniquePluginId = I.uniquePluginId ";
							$tmp .= "GROUP BY P.uniquePluginId ";
							$tmp .= "ORDER BY countOfScreens DESC ";							
							$res2 = fnDbGetResult($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
							$cnt2 = 0;
							if($res2){
                                while($row = mysql_fetch_array($res2)){
                                
									$cnt2++;
												
									//style
									$css = (($cnt2 % 2) == 0) ? "rowNormal" : "rowAlt" ;
									
										
									$pluginGuid = $row['guid'];
									$uniquePluginId = $row['uniquePluginId'];
									$displayAs = fnFormOutput($row["displayAs"]);
									$category = $row['category'];
									$webDirectoryName = $row['webDirectoryName'];
									$versionString = $row['versionString'];
									$shortDescription = $row['shortDescription'];

									//icon URL
									$iconURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/plugins" . $webDirectoryName . "/icon.png";
	
									echo "\n\n<tr class='" . $css . "'>";
										echo "\n<td class='data' style='vertical-align:middle;padding-left:5px;width:50px;height:50px;border-bottom:1px solid #999999;'>";
											echo "<a href='bt_pickerPluginDetails.php?pluginGuid=" . $pluginGuid . "' rel=\"shadowbox;height=550;width=950\"><img src='" . $iconURL . "' style='height:50px;width:50px;' alt='Plugin icon'/></a>";
										echo "</td>";
										echo "\n<td class='data' style='vertical-align:middle;padding-left:5px;padding-top:5px;white-space:normal;border-bottom:1px solid #999999;'>";
											echo "<a href='bt_pickerPluginDetails.php?pluginGuid=" . $pluginGuid . "' rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a> <i>(" . $row["countOfScreens"] . ")</i>";
											echo "<br/>" . fnFormOutput($shortDescription);
										echo "</td>";
									echo "\n</tr>";
                                    
                                    
                                }//end while
                            }//res2
                            
                        ?>
                        </table>
                	</div>
                </div>
                
                <div id="dataBox" style="display:<?php echo $existingDataDisplay;?>;">
                
                    <!--list view-->
                    <?php if(strtoupper($viewStyle) == "LISTVIEW"){ ?>
                        <table cellspacing='0' cellpadding='0' width="100%" style='margin-left:0px;'>
                            <tr>
                                <td class="tdSort">&nbsp;
									
                                </td>
                                <td class="tdSort">
                                   <a title="Sort" href="#" onclick="top.fnSort(document, 'I.nickname');return false;">Nickname</a> <?php echo fnSortIcon("I.nickname", $tmpSort, $sortColumn); ?>
                                </td>
                                
                                <td class="tdSort">
                                    <a title="Sort" href="#" onclick="top.fnSort(document, 'I.itemTypeLabel');return false;">Screen / Action Type</a> <?php echo fnSortIcon("I.itemTypeLabel", $tmpSort, $sortColumn); ?>
                                </td>
                
                                <td class="tdSort">
                                    <a title="Sort" href="#" onclick="top.fnSort(document, 'I.modifiedUTC');return false;">Modified</a> <?php echo fnSortIcon("I.modifiedUTC", $tmpSort, $sortColumn); ?>
                                </td>
                                <td class="tdSort" style='text-align:right;vertical-align:top;padding:0px;padding-right:5px;' >
                                    <input id="checkAll" type='checkbox' style="width:12px;height:12px;margin:0px;display:inline;" onclick="top.fnCheckAll(self);">
                                </td>
                            </tr>
                            
                            <?php
                                //data
                                $numRows = 0;
                                $cnt = 0;
                                if($totalRecs > 0){
                                    $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                                    if($res){
                                        $numRows = mysql_num_rows($res);
                                        $cnt = 0;
                                            while($row = mysql_fetch_array($res)){
                                            $cnt++;
                                                        
                                            //style
                                            $css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
                                            
                                            $BT_itemId = $row['guid'];
                                            $itemTypeLabel = $row['itemTypeLabel'];
                                            $nickname = fnFormOutput($row['nickname']);
                                            $webDirectoryName = $row['webDirectoryName'];
    
                                            $modDate = fnFromUTC($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $modHours = fnDateDiff("h", $modDate, $dtToday);
                                            $modLabel = fnSinceLabel($modHours, $row['modifiedUTC'], $thisUser->infoArray["timeZone"]);
            
                                            //url to plugin management screen depends on the plugin's setting. It could be the built in page, or a custom script...
                                            $landingPage = $row['landingPage'];
											$pluginScreenURL = "";
											if(strlen($landingPage) < 1){
												$pluginScreenURL = trim(APP_URL, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($webDirectoryName, "/") . "/?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . $qVars;
											}else{
												if(strtoupper($landingPage) == "BT_SCREEN.PHP"){
                                            		$pluginScreenURL = $landingPage . "?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId;
												}else{
													$pluginScreenURL = trim(APP_URL, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($webDirectoryName, "/") . "/" . $landingPage . "?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . $qVars;
												}
											}
											
											
											

											//url to pop-up JSON screen
                                            $popUpJsonURL = "bt_screen_json.php?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId;
            
                                            echo "\n\n<tr id='i_" . $BT_itemId . "' class='" . $css . "'>";
                                                echo "\n<td class='data' style='vertical-align:middle;width:20px;'>";
													echo "<a href='" . $popUpJsonURL . "' rel=\"shadowbox;height=550;width=950\"><img src='../../images/code.png' alt='code' style='vertical-align:middle;'></a>";
												echo "</td>";
                                                echo "\n<td class='data' style='padding-left:5px;vertical-align:middle;'>";
                                                    echo "<a href=\"" . $pluginScreenURL . "\">" . $nickname . "</a>";
                                                echo "</td>";
                                                echo "\n<td class='data' style='padding-left:10px;vertical-align:middle;'>" . $itemTypeLabel . "</td>";
                                                echo "\n<td class='data' style='padding-left:10px;vertical-align:middle;'>" . $modLabel . "</td>";
												echo "\n<td class='data' style='padding:0px;padding-right:5px;text-align:right;'><input id='selected[]' name='selected[]' type='checkbox' style='height:12px;width:12px;margin:0px;' value='" . $BT_itemId . "' " . fnIsChecked($BT_itemId) . "></td>";
											
											echo "\n</tr>";
                                            
                                            
                                        }//end while
                                    }//no res
                                }//no records
                    
                            ?>
                
							<?php if($totalRecs > 0){?>
                                <tr>
                                    <td colspan='5' style='padding-top:5px;text-align:right;vertical-align:top;'>
                                        
                                        <div style='padding:5px;'>
                                            <select id='doWhat' name='doWhat' onChange="document.forms[0].submit();" style='vertical-align:middle;width:250px;' align='absmiddle'>
                                                <option value="">&nbsp;Actions menu...</option>
                                                <option value="deleteItems">&nbsp;Delete Selected</option>
                                                <option value="">&nbsp;</option>
                                            </select>
                                        </div>
                                        
                                        <div style='padding:5px;'>
                                            <?php 
                                                if($totalRecs > 0){
                                                    echo ($firstRec + 1) . " - " . $lastRec . " of " . $totalRecs;
                                                    if($totalRecs > $recsPerPage){
                                                        echo "<span>&nbsp;</span>";
                                                        echo $prevPageLink . $nextPageLink;
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                        	<?php } ?>
                            
                        </table>
                    
					
					
					
					
					<?php } //listView ?>
                    
                    
                    <!--grid view-->
                    <?php if(strtoupper($viewStyle) == "GRIDVIEW"){ ?>
                        <?php
                            //data
                            $numRows = 0;
                            $cnt = 0;
                            if($totalRecs > 0){
                                $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                                if($res){
                                    $numRows = mysql_num_rows($res);
                                    $cnt = 0;
                                    while($row = mysql_fetch_array($res)){
                                        $cnt++;
                                        
                                        //screen info
                                        $BT_itemId = $row['guid'];
                                        $nickname = fnFormOutput($row['nickname']);
                                        $webDirectoryName = $row['webDirectoryName'];
										
										//url to plugin management screen depends on the plugin's setting. It could be the built in page, or a custom script...
										$landingPage = $row['landingPage'];
										$pluginScreenURL = "";
										if(strlen($landingPage) < 1){
											$pluginScreenURL = trim(APP_URL, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($webDirectoryName, "/") . "/?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . $qVars;
										}else{
											if(strtoupper($landingPage) == "BT_SCREEN.PHP"){
												$pluginScreenURL = $landingPage . "?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId;
											}else{
												$pluginScreenURL = trim(APP_URL, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($webDirectoryName, "/") . "/" . $landingPage . "?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . $qVars;
											}
										}
										
										
										
										//url to pop-up JSON screen
                                    	$popUpJsonURL = "bt_screen_json.php?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId;
	
										//plugin page click...
										$pluginPageClick = "onClick=\"document.location.href='" . $pluginScreenURL . "';return false;\"";
										
                                        echo "\n<div class='pluginBox'>";
                                            echo "\n<div class='pluginIcon'>";
												echo "<img src='../.." . APP_DATA_DIRECTORY . "/plugins" . $webDirectoryName . "/icon.png' style='cursor:pointer;' " . $pluginPageClick . ">";
                                            echo "</div>";    
											echo "\n<div class='pluginNickname' style='cursor:pointer;' " . $pluginPageClick . ">";
												echo $nickname;
											echo "</div>";
											echo "\n<div style='margin-top:-7px;'>";
												echo "<a href='" . $popUpJsonURL . "' rel=\"shadowbox;height=550;width=950\"><img src='../../images/code.png' alt='code' style='vertical-align:middle;float:right;'></a>";
											echo "</div>";
                                        echo "\n</div>";
                                        
                                    }//while
                                }//res
                            }//totalRecs
                        ?>
                        <div style='clear:both;'>&nbsp;</div>
                    <?php } //gridView ?>
    
    
                    <?php if($totalRecs > 0 && strtoupper($viewStyle) == "GRIDVIEW"){ ?>
                        <div style='padding-left:15px;text-align:left;vertical-align:top;'>
                            <?php 
                                echo ($firstRec + 1) . " - " . $lastRec . " of " . $totalRecs;
                                if($totalRecs > $recsPerPage){
                                    echo "<span>&nbsp;</span>";
                                    echo $prevPageLink . $nextPageLink;
                                }
                            ?>
                        </div>
                    <?php } ?>
				
                
                	<?php if($totalRecs < 1){?>
                    	<div class='infoDiv'>
                        <?php if($searchHint == "" && $searchPluginTypeUniqueId == ""){ ?>
                        	<b>This application doesn't have any screens or actions setup yet.</b>
                            <div style='padding-top:5px;'>
                            	Use the "Add" option to add some screens and actions. Use menu type screen to create
                                navigational depth. After you create a screen or action adjust the advanced properties
                                for that individual screen or action to best fit your needs. 
                            </div>
                         <?php }else{ ?>
                         	Your search produced no results.
                         <?php } ?>
                         
                        </div>
                    
                    <?php } ?>
                
                
                </div>
            
            </div>  
         </div>       
    </fieldset>
        


<script stype="text/javascript">var searchPluginTypeUniqueId = "<?php echo $searchPluginTypeUniqueId;?>";</script>
<script stype="text/javascript">var addPluginUniqueId = "<?php echo $addPluginUniqueId;?>";</script>



        

<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
