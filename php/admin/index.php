<?php   require_once("../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	/* if we have a guid we are logged in, re-set session var in this directory */
	if($guid != "")	$_SESSION[APP_LOGGEDIN_COOKIE_NAME] = $guid;

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Admin Control Panel | Application List";

	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();
	
	//vars
	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$status = fnGetReqVal("status", "", $myRequestVars);
	$doWhat = fnGetReqVal("doWhat", "", $myRequestVars);
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;

	//viewStyle
	$viewStyle = "listView";
	$nextViewStyle = fnGetReqVal("nextViewStyle", "", $myRequestVars);
		if(strlen($nextViewStyle) > 1) $viewStyle = $nextViewStyle;
			if($viewStyle == "") $viewStyle = "listView";
			
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
	//change app owner
	$newOwnerGuid = fnGetReqVal("newOwnerGuid", "", $myRequestVars);
	if($newOwnerGuid != "" && count($selectedIds) > 0){
		$tmp = "UPDATE " . TBL_APPLICATIONS . " SET ownerGuid = '" . $newOwnerGuid . "'";
		$tmp .= " WHERE guid IN (" . $inClauseSQL . ")";
		fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		$bolDone = true;
		$strMessage = "Application owner updated for " . count($selectedIds) . " Application(s)";
		$selectedIds = array();
	}
	//end change app owner
	///////////////////////////////////////////////////////////////



	$scriptName = "index.php";
	$recsPerPage = 50;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "A.id";
	$defaultUpDown = "DESC";
	$colCount = 7;

	//sort up / down
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
		//if we clicked a column header
		$sortUpDown = fnGetReqVal("nextUpDown", $sortUpDown, $myRequestVars);
	//sort column
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
		//if we clicked a column header
		$sortColumn = fnGetReqVal("nextCol", $sortColumn, $myRequestVars);
			if($sortColumn == "") $sortColumn = $defaultSort;
				if(substr($sortColumn, 0, 1) != "A"){
					$sortColumn = "A.id";
				}
	
	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE A.id > 0 AND A.status != 'deleted' ";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "") ){
		//if is numeric, search number of views
		if(is_numeric($search)){
			$whereClause .= " AND A.viewCount >= '" . $search . "' ";
			$searchHint = "<span style='color:red;'>You are searching</span> for apps with at least <b>" . $search . " views</b>";
		}else{
			if(strlen($search) == 1){ // clicked a letter for "last name"
				$whereClause .= " AND A.name LIKE '" . $search . "%'";
				$searchHint = "<span style='color:red;'>You are searching</span> for apps with a <b>name</b> that starts with <b>\"" . $search . "\"</b>";
			}else{
				$whereClause .= " AND A.name LIKE '%" . $search . "%' ";
				$searchHint = "<span style='color:red;'>You are searching</span> for apps with a <b>name</b> that contains <b>\"" . $search . "\"</b>";
			} //is one letter
		}//is numeric
	}
	
	//querystring for links
	$qVars = "&from=admin&status=" . $status . "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	
	//JOIN CLAUSE. 
	$join = "";
	$join .= " INNER JOIN " . TBL_USERS . " AS U ON A.ownerGuid = U.guid ";
	
	//get total recs.
	$totalSql = "  SELECT Count(A.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_APPLICATIONS . " AS A ";
	//IGNORE join on count becuase no User Fields are in WHERE. Much faster this way!
	//$totalSql .= $join;
	//append where
	$totalSql .= $whereClause;
		
	//echo $totalSql;
		
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
    $strSql = " SELECT A.id, A.guid, A.currentPublishVersion, A.name, A.iconUrl, A.status, A.dateStampUTC, A.modifiedUTC, A.currentPublishDate, ";
	$strSql .= " A.viewCount, U.guid AS ownerGuid, U.firstName, U.lastName, U.email ";
	$strSql .= "FROM " . TBL_APPLICATIONS . " AS A ";
	$strSql .= $join;
	$strSql .= $whereClause;
	if($sortColumn != ""){
		$strSql .= " ORDER BY " . $sortColumn . " " . $sortUpDown;
	}
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
	if($search == "" || strtoupper($search) == "ALL") $search = "search...";
	
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="recsPerPage" id="recsPerPage" value="<?php echo $recsPerPage;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="totalPages" id="totalPages" value="<?php echo $totalPages;?>">
<input type="hidden" name="firstRec" id="firstRec" value="<?php echo $firstRec;?>">
<input type="hidden" name="lastRec" id="lastRec" value="<?php echo $lastRec;?>">
<input type="hidden" name="totalRecs" id="totalRecs" value="<?php echo $totalRecs;?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">
<input type="hidden" name="status" id="status" value="<?php echo $status;?>">
<input type="hidden" name="command" id="command" value="<?php echo $command;?>">

