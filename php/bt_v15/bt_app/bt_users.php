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

	//javascript inline in head section...
	$thisPage->jsInHead = "";
	
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$BT_userId = fnGetReqVal("BT_userId", "", $myRequestVars);

	$appName = "";
	
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
	
	/////////////////////////////////////////////////////////////////////////////////
	//deleteing a user
	$deleteDisplayName = "";
	if($appGuid != "" && $BT_userId != ""){
		
		//get name of user to show
		$objUser = new Appuser($BT_userId);
		$deleteDisplayName = $objUser->infoArray["displayName"];
	
		if($command == "confirmDelete"){
			
			//delete user
			$strSql = "DELETE FROM " . TBL_APP_USERS . " WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND guid = '" . $BT_userId . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//delete user app_requests
			$strSql = "DELETE FROM " . TBL_API_REQUESTS . " WHERE appGuid = '" . $appGuid . "' ";
			$strSql .= " AND appUserGuid = '" . $BT_userId . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//flag
			$bolDeleted = TRUE;
			$BT_userId = "";
			
		}//confirm delete	
		
		
	}//if deleting
	
	
	
	
	////////////////////////////////////////////////////////////////////////
	//adding a new user
	$strMessageNewUser = "";
	$bolDoneNewUser = "";
	$bolPassedNewUser = true;
	$newUserDisplayName = fnGetReqVal("newUserDisplayName", "", $myRequestVars);
	$newUserEmailAddress = fnGetReqVal("newUserEmailAddress", "", $myRequestVars);
	$newUserEmailAddressConfirm = fnGetReqVal("newUserEmailAddressConfirm", "", $myRequestVars);
	$newUserPassword = fnGetReqVal("newUserPassword", "", $myRequestVars);
	$newUserPasswordConfirm = fnGetReqVal("newUserPasswordConfirm", "", $myRequestVars);
	$newUserAddedName = "";
	if(strtoupper($command) == "ADDUSER"){
		
		//display name req.
		if($newUserDisplayName == ""){
			$bolPassedNewUser = false;
			$strMessageNewUser .= "<br/>Display name required.";
		}else{
			if(!fnIsAlphaNumeric($newUserDisplayName, true)){
				$bolPassedNewUser = false;
				$strMessageNewUser .= "<br/>Display name invalid.";
			}
		}
		
		//email address req.
		if(!fnIsEmailValid($newUserEmailAddress)){
			$bolPassedNewUser = false;
			$strMessageNewUser .= "<br/>Email address invalid.";
		}else{
			if(strtolower($newUserEmailAddress) != strtolower($newUserEmailAddressConfirm)){
				$bolPassedNewUser = false;
				$strMessageNewUser .= "<br/>Email addresses don't match.";
			}
		}
		
		//password req.
		if($newUserPassword == ""){
			$bolPassedNewUser = false;
			$strMessageNewUser .= "<br/>Password required.";
		}else{
			if(!fnIsAlphaNumeric($newUserPassword, false)){
				$bolPassedNewUser = false;
				$strMessageNewUser .= "<br/>Password invalid.";
			}else{
				if(strtolower($newUserPassword) != strtolower($newUserPasswordConfirm)){
					$bolPassedNewUser = false;
					$strMessageNewUser .= "<br/>Passwords don't match.";
				}
			}
		}		
		
		//if passed, check for duplicate display name, or email address...
		if($bolPassedNewUser){
			$tmp = "SELECT Count(id) FROM " . TBL_APP_USERS . " WHERE appGuid = '" . $appGuid . "' AND displayName = '" . $newUserDisplayName . "'";
			$iExists = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($iExists > 0){
				$bolPassedNewUser = false;
				$strMessageNewUser .= "<br/>Display name not available, please choose another display name.";
			}
		}
		if($bolPassedNewUser){
			$tmp = "SELECT Count(id) FROM " . TBL_APP_USERS . " WHERE appGuid = '" . $appGuid . "' AND email = '" . $newUserEmailAddress . "'";
			$iExists = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($iExists > 0){
				$bolPassedNewUser = false;
				$strMessageNewUser .= "<br/>Email address already in use, please use another email address.";
			}			
		}

		//if passed
		if($bolPassedNewUser){
		
			//insert
			$newUserGuid = strtoupper(fnCreateGuid());
			$encryptedPassword = md5($newUserPassword);
			$tmp = "INSERT INTO " . TBL_APP_USERS . " (guid, appGuid, userType, displayName, email, encLogInPassword, ";
			$tmp .= "status, lastRequestUTC, lastLoginUTC, dateStampUTC, modifiedUTC ) VALUES ( '" . $newUserGuid . "', '" . $appGuid . "', 'normal',";
			$tmp .= "'" . $newUserDisplayName . "','" . strtolower($newUserEmailAddress) . "', '" . $encryptedPassword . "',";
			$tmp .= "'active', '', '', '" . $dtNow . "','" . $dtNow . "')";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
			//flag
			$bolDoneNewUser = true;
			$newUserAddedName = $newUserDisplayName;
			
			//reset
			$newUserDisplayName = "";
			$newUserEmailAddress = "";
			$newUserEmailAddressConfirm = "";
			$newUserPassword = "";
			$newUserPasswordConfirm = "";
			
		}
		
	}
	//done adding a new user
	////////////////////////////////////////////////////////////////////////


	//list vars
	$search = fnGetReqVal("searchInput", "Display name...", $myRequestVars);
	if($search == "Filename..." || $search == "Nickname...") $search = "Display name..";
	$scriptName = "bt_users.php";
	$recsPerPage = 50;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "AU.id";
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
	
	//sort colum may contain "I." if we sorted then came from the screens list...
	if(strpos($sortColumn, "I.") > -1 || strpos($sortColumn, "F.") > -1){
		$sortColumn = $defaultSort;
		$sortUpDown = $defaultUpDown;
	}	
	
	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE AU.appGuid = '" . $appGuid . "'";
	$whereClause .= " AND AU.status != 'deleted' ";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "DISPLAY NAME..." && $search != "") ){
		$whereClause .= " AND AU.displayName LIKE '%" . $search . "%' ";
		$searchHint = "Display name contains";
	}
			
	//querystring for links
	$qVars = "&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	
	//get total recs.
	$totalSql = "  SELECT Count(AU.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_APP_USERS . " AS AU ";
	
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
    $strSql = " SELECT AU.guid, AU.usertype, AU.displayName, AU.email, AU.status, ";
	$strSql .= " AU.numRequests, AU.lastLoginUTC, AU.lastRequestUTC, AU.dateStampUTC, AU.modifiedUTC ";
	$strSql .= "FROM " . TBL_APP_USERS . " AS AU ";
	//$strSql .= " FORCE INDEX(" . str_replace("AU.", "", $sortColumn) . ") ";
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
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<script>
	function fnAddUser(){
		document.getElementById("addUserButton").disabled = true;
		document.forms[0].command.value = 'addUser';
		document.forms[0].submit();
	}
