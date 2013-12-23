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
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_themes.js";

	//javascript inline in head section...
	$thisPage->jsInHead = "";
	
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$bolActivated = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$BT_itemId = fnGetReqVal("BT_itemId", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appName = "";
	$deleteNickname = "no nickname found";
	$createdNickname = "no nickname found";
	$activatedNickname = "";
	
	//for css
	$addThemeBoxDisplay = "none";
	$existingDataDisplay = "block";
	
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
	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if activating... Only the first theme is downloaded by the app...
	if($appGuid != "" && $BT_itemId != ""){
	
		if($command == "makeActive"){
		
			//get the nickname of the BT_item to display in confirmation message
			$objBT_item = new Bt_item($BT_itemId);
			$activatedNickname = $objBT_item->infoArray["nickname"];
		
			//set all themes to orderIndex = 99
			$strSql = "UPDATE " . TBL_BT_ITEMS . " SET orderIndex = 99 ";
			$strSql .= "WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND controlPanelItemType = 'theme' ";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

			//set selected theme to orderIndex = 0
			$strSql = "UPDATE " . TBL_BT_ITEMS . " SET orderIndex = 0 ";
			$strSql .= "WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND guid = '" . $BT_itemId . "'";
			$strSql .= " AND controlPanelItemType = 'theme' ";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

			//flag
			$bolActivated = TRUE;

		}//makeActive	
		
	}//if activating
	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if deleting a BT_item (theme)
	if($appGuid != "" && $BT_itemId != ""){
	
		//get the nickname of the BT_item to display in confirmation message
		$objBT_item = new Bt_item($BT_itemId);
		$deleteNickname = $objBT_item->infoArray["nickname"];
	
		if($command == "confirmDelete"){
			
			$strSql = "DELETE FROM " . TBL_BT_ITEMS . " WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND guid = '" . $BT_itemId . "' AND orderIndex != '0'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			$bolDeleted = TRUE;
			
		}//confirm delete	
		
	}//if deleting

	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if we are adding a new BT_item...
	$addNickname = fnGetReqVal("addNickname", "Enter a nickname...", $myRequestVars);
	$addType = "theme";
	if(strtoupper($command) == "ADDITEM"){
		
		//for css
		$addThemeBoxDisplay = "block";
		$existingDataDisplay = "none";
	
		if(strlen($addNickname) < 1 || strtoupper($addNickname) == "ENTER A NICKNAME..."){
			$bolPassed = false;
		}
		if(strlen($addType) < 1){
			$bolPassed = false;
		}	
		
		//make sure nickname is available
		$strSql = "SELECT id FROM " . TBL_BT_ITEMS . " WHERE nickname = '" . $addNickname . "' AND appGuid = '" . $appGuid . "'";
		$tmpId = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		if($tmpId != ""){
			$bolPassed = false;
			$strMessage .= "<br>The nickname you entered is already in use, no duplicates allowed.";
		}		
		
		if(!$bolPassed){
			if($strMessage == ""){
				$strMessage .= "<br>Theme not added. To add a new theme, start by entering a nickname for the theme, ";
				$strMessage .= "then clicking the \"add\" button.";
			}
		}else{
		
			//itemType, itemTypeLabel and json values depend on the screen type
			$newItemGuid = strtoupper(fnCreateGuid());
			$itemType = "BT_theme";
			$itemTypeLabel = "Global Theme";
			$jsonVars = "{\"itemId\":\"" . $newItemGuid . "\", \"itemType\":\"BT_theme\", ";
			$jsonVars .= "\"itemNickname\":\"" . $addNickname . "\" ";
			$jsonVars .= "}";

			//validate again
			if($itemType == "" || $itemTypeLabel == "" || $jsonVars == "" || $newItemGuid == ""){
			
				$bolPassed = false;
				$strMessage .= "<br>There was a problem adding the theme?";
			
			}else{

				//create new BT_item..
				$objNewItem = new Bt_item();
				$objNewItem -> infoArray["guid"] = fnFormInput($newItemGuid);
				$objNewItem -> infoArray["parentItemGuid"] = "";
				$objNewItem -> infoArray["uniquePluginId"] = "";
				$objNewItem -> infoArray["loadClassOrActionName"] = "";
				$objNewItem -> infoArray["hasChildItems"] = "";
				$objNewItem -> infoArray["loadItemGuid"] = "";
				$objNewItem -> infoArray["appGuid"] = fnFormInput($appGuid);
				$objNewItem -> infoArray["controlPanelItemType"] = "theme";
				$objNewItem -> infoArray["itemType"] = fnFormInput($itemType);
				$objNewItem -> infoArray["itemTypeLabel"] = fnFormInput($itemTypeLabel);
				$objNewItem -> infoArray["nickname"] = fnFormInput($addNickname);
				$objNewItem -> infoArray["orderIndex"] = "99";
				$objNewItem -> infoArray["jsonVars"] = $jsonVars;
				$objNewItem -> infoArray["status"] = "active";
				$objNewItem -> infoArray["dateStampUTC"] = $dtNow;
				$objNewItem -> infoArray["modifiedUTC"] = $dtNow;
				$objNewItem -> fnInsert();

				//flag, reset
				$createdNickname = $addNickname;
				$bolDone = true;
				$addNickname = "";
				$addType = "";
				$addThemeBoxDisplay = "none";
				
			}
			
			
		}//bolPassed
	
	
	}
	
	
	//done adding a new BT_item
	/////////////////////////////////////////////////////////////////////////////////

	
	//list vars
	$search = fnGetReqVal("searchInput", "Nickname...", $myRequestVars);
	$scriptName = "bt_themes.php";
	$recsPerPage = 50;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "I.modifiedUTC";
	$defaultUpDown = "DESC";
	$colCount = 4;

	//sort up / down
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
		//if we clicked a column header
		$sortUpDown = fnGetReqVal("nextUpDown", $sortUpDown, $myRequestVars);
	//sort column
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
		//if we clicked a column header
		$sortColumn = fnGetReqVal("nextCol", $sortColumn, $myRequestVars);
			if($sortColumn == "") $sortColumn = $defaultSort;
	
	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE I.appGuid = '" . $appGuid . "'";
	$whereClause .= " AND controlPanelItemType = 'theme' ";
	$whereClause .= " AND I.status != 'Deleted' ";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "NICKNAME..." && $search != "") ){
		$whereClause .= " AND I.nickname LIKE '%" . $search . "%' ";
		$searchHint = "Nickname contains";
	}
			
	//querystring for links
	$qVars = "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	
	//get total recs.
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
    $strSql = " SELECT I.guid, I.parentItemGuid, I.appGuid, I.controlPanelItemType, I.itemType, I.itemTypeLabel, I.nickname, I.orderIndex, ";
	$strSql .= "I.jsonVars, I.status, I.dateStampUTC, I.modifiedUTC ";
	$strSql .= "FROM " . TBL_BT_ITEMS . " AS I ";
	$strSql .= $whereClause;
	$strSql .= " ORDER BY " . $sortColumn . " " . $sortUpDown;
	$strSql .= " LIMIT " . $firstRec . "," . $recsPerPage;
	
	//shows sort arrow.
	$tmpSort = ($sortUpDown == "ASC") ? 'DESC' : 'ASC' ;
	
	//paging links
	$prevPageLink = "\n<a onClick=\"top.showProgress();\" href='" . $scriptName . "?" . $qVars . "&nextPage=" . ($currentPage - 1) . "' title='Previous Page' target='_self'>< Previous Page</a><span>&nbsp;</span>";
	$nextPageLink = "\n<a onClick=\"top.showProgress();\" href='" . $scriptName . "?" . $qVars . "&nextPage=" . ($currentPage + 1) . "' title='Next Page' target='_self'>Next Page ></a><span>&nbsp;</span>";
	if($firstRec < $recsPerPage) $prevPageLink = "";
	if(($firstRec + $recsPerPage) >= $totalRecs) $nextPageLink = "";
	
	//fix up search
	$tmpSearch = $search;
	if($search == "" || strtoupper($search) == "ALL") $search = "Nickname...";

	//fix up add-nickname
	if($addNickname == "") $addNickname = "Nickname...";
	
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<script>
	function fnAddBT_item(){
		document.getElementById("addButton").disabled = true;
		document.forms[0].command.value = 'addItem';
		document.forms[0].submit();	
	}
