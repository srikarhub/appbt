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
	$thisPage->pageTitle = "Admin Control Panel | Control Panel Links";
	
	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$linkGuid = fnGetReqVal("linkGuid", "", $myRequestVars);
	
	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if updating order
	if(isset($_POST)){
		foreach($_POST as $name => $value){
			if(substr($name, 0, 6) == "order_"){
				$tmpGuid = substr($name, 6);
				$newIndex = $value;
				if(!is_numeric($newIndex)) $newIndex = 99;
				$tmpSql = "UPDATE " . TBL_CP_LINKS . " SET orderIndex = '" . $newIndex . "' WHERE guid = '" . $tmpGuid . "'";
				fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			}
		}
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if deleting a link item
	if($linkGuid != "" && $command == "confirmDelete"){
		
		$strSql = "DELETE FROM " . TBL_CP_LINKS . " WHERE guid = '" . $linkGuid . "' ";
		$strSql .= " AND isEditable = '1'";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		$bolDeleted = TRUE;
			
	}//if deleting
	
	//this page...
	$scriptName = "admin_links.php";
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>


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
                   Control Panel Links
                </div>
                
                <div style='padding:10px;'>
                	
					<?php if(strtoupper($command) == "DELETE"){ ?>
                        
                        <div class="errorDiv">
                            <br/>
                            <b>Are you sure you want to delete this link? This cannot be undone.</b>
                            <div style='padding-top:10px;'>
                                <a href="<?php echo $scriptName;?>"><img src="../images/arr_right.gif" alt='arrow'/>No, do not delete this link</a>
                            </div>
                            <div style='padding-top:10px;'>
                                <a href="<?php echo $scriptName;?>?linkGuid=<?php echo $linkGuid;?>&command=confirmDelete"><img src="../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this link</a>
                            </div>
                        </div>
                    
                    <?php } ?>
                    
                    
                    
                    <?php
						
						//fill arrays with data....
						$accountLinks = array();
						$adminLinks = array();
						$appLinks = array();
						
						
						//fetch all the links...
                        $strSql = " SELECT guid, linkType, linkLabel, linkURL, linkTarget, orderIndex, isEditable ";
                        $strSql .= "FROM " . TBL_CP_LINKS;
                        $strSql .= " ORDER BY orderIndex ";
                        $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                        if($res){
                        	$numRows = mysql_num_rows($res);
                            $cnt = 0;
                            while($row = mysql_fetch_array($res)){
								
								//pipe separated string is split during output...
								$linkData = $row["guid"] . "|" . fnFormOutput($row["linkLabel"]) . "|" . $row["linkURL"] . "|" . $row["orderIndex"] . "|" . $row["isEditable"];
                            	
								switch(strtoupper($row["linkType"])){
									case "ACCOUNT":
										$accountLinks[] = $linkData;
										break;
									case "ADMIN":
										$adminLinks[] = $linkData;;
										break;
									case "APPLICATION":
										$appLinks[] = $linkData;;
										break;
								}
								
								
							}//end while
						}
					?>
                    
                    
					<style type="text/css">
                        .h{padding:3px;padding-left:5px;font-weight:bold;}
                        .d{padding:0px;padding-left:5px;vertical-align:middle;}
                    </style>                    
                    
                    <table cellspacing='0' cellpadding='0' width="99%;">
                        
                        <!-- account links -->
                        <tr class="rowAlt">
                        	<td class="h">
                            	Account Control Panel Links
                            </td>
                        	<td class="h">
								URL
							</td>
                            <td class='h' style='text-align:center;width:75px;'>
                            	Order
                            </td>
                            <td class='h' style='text-align:center;width:25px;'>
                            	
                            </td>

                        </tr>
                        	<?php 
								for($x = 0; $x < count($accountLinks); $x++){
									$parts = explode("|", $accountLinks[$x]);
									if(count($parts) == 5){
										$linkGuid = $parts[0];
										$label = $parts[1];
										$url = $parts[2];
											if(strlen($url) > 75) $url = substr($url, 0, 75) . "...";
										$orderIndex = $parts[3];
										$isEditable = $parts[4];
										
										//is editable?
										$deleteLink = "&nbsp;";
										if($isEditable == "1"){
                                			$deleteLink = "<a href='" . $scriptName . "?linkGuid=" . $linkGuid . "&command=delete' title='Delete' style='vertical-align:middle;'>delete</a>";
										}
										
										echo "\n<tr>";
											echo "\n<td class='d'>";
                                				echo "<a href='link_details.php?linkGuid=" . $linkGuid . "' title='Details' style='vertical-align:middle;'>" . $label . "</a>";
											echo "</td>";
											echo "\n<td class='d'>" . $url . "</td>";
											echo "\n<td class='d' style='text-align:center;width:75px;'>";
												echo "\n<input type='text' id='order_" . $linkGuid . "' name='order_" . $linkGuid . "' value='" . $orderIndex . "' style='width:75px;margin:1px;text-align:center;'>";
											echo "</td>";
											echo "\n<td class='d' style='width:25px;'>";
												echo $deleteLink;
											echo "\n</td>";
										echo "\n</tr>";
									}
								}
								
								//footer row...
								echo "\n<tr>";
									echo "\n<td colspan='2' class='d' style='padding-top:5px;'>";
                                		echo "<a href='link_details.php?linkType=account' title='Add Link' style='vertical-align:middle;'><img src='../images/plus.png' style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add Link</a>";
									echo "</td>";
									echo "\n<td class='d' class='d' style='text-align:center;width:75px;'>";
                                    	echo "\n<input type='button' title='update' value=update align='absmiddle' class='buttonSubmit' onClick=\"document.forms[0].submit();return false;\">";
									echo "\n</td>";
									echo "\n<td class='d' style='text-align:center;width:25px;'>";
										echo "&nbsp;";
									echo "\n</td>";
								echo "\n</tr>";	
								
							?>
                        <tr>
                        	<td colspan='3'>&nbsp;</td>
                        </tr>



                        <!-- admin links -->
                        <tr class="rowAlt">
                        	<td class="h">
                            	Admin Control Panel Links
                            </td>
                        	<td class="h">
								URL
							</td>
                            <td class='h' style='text-align:center;width:75px;'>
                            	Order
                            </td>
                            <td class='h' style='text-align:center;width:25px;'>
                            	
                            </td>
                        </tr>
                        	<?php 
								for($x = 0; $x < count($adminLinks); $x++){
									$parts = explode("|", $adminLinks[$x]);
									if(count($parts) == 5){
										$linkGuid = $parts[0];
										$label = $parts[1];
										$url = $parts[2];
											if(strlen($url) > 75) $url = substr($url, 0, 75) . "...";
										$orderIndex = $parts[3];
										$isEditable = $parts[4];
										
										//is editable?
										$deleteLink = "&nbsp;";
										if($isEditable == "1"){
                                			$deleteLink = "<a href='" . $scriptName . "?linkGuid=" . $linkGuid . "&command=delete' title='Delete' style='vertical-align:middle;'>delete</a>";
										}
										
										echo "\n<tr>";
											echo "\n<td class='d'>";
                                				echo "<a href='link_details.php?linkGuid=" . $linkGuid . "' title='Details' style='vertical-align:middle;'>" . $label . "</a>";
											echo "</td>";
											echo "\n<td class='d'>" . $url . "</td>";
											echo "\n<td class='d' style='text-align:center;width:75px;'>";
												echo "\n<input type='text' id='order_" . $linkGuid . "' name='order_" . $linkGuid . "' value='" . $orderIndex . "' style='width:75px;margin:1px;text-align:center;'>";
											echo "</td>";
											echo "\n<td class='d' style='width:25px;'>";
												echo $deleteLink;
											echo "\n</td>";
										echo "\n</tr>";
									}
								}
								
								//footer row...
								echo "\n<tr>";
									echo "\n<td colspan='2' class='d' style='padding-top:5px;'>";
                                		echo "<a href='link_details.php?linkType=admin' title='Add Link' style='vertical-align:middle;'><img src='../images/plus.png' style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add Link</a>";
									echo "</td>";
									echo "\n<td class='d' style='text-align:center;width:75px;'>";
                                    	echo "\n<input type='button' title='update' value=update align='absmiddle' class='buttonSubmit' onClick=\"document.forms[0].submit();return false;\">";
									echo "\n</td>";
									echo "\n<td class='d' style='text-align:center;width:25px;'>";
										echo "&nbsp;";
									echo "\n</td>";
								echo "\n</tr>";	
								
							?>
                        <tr>
                        	<td colspan='3'>&nbsp;</td>
                        </tr>


                        <!-- application links -->
                        <tr class="rowAlt">
                        	<td class="h">
                            	Application Control Panel Links
                            </td>
                        	<td class="h">
								URL
							</td>
                            <td class='h' style='text-align:center;width:75px;'>
                            	Order
                            </td>
                            <td class='h' style='text-align:center;width:25px;'>
                            	
                            </td>
                        </tr>
                        	<?php 
								for($x = 0; $x < count($appLinks); $x++){
									$parts = explode("|", $appLinks[$x]);
									if(count($parts) == 5){
										$linkGuid = $parts[0];
										$label = $parts[1];
										$url = $parts[2];
											if(strlen($url) > 75) $url = substr($url, 0, 75) . "...";
										$orderIndex = $parts[3];
										$isEditable = $parts[4];
										
										//is editable?
										$deleteLink = "&nbsp;";
										if($isEditable == "1"){
                                			$deleteLink = "<a href='" . $scriptName . "?linkGuid=" . $linkGuid . "&command=delete' title='Delete' style='vertical-align:middle;'>delete</a>";
										}
										
										echo "\n<tr>";
											echo "\n<td class='d'>";
                                				echo "<a href='link_details.php?linkGuid=" . $linkGuid . "' title='Details' style='vertical-align:middle;'>" . $label . "</a>";
											echo "</td>";
											echo "\n<td class='d'>" . $url . "</td>";
											echo "\n<td class='d' style='text-align:center;width:75px;'>";
												echo "\n<input type='text' id='order_" . $linkGuid . "' name='order_" . $linkGuid . "' value='" . $orderIndex . "' style='width:75px;margin:1px;text-align:center;'>";
											echo "</td>";
											echo "\n<td class='d' style='width:25px;'>";
												echo $deleteLink;
											echo "\n</td>";
										echo "\n</tr>";
									}
								}
								
								//footer row...
								echo "\n<tr>";
									echo "\n<td colspan='2' class='d' style='padding-top:5px;'>";
                                		echo "<a href='link_details.php?linkType=application' title='Add Link' style='vertical-align:middle;'><img src='../images/plus.png' style='vertical-align:middle;margin-bottom:4px;margin-right:5px;'/>Add Link</a>";
									echo "</td>";
									echo "\n<td class='d' style='text-align:center;width:75px;'>";
                                    	echo "\n<input type='button' title='update' value=update align='absmiddle' class='buttonSubmit' onClick=\"document.forms[0].submit();return false;\">";
									echo "\n</td>";
									echo "\n<td class='d' style='text-align:center;width:25px;'>";
										echo "&nbsp;";
									echo "\n</td>";
								echo "\n</tr>";	
								
							?>
                        <tr>
                        	<td colspan='3'>&nbsp;</td>
                        </tr>

            
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






