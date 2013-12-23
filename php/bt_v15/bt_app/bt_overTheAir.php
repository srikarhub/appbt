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
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";	

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
	$allowed_file_types[] = array("application/octet-stream", "ipa");
	$allowed_file_types[] = array("application/octet-stream", "plist");	
	$allowed_file_types[] = array("application/octet-stream", "apk");

	
	//allowed mime-types and extentions...
	$allowed_mime_types = array(); 
	$allowed_file_ext = array(); 
	foreach ($allowed_file_types as $theType){
		$allowed_mime_types[] = $theType[0];
		$allowed_file_ext[] = $theType[1];
	}

	//for css
	$colCount = 4;
	$scriptName = "bt_overTheAir.php";


	//build a friendly path name for display...
	$appInstallURL = fnGetSecureURL(APP_URL) . "/download/?id=" . $appGuid;
	
	//make sure this app's install-ios and install-android directories exists and are writeable...
	$strDirectoryMessage = "<br/>This applications \"install-ios\" and / or \"install-android\" directories do not exist or are not writeable. ";
	$strDirectoryMessage .= "This means you cannot use this control panel to upload .ipa or .apk files.";
	$appDirectoryPath = APP_PHYSICAL_PATH . $dataDir;
	if(is_dir($appDirectoryPath . "/install-ios/")){
		if(is_dir($appDirectoryPath . "/install-android/")){
			if(is_writable($appDirectoryPath . "/install-ios/")){
				if(is_writable($appDirectoryPath . "/install-android/")){
					$strDirectoryMessage = "";
				}
			}
		}
	}
	
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//upload submit...
	if(strtoupper($command) == "UPLOADARCHIVE"){
		
		//Get the file information
		$userfile_name = $_FILES['fileUpload']['name'];
		$userfile_tmp = $_FILES['fileUpload']['tmp_name'];
		$userfile_size = $_FILES['fileUpload']['size'];
		$userfile_type = $_FILES['fileUpload']['type'];
		$filename = basename($_FILES['fileUpload']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
		$saveAsFileName = "";
		
		//the directory we save to depends on the file type...	
		$archive_directory = "";		

		//ios files...
		if($file_ext == "ipa" || $file_ext == "plist"){
			$archive_directory = $appDirectoryPath . "/install-ios"; 
		}
		
		//android files...
		if($file_ext == "apk"){
			$archive_directory = $appDirectoryPath . "/install-android"; 
		}
		
		//save as file name..
		$saveAsFileName = $archive_directory . "/" . $userfile_name;
				
		//only process if the file is acceptable and below the allowed limit
		if((!empty($_FILES["fileUpload"])) && ($_FILES['fileUpload']['error'] == 0)) {
				
			//mime-type and extenstion must be allowed...
			if(!in_array($userfile_type, $allowed_mime_types) && !in_array($file_ext, $allowed_file_ext)){
				$bolPassed = false;
				$strMessage = "<br/><b>Invalid File Type</b>. Only app.ipa, app.plist, or app.apk files are allowed. ";
				$strMessage .= "<br/>You tried to upload a file named <b>" . $filename . "</b> with type <b>" . $userfile_type . "</b> with file extention <b>." . $file_ext . "</b>";
			}else{
			
				//files must only be named this!!!
				if(strtolower($userfile_name) != "app.ipa" && strtolower($userfile_name) != "app.plist" && strtolower($userfile_name) != "app.apk"){
					$bolPassed = false;
					$strMessage = "<br/><b>Invalid File Names</b>. Only app.ipa, app.plist, or app.apk files are allowed. ";
					$strMessage .= "<br/>You tried to upload a file named <b>" . $filename . "</b> with type <b>" . $userfile_type . "</b> with file extention <b>." . $file_ext . "</b>";
				}
			
			}
			
		}else{
			$bolPassed = false;
			$strMessage .= "<br/>Please select a app.ipa, app.plist, or app.apk file on your computer before clicking upload";
		}
		
		//see if the file exists already....
		if(is_file($saveAsFileName)){
			$bolPassed = false;
			$strMessage .= "<br>Duplicate file already exists, you cannot have duplicates. Delete the existing one then re-upload it. ";
			$strMessage .= "Preventing duplicates files is intentional, the process is designed to help you avoid mistakes."; 
		}
		
		
		//check if the file size is above the allowed limit
		if($bolPassed){
			if ($userfile_size > APP_MAX_UPLOAD_SIZE) {
				$bolPassed = false;
				$strMessage .= "<br/>Uploaded file is too large. The maximum allowed size is " . fnFormatBytes(APP_MAX_UPLOAD_SIZE) . " and you ";
				$strMessage .= "<br/>tried to upload a file that is " . fnFormatBytes($userfile_size);
			}
		}		
		
		//move file from temp. upload folder to /temp folder..
		if($bolPassed){
			if(!move_uploaded_file($userfile_tmp, $saveAsFileName)){
				$bolPassed = false;
				$strMessage .= "<br/><b>Error Saving File</b>. The file uploaded OK but it could not be saved to the file system?";
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
	$deleteFolder = fnGetReqVal("deleteFolder", "", $myRequestVars);
	if(strtoupper($command) == "CONFIRMDELETE" && strlen($deleteFileName) > 1){
		if(is_dir($appDirectoryPath . "/archives/")){
			if(is_writable($appDirectoryPath . "/" . $deleteFolder)){
				if(is_writable($appDirectoryPath . "/" . $deleteFolder . "/" . $deleteFileName)){
					@unlink($appDirectoryPath . "/" . $deleteFolder . "/" . $deleteFileName);
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
                   		<?php echo fnFormOutput($objApp->infoArray["name"]);?> Over the Air Distribution
                    </div>
                        		
                   	<div style='padding:10px;'>

                        <div class='cpExpandoBox colorDarkBg'>
                            <a href='#' onClick="fnShowHide('box_install');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Over the Air Installation URL</a>
                            <div id="box_install" style="display:none;padding:10px;">
                                <b>App Install URL:</b> The URL listed below is the URL you provide to end-users. It will not work unless
                                	 the requirement for iOS and / or Android distribution are met. After you upload the proper files, end-users
                                     install the app by entering the URL below in their MOBILE BROWERS
                                    (not their desktop browser). It's best to email or text or tweet this URL to end-users because entering
                                    long URL's in mobile browsers is very difficult (it is case senstive).
                                
                                    <div style='padding-top:10px;white-space:nowrap;'>
                                        <div style='margin-bottom:5px;'>
                                        	<a href='<?php echo $appInstallURL;?>&platform=ios' target='_blank'><img src='../../images/arr_right.gif' alt='arrow' />Show the iOS Install Screen</a>
                                        </div>
                                        <div style="font-size:12pt;">
											<?php echo $appInstallURL . "/platform=ios"?>
                                    	</div>
                                    </div>
                                    <div style='padding-top:10px;white-space:nowrap;'>
                                        <div style='margin-bottom:5px;'>
											<a href='<?php echo $appInstallURL;?>&platform=android' target='_blank'><img src='../../images/arr_right.gif' alt='arrow' />Show the Android Install Screen</a>
                                        </div>
                                        <div style="font-size:12pt;">
											<?php echo $appInstallURL . "/platform=android"?>
                                    	</div>
                                    </div>

                        
							</div>
                        </div>



                        <div class='cpExpandoBox colorDarkBg'>
                            <a href='#' onClick="fnShowHide('box_android');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Android Required Files</a>
                            <div id="box_android" style="display:none;padding:10px;">
                                </b> Upload an .apk file that you compiled and exported using Eclipse. When you export this file, 
                                it must be named app.apk (exactly, case sensitive). This app.apk file should be compiled and signed
                                for "release" as described here
                                <a href="http://developer.android.com/guide/publishing/app-signing.html" target="_blank">Signing your applications</a>.
                                End-users must have their devices set to "allow non-market apps" or they will not be able to
                                install the application. 
                            </div>
                        </div>

                        <div class='cpExpandoBox colorDarkBg'>
                            <a href='#' onClick="fnShowHide('box_ios');return false;"><img src='../../images/arr_right.gif' alt='arrow' />iOS Required Files</a>
                            <div id="box_ios" style="display:none;padding:10px;">
                                Upload an .ipa file that you compiled using Xcode for Ad-Hoc distribution. When you export this file,
                                it must be named app.ipa (exactly, case sensitive). Additionally, upload the app's .plist file. The .plist file
                                must be named app.plist (exactly, case sensitive). End users will not be able to install this app unless you
                                registered their device's UDID in the <a href="http://developer.apple.com/devcenter/ios/" target="_blank">Apple Developer Portal</a>, 
                                and included it in the provisioning profile you created for the Ad-Hoc build.
                            
                                <div style='padding-top:5px;color:red;'>
                                    When exporting the application using Xcode's organizer, you need to name the application file
                                    app.ipa (exactly, case sensitive). The export process will create the app.ipa file and the app.plist file
                                    for you. Next, you'll need to copy-n-paste 
                                    these URL's in the Distribute for Enterprise Dialogue:
                                </div>
                                <div style='padding-top:5px;'>
                                    <b>Application URL:</b> <?php echo fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/applications/" . $appGuid . "/install-ios/app.ipa";?>
                                </div>
                                <div style='padding-top:5px;'>
                                    <b>Icon URL:</b> <?php echo fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/applications/" . $appGuid . "/install-ios/icon.png";?>
                                </div>
                            </div>
                        </div>

                        <div class='cpExpandoBox colorDarkBg'>
                            <a href='#' onClick="fnShowHide('box_largeFiles');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Uploading Large Files</a>
                            <div id="box_largeFiles" style="display:none;padding:10px;">
                                <b>Large Uploads:</b> Because some servers will not allow large file uploads, you may want to use
                                an FTP program to upload the .apk, .ipa, and .plist files. If you do this, refresh this page to see the results. 
                            </div>
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

                           <?php if(strtoupper($command) == "DELETE"){ ?>
                                <div class="errorDiv" style='margin-top:20px;'>
                                    <br/>
                                    <b>Delete "<?php echo $deleteFileName;?>"</b>
                                    <div style='padding-top:5px;'>
                                        Are you sure you want to do this? This cannot be undone! When you
                                        confirm this operation, this file will be permanently removed and you
                                        will not be able to get it back - ever. 
                                    </div>
                                    <div style='padding-top:10px;'>
                                        <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete this file</a>
                                    </div>
                                    <div style='padding-top:10px;'>
                                        <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid . "&deleteFolder=" . $deleteFolder . "&deleteFileName=" . $deleteFileName;?>&command=confirmDelete"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete this file</a>
                                    </div>
                                </div>
                            <?php } ?>                                    

                            <?php if($bolDeleted){ ?>
                                <div class='doneDiv' style='margin-top:20px;'>
                                    File Deleted Successfully.                                
                                    <div style='padding-top:5px;'>
                                        <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                    </div>
                                </div>
                            <?php } ?> 

                            <?php if($bolDone){ ?>
                                <div class='doneDiv' style='margin-top:20px;'>
                                    File Uploaded Successfully.                                
                                    <div style='padding-top:5px;'>
                                        <a href="<?php echo $scriptName;?>?appGuid=<?php echo $appGuid;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                    </div>
                                </div>
                            <?php } ?> 
                            
                            <div style='padding:10px;padding-top:0px;'>
                                        
                                <table cellspacing='0' cellpadding='0' style='width:100%;'>
                                    <tr>
                                        <td style='vertical-align:top;padding-right:10px;'>
    
    
                                            <div style='padding-top:15px;'>
                                                <b>Choose the app.apk, app.ipa, <br/>
                                                or app.plist file to upload</b>
                                            </div>
                                        
                                            <div class="fileinputs">
                                                <input type="file" id="fileUpload" name="fileUpload" class="file"/>
                                                <div class="fakefile">
                                                    <input id="fileUploadValue" name="fileUploadValue" style="width:115px;height:18px;display:inline;vertical-align:middle;"/>
                                                    <img src="../../images/plus.png" alt="select" style='display:inline;vertical-align:middle;margin-top:-8px;cursor:pointer;'/>
                                                </div>
                                            </div>                                
                                			
                                            <div style='padding-top:5px;'>
                                                <input type='button' title="upload" value="upload" id="uploadButton" name="uploadButton" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnUploaddArchive();return false;">
                                            </div>
                                            <div id="isLoadingFile" class="cpExpandoBox" style='margin-left:0px;float:left;visibility:hidden;'>
                                                <img src="../../images/gif-loading-small.gif" style="height:40px;width:40px;margin-top:5px;">
                                            </div>
                                            <div id="isLoadingText" style='margin-top:15px;font-size:9pt;color:red;visibility:hidden;'>
                                                uploading...
                                                <br/>
                                                please wait...
                                            </div>
                                            <div class="clear"></div>
                                           
                                           
                                           
                                           
                                           
                                           
                                            
                                        </td>
                                        <td style='vertical-align:top;padding-top:25px;'>
            
                                
                                            <table cellspacing='0' cellpadding='0' width="99%">
                                
                                
                                                <?php
                                                
                                                    //build HTML rows for iOS files...
                                                    $cnt = 0;
                                                    $archiveRows = "";
                                                    if(is_dir($appDirectoryPath . "/install-ios/")){
                                                        if($handle = opendir($appDirectoryPath . "/install-ios/")){
                                                            while(false !== ($file = readdir($handle))){
                                                                if($file != "." && $file != ".."){
                                                                    
                                                                    //show the file?
																	$showName = $file;
                                                                    $bolShowFile = false;
                                                                    if(strtolower(substr($file, strrpos($file, ".") + 1)) == "ipa"){
                                                                        $bolShowFile = true;
																		$showName .= " (<i>iOS application</i>)";
                                                                    }
                                                                    if(strtolower(substr($file, strrpos($file, ".") + 1)) == "plist"){
                                                                        $bolShowFile = true;
																		$showName .= " (<i>iOS property list</i>)";
                                                                    }
                                                                    
                                                                    //do we show the file?
                                                                    if($bolShowFile){
                                                                        $cnt++;
                                                                        
                                                                        //style
                                                                        $css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
                                                                        
                                                                        $size = fnFormatBytes(filesize($appDirectoryPath . "/install-ios/" . $file));
                                                                        $modified = date("F d Y H:i:s", filemtime($appDirectoryPath . "/install-ios/" . $file));
            
                                                                        
                                                                        $archiveRows .= "\n\n<tr class='" . $css . "'>";
                                                                            $archiveRows .= "<td class='data'>" . $showName . "</td>";
                                                                            $archiveRows .= "<td class='data'>" . $modified . "</td>";
                                                                            $archiveRows .= "<td class='data'>" . $size . "</td>";
                                                                            $archiveRows .= "<td class='data' style='text-align:right;padding-right:5px;'><a href='" . $scriptName . "?appGuid=" . $appGuid . "&deleteFolder=install-ios&deleteFileName=" . urlencode($file) . "&command=delete' title='delete'>delete</a></td>";
                                                                        $archiveRows .= "</tr>";
                                                                        
                                                                    }
                                                                    
                                                                }
                                                            }
                                                            
                                                            //close dir
                                                            closedir($handle);
                                                            
                                                        }
                                                    }//isDir
    
                                                    //build HTML rows for Android files...
                                                    if(is_dir($appDirectoryPath . "/install-android/")){
                                                        if($handle = opendir($appDirectoryPath . "/install-android/")){
                                                            while(false !== ($file = readdir($handle))){
                                                                if($file != "." && $file != ".."){
                                                                    
                                                                    //show the file?
																	$showName = $file;
                                                                    $bolShowFile = false;
                                                                    if(strtolower(substr($file, strrpos($file, ".") + 1)) == "apk"){
                                                                        $bolShowFile = true;
																		$showName .= " (<i>Android application</i>)";
                                                                    }
                                                                    
                                                                    //do we show the file?
                                                                    if($bolShowFile){
                                                                        $cnt++;
                                                                        
                                                                        //style
                                                                        $css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
                                                                        
                                                                        $size = fnFormatBytes(filesize($appDirectoryPath . "/install-android/" . $file));
                                                                        $modified = date("F d Y H:i:s", filemtime($appDirectoryPath . "/install-android/" . $file));
            
                                                                        
                                                                        $archiveRows .= "\n\n<tr class='" . $css . "'>";
                                                                            $archiveRows .= "<td class='data'>" . $showName . "</td>";
                                                                            $archiveRows .= "<td class='data'>" . $modified . "</td>";
                                                                            $archiveRows .= "<td class='data'>" . $size . "</td>";
                                                                            $archiveRows .= "<td class='data' style='text-align:right;padding-right:5px;'><a href='" . $scriptName . "?appGuid=" . $appGuid . "&deleteFolder=install-android&deleteFileName=" . urlencode($file) . "&command=delete' title='delete'>delete</a></td>";
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
                                                        <td class="tdSort" style='padding-left:5px;'><b>File</b></td>
                                                        <td class="tdSort" style='padding-left:5px;'><b>Uploaded</b> (server time)</td>
                                                        <td class="tdSort" style='padding-left:5px;'><b>File Size</b></td>
                                                        <td class="tdSort" style='padding-left:5px;'></td>
                                                    </tr>
                                                    
                                                    <?php echo $archiveRows;?>
                                                <?php } ?>
                                                
                                            </table>
    
    
    
    
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        <?php } ?> 


                                
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
