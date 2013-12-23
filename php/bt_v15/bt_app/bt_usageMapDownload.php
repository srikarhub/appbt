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

	//vars...
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$startIndex = fnGetReqVal("startIndex", "0", $myRequestVars);
	$numRows = fnGetReqVal("numRows", "500", $myRequestVars);
	$dateSince = fnGetReqVal("dateSince", "", $myRequestVars);
	$locationOnly = fnGetReqVal("locationOnly", "", $myRequestVars);
	$csv = "";
	
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
	
	//app vars...
	$appName = $objApp->infoArray["name"];

	//count of total rows...
	$strSql = "SELECT count(id) FROM " . TBL_API_REQUESTS . " WHERE appGuid = '". $appGuid . "' ";
	$count = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	
	//########################################################################
	//create file on form submit
	if($isFormPost){
		
		$whereClause = " WHERE appGuid = '". $appGuid . "'";
		$limitClause = "";
		
		if(strlen($dateSince) > 1){
		
			if(!fnIsValidDate($dateSince)){
				$bolPassed = false;
				$strMessage .= "<br />Invalid date format";
			}else{
				
				//clear start / end index..
				$startIndex = "";
				$numRows = "";
				$whereClause .= " AND dateStampUTC >= '". fnShortMySqlDate($dateSince) . "'";
			
			}
			
		}else{
		
			if(!is_numeric($startIndex)){
				$bolPassed = false;
				$strMessage .= "<br />Start Row not numeric";
			}
			if(!is_numeric($numRows)){
				$bolPassed = false;
				$strMessage .= "<br />Number of Rows not numeric";
			}
			
			//still good?
			if(is_numeric($startIndex) && is_numeric($numRows)){
				$limitClause .= " LIMIT " . $startIndex . ", " . $numRows;
			}
			
			
		}
		
		//passed
		if($bolPassed){
		
			//output for rows...
			$csv = "";
		
			//update where clause?
			if($locationOnly == "1"){
				$whereClause .= " AND deviceLatitude > 0 ";
			}
			
			//get rows...
			$strSql = "SELECT requestCommand, clientRemoteAddress, deviceId, deviceModel, deviceLatitude, deviceLongitude, dateStampUTC ";
			$strSql .= " FROM " . TBL_API_REQUESTS;
			$strSql .= $whereClause;
			$strSql .= " ORDER BY id DESC ";
			if($limitClause != ""){
				$strSql .= $limitClause;
			}
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($res){
			
				$cnt = 0;
				$items = mysql_num_rows($res);
				$arrayDevices = array();
				$arrayLatitudes = array();
				
					//header row
					$csv .= "item, command, clientIPAddress, deviceId, deviceModel, deviceLatitude, deviceLongitude, UTCDate\n";
					
					while($row = mysql_fetch_array($res)){
						$cnt++;
						
						$csv .= "\"" . $cnt . "\",";
						$csv .= "\"" . $row["requestCommand"] . "\",";
						$csv .= "\"" . $row["clientRemoteAddress"] . "\",";
						$csv .= "\"" . $row["deviceId"] . "\",";
						$csv .= "\"" . $row["deviceModel"] . "\",";
						
						//latitude...
						if(is_numeric($row["deviceLatitude"])){
							$csv .= "\"" . $row["deviceLatitude"] . "\",";
						}else{
							$csv .= "\"0\",";
						}
						
						//longitude...
						if(is_numeric($row["deviceLongitude"])){
							$csv .= "\"" . $row["deviceLongitude"] . "\",";
						}else{
							$csv .= "\"0\",";
						}
						
						$csv .= "\"" . $row["dateStampUTC"]  . "\"";
						
						//last comma?
						if($cnt < $items){
							$csv .=",\n";
						}else{
							$csv .="\n";
						}
						
					}//end while
		
			}//if res
	
		}else{
		
			$strMessage .= "<br>(use start / end rows OR date since, not both)";
			
		}//bolPassed
	}//submit
	//########################################################################




	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="command" id="command" value="">


<script type="text/javascript">
	function startDownload(){
		var theForm = document.forms[0];
		document.getElementById("submit_data").style.visibility = "visible";
		theForm.submit();
	}
</script>


