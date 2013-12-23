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
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";	

	//add some inline css (in the <head>) for 100% width...
	$inlineCSS = "";
	$inlineCSS .= "html{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= "body{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= ".contentWrapper, .contentWrap{height:100%;width:100%;margin:0px;padding:0px;} ";
	$thisPage->cssInHead = $inlineCSS;

	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$formElVal = fnGetReqVal("formElVal", "", $myRequestVars);
	$formElLabel = fnGetReqVal("formElLabel", "", $myRequestVars);
	
	//screenType used to filter by "splash", "menu", or "screen"...
	$screenType = fnGetReqVal("screenType", "", $myRequestVars);

	$command = fnGetReqVal("command", "", $myRequestVars);
	
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
	
	//list vars
	$search = fnGetReqVal("searchInput", "Nickname...", $myRequestVars);
	$scriptName = "bt_pickerScreen.php";
	$recsPerPage = 15;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "I.modifiedUTC";
	$defaultUpDown = "DESC";
	$colCount = 3;

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
	$whereClause .= " AND I.controlPanelItemType = 'screen' ";
	$whereClause .= " AND I.status != 'Deleted' ";
	
	//screen type...
	if(strtoupper($screenType) == "SPLASHSCREEN"){
		$whereClause .= " AND I.itemTypeLabel LIKE '%Splash%' ";
	}
	
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "NICKNAME..." && $search != "") ){
		$whereClause .= " AND I.nickname LIKE '%" . $search . "%' ";
		$searchHint = "Nickname contains";
	}

			
	//querystring for links
	$qVars = "&appGuid=" . $appGuid . "&formElVal=" . $formElVal . "&formElLabel=" . $formElLabel . "&screenType=" . $screenType;
	$qVars .= "&searchInput=" . fnFormOutput($search);
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
    $strSql = " SELECT I.guid, I.parentItemGuid, I.appGuid, I.itemType, I.itemTypeLabel, I.nickname, I.orderIndex, ";
	$strSql .= "I.jsonVars, I.status, I.dateStampUTC, I.modifiedUTC ";
	$strSql .= "FROM " . TBL_BT_ITEMS . " AS I ";
	$strSql .= $whereClause;
	$strSql .= " ORDER BY " . $sortColumn . " " . $sortUpDown;
	$strSql .= " LIMIT " . $firstRec . "," . $recsPerPage;
	
	//shows sort arrow.
	$tmpSort = ($sortUpDown == "ASC") ? 'DESC' : 'ASC' ;
	
	//paging links
	$prevPageLink = "\n<a href='" . $scriptName . "?unused=true" . $qVars . "&nextPage=" . ($currentPage - 1) . "' title='Previous Page' target='_self'>< Previous Page</a><span>&nbsp;</span>";
	$nextPageLink = "\n<a href='" . $scriptName . "?unused=true" . $qVars . "&nextPage=" . ($currentPage + 1) . "' title='Next Page' target='_self'>Next Page ></a><span>&nbsp;</span>";
	if($firstRec < $recsPerPage) $prevPageLink = "";
	if(($firstRec + $recsPerPage) >= $totalRecs) $nextPageLink = "";
	
	//fix up search
	$tmpSearch = $search;
	if($search == "" || strtoupper($search) == "ALL") $search = "Nickname...";

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	
?>

<script type='text/javascript'>
	function selectScreen(theScreenId, theNickname){
		try{
			parent.document.forms[0].<?php echo $formElVal;?>.value = theScreenId;
			parent.document.forms[0].<?php echo $formElLabel;?>.value = theNickname;
			parent.Shadowbox.close();
		}catch(er){
		}
	}
</script>



<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="recsPerPage" id="recsPerPage" value="<?php echo $recsPerPage;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="totalPages" id="totalPages" value="<?php echo $totalPages;?>">
<input type="hidden" name="firstRec" id="firstRec" value="<?php echo $firstRec;?>">
<input type="hidden" name="lastRec" id="lastRec" value="<?php echo $lastRec;?>">
<input type="hidden" name="totalRecs" id="totalRecs" value="<?php echo $totalRecs;?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">
<input type="hidden" name="command" id="command" value="">
<input type="hidden" name="formElVal" id="formElVal" value="<?php echo $formElVal;?>">
<input type="hidden" name="formElLabel" id="formElLabel" value="<?php echo $formElLabel;?>">
<input type="hidden" name="screenType" id="screenType" value="<?php echo $screenType;?>">


