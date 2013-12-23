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
	$thisPage->pageTitle = "Admin Control Panel | System Maintenance";

	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/admin_maintenance.js";	

	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/admin/maintenance_AJAX.php\";";

	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();
	$scriptName = "maintenance.php";

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;


	//get temp directory contents / size...
	$tmpFileCount = 0;
	$tmpDirectoryCount = 0;
	$tmpDir = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp";
	if(is_dir($tmpDir)){
		if($handle = opendir($tmpDir)){
			while(false !== ($file = readdir($handle))){
				if(strlen($file) > 3){
					if(is_dir($tmpDir . "/" . $file)){
						$tmpDirectoryCount++;
					}else{
						if(is_file($tmpDir . "/" . $file)){
							$tmpFileCount++;
						}
					}
				}
			}
		}		
	}
	
	//format tmpFileSize...
	if($tmpFileCount > 0 || $tmpDirectoryCount > 0){
		$tmpStatus = " contains <b>" . $tmpFileCount . "</b> files and <b>" . $tmpDirectoryCount . "</b> directories";
	}else{
		$tmpStatus = " <i>the directory is empty</i>";
	}
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<div class='content'>
<input type="hidden" name="command" id="command" value=""/>
        
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
                   System Maintenance
                </div>
                
                <div style='padding:10px;'>
                	
					<?php if($strMessage != "" && $bolDone){ ?>
                        <div class='doneDiv' style='margin-top:0px;'>
                            <?php echo $strMessage;?> 
                        </div>
                    <?php } ?>   
					<?php if($strMessage != "" && !$bolDone){ ?>
                        <div class='errorDiv' style='margin-top:0px;'>
                            <?php echo $strMessage;?> 
                        </div>
                    <?php } ?>   

                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_checkForUpdates');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Check for Updates</a>
                        <div id="box_checkForUpdates" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                            	
                                This software is capable of communicating with buzztouch.com server's to perform some behind-the-scenes functions.
                                However, this software will never contact buzztouch.com servers without your consent. 
                                <hr>
								No personal or confidential information is passed between this server and the buzztouch.com servers when the 
                                "Check for Updates" button is clicked. A Buzztouch Self Hosted API Key is required for these features.                           
                                
                            </div>
                            
                            <div style='padding-top:10px;'>
                                <input type='button' title="check" value="check" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('checkForUpdates');return false;">
                                <div id="submit_checkForUpdates" class="submit_working">&nbsp;</div>
                            </div>
                        
                        </div>
                    </div>



                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_tempFiles');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Temporary Files</a>
                        <div id="box_tempFiles" style="display:none;">
                            
                            <div style='padding-top:10px;'>
                            
								This software creates miscellaneous temporary files during normal operation.
                                It's important that these files are purged on occassion to save valuable 
                                storage space on your server. 
                                
                                <div style='padding-top:5px;font-size:12pt;'>
                                	<b><?php echo APP_DATA_DIRECTORY;?>/temp</b> <?php echo $tmpStatus;?></b> 
                                </div>
                                
                                <div style='padding-top:5px;'>
                                	There are a few ways to remove these files, including...
                                </div>
                                
                                <ol style='margin-top:0px;padding-top:0px;'>
                                	
                                    <li style='padding-top:5px;'>
                                    	Manually with this control panel by clicking the "empty" button below.
                                    </li>

                                    <li style='padding-top:5px;'>
                                    	Manually using an FTP client (like FileZilla, Dreamweaver, etc)
                                        to connect to your server then remove the files.
                                    </li>

                                </ol>
                            </div>
                            
                            <div style='padding-top:0px;'>
								<div sytle='padding-top:5px;'>
                                	<input type='button' title="empty" value="empty" align='absmiddle' class="buttonSubmit" onClick="saveScreenData('tempFiles');return false;">
                                </div>
                                <div id="submit_tempFiles" class="submit_working">&nbsp;</div>
                            </div>
                        
                        </div>
                    </div>


                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_deleteData');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Remove data marked for delete</a>
                        <div id="box_deleteData" style="display:none;">
                            
                            <div style='padding-top:10px;padding-bottom:10px;'>
                            
								Delete choices are provided on some control panel screens such as "delete this application" allowing you
                                to remove uneeded items. However, not all items that are
                                removed from the control panel are actually deleted from the database. In some cases the data is
                                flagged as "deleted" so it can be recovered in the event of an error. It is up to database
                                administrator managing this software to purge records marked as deleted. 
                                
                                
                            </div>
                            
                         </div>
                    </div>

                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_purgeTables');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Purge massive database tables</a>
                        <div id="box_purgeTables" style="display:none;">
                            
                            <div style='padding-top:10px;padding-bottom:10px;'>
                            
								The <b>bt_api_requests</b> table in the mySQL database can grow to extraordinary numbers. It's not unheard of for this tables to
                                hold many millions of rows. Depending on your hardware and other server variables, this may or may not pose a problem. 
                                However, low-cost, low-performance webservers may experience a performance hit if the size of the table grows too large. 
                                
                                
                                In this case, you'll want to consider purging this data ocassionally. Before purging the data, you'll 
                                want to carefully consider it's usefullness. Do you need to keep a copy for later reference? Reporting? Analytics?
                                
                             </div>
                            
                         </div>
                    </div>

                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_backupTables');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Backup mySQL database tables</a>
                        <div id="box_backupTables" style="display:none;">
                            
                            <div style='padding-top:10px;padding-bottom:10px;'>
                            
								This software relies heavily on mySQL and as such it's critical that it's database is backed up on a regular basis. 
								This advice is meant to serve as a reminder that hardware and software failures are common (as much as we all hate
                                to admit it) and backing up your database on a regular basis is critical.
                                
                            </div>
                            
                         </div>
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