</script>


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
               	Themes for <?php echo fnFormOutput($appName, true);?>
           	</div>

            <div style='padding:10px;'>

                   
			   <?php if(strtoupper($command) == "DELETE"){ ?>
                    <div class="errorDiv">
                        <br/>
                        <b>Delete "<?php echo $deleteNickname;?>"</b>
                        <div style='padding-top:5px;'>
                            Are you sure you want to do this? This cannot be undone! When you
                            confirm this operation, all information and content associated with this theme will be permanently removed and you
                            will not be able to get it back - ever. 
                        </div>
                        <div style='padding-top:10px;'>
                            <a href="bt_themes.php?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this theme</a>
                        </div>
                        <div style='padding-top:10px;'>
                            <a href="bt_themes.php?appGuid=<?php echo $appGuid . $qVars;?>&BT_itemId=<?php echo $BT_itemId;?>&command=confirmDelete"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this theme</a>
                        </div>
                    </div>
                <?php } ?>                                    
    
			   <?php if($bolDeleted){ ?>
                    <div class="doneDiv">
                        <b>"<?php echo $deleteNickname;?>"</b> Deleted.
                        <div style='padding-top:10px;'>
                            <a href="bt_themes.php?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                        </div>
                     </div>
                <?php } ?> 
                
                            
                <?php if($bolDone){ ?>
                    <div class='doneDiv'>
                        <b>"<?php echo fnFormOutput($createdNickname);?>" added</b>. 
                        This theme was created with generic properties that need additional configuration.
                        The types of properties you need to configure depend on what type of application you are trying to create.
                        <div style='padding-top:5px;'>
                            <a href="bt_themes.php?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                        </div>
                    </div>
                <?php } ?>                     
                
                <?php if($bolActivated){ ?>
                    <div class='doneDiv' style='margin-top:0px;'>
                        <b>"<?php echo fnFormOutput($activatedNickname);?>"</b> was set as the active theme. 
                        <div style='padding-top:5px;'>
                            <a href="bt_themes.php?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                        </div>
                    </div>
                <?php } ?>                     

                <div id="headerBox" style="display:<?php echo $existingDataDisplay;?>;">
                    <table cellspacing='0' cellpadding='0' width="99%" style='margin-bottom:10px;'>
                        <tr>
                            <td style='vertical-align:middle;'>
        
                                <a href="#" onClick="fnShowHide();return false;" style='vertical-align:middle;'><img src="../../images/plus.png" alt="Add Screen or Menu" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add New Theme</a>
                            
                            </td>
                            <td nowrap style='padding-left:10px;'>
                                <div style='float:right;margin:0px;'>
                                    &nbsp;
                                    <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                </div>
                                <div style='float:right;margin:0px;'>
                                    <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('Nickname...',this);" type='text' value="<?php echo fnFormOutput($search, true);?>" class='searchBox' style='margin:0px;display:inline;vertical-align:middle;overflow:hidden;' onkeyup="document.forms[0].currentPage.value='1';">
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>


               <!-- add new screen -->
               	<div id="addThemeBox" style='padding-bottom:25px;margin-bottom:25px;display:<?php echo $addThemeBoxDisplay;?>;'>
					
					<?php if($strMessage != "" && !$bolDone){ ?>
                        <div class='errorDiv' style='margin-bottom:20px;'>
                            <?php echo $strMessage;?>                                
                        </div>
                    <?php } ?> 
                
                    
                    <div style='padding:5px;padding-left:10px;'>
						
			            <table style='width:100%;' cellspacing='0' cellpadding='0'>
                        	<tr>
                            	<td style='vertical-align:top;'>
                                    <div style='padding-top:0px;'>
                                        <b>Enter a nickname for the new theme</b><br/>
                                        <input type="text" name="addNickname" id="addNickname" value="<?php echo fnFormOutput($addNickname);?>" style="width:250px;" onFocus="top.fnClearSearch('Enter a nickname...',this);"/>
                                    </div>
                                    
                                    <div style='padding-top:15px;'>
                                        <input type='button' title="add" value="add" align='absmiddle' class="buttonSubmit" onClick="fnAddTheme();return false;" />
                                        <input type='button' title="cancel" value="cancel" align='absmiddle' class="buttonCancel" onClick="fnShowHide();return false;" />
                                    </div>
                                    <div id='addThemeMessage' style='padding-top:5px;font-size:9pt;color:red;visibility:hidden;'>
                                    
                                    </div>
                            	</td>
                            	<td style='vertical-align:top;'>

                                </td>
                            </tr>
                        </table>
                        <div style='padding-top:0px;'>&nbsp;</div>
                        		
                	</div>
                </div>
 
                <div id="dataBox" style="display:<?php echo $existingDataDisplay;?>;">

                
                    <table cellspacing='0' cellpadding='0' width="99%">
                        <?php if( strtoupper($search) != "ALL" && strtoupper($search) != "NICKNAME..." && $search != "" ){ //show message?>
                        <tr>
                            <td class='searchDiv' colspan='<?php echo $colCount;?>' style='padding-left:0px;'>
                                <?php if( (strtoupper($search) != "ALL" && strtoupper($search) != "NICKNAME..." && $search != "")){?>
                                    <div>
                                        <span style='color:red;'><?php echo $searchHint;?>:</span> <?php echo fnFormOutput($search, true);?>
                                        &nbsp;&nbsp;                              
                                        <a href="<?php echo $scriptName;?>?unused=true&appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>clear search</a>
                                    </div>
                                <?php } ?>
                             </td>
                        </tr>
                        <?php } ?>
        
                        <tr>
                            
                            <td class="tdSort" style='padding-left:5px;'>
                               <a title="Sort" href="#" onclick="top.fnSort(document, 'I.nickname');return false;">Nickname</a> <?php echo fnSortIcon("I.nickname", $tmpSort, $sortColumn); ?>
                            </td>
                            
                            <td class="tdSort">
                                
                            </td>
                            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'I.modifiedUTC');return false;">Modified</a> <?php echo fnSortIcon("I.modifiedUTC", $tmpSort, $sortColumn); ?>
                            </td>
        
                            <td class="tdSort">
                                
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
                                        $itemType = $row['itemType'];
                                        $itemTypeLabel = $row['itemTypeLabel'];
                                        $nickname = fnFormOutput($row['nickname']);
                                        
                                        $modifiedUTC = fnFromUTC($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                        $modifiedHours = fnDateDiff("h", $modifiedUTC, $dtToday);
                                        $modifiedLabel = fnSinceLabel($modifiedHours, $row['modifiedUTC'], $thisUser->infoArray["timeZone"]);
                                         
                                        //activate / delete
                                        $makeActiveLink = "";
                                        $deleteLink = "";
                                        if($row['orderIndex'] == "0"){
                                            $makeActiveLink = "<span style='color:green;'><i>active</i></span>";
                                            $deleteLink = "";
                                        }else{
                                            $makeActiveLink = "<a href='bt_themes.php?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . "&command=makeActive" . $qVars . "'>activate</a>";
                                            $deleteLink = "<a href='bt_themes.php?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . "&command=delete" . $qVars . "'>delete</a>";
                                        }
        
        
                                        echo "\n\n<tr id='i_" . $BT_itemId . "' class='" . $css . "'>";
                                            echo "\n<td class='data'>";
                                                echo "<a href='bt_theme.php?appGuid=" . $appGuid . "&BT_itemId=" . $BT_itemId . $qVars . "'>" . $nickname . "</a>";
                                            echo "</td>";	
                                            echo "\n<td class='data' style='padding-left:10px;'>";
                                                echo $makeActiveLink;
                                            echo "</td>";	     
                                            echo "\n<td class='data'>" . $modifiedLabel . "</td>";
                                            echo "\n<td class='data' style='text-align:right;padding-right:10px;'>";
                                                echo $deleteLink;
                                            echo "</td>";
                                        echo "\n</tr>";
                                        
                                        
                                    }//end while
                                }//no res
                            }//no records
                
                        ?>
            
                        <tr>
                            <td colspan='<?php echo $colCount;?>'>&nbsp;</td>
                        </tr>
                
                    
                        <?php if($totalRecs > 0){?>
                            <tr>
                                <td colspan='<?php echo $colCount;?>' style='padding-top:5px;text-align:right;vertical-align:top;'>
                                   
                                    <div style='padding:5px;'>
                                        <?php 
                                            echo ($firstRec + 1) . " - " . $lastRec . " of " . $totalRecs;
                                            if($totalRecs > $recsPerPage){
                                                echo "<span>&nbsp;</span>";
                                                echo $prevPageLink . $nextPageLink;
                                            }
                                        ?>
                                    </div>
                                     
                                </td>
                            </tr>
                        <?php }else{ ?>
                            <tr>
                                <td colspan='<?php echo $colCount;?>'>
        
                                    <div class='infoDiv'>
                                        <b>No Themes found?</b> If you are searching for a theme by nickname, 
                                        try changing your search criteria. If you have not configured any themes for this app, 
                                        start by "Adding a Theme" above.                                    
                                    </div>
        
                                </td>
                            </tr>
                        <?php } ?>
                        
                    </table>
        
        
                    <div class='infoDiv'>
                        <b>Themes allow you to make global changes</b> to all screens in the app.
                        You can create as many themes as you want. Experiement by creating new themes
                        then clicking the "activate" link. Only one theme can be active at a time and
                        you can change the active theme anytime.
                        <div style='padding-top:5px;'>
                            <b>Override</b> the theme by making adjustments to the advanced properties on individual screens.
                            Exmaple: You may want all the screen in the app to share a global background color with the exception
                            of one screen. In this case, adjust the background color property on that screen to override the theme.
                        </div>
                    </div>
				
                
                </div>
         
         </div>
       </div> 
    </fieldset>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
