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
	$thisPage->pageTitle = "Admin Control Panel | Manage Plugins";

	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/admin_plugins.js";	

	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/admin/pluginUpdate_AJAX.php\";";


	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();
	
	//vars
	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$status = fnGetReqVal("status", "", $myRequestVars);
	$refreshPlugins = fnGetReqVal("refreshPlugins", "", $myRequestVars);
	
	/////////////////////////////////////////////////////////////////////////
	//get rid of possible hidden __MACOSX directory in /plugins folder
	$plugin_directory = ".." . APP_DATA_DIRECTORY . "/plugins"; 
	if(is_writable($plugin_directory)){
		if(is_dir($plugin_directory . "/__MACOSX")){
			fnRemoveDirectory($plugin_directory . "/__MACOSX");
		}		
	}

	$scriptName = "plugins.php";
	$recsPerPage = 1000;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "P.category";
	$defaultUpDown = "ASC";

	//sort up / down
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
		//if we clicked a column header
		$sortUpDown = fnGetReqVal("nextUpDown", $sortUpDown, $myRequestVars);
		
	//sort column
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
		//if we clicked a column header
		$sortColumn = fnGetReqVal("nextCol", $sortColumn, $myRequestVars);
			if($sortColumn == "") $sortColumn = $defaultSort;
				if(substr($sortColumn, 0, 1) != "P"){
					$sortColumn = $defaultSort;
				}

	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE P.id > 0 ";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "") ){
		$whereClause .= " AND P.displayAs LIKE '%" . $search . "%' ";
		$searchHint = "<span style='color:red;'>You are searching</span> for plugins with a <b>Plugin name</b> that contains <b>\"" . $search . "\"</b>";
	}
	
	//querystring for links
	$qVars = "&from=plugins&status=" . $status . "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;

	//get total recs.
	$totalSql = "  SELECT Count(P.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_BT_PLUGINS . " AS P ";
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
    $strSql = " SELECT P.guid, P.category, P.uniquePluginId, P.versionString, P.displayAs, P.loadClassOrActionName, P.webDirectoryName, ";
	$strSql .= " P.dateStampUTC, P.shortDescription ";
	$strSql .= "FROM " . TBL_BT_PLUGINS . " AS P ";
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
<input type="hidden" name="command" id="command" value="">
<input type="hidden" name="pluginGuid" id="pluginGuid" value="">

