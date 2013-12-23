<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//User Object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$doWhat = fnGetReqVal("doWhat", "", $myRequestVars);

	$devices = fnGetReqVal("devices", "all", $myRequestVars);
	$deviceMode = fnGetReqVal("deviceMode", "", $myRequestVars);
	
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
	//deleting registered devices(s)...
	if($appGuid != "" && strtoupper($doWhat) == "DELETEITEMS" && count($selectedIds) > 0){
		
		//show the confirmation message on step one...
		$command = "delete";
		
	}
	if($appGuid != "" && strtoupper($command) == "CONFIRMDELETE" && count($selectedIds) > 0){
		
		//delete registered devices...
		$strSql = "DELETE FROM " . TBL_APN_DEVICES . " WHERE appGuid = '" . $appGuid . "' ";
		$strSql .= " AND guid IN (" . $inClauseSQL . ")";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
		//flag...
		$bolDeleted = TRUE;
		$command = "";

	}	
	//end change app owner
	///////////////////////////////////////////////////////////////


	//list vars
	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);
	$scriptName = "bt_pushDevices.php";
	$totalRecs = 0;
	$recsPerPage = 100;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "D.dateStampUTC";
	$defaultUpDown = "DESC";
	$colCount = 6;

	//sort up / down
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
		//if we clicked a column header
		$sortUpDown = fnGetReqVal("nextUpDown", $sortUpDown, $myRequestVars);
	
	//sort column
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
		//if we clicked a column header
		$sortColumn = fnGetReqVal("nextCol", $sortColumn, $myRequestVars);
			if($sortColumn == "") $sortColumn = $defaultSort;
	
	//sort colum may not contain "D." if we sorted then came from a different screen...
	if(strpos($sortColumn, "D.") < 0){
		$sortColumn = $defaultSort;
		$sortUpDown = $defaultUpDown;
	}
	
	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE D.appGuid = '" . $appGuid . "'";
	
	//which devices...
	if($devices != ""){
		if(strtoupper($devices) == "IOS"){
			$whereClause .= " AND D.deviceType = 'ios'";
		}
		if(strtoupper($devices) == "ANDROID"){
			$whereClause .= " AND D.deviceType = 'android'";
		}
		if(strtoupper($devices) == "ALL" || $devices == ""){
			//show all...
		}
	}

	//show live / design devices...
	if($deviceMode != ""){
		$whereClause .= " AND D.deviceMode = '" . $deviceMode . "'";
	}
	
	
	//if searching...
	$searchHint = "";
	$typeHint = "";
	
	//nickname...	
	if((strtoupper($search) != "ALL" && strtoupper($search) != "SEARCH..." && $search != "")){
		$whereClause .= " AND (D.deviceModel LIKE '%" . $search . "%' OR D.deviceToken = '" . $search . "' OR deviceType = '" . $search . "') ";
		$searchHint = "<span style='color:red;'>You searched for devices with...</span> ";
		$searchHint .= "<br/>a <span style='color:black;font-weight:bold;'>Token</span> that equals</span> <b>\"" . fnFormOutput($search) . "\"</b> ";
		$searchHint .= "<br/>OR a <span style='color:black;font-weight:bold;'>Device Type</span> that equals <b>\"" . fnFormOutput($search) . "\"</b> ";
		$searchHint .= "<br/>OR a <span style='color:black;font-weight:bold;'>Device Model</span> that contains <b>\"" . fnFormOutput($search) . "\"</b> ";
	}
	
	//querystring for links
	$qVars = "&searchInput=" . fnFormOutput($search) . "&appGuid=" . $appGuid;
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	$qVars .= "&devices=" . $devices . "&deviceMode=" . $deviceMode;
	
	//get total recs...
	$totalSql = "  SELECT Count(D.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_APN_DEVICES . " AS D ";

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
    $strSql = " SELECT D.guid, D.deviceMode, D.deviceToken, D.deviceType, D.deviceModel, D.deviceLatitude, D.deviceLongitude, D.dateStampUTC ";
	$strSql .= "FROM " . TBL_APN_DEVICES . " AS D ";
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



	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>


<!--load the jQuery files using the Google API -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>



<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="command" id="command" value="">