<div class='content'>
        
    <fieldset class='colorLightBg minHeightShadowbox'>
        
       	<div class='contentBox colorLightBg' style='-moz-border-radius:0px;border-radius:0px;border-bottom:0px;'>
        
            <div class="contentBand colorBandBg" style='-moz-border-radius:0px;border-radius:0px;'>
                    Select a Screen
            </div>
            <div style='padding:10px;'>
                        
                <div>
                   <div style='float:left;'>
                        When you make a selection this window will close.
                    </div>
                    
                    <div style='white-space:nowrap;'>
                        <div style='float:right;margin:0px;'>
                            &nbsp;
                            <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;">
                            
                        </div>
                        <div style='float:right;margin:0px;'>
                            <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('Nickname...',this);" type='text' value="<?php echo fnFormOutput($search);?>" class='searchBox' style='vertical-align:middle;overflow:hidden;' onkeyup="document.forms[0].currentPage.value='1';">
                        </div>
                    </div>
                    <div style='clear:both;'></div>
                </div>
                          
                          
                          
                        <table cellspacing='0' cellpadding='0' width="100%">
                            <?php if( strtoupper($search) != "ALL" && strtoupper($search) != "NICKNAME..." && $search != ""){ //show message?>
                            <tr>
                                <td class='searchDiv' colspan='<?php echo $colCount;?>'>
                                    <?php if( (strtoupper($search) != "ALL" && strtoupper($search) != "NICKNAME..." && $search != "")){?>
                                        <div style='margin-right:10px;'>
                                            <span style='color:red;'><?php echo $searchHint;?>:</span> "<?php echo fnFormOutput($search);?>"
                                            &nbsp;&nbsp;                              
                                            <a href="<?php echo fnGetSecureURL(APP_URL);?>/bt_v15/bt_app/bt_pickerScreen.php?appGuid=<?php echo $appGuid;?>&formElVal=<?php echo $formElVal;?>&formElLabel=<?php echo $formElLabel;?>"><img src="../../images/arr_right.gif" alt='arrow'/>clear search</a>
                                        </div>
                                    <?php } ?>
                                 </td>
                            </tr>
                            <?php } ?>
                            
    
                            <?php if($totalRecs > 0){ ?>
                            <tr>
                                
                                <td class="tdSort">
                                   <a title="Sort" class="bold" href="#" onclick="top.fnSort(document, 'I.nickname');return false;">Nickname</a> <?php echo fnSortIcon("I.nickname", $tmpSort, $sortColumn); ?>
                                </td>
                                
                                <td class="tdSort">
                                    <a title="Sort" class="bold" href="#" onclick="top.fnSort(document, 'I.itemTypeLabel');return false;">Screen / Plugin Type</a> <?php echo fnSortIcon("I.itemTypeLabel", $tmpSort, $sortColumn); ?>
                                </td>
                
                                <td class="tdSort">
                                    <a title="Sort" class="bold" href="#" onclick="top.fnSort(document, 'I.modifiedUTC');return false;">Modified</a><?php echo fnSortIcon("I.modifiedUTC", $tmpSort, $sortColumn); ?>
                                </td>
                 
                            </tr>
                            <tr>
                                <td colspan="<?php echo $colCount;?>">&nbsp;</td>
                            </tr>
                            <?php } ?>                        
                            
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
                                            
                                            $modifiedUTC = fnFromUTC($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $modifiedHours = fnDateDiff("h", $modifiedUTC, $dtToday);
                                            $modifiedLabel = fnSinceLabel($modifiedHours, $row['modifiedUTC'], $thisUser->infoArray["timeZone"]);
                                             
    
                                            $pad = "&nbsp;&nbsp;|&nbsp;&nbsp;";
                                            
                                            echo "\n\n<tr id='i_" . $row['guid'] . "' class='" . $css . "'>";
                                                echo "\n<td class='data' style='padding-left:10px;'>";
                                                    echo  "<a href='#' onClick=\"selectScreen('" . $BT_itemId . "', '" . str_replace("'", "\\'", $nickname) . "');return false\">" . $nickname . "</a>";
											    echo "</td>";
                                                echo "\n<td class='data' style='padding-left:10px;'>" . $itemTypeLabel . "</td>";
                                                echo "\n<td class='data' style='padding-left:10px;'>" . $modifiedLabel . "</td>";
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
                                    <td colspan='<?php echo $colCount;?>' style='text-align:right;vertical-align:top;'>
                                       
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
                                            <b>No screens to select from?</b>                                     
                                        </div>
    
    
                                    </td>
                                </tr>
                            <?php } ?>
                            
                            
                        </table>
                        
                        
            </div>
    	</div>
        
    </fieldset>
    
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