<!--load the jQuery files using the Google API -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>



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
                  Manage Plugins
                </div>
                
                <div style='padding:10px;'>
                           
                           
                    <table cellspacing='0' cellpadding='0' width="99%" style='margin-left:10px;margin-bottom:10px;'>
                        <tr>
                            <td style='vertical-align:middle;white-space:nowrap;'>
                                <a href="pluginsUpdate.php?addingPlugin=true<?php echo $qVars;?>" style='vertical-align:middle;white-space:nowrap;' title="Plugin Maintenance"><img src="../images/plus.png" alt="Plugin Maintenance" style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Plugin Maintenance (Add, Remove, Refresh)</a>
                           		<span id="checkAllForUpdates"></span>
                            </td>
                            <td nowrap style='padding-left:10px;white-space:nowrap;'>
                                <div style='float:right;margin:0px;'>
                                    &nbsp;
                                    <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                </div>
                                <div style='float:right;margin:0px;'>
                                    <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('search...',this);" type='text' value="<?php echo fnFormOutput($search, true);?>" class='searchBox' style='margin:0px;display:inline;vertical-align:middle;overflow:hidden;' onkeyup="document.forms[0].currentPage.value='1';">
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <div id='batchUpdateProgress'></div>
            
                     <?php if( (strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "")){?>
                        <div style='margin-bottom:15px;'>
                           <?php echo $searchHint;?>
                        </div>
                    <?php } ?>
                    
                    <!--plugin list-->
                    <table cellspacing='0' cellpadding='0' width="99%" style='margin-left:10px;'>
                        <tr>
                            
                            <td class="tdSort">

                            </td>
                            
                            <td class="tdSort" style='padding-left:5px;'>
                               <a title="Sort" href="#" onclick="top.fnSort(document, 'P.displayAs');return false;">Name</a> <?php echo fnSortIcon("P.displayAs", $tmpSort, $sortColumn); ?>
                            </td>
                            
                            <td class="tdSort" style='padding-left:5px;'>
                               <a title="Sort" href="#" onclick="top.fnSort(document, 'P.versionString');return false;">Version</a> <?php echo fnSortIcon("P.versionString", $tmpSort, $sortColumn); ?>
                            </td>
                            
                            <td class="tdSort">
                                <a title="Sort" href="#" onclick="top.fnSort(document, 'P.category');return false;">Category</a> <?php echo fnSortIcon("P.category", $tmpSort, $sortColumn); ?>
                            </td>
            
                            <td class="tdSort">&nbsp;
                                
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
                                        $css = (($cnt % 2) == 0) ? "rowNormal" : "rowAlt" ;
                                        
                                            
                                        $pluginGuid = $row['guid'];
                                        $uniquePluginId = $row['uniquePluginId'];
                                        $displayAs = fnFormOutput($row["displayAs"]);
                                        $category = $row['category'];
                                        $webDirectoryName = $row['webDirectoryName'];
                                        $versionString = $row['versionString'];
                                        $shortDescription = $row['shortDescription'];

                                        $modDate = fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                        $modHours = fnDateDiff("h", $modDate, $dtToday);
                                        $modLabel = fnSinceLabel($modHours, $row['dateStampUTC'], $thisUser->infoArray["timeZone"]);
                                        
                                        //icon URL
                                        $iconURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/plugins" . $webDirectoryName . "/icon.png";
        
                                        $pad = "&nbsp;&nbsp;|&nbsp;&nbsp;";
                                        echo "\n\n<tr class='" . $css . "'>";
                                            echo "\n<td class='data' rowspan='2' style='vertical-align:middle;padding-left:5px;width:50px;height:50px;border-bottom:1px solid #999999;'>";
                                                echo "<a href=\"../bt_v15/bt_app/bt_pickerPluginDetails.php?pluginGuid=" . $pluginGuid . "\" rel=\"shadowbox;height=550;width=950\"><img src='" . $iconURL . "' style='height:50px;width:50px;' alt='Plugin icon'/></a>";
                                            echo "</td>";
                                            echo "\n<td class='data' style='padding-left:5px;padding-top:5px;border-bottom:1px solid #999999;'>";
                                                echo "<a href=\"../bt_v15/bt_app/bt_pickerPluginDetails.php?pluginGuid=" . $pluginGuid . "\" rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a>";
                                            echo "</td>";
                                            echo "\n<td class='data' style='padding-left:10px;padding-top:5px;border-bottom:1px solid #999999;'>" . $versionString . "</td>";
                                            echo "\n<td class='data' style='padding-left:10px;padding-top:5px;border-bottom:1px solid #999999;'>" . fnFormatProperCase($category) . "</td>";
                                            echo "\n<td class='data' rowspan='2' style='height:75px;vertical-align:top;text-align:right;padding-right:5px;padding-left:10px;padding-top:5px;border-bottom:1px solid #999999;'>";
												
												echo "<div id='fadeBox'>";
													
													echo "<div id='controls_" . $pluginGuid . "' style='display:block;'>";
														echo "<a href=\"#\" onClick=\"checkForUpdates('" . $pluginGuid . "');return false;\" style='white-space:nowrap;'>check for updates</a>";
                                            		echo "</div>";
												
											 		echo "<div id=\"submit_" . $pluginGuid . "\" class=\"submit_working\" style='padding-top:0px;margin-top:0px;'></div>";
												
												echo "</div>";
												
											echo "</td>";
                                        echo "\n</tr>";
                                        echo "\n\n<tr class='" . $css . "'>";
                                            echo "<td colspan='3' class='data' style='padding-top:5px;padding-bottom:5px;white-space:normal;border-bottom:1px solid #999999;'>";
                                                echo fnFormOutput($shortDescription);
                                            
												//hidden form field holding pluginGuid for "check all for updates" routine...
												echo "\n<input type='hidden' name='pluginBatch' id='pluginBatch' value='" . $pluginGuid . "'/>";
											
											
											echo "</td>";
                                        echo "</tr>";
                                        
                                    }//end while
                                }//no res
                            }//no records
                
                        ?>
            
                    </table>

    
                    <?php if($totalRecs > 0){?>
                        <div style='padding:5px;text-align:right;vertical-align:top;'>
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
                            <b>There are no plugins installed yet</b>
                            <div style='padding-top:5px;'>
                                Use the "Add" option to upload plugins. If you don't add any plugins you will not be able
                                to add any screen types to any applications.
                            </div>
                        </div>
                    
                    <?php } ?>
                    
                
                
                </div>  
         	</div>
         </div>       
    </fieldset>
        
    <script>
		fnShowUpdateAllOption();
	</script>    
        

<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