<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- left side--> 
        <div class='boxLeft'>
            <div class='contentBox colorDarkBg minHeight'>
                <div class='contentBand colorBandBg'>
                    Admin Options
                </div>
                <div id="leftNavLinkBox" style='padding:10px;white-space:nowrap;'>
					<?php echo $thisPage->fnGetControlPanelLinks("admin", "", "block", ""); ?>
				</div>
             </div>
        </div>
        
        <!-- right side--> 
        <div class='boxRight'>
        	<div class='contentBox colorLightBg minHeight'>
                    
                <div class='contentBand colorBandBg'>
                  	Application List
                </div>
                
                <div style='padding:10px;padding-right:0px;'>

                    <table cellspacing='0' cellpadding='0' style='width:99%;'>
                        <tr>
                            <td style='vertical-align:middle;'>
                                <a href="<?php echo fnGetSecureURL(APP_URL);?>/bt_v15/bt_app/bt_appNew.php?from=admin" title="Create New Application" style='vertical-align:middle;white-space:nowrap;'><img src="../images/plus.png" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Create New Application</a>
                            	&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="<?php echo $scriptName;?>?unused=true<?php echo $qVars;?>&nextViewStyle=listView" title="List View" style='vertical-align:middle;white-space:nowrap;'><img src="../images/list.png" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>List View</a>
                            	&nbsp;&nbsp;&nbsp;&nbsp;
                                <a href="<?php echo $scriptName;?>?unused=true<?php echo $qVars;?>&nextViewStyle=gridView" title="Grid View" style='vertical-align:middle;white-space:nowrap;'><img src="../images/grid.png" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Grid View</a>
                            </td>
                            <td nowrap style='white-space:nowrap;text-align:right;vertical-align:top;padding:0px;'>
                                <div style='display:inline;float:right;margin:0px;'>
                                    <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;" style="display:inline;">
                                </div>
                                <div style='display:inline;float:right;margin:0px;margin-right:2px;'>
                                    <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('search...',this);" type='text' value="<?php echo fnFormOutput($search);?>" class='searchBox' style='vertical-align:middle;overflow:hidden;display:inline;' onkeyup="document.forms[0].currentPage.value='1';">
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    
                    <!--list view-->
                    <?php if(strtoupper($viewStyle) == "LISTVIEW"){ ?>
                    <table cellspacing='0' cellpadding='0' style='width:99%;'>
                        <tr>
                            <script>top.fnWriteAlphabet(document, "<?php echo $search;?>");</script>
                        </tr>
                    </table>
                    
                    <?php if(strtoupper($doWhat) == "CHANGEAPPOWNER" && count($selectedIds) > 0){ ?>
						<div style='padding:10px;'>
                            <div style='padding-top:5px;'>
                            	<b>Change application owner?</b>
                            </div>
                            <div style='padding-top:5px;'>
                            	You selected <b><?php echo count($selectedIds);?> Application(s)</b> to change the owner for. 
                                Select the new owner from the drop-down list then click Submit.
                            </div>
                            
                            <div style='padding-top:5px;'>
                            	<select name="newOwnerGuid" id="newOwnerGuid" style="width:200px;">
                                	<option value="">--select new owner--</option>
                                    <?php
										$tmp = "SELECT guid, firstName, lastName, userType FROM " . TBL_USERS . " WHERE status != 'deleted'";
										$res2 = fnDbGetResult($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	                                    if($res2){
                                            while($row = mysql_fetch_array($res2)){
												echo "\n<option value='" . $row["guid"] . "'>" . fnFormOutput($row["lastName"] . ", " . $row["firstName"]) . " (" . fnFormatProperCase($row["userType"]) . ")</option>";
											}
										}								
									?>
                            	</select>
                            </div>
                            
                            <div style='padding-top:5px;'>
                                <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                                <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo $scriptName;?>?unused=true<?php echo $qVars;?>';">
                            </div>                                    
                        
                        </div>

					<?php } ?>              
                    
                    <table cellspacing='0' cellpadding='0' style='width:99%;'>
                        
                        
						<?php if($bolDone && strlen($strMessage) > 0){ ?>
                            <tr>
                            	<td colspan='<?php echo $colCount;?>'>
                            		<div class='doneDiv'>
                                		<?php echo $strMessage;?>
                                        <div style='padding-top:5px;'>
                                        	<a href='<?php echo $scriptName;?>?unused=true<?php echo $qVars;?>'><img src='../images/arr_right.gif' alt="pointer"/>OK, hide this message</a>
                                        </div>
                            		</div>
                                </td>
                            </tr>                    
                        <?php } ?>
                    
                        <tr>
                            <td colspan='<?php echo $colCount;?>' style='padding-left:0px;padding-top:10px;padding-bottom:10px;'>
                            	<?php echo $searchHint;?>
                            </td>
                        </tr>
                
                        <tr>
                            
                            <td class="tdSort" style='padding-left:5px;'>
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'A.name');return false;">Application</a> <?php echo fnSortIcon("A.name", $tmpSort, $sortColumn); ?>
                            </td>
                            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'A.currentPublishVersion');return false;">Version</a> <?php echo fnSortIcon("A.currentPublishVersion", $tmpSort, $sortColumn); ?>
                            </td>

                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'U.lastName');return false;">Owner</a> <?php echo fnSortIcon("U.lastName", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'A.id');return false;">Created</a> <?php echo fnSortIcon("A.id", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">&nbsp;
                                
                            </td>

                            <td class="tdSort" style='text-align:center;'>
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'A.viewCount');return false;">Views</a> <?php echo fnSortIcon("A.viewCount", $tmpSort, $sortColumn); ?>
                            </td>
                            <td class="tdSort" style='text-align:right;vertical-align:top;padding:0px;padding-right:5px;' >
                                <input id="checkAll" type='checkbox' style="width:12px;height:12px;margin:0px;display:inline;" onclick="top.fnCheckAll(self);">
                            </td>
                            
                        </tr>
                        <tr>
                            <td colspan='<?php echo $colCount;?>'>&nbsp;</td>
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
                                            
                                            $appGuid = $row['guid'];
                                            $appName = fnFormOutput($row['name']);
                                            $ownerName = fnFormOutput($row['lastName'] . ", " . $row['firstName']);
                                            $ownerEmail = fnFormOutput($row['email']);
                                            
                                            $createdDate = fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $createdHours = fnDateDiff("h", $createdDate, $dtToday);
                                            $createdLabel = fnSinceLabel($createdHours, $row['dateStampUTC'], $thisUser->infoArray["timeZone"]);
                                            
                                            //shorten app name if needed...
                                            if(strlen($appName) > 35){
                                                $appName = substr($appName, 0, 35) . "...";
                                            }

                                            //link to application control panel...
                                            $cpLink = "<a href='" . fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $row['guid'] . "' title='App Control Panel'>" . $appName . "</a>";

                                            if(strlen($ownerName) > 30){
                                                $ownerName = substr($ownerName, 0, 20) . "...";
                                            }
											
											//current version...
											$currentPublishVersion = $row["currentPublishVersion"];
                                            if($currentPublishVersion == ""){
												$currentPublishVersion = "1.0";
											}
											
                                            echo "\n\n<tr id='r_" . $appGuid . "' class='" . $css . "'>";
                                                echo "\n<td class='data'>" .  $cpLink . "</td>";
                                                echo "\n<td class='data'>" . $currentPublishVersion . "</td>";
                                                echo "\n<td class='data'><a href='user_details.php?unused=true" . $qVars . "&userGuid=" . $row['ownerGuid'] . "'>" . $ownerName . "</a></td>";
                                                echo "\n<td class='data'>" . $createdLabel . "</td>";
                                                echo "\n<td class='data'>&nbsp;</td>";
                                                echo "\n<td class='data' style='text-align:center;padding-left:1px;padding-right:1px;'>" . $row['viewCount'] . "</td>";
                                                echo "\n<td class='data' style='padding:0px;padding-right:5px;text-align:right;'><input id='selected[]' name='selected[]' type='checkbox' style='height:12px;width:12px;margin:0px;' value='" . $appGuid . "' " . fnIsChecked($appGuid) . "></td>";
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
                                        <select id='doWhat' name='doWhat' onChange="document.forms[0].submit();" style='vertical-align:middle;width:250px;' align='absmiddle'>
                                            <option value="">&nbsp;Actions menu...</option>
                                            <option value="changeAppOwner">&nbsp;Change application owner</option>
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
                        
                        <?php }else{ ?>
                            <tr>
                                <td colspan='<?php echo $colCount;?>' style='padding:15px;padding-top:0px;'>
                                    There are no applications to display. If you searched, consider changing your search criteria.
                                </td>
                            </tr>
                        <?php } ?>
            
                    </table>
                    <?php } ?>
                    <!--list view-->
                
                    <!--grid view-->
                    <?php if(strtoupper($viewStyle) == "GRIDVIEW"){ ?>

                        <div colspan='<?php echo $colCount;?>' style='padding-left:0px;padding-top:10px;padding-bottom:10px;'>
                            <?php echo $searchHint;?>
                        </div>

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
                                        
										$created = fnFromUtc($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
										$modified = fnFromUtc($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
										
										$currentPublishDate = $modified;
										if($row['currentPublishDate'] != "" && $row['currentPublishDate'] != "0000-00-00 00:00:00"){
											$currentPublishDate = fnFromUTC($row["currentPublishDate"], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
										}
                                        
										$ownerName = fnFormOutput($row['lastName'] . ", " . $row['firstName']);
										$iconUrl = $row['iconUrl'];	
										
										//default icon...
										if($iconUrl == ""){
											$iconUrl = fnGetSecureURL(APP_URL) . "/images/default_app_icon.png";
										}
										
										//make sure icon URL is secure...
										$iconUrl = fnGetSecureURL($iconUrl);
										
										//link to application control panel...
										$cpUrl = fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $row['guid'];
										
										if(strlen($ownerName) > 30){
											$ownerName = substr($ownerName, 0, 20) . "...";
										}
                                            
										//current version...
										$currentPublishVersion = $row["currentPublishVersion"];
										if($currentPublishVersion == ""){
											$currentPublishVersion = "1.0";
										}
											
											
										
										echo "\n<div class='appDiv colorDarkBg' title='App Control Panel' onclick=\"document.location.href='" . $cpUrl . "';\" style='cursor:pointer;'>";
											echo "\n<table class='appTable' cellspacing='0' cellpadding='0'>";
												echo "\n<tr>";
													echo "\n<td style='vertical-align:top;padding:0px;'>";
														echo "\n<div class='iconOuter'>";
																echo "\n<div id=\"iconImage\" class=\"iconImage\" style=\"background:url('" . $iconUrl . "');background-position:50% 50%;background-repeat:no-repeat;\">";
																	echo "<img src=\"" . fnGetSecureURL(APP_URL) . "/images/blank.gif\" alt=\"app icon\" />";
																echo "</div>";
																echo "\n<div id=\"iconOverlay\" class=\"iconOverlay\">";
																	echo "&nbsp;";
																echo "</div>";
														echo "</div>";
													echo "\n</td>";
													echo "\n<td style='white-space:nowrap;vertical-align:top;padding:5px;padding-left:10px;padding-top:0px;'>";
														echo "\n<b>" . fnFormOutput($row['name']) . "</b>";
														echo "\n<br/>owner: <a href='user_details.php?unused=true" . $qVars . "&userGuid=" . $row['ownerGuid'] . "'>" . $ownerName . "</a>";
														echo "\n<br/>modified: " . $modified;
														echo "\n<br/>published: " . $currentPublishDate;
														echo "<br/>vers: " . $currentPublishVersion . " views: " . $row['viewCount'];
													echo "\n</td>";
												echo "\n</tr>";
											echo "\n</table>";
										echo "\n</div>";
										
                                        
                                    }//while
                                }//res
                            }//totalRecs
                        ?>
                        <div style='clear:both;'>&nbsp;</div>
                        <div style='padding:5px;text-align:left;'>
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
                        
                    <?php } //gridView ?>
                
                    <!--grid view-->
                </div>
                    
			
            
            
                    
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>