</script>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_userId" id="BT_userId" value="<?php echo $BT_userId;?>">
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
        
       	<div class='contentBox colorLightBg' style='min-height:400px;'>
            
            <div class="contentBand colorBandBg">
                	Logins (App Users) for <?php echo fnFormOutput($appName, true);?>
            </div>
            <div style='padding:10px;'>

                    
			   <?php if(strtoupper($command) == "DELETE"){ ?>
                    <div class="errorDiv">
                        <br/>
                        <b>Delete "<?php echo $deleteDisplayName;?>"</b>
                        <div style='padding-top:5px;'>
                            Are you sure you want to do this? This cannot be undone! When you
                            confirm this operation, all information and content associated with this user will be permanently removed and you
                            will not be able to get it back - ever. 
                        </div>
                        <div style='padding-top:10px;'>
                            <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this user</a>
                        </div>
                        <div style='padding-top:10px;'>
                            <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>&BT_userId=<?php echo $BT_userId;?>&command=confirmDelete"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this user</a>
                        </div>
                    </div>
                <?php } ?>                                    

               <?php if($bolDeleted){ ?>
                    <div class="doneDiv">
                        <b>"<?php echo $deleteDisplayName;?>"</b> Deleted.
                        <div style='padding-top:10px;'>
                            <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                        </div>
                     </div>
                <?php } ?> 
			</div>
                    
                    <table cellspacing='0' cellpadding='0' width="99%" style='margin-left:10px;'>
                    	<tr>
                        	<td>
                            
                                <table cellspacing='0' cellpadding='0' width="100%">
            
                                    <?php if($totalRecs > 0){ ?>
                                    <tr>
                                        <td nowrap class="tdSort" style='padding-left:5px;'>
                                           <a title="Sort" href="#" onclick="top.fnSort(document, 'AU.displayName');return false;">Display Name</a> <?php echo fnSortIcon("AU.displayName", $tmpSort, $sortColumn); ?>
                                        </td>
                                        
                                        <td nowrap class="tdSort">
                                            <a title="Sort" href="#" onclick="top.fnSort(document, 'AU.lastRequestUTC');return false;">Last Cloud Report</a> <?php echo fnSortIcon("AU.lastRequestUTC", $tmpSort, $sortColumn); ?>
                                        </td>
                        
                                        <td nowrap class="tdSort">
                                            <a title="Sort" href="#" onclick="top.fnSort(document, 'AU.numRequests');return false;">Num. Requests</a> <?php echo fnSortIcon("AU.numRequests", $tmpSort, $sortColumn); ?>
                                        </td>
                                        
                                        <td nowrap class="tdSort">
                                       
                                        </td>
                        
                                    </tr>
                                    <tr>
                                    	<td colspan='<?php echo $colCount;?>'>&nbsp;</td>
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
                                                    
                                                    $BT_userId = $row['guid'];
                                                    $displayName = fnFormOutput($row['displayName']);
                                                    $email = $row['email'];
                                                    $status = $row['status'];
                                                    $lastRequestUTC = fnFormOutput($row['lastRequestUTC']);
                                                    $numRequests = fnFormOutput($row['numRequests']);
                                                    
                                                    //last logged in
                                                    $lastRequestLabel = $row["lastRequestUTC"];
                                                    if($lastRequestLabel == "" || $lastRequestLabel == "0000-00-00 00:00:00"){
                                                        $lastRequestLabel = "<i>never</i>";
                                                    }else{
                                                        $lastRequestUTC = fnFromUTC($row['lastRequestUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                                        $requestHours = fnDateDiff("h", $lastRequestUTC, $dtToday);
                                                        $lastRequestLabel = fnSinceLabel($requestHours, $row['lastRequestUTC'], $thisUser->infoArray["timeZone"]);
                                                    }
                                                    
                                                    //modified
                                                    $modifiedUTC = fnFromUTC($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                                    $modifiedHours = fnDateDiff("h", $modifiedUTC, $dtToday);
                                                    $modifiedLabel = fnSinceLabel($modifiedHours, $row['modifiedUTC'], $thisUser->infoArray["timeZone"]);
                                                     
                                                    $pad = "&nbsp;&nbsp;|&nbsp;&nbsp;";
													
                                                    echo "\n\n<tr id='i_" . $BT_userId . "' class='" . $css . "'>";
                                                        echo "\n<td class='data'>";
                                                            echo "<a href='bt_pickerUserProperties.php?appGuid=" . $appGuid . "&BT_userId=" . $BT_userId . $qVars . "' rel=\"shadowbox;height=550;width=950\">" . $displayName . "</a>";
                                                        echo "</td>";
                                                        echo "\n<td class='data' style='padding-left:10px;'>" . $lastRequestLabel . "</td>";
                                                        echo "\n<td class='data' style='text-align:center;'>" . $numRequests . "</td>";
                                                        echo "\n<td class='data' style='padding-left:10px;text-align:right;padding-right:10px;'>";
                                                            echo "<a href='" . $scriptName . "?appGuid=" . $appGuid . "&BT_userId=" . $BT_userId . $qVars . "&command=delete'>delete</a>";
                                                        echo "</td>";
                                                    echo "\n</tr>";
                                                    
                                                }//end while
                                            }//no res
                                        }//no records
                            
                                    ?>
                        
                                    <?php if($totalRecs < 1){?>

                                        <tr>
                                            <td colspan='<?php echo $colCount;?>'>
                                                <div class='errorDiv' style='margin-top:0px;'><br/>
                                                    <b>No logins found?</b> This means that if the app has any password
                                                    protected screens, nobody will be able to login to access them. 
                                                    <hr/>
                                                    Use the form on the right to create a new login.
                                                </div>
                                            </td>
                                        </tr>
                        
                                
                                    <?php }else{?>
                                    
                                    	<tr>
                                        	<td colspan='<?php echo $colCount;?>'>&nbsp;</td>
                                    	</tr>
                                    
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
                                    
                                    <?php } ?>
                                    
                                    
                                    
                                    
                                    
                                </table>
							</td>
                            
                            <td style='padding:10px;padding-top:0px;width:400px;'>
                            
                                        <fieldset class="colorDarkBg">
                                            
											<?php if($strMessageNewUser != "" && !$bolDoneNewUser){ ?>
                                                <div class='errorDiv'>
                                                    <?php echo $strMessageNewUser;?>                                
                                                </div>
                                            <?php } ?> 
                                            
                                            <?php if($bolDoneNewUser){ ?>
                                                <div class='doneDiv'>
                                                    <b>"<?php echo $newUserAddedName;?>" added</b>. 
                                                    <div style='padding-top:5px;'>
                                                        <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid . $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                                    </div>
                                                </div>
                                            <?php } ?>             
                                            
                                           <div style='padding-top:10px;'>
                                                <b>Add Login</b>
                                           </div> 
                                           
                                           <div style='padding-top:5px;'>
                                                Add users to this list to enable the login-protection features availalbe on 
                                                any screen or feature in the mobile app.
                                           </div>        
                                                                    
                                           <div style='padding-top:5px;'>
                                                Enter letters or numbers only for names and passwords. Do not use special characters
                                                or punctuation. No spaces allowed in passwords.
                                           </div>
                                           
                                           <div style='padding-top:10px;'>
                                               <label>Display Name</label>
                                               <input type="text" name="newUserDisplayName" id="newUserDisplayName" value="<?php echo fnFormOutput($newUserDisplayName);?>" maxlength="50"/>
                                           </div>

                                           <div style='padding-top:5px;'>
                                                <label>Email Address <span style='font-weight:normal;'>(this is the login username)</span></label>
                                                <input type="text" name="newUserEmailAddress" id="newUserEmailAddress" value="<?php echo fnFormOutput($newUserEmailAddress);?>" maxlength="100"/>
                                            </div>

                                           <div style='padding-top:5px;'>
                                                <label>Confirm Email Address</label>
                                                <input type="text" name="newUserEmailAddressConfirm" id="newUserEmailAddressConfirm" value="<?php echo fnFormOutput($newUserEmailAddressConfirm);?>" maxlength="100"/>
                                            </div>

                                            <div style='padding-top:5px;'>
                                                <label>Password</label>
                                                <input type="password" name="newUserPassword" id="newUserPassword" value="<?php echo fnFormOutput($newUserPassword);?>" maxlength="50"/>
                                            </div>

                                            <div style='padding-top:5px;'>
                                                <label>Confirm Password</label>
                                                <input type="password" name="newUserPasswordConfirm" id="newUserPasswordConfirm" value="<?php echo fnFormOutput($newUserPasswordConfirm);?>" maxlength="50"/>
                                            </div>
                                            
                                            <div style='padding-top:5px;padding-bottom:10px;'>
                                                <input type='button' id="addUserButton" name="addUserButton" title="submit" value="submit" align='absmiddle' class="buttonSubmit" onClick="fnAddUser();return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                                <input type='button' id="resetButton" name="resetButton" title="reset" value="reset" align='absmiddle' class="buttonCancel" onClick="document.forms[0].reset();return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                            </div>
                                       	
                                        </fieldset>
                                    
                                   
                            
                            </td>
                            
						</tr>
                        
                    </table>


                
       </div> 
    </fieldset>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
