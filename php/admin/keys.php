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
	$thisPage->pageTitle = "Admin Control Panel | Manage Data Access";
	
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
	$scriptName = "keys.php";
	$recsPerPage = 50;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "K.dateStampUTC";
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
				if(substr($sortColumn, 0, 1) != "K"){
					$sortColumn = "K.dateStampUTC";
				}

	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE K.status != 'Deleted' ";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "") ){
	
		if(is_numeric($search)){
			$whereClause .= " AND requestCount >= '" . $search . "' ";
			$searchHint = "<span style='color:red;'>You are searching</span> for Control Panel Id's  with at least <b>" . $search . " requests</b>";
		}else{
			
			if(strlen($search) == 1){ // clicked a letter for "last name"
				$whereClause .= " AND (K.ownerName LIKE '" . $search . "%' OR K.apiKey LIKE '" . $search . "%')";
				$searchHint = "<span style='color:red;'>You are searching</span> for Control Panel Id's with an <b>owner name</b> or <b>API key</b> that starts with <b>\"" . $search . "\"</b>";
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
					$whereClause .= " AND K.email LIKE '%" . $search . "%' ";
					$searchHint = "<span style='color:red;'>You are searching</span> for Control Panel Id's keys with an <b>owner email</b> that contains with <b>\"" . $search . "\"</b>";
				}else{
					$whereClause .= " AND (K.ownerName LIKE '" . $search . "%' OR K.apiKey LIKE '" . $search . "%')";
					$searchHint = "<span style='color:red;'>You are searching</span> for Control Panel Id's keys with an <b>owner name</b> or <b>API key</b> that contains with <b>\"" . $search . "\"</b>";
				}
			}//search length = 1
		
		}
	
	}
	
	//querystring for links
	$qVars = "&from=admin&status=" . $status . "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	
	//get total recs.
	$totalSql = "  SELECT Count(K.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_API_KEYS . " AS K ";
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
    $strSql = " SELECT K.id, K.guid, K.apiKey, K.ownerName, K.email, K.expiresDate, K.dateStampUTC, ";
	$strSql .= " K.requestCount, K.status, K.lastRequestUTC  ";
	$strSql .= "FROM " . TBL_API_KEYS . " AS K ";
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
                   Manage Data Access
                </div>
                
                <div style='padding:10px;padding-right:0px;'>
                    <table cellspacing='0' cellpadding='0' style='width:99%;'>
                        <tr>
                            <td style='vertical-align:middle;'>
                                <a href="key_add.php" alt="Add New Control Panel Id" style='vertical-align:middle;'><img src="../images/plus.png" alt="Add New Key" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add New Control Panel Id</a>
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
                               <a title="Sort" href="#" onclick="top.fnSort(document, 'K.apiKey');return false;">Control Panel Id</a> <?php echo fnSortIcon("K.apiKey", $tmpSort, $sortColumn); ?>
                            </td>


                            <td class="tdSort" style='padding-left:5px;'>
                               <a title="Sort" href="#" onclick="top.fnSort(document, 'K.ownerName');return false;">Owner / App Name</a> <?php echo fnSortIcon("K.ownerName", $tmpSort, $sortColumn); ?>
                            </td>
                            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'K.email');return false;">Email</a> <?php echo fnSortIcon("K.email", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'K.expiresDate');return false;">Expires</a> <?php echo fnSortIcon("K.expiresDate", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'K.lastRequestUTC');return false;">Last Request</a> <?php echo fnSortIcon("K.lastRequestUTC", $tmpSort, $sortColumn); ?>
                            </td>

                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'K.requestCount');return false;"># Requests</a> <?php echo fnSortIcon("K.requestCount", $tmpSort, $sortColumn); ?>
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
                                            
                                            $apiKeyGuid = $row['guid'];
                                            $apiKey = $row['apiKey'];
                                            
											$ownerName = fnFormOutput($row['ownerName']);
                                            if(strlen($ownerName) > 30){
                                                $ownerName = substr($ownerName, 0, 30) . "...";
                                            }

                                            $ownerEmail = fnFormOutput($row['email']);
                                            if(strlen($ownerEmail) > 30){
                                                $ownerEmail = substr($ownerEmail, 0, 30) . "...";
                                            }
                                            
                                            $createdDate = fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $createdHours = fnDateDiff("h", $createdDate, $dtToday);
                                            $createdLabel = fnSinceLabel($createdHours, $row['dateStampUTC'], $thisUser->infoArray["timeZone"]);

                                            $lastRequestDate = fnFromUTC($row['lastRequestUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            if($lastRequestDate != ""){
												$requestHours = fnDateDiff("h", $lastRequestDate, $dtToday);
                                            	$lastRequestLabel = fnSinceLabel($requestHours, $row['lastRequestUTC'], $thisUser->infoArray["timeZone"]);
                                            }else{
												$lastRequestLabel = "<i>never</i>";
											}
											
											//expires...
											$expiresDate = "";
											if($row["expiresDate"] != ""){
                                            	$expiresDate = date("m/d/Y", strtotime($row['expiresDate']));
											}
											
											//link
                                            $keyLink = "<a href='key_details.php?unused=true" . $qVars . "&apiKeyGuid=" . $row['guid'] . "'>" . $apiKey . "</a>";

                                            echo "\n\n<tr id='r_" . $apiKeyGuid . "' class='" . $css . "'>";
                                                echo "\n<td class='data'>" .  $keyLink . "</td>";
                                                echo "\n<td class='data'>" .  $ownerName . "</td>";
                                                echo "\n<td class='data'>" . $ownerEmail . "</td>";
                                                echo "\n<td class='data'>" . $expiresDate . "</td>";
                                                echo "\n<td class='data'>" . $lastRequestLabel . "</td>";
                                                echo "\n<td class='data' style='text-align:center;padding-left:1px;padding-right:1px;'>" . $row['requestCount'] . "</td>";
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
                                <td colspan='<?php echo $colCount;?>' style='padding:15px;padding-top:0px;'>
                                    There are no API keys to display. If you searched, consider changing your search criteria.
                                </td>
                            </tr>
                        <?php } ?>
            
                    </table>
                    
                    <div style='padding:15px;padding-bottom:0px;'>
                   		Every application in the control panel uses it's own Control Panel id / password when making requests
                        to this software for data. 
                		You can prevent an application from being able access data on your server by changing or removing it's key.
                        This is useful in cases where you have already distributed an application but want to prevent it from
                        making backend requests.
                    </div>



                </div>
                
                
                
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>






