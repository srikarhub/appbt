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
	$thisPage->pageTitle = "Admin Control Panel | Control Panel Id Details";

	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();

	//vars
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;

	//what apiKey did we click?
	$apiKeyGuid = fnGetReqVal("apiKeyGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	if($apiKeyGuid == ""){
		echo "invalid request";
		exit();
	}
	
	//apiKey details...
	$objApiKey = new Bt_apikey($apiKeyGuid);
	$apiKey = $objApiKey->infoArray["apiKey"];
	$apiSecret = $objApiKey->infoArray["apiSecret"];
	$ownerName = $objApiKey->infoArray["ownerName"];
	$email = $objApiKey->infoArray["email"];
	$allowedIPAddress = $objApiKey->infoArray["allowedIPAddress"];
	$expiresDate = $objApiKey->infoArray["expiresDate"];
	$lastRequestUTC = $objApiKey->infoArray["lastRequestUTC"];
	$requestCount = $objApiKey->infoArray["requestCount"];
	$dateStampUTC = $objApiKey->infoArray["dateStampUTC"];
	$modifiedUTC = $objApiKey->infoArray["modifiedUTC"];

	if(!is_numeric($requestCount)) $requestCount = 0;

	//average API requests per day
	$dailyRequests = "";
	if($dateStampUTC != "" && $requestCount > 0){
		$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
		$numDays = fnDateDiff("d", fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
		$dailyRequests = "n/a";
		if($numDays > 0 && $requestCount != "0" && $requestCount != ""){
			$dailyRequests = round(($requestCount / $numDays), 1);
		}else{
			$dailyRequests = $requestCount;
		}
	}
	
	//for updates...
	$newApiKey = $apiKey;
	$newOwnerName = $ownerName;
	$newOwnerName = $ownerName;
	$newEmail = $email;
	$newAllowedIPAddress = $allowedIPAddress;
	$newExpiresDate = $expiresDate;
	
		
	////////////////////////////
	//include files post updates
	if($isFormPost){
		
		//values from form elements
		$newApiKey = fnGetReqVal("newApiKey", "", $myRequestVars);
		$newApiSecret = fnGetReqVal("newApiSecret", "", $myRequestVars);
		$newConfirmApiSecret = fnGetReqVal("newConfirmApiSecret", "", $myRequestVars);
		$newOwnerName = fnGetReqVal("newOwnerName", "", $myRequestVars);
		$newEmail = fnGetReqVal("newEmail", "", $myRequestVars);
		$newAllowedIPAddress = fnGetReqVal("newAllowedIPAddress", "", $myRequestVars);
		$newExpiresDate = fnGetReqVal("newExpiresDate", "", $myRequestVars);
	
		if(strlen($newApiKey) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Control Panel Id Key Required";
		}else{
			if(!fnIsAlphaNumeric($newApiKey, false)){
				$bolPassed = false;
				$strMessage .= "<br />Letter and numbers only for Control Panel Id, no spaces";
			}
		}
		
		//may not be updating the secret...
		if($newApiSecret != ""){
			if(!fnIsAlphaNumeric($newApiSecret, false)){
				$bolPassed = false;
				$strMessage .= "<br />Letter and numbers only for password, no spaces";
			}
			if($newApiSecret != $newConfirmApiSecret){
				$bolPassed = false;
				$strMessage .= "<br />Passwors don't match.";
			}
		}

		if(!fnIsValidDate($newExpiresDate)){
			$bolPassed = false;
			$strMessage .= "<br />Expires date invalid. Required Format: 01/01/2025";
		}


		if(strlen($newOwnerName) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Owner Name Required";
		}
		if(strlen($newEmail) > 1){
			if(!fnIsEmailValid($newEmail) ){
				$bolPassed = false;
				$strMessage .= "<br />Valid Email Required";
			}
		}
		
		//quick check to make sure key is available....
		if($bolPassed){
			$tmpKey = new Bt_apikey("");
			if(!$tmpKey -> fnIsKeyAvailable($newApiKey, $apiKeyGuid)){
				$bolPassed = false;
				$strMessage .= "<br />This Control Panel Id is not available (duplicates are not allowed). Please choose a different id";
			}
		}
		
		//passed?
		if($bolPassed){

			//update expires date...
			$updateExpiresDate = fnMySqlDate($newExpiresDate);

			//update...
			$strSql = "UPDATE " . TBL_API_KEYS . " SET ";
			$strSql .= "apiKey = '" . $newApiKey . "', ";
			$strSql .= "ownerName = '" . $newOwnerName . "', ";
			$strSql .= "email = '" . $newEmail . "', ";
			$strSql .= "allowedIPAddress = '" . $newAllowedIPAddress . "', ";
			$strSql .= "expiresDate = '" . $updateExpiresDate . "', ";
			if($newApiSecret != ""){
				$strSql .= "apiSecret = '" . md5($newApiSecret) . "',";
			}
			$strSql .= " modifiedUTC = '" . $dtNow . "' ";
			$strSql .= " WHERE guid = '" . $apiKeyGuid . "' ";
			
			//execute update
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
			//refill display vars...
			$apiKey = $newApiKey;
			$ownerName = $newOwnerName;
			$email = $newEmail;
			$allowedIPAddress = $newAllowedIPAddress;
			$expiresDate = $newExpiresDate;

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
	if($lastRequestUTC == ""){
		$daysSinceLastRequest = "";
	}else{
		$daysSinceLastRequest = fnDateDiff("d", fnFromUTC($lastRequestUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
	}
	
	//days since registration
	if($dateStampUTC == ""){
		$daysSinceCreation = "";
	}else{
		$daysSinceCreation = fnDateDiff("d", fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A"), $dtToday);
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
	if($apiKeyGuid != "" && $command == "confirmDelete"){
		
		//cannot delete API keys that are assoicated with apps.
		$tmp = "SELECT guid FROM " . TBL_APPLICATIONS . " WHERE apiKey = '" . $apiKey . "' ";
		$tmp .= " AND status != 'deleted'";
		$tmpGuid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		if(strlen($tmpGuid) > 0){
			
			//get the app's name so we can show it...
			$objApp = new App($tmpGuid);
			
			$bolPassed = false;
			$strMessage .= "<br>This Control Panel Id is associated with an application. You cannot remove this Control Panel Id unless you first remove ";
			$strMessage .= "the application named <b>\"" . fnFormOutput($objApp->infoArray["name"]) . "\"</b>. ";
			
		}else{
		
			$tmp = "UPDATE " . TBL_API_KEYS . " SET status = 'deleted', modifiedUTC = '" . $dtNow . "' ";
			$tmp .= "WHERE guid = '" . $apiKeyGuid . "'";
			fnExecuteNonQuery($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
			$bolDeleted = true;
			$bolDone = true;
		
		}
	}//if deleting..
	
	//format expiresDate for display..
	if($expiresDate != ""){
		$expiresDate = date("m/d/Y", strtotime($expiresDate));
	}	
	$newExpiresDate = $expiresDate;
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="apiKeyGuid" id="apiKeyGuid" value="<?php echo $apiKeyGuid;?>" />
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
                   API Key Details
                </div>
    
                <div style='padding:10px;'>
                	
					<?php if(!$bolDeleted){ ?> 
                    
                        <div>
                            <a href="keys.php?unused=true<?php echo $qVars;?>" title="Users">Back to Manage Data Access</a>
                            &nbsp;|&nbsp;
                            <a href="key_details.php?unused=true<?php echo $qVars;?>&apiKeyGuid=<?php echo $apiKeyGuid;?>&command=details" title="Update Details">Update Details</a>
                            &nbsp;|&nbsp;
                            <a href="key_details.php?unused=true<?php echo $qVars;?>&apiKeyGuid=<?php echo $apiKeyGuid;?>&command=delete" title="Delete API Key">Delete Control Panel Id</a>
                        </div>     
                        
                        <table cellspacing='0' cellpadding='0' style='width:100%;margin-top:10px;border:1px solid;gray;'>
                            <tr class='rowAlt'>
                                <td style='padding:3px;padding-left:10px;'><b>Owner:</b></td>
                                <td style='padding:3px;'>
                                    <?php echo fnFormOutput($ownerName);?>
									&nbsp;&nbsp;
									<?php echo strtolower($email);?>
                                    
                                </td>
                                <td style='padding:3px;border-left:1px solid;gray;padding-left:10px;'><b>Expires:</b></td>
                                <td style='padding:3px;'><?php echo $expiresDate;?></td>
                            </tr>
                            
                            <tr class='rowNormal'>
                                <td style='padding:3px;padding-left:10px;'><b>Created:</b></td>
                                <td style='padding:3px;'>
                                    <?php 
                                        if($daysSinceCreation > 0){
                                            echo fnFromUTC($dateStampUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " (" . $daysSinceCreation . " days ago)";
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
										if($requestCount > 0){
                                        	if($daysSinceLastRequest > 0){
                                            	echo fnFromUTC($lastRequestUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " (" . $daysSinceLastRequest . " days ago)";
                                        	}else{
                                            	echo fnFromUTC($lastRequestUTC, $thisUser->infoArray["timeZone"], "m/d/y h:i A") . " <span style='color:red;'><i>today</i></span>";
                                        	}
										}else{
											echo "<i>never</i>";
										}
                                    ?>
                                </td>
                                <td style='padding:3px;border-left:1px solid;gray;padding-left:10px;'><b>Requests:</b></td>
                                <td style='padding:3px;'>
                                    <?php echo number_format(fnFormOutput($requestCount), 0, ".", ",");?>
                                    <?php if($requestCount > 0){
											echo " (about " . $dailyRequests . " per day)";
										}
									?>
                                
                                </td>
                            </tr>
                        </table>
					<?php } ?>
                    
                    <?php if($bolDeleted){ ?>
                        <div class='doneDiv'>
                            <b>API Key Deleted</b>
                            <div style='padding-top:10px;'>
                        		<a href="keys.php?unused=true<?php echo $qVars;?>" title="Back to API Keys"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>OK, go back to API Keys</a>
                            </div>
                        </div>
                    <?php } ?>
                    

                   	<?php if(strtoupper($command) == "DELETE"){ ?>
                        <div class='errorDiv' style='margin-top:15px;'>
                            <br/>
                            <b>Delete Control Panel Id</b>
                            <div style='padding-top:5px;'>
                                Are you sure you want to do this? This cannot be undone! When you
                                confirm this operation, no applications or people that use this Control Panel Id will
                                be able to pull data from the server. 
                            </div>
                            <div style='padding-top:5px;'>
                            	THIS INCLUDES APPS YOU HAVE DEPLOYED THAT HAVE THIS CONTROL PANEL ID 
                                COMPILED IN THEIR BINARIES.
                            </div>
                            <div style='padding-top:5px;'>
                            	A better approach may be to simply change the password or the expiration date to it's 
                                effectively unusable. 
                            </div>
                            <div style='padding-top:10px;'>
                        		<a href="key_details.php?unused=true<?php echo $qVars;?>&apiKeyGuid=<?php echo $apiKeyGuid;?>" title="No, do not delete"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>No, do not delete this Control Panel Id</a>
                            </div>
                            <div style='padding-top:10px;'>
                                <a href="key_details.php?unused=true<?php echo $qVars;?>&apiKeyGuid=<?php echo $apiKeyGuid;?>&command=confirmDelete"><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>Yes, permanently delete this Control Panel Id</a>
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
                            <b>Control Panel Id Updated</b>
                        </div>
                    <?php } ?>
                    
                    <?php 
                        if($command != "" && $command != "delete" && $command != "confirmDelete" && !$bolDeleted){ 

							echo "<div style='padding-top:10px;'>";
								if(strtoupper($command) == "DETAILS") include_once("inc_keyDetails.php");
							echo "</div>";
						
                        } 
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