<div class='content'>
        
    <fieldset class='colorLightBg'>
           
            <!-- left side--> 
            <div class='boxLeft'>
                <div class='contentBox colorDarkBg minHeight' style='min-height:500px;'>
                    <div class='contentBand colorBandBg'>
                        Application Options
                    </div>
                    <div id="leftNavLinkBox" style='padding:10px;padding-bottom:25px;white-space:nowrap;'>
                        
                        <div><a href="index.php?appGuid=<?php echo $appGuid;?>" title="Application Home"><img src="../../images/arr_right.gif" alt="arrow"/>Application Home</a></div>
                        
                        <div><hr></div>
                        
                            <?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "block", ""); ?>
                    
                            <div><hr></div>
                            
                            <div><a href='bt_usageMap.php?appGuid=<?php echo $appGuid;?>' title='Usage Map'><img src='../../images/arr_right.gif' alt='arrow'/>Usage Map</a></div>
                            <div><a href='bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>' title='Push Notifications'><img src='../../images/arr_right.gif' alt='arrow'/>Push Notifications</a></div>
                            <div><a href='bt_appPackage.php?appGuid=<?php echo $appGuid;?>' title='Prepare Project Download'><img src='../../images/arr_right.gif' alt='arrow'/>Prepare Project Download</a></div>
                            <div><a href='bt_overTheAir.php?appGuid=<?php echo $appGuid;?>' title='Over the Air Distribution'><img src='../../images/arr_right.gif' alt='arrow'/>Over the Air Distribution</a></div>
                            <div><a href='bt_archives.php?appGuid=<?php echo $appGuid;?>' title='Application Archives'><img src='../../images/arr_right.gif' alt='arrow'/>Application Archives</a></div>
                            <div><a href="index.php?appGuid=<?php echo $appGuid;?>&command=delete" title="Permanently Delete App"><img src="../../images/arr_right.gif" alt="arrow"/>Permanently Delete App</a></div>
                        
                    </div>
                 </div>
            </div>
                    
            <div class='boxRight'>
                
                <div class='contentBox colorLightBg minHeight' style='min-height:500px;'>
                    
                    <div class='contentBand colorBandBg'>
                   		<?php echo fnFormOutput($objApp->infoArray["name"]);?> Usage Data Dowload
                    </div>
                        		
                   	<div style='padding:10px;padding-bottom:0px;'>
	                    <img src="../../images/arr_right.gif" alt="arrow"/><a href='bt_usageMap.php?appGuid=<?php echo $appGuid;?>' title="Usage Map">Back to Usage Map</a>
					</div>

                    <div class='cpExpandoBox colorDarkBg'>
                        
                        <div style='padding:10px;'>
                        	<b>About Downloading Data:</b>
                            This screen allows you to export data in .CSV (comma seperated values).
                        
                            The <a href='bt_usageMap.php?appGuid=<?php echo $appGuid;?>' title="Usage Map">usage map</a> 
                            pulls data from the database where location data is available. In most cases, data exists that
                            does not contain location information - you may choose to export that data too.
                             
                        </div>
                        
                        <div style='padding:10px;padding-top:0px;'>
                            <b>Because the database</b> may contain hundreds of thousands
                            (or millions) of items, it's possible that exporting the .CSV data could take some time. 
                            If you export a large amount of data, do not leave this page until the exported data appears.
                        </div>
                        
                        <div style='padding:10px;padding-top:0px;'>
                            <b>In most cases</b> you will want to limit the amount of data you download. 
                            Use the "start row number" and "number of rows" OR the "date since" box to limit the size
                            of the export.
                        </div>
                    </div>
                    
                    <div class='cpExpandoBox colorDarkBg'>
                        
                        <div style='padding:10px;padding-bottom:0px;padding-top:0px;'>
                            <h3>There are <b><u><?php echo $count;?></u></b> total items.</h3>
                        </div>
                        
                        <div style='padding:10px;padding-top:0px;'>
                            <b>Example 1:</b> If you enter 0 in the Start Row and 500 for Number of Rows you would get the 500 NEWEST items. 
                        </div>
                        <div style='padding:10px;padding-top:0px;'>
                            <b>Example 2:</b> If you entered 01/01/2012 in the Date Since box (and left start / number of rows empty) you would
                            get all items since January 1, 2012. 
                        </div>
                        
                        <div style='padding:10px;'>
                            
                            <div>
                                <input type="checkbox" name="locationOnly" id="locationOnly" value="1" <?php echo fnGetChecked("1", $locationOnly);?> />
                                Ignore items without location data
                            </div>
                        
                            <div style='float:left;width:175px;padding-top:5px;'>
                                <b>Start Row</b> (numeric)<br>
                                <input type="text" name="startIndex" id="startIndex" value="<?php echo fnFormOutput($startIndex);?>" style='width:150px;' />
                            </div>
                            
                            <div style='float:left;padding-top:5px;'>
                                <b>Number of Rows</b> (numeric)<br>
                                <input type="text" name="numRows" id="numRows" value="<?php echo fnFormOutput($numRows);?>" style='width:150px;' />
                            </div>
                            
                            <div style='clear:both;'></div>
                        
                            <div style='float:left;width:175px;'>
                                <b>Since Date</b> (01/01/2012)<br>
                                <input type="text" name="dateSince" id="dateSince" value="<?php echo fnFormOutput($dateSince);?>" style='width:150px;'>
                            </div>
                        
                            <div style='float:left;padding-top:15px;vertical-align:middle;'>
                                <input type='button' title="save" value="submit" align='absmiddle' class="buttonSubmit" onClick="startDownload();return false;">
                                <span id="submit_data" style='visibility:hidden;color:red;'>&nbsp;Working, please wait...</span>
                            </div>
                        
                            <div style='clear:both;'></div>
                        
                            <?php if($strMessage != "" && !$bolDone){ ?>
                                <div class='errorDiv'>
                                    <?php echo $strMessage;?>                                
                                </div>
                            <?php } ?> 
                        </div>
                       
                            
                        <div style="clear:both;"></div>
                        <div style='padding:10px;padding-top:0px;'>
                            <?php if($csv != "") { ?>
                                <b>Copy -n- Paste data into a plain text file to Save on your computer</b>
                                <textarea style='width:100%;height:500px;margin-top:5px;'><?php echo $csv;?></textarea>
                            <?php } ?>
                        </div>
                    
                    
                    </div>
            	</div>
            </div>                      
           
           
    </fieldset>


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