<input type="hidden" name="recsPerPage" id="recsPerPage" value="<?php echo $recsPerPage;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="totalPages" id="totalPages" value="<?php echo $totalPages;?>">
<input type="hidden" name="firstRec" id="firstRec" value="<?php echo $firstRec;?>">
<input type="hidden" name="lastRec" id="lastRec" value="<?php echo $lastRec;?>">
<input type="hidden" name="totalRecs" id="totalRecs" value="<?php echo $totalRecs;?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">


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
                Push Notification Registered Devices for <?php echo fnFormOutput($appName, true);?>
           	</div>


             <div id="dataForm" style='padding:10px;visibility:visible;'>

				<?php if($strMessage != "" && !$bolDone) { ?>
					<div class="errorDiv">
                    	<?php echo $strMessage;?>
                    </div>
				<?php } ?>

				<?php if($strMessage != "" && $bolDone) { ?>
					<div class="doneDiv">
                    	<?php echo $strMessage;?>
                    </div>
				<?php } ?>
                
               
                        	
                <div class="cpExpandoBox" style="min-width:500px;">
                
                    <div style='padding-top:5px;'>
                        
                   
				   
					   <?php if(strtoupper($command) == "DELETE"){ ?>
                            <div class="errorDiv">
                                <br/>
                                <b>Confirmation Required: </b>
                                <?php 
                                    if(count($selectedIds) > 1){
                                        echo "Delete " . count($selectedIds) . " registered devices";
                                    }else{
                                        echo "Delete " . count($selectedIds) . " registered devices";
                                    }
                                ?>
    
                                <div style='padding-top:5px;'>
                                
                                    Are you sure you want to do this? This cannot be undone! When you
                                    confirm this operation
                                    
                                    <?php 
                                        if(count($selectedIds) > 1){
                                            echo "<b>" . count($selectedIds) . " registered devices</b>";
                                        }else{
                                            echo "<b>" . count($selectedIds) . " registered devices</b>";
                                        }
                                    ?>
                                    
                                    
                                    will be permanently removed. 
                                    
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
                                    <a href="<?php echo $scriptName;?>?unused=true&<?php echo $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                </div>
                             </div>
                        <?php } ?> 
                        
                        
                        <table cellspacing='0' cellpadding='0' width="99%" style='margin-bottom:10px;'>
                            <tr>
                                <td style='vertical-align:middle;'>
			                        <a href="bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" class="pointer" alt="pointer">Back to Push Notifications</a>
                                </td>
                                <td nowrap style='padding-left:10px;text-align:right;'>
                                    
                                    
                                </td>
                                <td nowrap style='padding-left:10px;'>
                                    
                                    <div style='float:right;margin:0px;margin-left:5px;'>
                                        <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                    </div>
                                    <div style='vertical-align:middle;vertical-align:middle;float:right;margin-left:5px;'>
                                        <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('search...',this);" type='text' value="<?php echo fnFormOutput($search, true);?>" class='searchBox' style='margin:0px;display:inline;vertical-align:middle;overflow:hidden;' onkeyup="document.forms[0].currentPage.value='1';">
                                    </div>
                                    
                                    <div style="vertical-align:middle;float:right;margin-left:5px;">
                                        <select name="devices" id="devices" onChange="top.fnRefresh(document);return false;">
                                        	<option value="">--Filter by Platform--</option>
                                            <option value="All" <?php echo fnGetSelectedString("All", $devices);?>>All Device Types</option>
                                            <option value="iOS" <?php echo fnGetSelectedString("iOS", $devices);?>>iOS Devices Only</option>
                                            <option value="Android" <?php echo fnGetSelectedString("Android", $devices);?>>Android Devices Only</option>
                                        </select>
                                    </div>
    
                                    <div style="vertical-align:middle;float:right;margin-left:5px;">
										<select name="deviceMode" id="deviceMode" onChange="top.fnRefresh(document);return false;">
                                        	<option value="">--All Modes--</option>
                                            <option value="Design" <?php echo fnGetSelectedString("Design", $deviceMode);?>>Design Devices Only</option>
                                            <option value="Live" <?php echo fnGetSelectedString("Live", $deviceMode);?>>Live Devices Only</option>
                                        </select>
                                    </div>
                                    
                                    
                                </td>
                            </tr>
                        </table>
                        
                        
						<?php if(strtoupper($searchHint) != ""){ ?>
                            <div style='margin-bottom:15px;'>
                               <?php echo $searchHint;?>
                            </div>
                        <?php } ?>
                        
                        <table cellspacing='0' cellpadding='0' width="100%" style='margin-left:0px;'>
                            <tr>
                                
                                <td class="tdSort" style='padding-left:5px;'>
                                    <a title="Sort" href="#" onclick="top.fnSort(document, 'D.dateStampUTC');return false;">Registration Date</a> <?php echo fnSortIcon("D.dateStampUTC", $tmpSort, $sortColumn); ?>
                                </td>

                                
                                <td class="tdSort">
                                   <a title="Sort" href="#" onclick="top.fnSort(document, 'D.deviceType');return false;">Platform</a> <?php echo fnSortIcon("D.deviceType", $tmpSort, $sortColumn); ?>
                                </td>
                                
                                <td class="tdSort">
                                    <a title="Sort" href="#" onclick="top.fnSort(document, 'D.deviceModel');return false;">Model</a> <?php echo fnSortIcon("D.deviceModel", $tmpSort, $sortColumn); ?>
                                </td>

                                <td class="tdSort">
                                    <a title="Sort" href="#" onclick="top.fnSort(document, 'D.deviceMode');return false;">Mode</a> <?php echo fnSortIcon("D.deviceMode", $tmpSort, $sortColumn); ?>
                                </td>

                                <td class="tdSort">
                                	Token
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
                                            
                                            $deviceGuid = $row['guid'];
                                            $deviceMode = fnFormatProperCase($row['deviceMode']);
                                            $deviceToken = fnFormOutput($row['deviceToken']);
											$deviceType = fnFormatProperCase($row['deviceType']);
                                            $deviceModel = fnFormOutput($row['deviceModel']);
                                            $deviceLatitude = fnFormOutput($row['deviceLatitude']);
                                            $deviceLongitude = fnFormOutput($row['deviceLongitude']);
    
											//latitude must be numeric...
											if(!is_numeric($deviceLatitude)){
												$deviceLatitude = "";
											}
											if($deviceLatitude == "0") $deviceLatitude = "";
											
											//longitude must be numeric...
											if(!is_numeric($deviceLongitude)){
												$deviceLongitude = "";
											}
											if($deviceLongitude == "0") $deviceLongitude = "";

											//fix up iOS...
											if(strtoupper($deviceType) == "IOS"){
												$deviceType = "iOS";
											}
	
											//truncate device token...
											if(strlen($deviceToken) > 40){
												$deviceToken = substr($deviceToken, 0, 40) . "...";
											}
											
											//modified...
                                            $modDate = fnFromUTC($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                            $modHours = fnDateDiff("h", $modDate, $dtToday);
                                            $modLabel = fnSinceLabel($modHours, $row['dateStampUTC'], $thisUser->infoArray["timeZone"]);
            
                                            echo "\n\n<tr id='i_" . $deviceGuid . "' class='" . $css . "'>";
                                                echo "\n<td class='data' style='padding-left:5px;width:150px;'>";
                                                    echo $modLabel;
                                                echo "</td>";
                                                echo "\n<td class='data' style='padding-left:10px;'>" . $deviceType . "</td>";
                                                echo "\n<td class='data' style='padding-left:10px;'>" . $deviceModel . "</td>";
												echo "\n<td class='data' style='padding-left:10px;'>" . $deviceMode . "</td>";
												echo "\n<td class='data' style='padding-left:10px;'>" . $deviceToken . "</td>";
												echo "\n<td class='data' style='padding:0px;padding-right:5px;text-align:right;'><input id='selected[]' name='selected[]' type='checkbox' style='height:12px;width:12px;margin:0px;' value='" . $deviceGuid . "' " . fnIsChecked($deviceGuid) . "></td>";
											echo "\n</tr>";
                                            
                                            
                                        }//end while
                                    }//no res
                                }//no records
                    
                            ?>
                
							<?php if($totalRecs > 0){?>
                                <tr>
                                    <td colspan='<?php echo $colCount;?>' style='padding-top:5px;text-align:right;vertical-align:top;'>
                                        
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
                        
                        <?php if($cnt < 1){ ?>
							<div style='padding:10px;padding-left:5px;'>
                            	There are no registered devices that meet your filter criteria. 
                                Devices are registered when users "allow push notifications"
                                while using the app. You cannot register devices manually.
                            </div>
						<?php } ?>

                    </div>
                            
				</div>
                 
            </div> 
            
            
            
            
            
             
         </div>       
    </fieldset>
    


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
