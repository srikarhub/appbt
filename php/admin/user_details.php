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
	$thisPage->pageTitle = "Admin Control Panel | User Details";

	//vars
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$dtNow = fnMySqlNow();

	//what user did we click?
	$userGuid = fnGetReqVal("userGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	if($userGuid == ""){
		echo "invalid request";
		exit();
	}
	
	//user details...
	$selectedUser = new User($userGuid);
	$userType = $selectedUser->infoArray["userType"];
	$firstName = $selectedUser->infoArray["firstName"];
	$lastName = $selectedUser->infoArray["lastName"];
	$email = $selectedUser->infoArray["email"];
	$logInId = $selectedUser->infoArray["logInId"];
	$dateStampUTC = $selectedUser->infoArray["dateStampUTC"];
	$modifiedUTC = $selectedUser->infoArray["modifiedUTC"];
	$lastPageRequest = $selectedUser->infoArray["lastPageRequest"];
	$pageRequests = $selectedUser->infoArray["pageRequests"];
	
	//average visits per day
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$regDays = fnDateDiff("d", fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
	$dailyVisits = "n/a";
	if($regDays > 0 && $pageRequests != "0" && $pageRequests != ""){
		$dailyVisits = round(($pageRequests / $regDays), 1);
	}else{
	  	$dailyVisits = $pageRequests;
	}
	
	//for updates...
	$newFirstName = $firstName;
	$newLastName = $lastName;
	$newEmail = "";
	$newUserType = $userType;
	
	////////////////////////////
	//include files post updates
	if($isFormPost){
		
		//values from form elements
		$newFirstName = fnGetReqVal("newFirstName", "", $myRequestVars);
		$newLastName = fnGetReqVal("newLastName", "", $myRequestVars);
		$newEmail = fnGetReqVal("newEmail", "", $myRequestVars);
		$newEmailConfirm = fnGetReqVal("newEmailConfirm", "", $myRequestVars);
		$newPassword = fnGetReqVal("newPassword", "", $myRequestVars);
		$newPasswordConfirm = fnGetReqVal("newPasswordConfirm", "", $myRequestVars);
		$newUserType = fnGetReqVal("newUserType", "", $myRequestVars);
	
		$strSql = "";
		switch (strtoupper($command)){
			case "USERNAME":
				$strSql .= " UPDATE " . TBL_USERS . " SET firstName = '" . $newFirstName . "', lastName = '" . $newLastName . "'";
				if(strlen($newFirstName) < 1){
					$bolPassed = false;
					$strMessage .= "<br />First Name Required";
				}
				if(strlen($newLastName) < 1){
					$bolPassed = false;
					$strMessage .= "<br />Last Name Required";
				}
				break;
			case "USEREMAIL":
				$strSql .= " UPDATE " . TBL_USERS . " SET email = '" . strtolower($newEmail) . "'";
				if(!fnIsEmailValid($newEmail) ){
					$bolPassed = false;
					$strMessage .= "<br />Valid Email Required";
				}else{
					//make sure emails match
					if(strtoupper($newEmail) != strtoupper($newEmailConfirm)){
						$bolPassed = false;
						$strMessage .= "<br />Email Addresses Don't Match";
					}else{
						//see if email already in use?...
						if($selectedUser->fnIsEmailInUse($newEmail, $userGuid)){
							$bolPassed = false;
							$strMessage .= "<br />Email Address already in use. You cannot use this email address";
						}
					}
				}
				break;
			case "USERPASSWORD":
				$strSql .= " UPDATE " . TBL_USERS . " SET logInPassword = '" . md5($newPassword) . "'";
				if(strlen($newPassword) < 4){
					$bolPassed = false;
					$strMessage .= "<br />Password required (at least 4 characters)";
				}else{
					if(strtoupper($newPassword) != strtoupper($newPasswordConfirm)){
						$bolPassed = false;
						$strMessage .= "<br />Passwords do not match";
					}
				}
				break;
			case "USERTYPE":
				$strSql .= " UPDATE " . TBL_USERS . " SET userType = '" . strtolower($newUserType) . "'";
				if(strlen($newUserType) < 1){
					$bolPassed = false;
					$strMessage .= "<br />User Type Required";
				}
				break;
		}
		
		//passed?
		if($bolPassed && strlen($strSql) > 5){
			
			//finish SQL update...
			$strSql .= ", modifiedUTC = '" . $dtNow . "' ";
			$strSql .= " WHERE guid = '" . $userGuid . "' ";
			
			//execute update
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//re-fill user vars
			$selectedUser = new User($userGuid);
			$userType = $selectedUser->infoArray["userType"];
			$firstName = $selectedUser->infoArray["firstName"];
			$lastName = $selectedUser->infoArray["lastName"];
			$email = $selectedUser->infoArray["email"];
			$modifiedUTC = $selectedUser->infoArray["modifiedUTC"];
			$lastPageRequest = $selectedUser->infoArray["lastPageRequest"];
			$pageRequests = $selectedUser->infoArray["pageRequests"];
			
			//flag as done
			$bolDone = true;
			$command = "";
			
		}else{
			$bolDone = false;
		}
		
		
	}//if updating
	
	//days since modified
	if($modifiedUTC == ""){
		$daysSinceModified = "";
	}else{
		$daysSinceModified = fnDateDiff("d", fnFromUTC($modifiedUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
	}
	
	//days since last request
	if($lastPageRequest == ""){
		$daysSinceLastRequest = "";
	}else{
		$daysSinceLastRequest = fnDateDiff("d", fnFromUTC($lastPageRequest, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
	}
	
	//days since registration
	if($dateStampUTC == ""){
		$daysSinceRegistration = "";
	}else{
		$daysSinceRegistration = fnDateDiff("d", fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
	}
	
	//from previous screen...
	$defaultUpDown = "DESC";
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
	$currentPage = fnGetReqVal("currentPage", "1", $myRequestVars);
	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);
	
	//querystring for links
	$qVars = "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	$qVars .= "&searchInput=" . fnFormOutput($search);
	
	/////////////////////////////////////////////////////////////////////////////////
	//if deleting a user
	if($userGuid != "" && $command == "confirmDelete"){
		
		//can't delete own account...
		if($userGuid == $guid){
			$bolPassed = false;
			$strMessage .= "<br>You cannot remove your own account";
		}
		if($bolPassed){
		
			$tmp = "SELECT Count(id) FROM " . TBL_APPLICATIONS . " WHERE ownerGuid = '" . $userGuid . "' ";
			$tmp .= " AND status != 'deleted'";
			$iExists = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			if($iExists != 0 || $iExists != "0"){
			
				$bolPassed = false;
				$strMessage .= "<br>This user is the owner of at least one application. You cannot remove the user until ";
				$strMessage .= "those applications are removed or until you assoicate those apps them with another user using ";
				$strMessag .= " the admin panel.";
			
			}else{
				
				$tmpRandom = strtoupper(fnCreateGuid());
				$tmp = "UPDATE " . TBL_USERS . " SET email = '" . $tmpRandom . "', logInId = '" . $tmpRandom . "', isLoggedIn = '0', status = 'deleted', modifiedUTC = '" . $dtNow . "' ";
				$tmp .= "WHERE guid = '" . $userGuid . "' AND guid != '" . $guid . "'";
				fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
				$bolDeleted = true;
				$bolDone = true;
				
			}
		
		}
		//can't delete if app's are owned by this person...
		
	}//if deleting..
	

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="userGuid" id="userGuid" value="<?php echo $userGuid;?>" />
<input type="hidden" name="command" id="command" value="<?php echo $command;?>" />
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>" />
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>" />
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>" />
<input type="hidden" name="search" id="search" value="<?php echo $search;?>" />


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
                   User Details
                </div>
    
                <div style='padding:10px;'>
                	
					<?php if(!$bolDeleted){ ?> 
                    
                        <div>
                            <a href="users.php?unused=true<?php echo $qVars;?>" title="Users">Back to users</a>
                            &nbsp;|&nbsp;
                            <a href="index.php?unused=true<?php echo $qVars;?>" title="Applications">Back to applications</a>
                            &nbsp;|&nbsp;
                            <a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>&command=userName" title="Update Name">Update name</a>
                            &nbsp;|&nbsp;
                            <a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>&command=userEmail" title="Update Email">Update email</a>
                            &nbsp;|&nbsp;
                            <a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>&command=userPassword" title="Reset Password">Re-set password</a>
                            &nbsp;|&nbsp;
                            <a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>&command=userType" title="Change User Type">Change user type</a>
    
                            <?php if($guid != $userGuid) {?>
                                &nbsp;|&nbsp;
                                <a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>&command=delete" title="Delete User">Delete user</a>
                            <?php } ?>
                            
                        </div>     
                        
                        <table cellspacing='0' cellpadding='0' style='width:100%;margin-top:10px;border:1px solid;gray;'>
                            <tr class='rowAlt'>
                                <td style='padding:3px;padding-left:10px;'><b>Name:</b></td>
                                <td style='padding:3px;'>
                                    <?php 
                                        echo fnFormOutput($firstName . " " . $lastName . " (" . strtolower($userType) . ")");
                                    ?>
                                </td>
                                <td style='padding:3px;border-left:1px solid;gray;padding-left:10px;'><b>Email:</b></td>
                                <td style='padding:3px;'><?php echo strtolower($email);?></td>
                            </tr>
                            
                            <tr class='rowNormal'>
                                <td style='padding:3px;padding-left:10px;'><b>Created:</b></td>
                                <td style='padding:3px;'>
                                    <?php 
                                        if($daysSinceRegistration > 0){
                                            echo fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " (" . $daysSinceRegistration . " days ago)";
                                        }else{
                                            echo fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " <span style='color:red;'><i>today</i></span>";
                                        }
                                    ?>
                                </td>
                                
                                <td style='padding:3px;border-left:1px solid;gray;padding-left:10px;'><b>Modified:</b></td>
                                <td style='padding:3px;'>
                                
                                    <?php 
                                        if($daysSinceModified > 0){
                                            echo fnFromUTC($modifiedUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " (" . $daysSinceModified . " days ago)";
                                        }else{
                                            echo fnFromUTC($modifiedUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " <span style='color:red;'><i>today</i></span>";
                                        }
                                    ?>
                                
                                </td>
                                
                            </tr>
            
                            <tr class='rowAlt'>
                                <td style='padding:3px;padding-left:10px;'><b>Last Request:</b></td>
                                <td style='padding:3px;'>
                                    <?php 
                                        if($daysSinceLastRequest > 0){
                                            echo fnFromUTC($lastPageRequest, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " (" . $daysSinceLastRequest . " days ago)";
                                        }else{
                                            echo fnFromUTC($lastPageRequest, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " <span style='color:red;'><i>today</i></span>";
                                        }
                                    ?>
                                </td>
                                <td style='padding:3px;border-left:1px solid;gray;padding-left:10px;'><b>Page Views:</b></td>
                                <td style='padding:3px;'>
                                    <?php echo number_format(fnFormOutput($pageRequests), 0, ".", ",");?>
                                    <?php echo " (about " . $dailyVisits . " per day)";?>
                                
                                </td>
                            </tr>
                        </table>
					<?php } ?>
                    
                    <?php if($bolDeleted){ ?>
                        <div class='doneDiv'>
                            <b>User Deleted</b>
                            <div style='padding-top:10px;'>
                        		<a href="users.php?unused=true<?php echo $qVars;?>" title="Back to users"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>OK, go back to user list</a>
                            </div>
                        </div>
                    <?php } ?>
                    

                   	<?php if(strtoupper($command) == "DELETE"){ ?>
                        <div class='errorDiv' style='margin-top:15px;'>
                            <br/>
                            <b>Delete User</b>
                            <div style='padding-top:5px;'>
                                Are you sure you want to do this? This cannot be undone! When you
                                confirm this operation, all information and content associated with this user will be
                                permanently removed and you will not be able to get it back - ever. 
                            </div>
                            <div style='padding-top:10px;'>
                        		<a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>" title="No, do not delete"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>No, do not delete this user</a>
                            </div>
                            <div style='padding-top:10px;'>
                                <a href="user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>&command=confirmDelete"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>Yes, permanently delete this user</a>
                            </div>
                        </div>
                    <?php } ?>                                    


                    
                    <?php if($strMessage != "" && !$bolDone){ ?>
                        <div class='errorDiv' style='margin-top:15px;'>
                            <?php echo $strMessage;?>
                        </div>
                    <?php } ?>

                    <?php if($bolDone && !$bolDeleted){ ?>
                        <div class='doneDiv' style='margin-top:15px;'>
                            <b>User Updated</b>
                        </div>
                    <?php } ?>
                    
                    <?php 
                        if($command != "" && $command != "delete" && $command != "confirmDelete" && !$bolDeleted){ 
                            if($userGuid == $guid) {
                                
                                echo "<div class='infoDiv' style='margin-top:15px;'>";
                                echo "This is your own account information. ";
                                echo "Visit the <a href='" . fnGetSecureURL(APP_URL) . "/account' title='My Account'>My Account</a> ";
                                echo "screen to make changes to your own information. ";
                                echo "This process is designed to prevent you from accidentally locking yourself out of the portal.";
                                echo "</div>";
                            
                            }else{
                                echo "<div style='padding-top:10px;'>";
                                    if(strtoupper($command) == "USERNAME") include_once("inc_userName.php");
                                    if(strtoupper($command) == "USEREMAIL") include_once("inc_userEmail.php");
                                    if(strtoupper($command) == "USERPASSWORD") include_once("inc_userPassword.php");
                                    if(strtoupper($command) == "USERTYPE") include_once("inc_userType.php");
                                echo "</div>";
                            }
                        } 
                    ?>
                    
                </div>
                
                <div style='padding:10px;'>
                    <?php
                        if($command == "" && !$bolDone == true) {

                            $strSql = " SELECT A.id, A.guid, A.currentPublishVersion, A.version, A.name, A.iconUrl, A.dateStampUTC, A.modifiedUTC, A.viewCount ";
                            $strSql .= " FROM " . TBL_APPLICATIONS . " AS A ";
                            $strSql .= " WHERE A.status != 'deleted' AND A.ownerGuid = '" . $userGuid . "'";
                            $strSql .= " ORDER BY A.dateStampUTC DESC";
                            $remRes = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                            $cnt = 0;
                            if($remRes){
                                $numRows = mysql_num_rows($remRes);
                                if($numRows > 0){
                                    while($row = mysql_fetch_array($remRes)){
                                        $cnt++;
                                        $created = fnFromUtc($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
                                        $modified = fnFromUtc($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
                                        $iconUrl = fnGetSecureURL($row['iconUrl']);	
										if($iconUrl == ""){
											$iconUrl = fnGetSecureURL(APP_URL) . "../../images/default_app_icon.png";
										}
									
                                    	//link to application control panel...
                                    	$cpUrl = fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $row['guid'];
                                        
                                        
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
                                                    echo "\n<td style='vertical-align:top;padding:5px;'>";
                                                        echo "\n<b>" . fnFormOutput($row['name']) . "</b>";
                                                        echo "\n<br/>created: " . $created;
                                                        echo "\n<br/>modified: " . $modified;
                                                        echo "<br/>vers: " . $row['currentPublishVersion'] . " views: " . $row['viewCount'];
                                                    echo "\n</td>";
                                                echo "\n</tr>";
                                            echo "\n</table>";
                                        echo "\n</div>";
                                        
                                        
                                    }//end while
                                }//num rows
                            }//remRes
                            
                            if($cnt < 1){
                                echo "<div class='infoDiv'>This user does not own any apps yet.</div>";
                            }else{
                                if($cnt > 1){
                                    echo "\n<div style='clear:both;padding-top:10px;'>";
                                        echo  $cnt . " apps</b>";
                                    echo "</div>";
                                }
                            }	
                        
                        
                        }//command == ""
                        
                        ?>  
                </div>
                    
                    
                    
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
