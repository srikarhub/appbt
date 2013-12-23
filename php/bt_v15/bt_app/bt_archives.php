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
	
	//form does uploads...
	$thisPage->formEncType = "multipart/form-data";

	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appName = "";
	$command = fnGetReqVal("command", "", $myRequestVars);
	
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
	$dataDir = $objApp->infoArray["dataDir"];

	//allowed upload file types...
	$allowed_file_types = array();
	$allowed_file_types[] = array("application/zip",  "zip");
	$allowed_file_types[] = array("application/x-gzip", "zip");
	
	//allowed mime-types and extentions...
	$allowed_mime_types = array(); 
	$allowed_file_ext = array(); 
	foreach ($allowed_file_types as $theType){
		$allowed_mime_types[] = $theType[0];
		$allowed_file_ext[] = $theType[1];
	}

	//for css
	$colCount = 4;
	$scriptName = "bt_archives.php";


	//make sure this app's archives directory exists and is writeable...
	$strDirectoryMessage = "<br/>This applications \"archives\" directory does not exist or is not writeable. ";
	$strDirectoryMessage .= "This means you cannot use this control panel to upload application archives.";
	$appDirectoryPath = APP_PHYSICAL_PATH . $dataDir;
	if(is_dir($appDirectoryPath . "/archives/")){
		if(is_writable($appDirectoryPath . "/archives/")){
			$strDirectoryMessage = "";
		}
	}
	

	//build a friendly path name for display...
	$friendlyPath = $appDirectoryPath;
	$parts = explode("/", $friendlyPath);
	$cnt = 0;
	for($x = count($parts) - 1; $x > 0; $x--){
		if($parts[$x] != ""){
			$cnt++;
			if($cnt == 4){
				$friendlyPath = $parts[$x];
			}
		}
	}
	$friendlyPath = "/" . $friendlyPath . $dataDir . "/archives/";
	
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//upload submit...
	$archive_directory = $appDirectoryPath . "/archives"; 
	if(strtoupper($command) == "UPLOADARCHIVE"){
		
		//Get the file information
		$userfile_name = $_FILES['fileUpload']['name'];
		$userfile_tmp = $_FILES['fileUpload']['tmp_name'];
		$userfile_size = $_FILES['fileUpload']['size'];
		$userfile_type = $_FILES['fileUpload']['type'];
		$filename = basename($_FILES['fileUpload']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
		$saveAsFileName = "";
		
		//save as file name..
		$saveAsFileName = $archive_directory . "/" . $userfile_name;
				
		//only process if the file is acceptable and below the allowed limit
		if((!empty($_FILES["fileUpload"])) && ($_FILES['fileUpload']['error'] == 0)) {
				
			//mime-type and extenstion must be allowed...
			if(!in_array($userfile_type, $allowed_mime_types) && !in_array($file_ext, $allowed_file_ext)){
				$bolPassed = false;
				$strMessage = "<br/><b>Invalid File Type</b>. Only .zip archives are allowed. ";
				$strMessage .= "<br/>You tried to upload a file named <b>" . $filename . "</b> with type <b>" . $userfile_type . "</b> with file extention <b>." . $file_ext . "</b>";
			}
			
		}else{
			$bolPassed = false;
			$strMessage .= "<br/>Please select a .zip archive on your computer before clicking upload";
		}
		
		//see if the file exists already....
		if(is_file($saveAsFileName)){
			$bolPassed = false;
			$strMessage .= "<br>Duplicate .zip file already exists, you cannot have duplicates. Rename this file then re-upload it.";
		}
		
		
		//check if the file size is above the allowed limit
		if($bolPassed){
			if ($userfile_size > APP_MAX_UPLOAD_SIZE) {
				$bolPassed = false;
				$strMessage .= "<br/>Uploaded archive is too large. The maximum allowed size is " . fnFormatBytes(APP_MAX_UPLOAD_SIZE) . " and you ";
				$strMessage .= "<br/>tried to upload a archive that is " . fnFormatBytes($userfile_size);
			}
		}		
		
		//move file from temp. upload folder to /temp folder..
		if($bolPassed){
			if(!move_uploaded_file($userfile_tmp, $saveAsFileName)){
				$bolPassed = false;
				$strMessage .= "<br/><b>Error Saving Archive</b>. The file archive uploaded OK but it could not be saved to the file system?";
			}else{
				chmod($saveAsFileName, 0755);
			}
		}
		
		//continue if OK so far...
		if($bolPassed){
		
			$bolDone = true;
			
		}//bolPassed		
	
	}
	//done uploading
	///////////////////////////////////////////////////////////////////////////////////////////
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//if deleting...
	$deleteFileName = fnGetReqVal("deleteFileName", "", $myRequestVars);
	if(strtoupper($command) == "CONFIRMDELETE" && strlen($deleteFileName) > 1){
		if(is_dir($appDirectoryPath . "/archives/")){
			if(is_writable($appDirectoryPath . "/archives/")){
				if(is_writable($appDirectoryPath . "/archives/" . $deleteFileName)){
					@unlink($appDirectoryPath . "/archives/" . $deleteFileName);
					$bolDeleted = true;
				}
			}
		}
	}
	//done deleting...
	///////////////////////////////////////////////////////////////////////////////////////////


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="command" id="command" value="">

<script type="text/javascript">
	
	//onsubmit...
	function fnUploaddArchive(){
		document.getElementById("uploadButton").disabled = true;
		document.getElementById("isLoadingFile").style.visibility = "visible";
		document.getElementById("isLoadingText").style.visibility = "visible";
		document.forms[0].command.value = "uploadArchive";
		document.forms[0].submit();	
	}

	//fills div with selected file value, runs in loop..
	var theTimer = null;
	function fileUploadLabel(){
		try{
			var theValueEl = document.getElementById("fileUpload");
			var theDisplayEl = document.getElementById("fileUploadValue");
			if(theValueEl.value != ""){
				theDisplayEl.value = theValueEl.value;
			}
			theTimer = setTimeout("fileUploadLabel()", 100);
		}catch(err){
		
		}
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
                   		<?php echo fnFormOutput($objApp->infoArray["name"]);?> Project Archives
                    </div>
                        		
                   	<div style='padding:10px;'>

                        <div class='infoDiv'>
                            <b>Archives allow you to do 2 things...</b> 
                        </div>
                        <div>   
                            <ol>
    
                                <li style='margin-top:10px;'>
                                    <b>Version Control:</b> Upload a .zip folder containing all the app's files and assets for safe keeping.
                                    This is useful for storing different versions of the app. A common approach is to "zip up" the 
                                    Xcode or Eclipse project on a regular basis then upload them here for safe keeping.
                                </li>

                                <li style='margin-top:10px;'>
                                    <b>Project Sharing:</b> You can share the URL to any archive you upload. This is useful if you 
                                    want to allow someone else to download the project for testing, debugging, or design assistance. Copy the
                                    link to the archive after you upload it then email it to your colleagues.  
                                </li>
                                
                                
                                	<div style='padding-top:10px;'>
                                    	<b>Large Uploads:</b> Because some servers will not allow large file uploads, you may want to use
                                        an FTP program to upload the .zip archive to this app's archive directory. If you do this, 
                                        refresh the page to see the results.
                                    </div>
                                    
                                    <div class="cpExpandoBox" style='margin-left:0px;margin-top:10px;background-color:#FFFFFF;font-size:12pt;'>
										<div style='margin-bottom:5px;font-size:9pt;'>
                                        	<i>This applications archives are saved here:</i>
                                        </div>
										<?php echo $friendlyPath;?>
                                    </div>
                                
                                
									<?php if($strMessage != "" && !$bolDone){ ?>
                                        <div class='errorDiv' style='margin-top:20px;'>
                                            <?php echo $strMessage;?>                                
                                        </div>
                                    <?php } ?> 

                                
                                
									<?php if($strDirectoryMessage != ""){ ?>
                                    
                                        <div class='errorDiv' style='margin-top:20px;'>
                                            <?php echo $strDirectoryMessage;?>                                
                                        </div>
                                    
									<?php }else{ ?> 

                                        <div style='padding-top:15px;'>
                                            <b>Choose a .zip to archive</b>
                                        </div>
                                    
                                        <div class="fileinputs">
                                            <input type="file" id="fileUpload" name="fileUpload" class="file"/>
                                            <div class="fakefile">
                                                <input id="fileUploadValue" name="fileUploadValue" style="width:115px;height:18px;display:inline;vertical-align:middle;"/>
                                                <img src="../../images/plus.png" alt="select" style='display:inline;vertical-align:middle;margin-top:-8px;cursor:pointer;'/>
                                            </div>
                                        </div>                                
                            
                                        <div style='padding-top:5px;float:left;'>
                                            <input type='button' title="upload" value="upload" id="uploadButton" name="uploadButton" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnUploaddArchive();return false;">
                                        </div>
                                        
                                        <div id="isLoadingFile" style='margin-left:0px;float:left;visibility:hidden;'>
                                        </div>
                                        <div id="isLoadingText" style='margin-top:15px;float:left;margin-left:10px;font-size:9pt;color:red;visibility:hidden;'>
                                            uploading...
                                        </div>
                                        <div class="clear"></div>
                                        
                                        
                                        
										<?php if($bolDone){ ?>
                                            <div class='doneDiv' style='margin-top:0px;'>
                                                Archive Uploaded Successfully.                                
                        						<div style='padding-top:5px;'>
                            						<a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                        						</div>
                                            </div>
                                        <?php } ?> 

                                        
                                        
                                    <?php } ?> 


								   <?php if(strtoupper($command) == "DELETE"){ ?>
                                        <div class="errorDiv" style='margin-top:20px;'>
                                            <br/>
                                            <b>Delete "<?php echo $deleteFileName;?>"</b>
                                            <div style='padding-top:5px;'>
                                                Are you sure you want to do this? This cannot be undone! When you
                                                confirm this operation, this archive will be permanently removed and you
                                                will not be able to get it back - ever. 
                                            </div>
                                            <div style='padding-top:10px;'>
                                                <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this archive</a>
                                            </div>
                                            <div style='padding-top:10px;'>
                                                <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid . "&deleteFileName=" . $deleteFileName;?>&command=confirmDelete"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this archive</a>
                                            </div>
                                        </div>
                                    <?php } ?>                                    

									<?php if($bolDeleted){ ?>
                                        <div class='doneDiv' style='margin-top:20px;'>
                                            Archive Deleted Successfully.                                
                                            <div style='padding-top:5px;'>
                                                <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                            </div>
                                        </div>
                                    <?php } ?> 

									<br/>
                                    <table cellspacing='0' cellpadding='0' width="99%;margin-top:30px;">
                        
                        
                                        <?php
                                        
                                            //build HTML rows for each archive...
                                            $cnt = 0;
                                            $archiveRows = "";
                                            if(is_dir($appDirectoryPath . "/archives/")){
                                                if($handle = opendir($appDirectoryPath . "/archives/")){
                                                    while(false !== ($file = readdir($handle))){
                                                        if($file != "." && $file != ".."){
                                                            if(strtolower(substr($file, strrpos($file, ".") + 1)) == "zip"){
                                                                $cnt++;
                                                                
                                       							//style
                                        						$css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
																
																$size = fnFormatBytes(filesize($appDirectoryPath . "/archives/" . $file));
																$modified = date("F d Y H:i:s", filemtime($appDirectoryPath . "/archives/" . $file));

																
                                        						$archiveRows .= "\n\n<tr class='" . $css . "'>";
                                                                    $archiveRows .= "<td class='data'><a href='" . fnGetSecureURL(APP_URL) . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/applications/" . $appGuid . "/archives/" . $file . "' target='_blank' title='download'>" . $file . "</a></td>";
                                                                    $archiveRows .= "<td class='data'>" . $modified . "</td>";
                                                                    $archiveRows .= "<td class='data'>" . $size . "</td>";
                                                                    $archiveRows .= "<td class='data' style='text-align:right;padding-right:5px;'><a href='" . $scriptName . "?appGuid=" . $appGuid . "&deleteFileName=" . urlencode($file) . "&command=delete' title='delete'>delete</a></td>";
                                                                $archiveRows .= "</tr>";
                                                            }
                                                            
                                                        }
                                                    }
                                                    
                                                    //close dir
                                                    closedir($handle);
                                                    
                                                }
                                            }//isDir
                                        
                                        ?>
                        
                        
                                        <?php if($cnt > 0){ ?>
                                        <tr>
                                            <td class="tdSort" style='padding-left:5px;'><b>Archive</b></td>
                                            <td class="tdSort" style='padding-left:5px;'><b>Uploaded</b> (server time)</td>
                                            <td class="tdSort" style='padding-left:5px;'><b>File Size</b></td>
                                            <td class="tdSort" style='padding-left:5px;'></td>
                                        </tr>
                                        
                                        <?php echo $archiveRows;?>
                                        
                                        
                                        <tr>
                                            <td colspan='<?php echo $colCount;?>' style='padding-top:5px;text-align:right;vertical-align:top;'>
                                               
                                                <div style='padding:5px;'>
                                                    <?php 
														$plural = "";
														if($cnt > 1) $plural = "s";
														echo $cnt . " Archive" . $plural;
														
													?>
                                                </div>
                                                 
                                            </td>
                                        </tr>
                                        
                                        <?php } ?>
                                        
                                    </table>
            
 								</li>
                            </ol>
                        </div>
                        
                                
                    </div>
                    
                    
                    
            	</div>
            </div>                      
           
           
    </fieldset>
        

<script type="text/javascript">
	//continuous loop  to show selected file label..
	theTimer = setTimeout("fileUploadLabel()", 100);
</script>

<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
