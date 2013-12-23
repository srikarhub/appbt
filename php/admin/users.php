<?php   require_once("../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Admin Control Panel | Control Panel Users";
	
	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();
	
	
	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;

	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$status = fnGetReqVal("status", "", $myRequestVars);
	
	///////////////////////////////////////////////////////////////
	//selected id's
	$selectedIds = array();
	$inClauseSQL = "";
	if(isset($_POST['selected'])){
		while (list ($key, $val) = each ($_POST['selected'])) { 
			if($val != ""){
				$selectedIds[] = trim($val);
				$inClauseSQL .= trim($val) . ",";
			}
		}
		$inClauseSQL = fnRemoveLastChar($inClauseSQL, ",");
	}
	//end selected id's
	
	function fnIsChecked($theId){
		global $selectedIds;
		if(in_array($theId, $selectedIds)){
			return "checked";
		}else{
			return "";
		}	
	}
	$doWhat = fnGetReqVal("doWhat", "", $myRequestVars);
	if($doWhat != "" && count($selectedIds) > 0){
		$tmpSql = "";
	}
	///////////////////////////////////////////////////////////////
	
	//vars for data grid
	$scriptName = "users.php";
	$recsPerPage = 50;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "U.dateStampUTC";
	$defaultUpDown = "DESC";
	$colCount = 8;

	//sort up / down
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
		//if we clicked a column header
		$sortUpDown = fnGetReqVal("nextUpDown", $sortUpDown, $myRequestVars);
	//sort column
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
		//if we clicked a column header
		$sortColumn = fnGetReqVal("nextCol", $sortColumn, $myRequestVars);
			if($sortColumn == "") $sortColumn = $defaultSort;
				if(substr($sortColumn, 0, 1) != "U"){
					$sortColumn = "U.dateStampUTC";
				}

	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE U.hideFromControlPanel = 0 AND status != 'Deleted' ";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "") ){
	
		if(is_numeric($search)){
			$whereClause .= " AND pageRequests >= '" . $search . "' ";
			$searchHint = "<span style='color:red;'>You are searching</span> for users with at least <b>" . $search . " page requests</b>";
		}else{
			
			if(strlen($search) == 1){ // clicked a letter for "last name"
				$whereClause .= " AND U.lastName LIKE '" . $search . "%'";
				$searchHint = "<span style='color:red;'>You are searching</span> for users with a <b>last name</b> that starts with <b>\"" . $search . "\"</b>";
			}else{
				
				//if we have an apersand in the search look in email.
				$searchEmail = false; 
				$Epos = strpos($search, "@");
				if ($Epos === false) {
					$searchEmail = false;
				} else {
					$searchEmail = true;
				}			
							
				if($searchEmail){
					$whereClause .= " AND U.email LIKE '%" . $search . "%' ";
					$searchHint = "<span style='color:red;'>You are searching</span> for users with an <b>email address</b> that contains with <b>\"" . $search . "\"</b>";
				}else{
					$whereClause .= " AND U.lastName LIKE '%" . $search . "%' ";
					$searchHint = "<span style='color:red;'>You are searching</span> for users with a <b>last name</b> that contains with <b>\"" . $search . "\"</b>";
				}
			}//search length = 1
		
		}
	
	}
	
	//querystring for links
	$qVars = "&from=admin&status=" . $status . "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	
	//get total recs.
	$totalSql = "  SELECT Count(U.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_USERS . " AS U ";
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
    $strSql = " SELECT U.id, U.guid AS userGuid, U.firstName, U.lastName, U.email, U.status, U.dateStampUTC, U.userType, ";
	$strSql .= "U.lastPageRequest, U.isLoggedIn, U.pageRequests, ";
	$strSql .= " (SELECT COUNT(id) FROM " . TBL_APPLICATIONS . " WHERE ownerGuid = U.guid AND status != 'deleted') AS countOfApps ";
	$strSql .= "FROM " . TBL_USERS . " AS U ";
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
                   Control Panel Users
                </div>
                
                <div style='padding:10px;padding-right:0px;'>
                    <table cellspacing='0' cellpadding='0' style='width:99%;'>
                        <tr>
                            <td style='vertical-align:middle;'>
                                <a href="user_add.php" alt="Add New User" style='vertical-align:middle;'><img src="../images/plus.png" alt="Add New User" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add New User</a>
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
                    
                    <table cellspacing='0' cellpadding='0' style='width:99%;'>
                        <tr>
                            <script>top.fnWriteAlphabet(document, "<?php echo $search;?>");</script>
                        </tr>
                    </table>
                    
                    <table cellspacing='0' cellpadding='0' width="99%">
                        
                    
                        <tr>
                            <td colspan='<?php echo $colCount;?>' style='padding-left:0px;padding-top:10px;padding-bottom:10px;'>
                            	<?php echo $searchHint;?>
                            </td>
                        </tr>
                
                        <tr>
                            
                            <td class="tdSort" style='padding-left:5px;'>
                               <a title="Sort" href="#" onclick="top.fnSort(document, 'U.lastName');return false;">Last, First</a> <?php echo fnSortIcon("U.lastName", $tmpSort, $sortColumn); ?>
                            </td>
                            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'U.userType');return false;">Type</a> <?php echo fnSortIcon("U.userType", $tmpSort, $sortColumn); ?>
                            </td>

                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'U.email');return false;">Email</a> <?php echo fnSortIcon("U.email", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'U.dateStampUTC');return false;">Created</a> <?php echo fnSortIcon("U.dateStampUTC", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'U.lastPageRequest');return false;">Last Page View</a> <?php echo fnSortIcon("U.lastPageRequest", $tmpSort, $sortColumn); ?>
                            </td>

                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'pageRequests');return false;"># Page Views</a> <?php echo fnSortIcon("pageRequests", $tmpSort, $sortColumn); ?>
                            </td>

                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'countOfApps');return false;"># Apps</a> <?php echo fnSortIcon("countOfApps", $tmpSort, $sortColumn); ?>
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
                                            
                                                
                                            $userGuid = $row['userGuid'];
                                            $userName = fnFormOutput($row['lastName'] . ", " . $row['firstName']);
                                            if(strlen($userName) > 20){
                                                $userName = substr($userName, 0, 20) . "...";
                                            }

                                            $userEmail = fnFormOutput($row['email']);
                                            if(strlen($userEmail) > 30){
                                                $userEmail = substr($userEmail, 0, 30) . "...";
                                            }
                                            $userType = fnFormOutput($row['userType']);
                                            
                                            $createdDate = fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $createdHours = fnDateDiff("h", $createdDate, $dtToday);
                                            $createdLabel = fnSinceLabel($createdHours, $row['dateStampUTC'], $thisUser->infoArray["timeZone"]);

                                            $lastRequestDate = fnFromUTC($row['lastPageRequest'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $requestHours = fnDateDiff("h", $lastRequestDate, $dtToday);
                                            $lastRequestLabel = fnSinceLabel($requestHours, $row['lastPageRequest'], $thisUser->infoArray["timeZone"]);
                                            
                                            //link to profile
                                            if(strlen($userName) > 30){
                                                $userName = substr($userName, 0, 20) . "...";
                                            }
                                            
                                            $userLink = "<a href='user_details.php?unused=true" . $qVars . "&userGuid=" . $row['userGuid'] . "'>" . $userName . "</a>";

                                            echo "\n\n<tr id='r_" . $userGuid . "' class='" . $css . "'>";
                                                echo "\n<td class='data'>" .  $userLink . "</td>";
                                                echo "\n<td class='data'>" .  $userType . "</td>";
                                                echo "\n<td class='data'>" . $userEmail . "</td>";
                                                echo "\n<td class='data'>" . $createdLabel . "</td>";
                                                echo "\n<td class='data'>" . $lastRequestLabel . "</td>";
                                                echo "\n<td class='data' style='text-align:center;padding-left:1px;padding-right:1px;'>" . $row['pageRequests'] . "</td>";
                                                echo "\n<td class='data' style='text-align:center;padding-left:1px;padding-right:1px;'>" . $row['countOfApps'] . "</td>";
                                                echo "\n<td class='data' style='padding:0px;padding-right:5px;text-align:right;'><input id='selected[]' name='selected[]' type='checkbox' style='height:12px;width:12px;margin:0px;' value='" . $row["id"] . "' " . fnIsChecked($row["id"]) . "></td>";
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
                                        <select id='doWhat' name='doWhat' onChange="" style='vertical-align:middle;width:250px;' align='absmiddle'>
                                            <option value="">&nbsp;Actions menu...</option>
                                            <option value="">&nbsp;no global options available</option>
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
                                    There are no users to display. If you searched, consider changing your search criteria.
                                </td>
                            </tr>
                        <?php } ?>
            
                    </table>
                    

                </div>
                
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>






