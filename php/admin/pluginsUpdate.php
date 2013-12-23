<?php   require_once("../config.php");
		require_once("../includes/zip.php");
	
	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	//fnContainsPHP...
	function fnContainsPHP($path){
		$ret = false;
		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
		while($it->valid()) {
			if (!$it->isDot()) {
				if(substr(strtoupper($it->getSubPathName()), -4) == ".PHP"){
					$ret = true;
				}
			}
		
			//move next...
			$it->next();
		}
		
		//return...
		return $ret;

	}



	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Admin Control Panel | Plugin Maintenance";

	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/admin_plugins.js";	

	//javascript inline in head section...
	$thisPage->jsInHead = "var ajaxURL = \"" . fnGetSecureURL(APP_URL) . "/admin/pluginUpdate_AJAX.php\";";

	//form does uploads...
	$thisPage->formEncType = "multipart/form-data";

	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$month = date("m", strtotime($dtToday));
	$dtNow = fnMySqlNow();
	$scriptName = "pluginsUpdate.php";
	
	//hide / show boxes...
	$uploadBoxCSS = "none";
	$downloadBoxCSS = "none";
	
	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$pluginGuid = fnGetReqVal("pluginGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$overwiteDuplicate = fnGetReqVal("overwiteDuplicate", "", $myRequestVars);

	//from previous screen...
	$defaultUpDown = "DESC";
	$sortUpDown = fnGetReqVal("sortUpDown", $defaultUpDown, $myRequestVars);
	$sortColumn = fnGetReqVal("sortColumn", "", $myRequestVars);
	$currentPage = fnGetReqVal("currentPage", "1", $myRequestVars);
	$search = fnGetReqVal("searchInput", "search...", $myRequestVars);

	//querystring for links
	$qVars = "&from=plugins&searchInput=" . fnFormOutput($search);
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	$qVars .= "&currentPage=" . $currentPage;
	
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
	
	///////////////////////////////////////////////////////////////////////////////////////////
	//upload submit...
	$plugin_directory = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins"; 
	$temp_directory = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp"; 					
	if(strtoupper($command) == "UPLOADPLUGIN"){
		
		//keep upload box open...
		$uploadBoxCSS = "block";
		$downloadBoxCSS = "none";
		
		//get info from uploaded file...
		$userfile_name = $_FILES['fileUpload']['name'];
		$userfile_tmp = $_FILES['fileUpload']['tmp_name'];
		$userfile_size = $_FILES['fileUpload']['size'];
		$userfile_type = $_FILES['fileUpload']['type'];
		$filename = basename($_FILES['fileUpload']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
		$saveAsFileName = "";
		
		//save as file name..
		$saveAsFileName = $plugin_directory . "/" . $userfile_name;
		$tempSaveAsFileName = $temp_directory . "/" . $userfile_name;
				
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
			if(!move_uploaded_file($userfile_tmp, $tempSaveAsFileName)){
				$bolPassed = false;
				$strMessage .= "<br/><b>Error Saving Archive</b>. The file archive uploaded OK but it could not be saved to the file system?";
			}else{
				chmod($tempSaveAsFileName, 0755);
			}
		}
		
		//continue if OK so far...
		if($bolPassed){
		
			//extract the .zip to the /temp directory..
			$archive = new PclZip($tempSaveAsFileName);
			$list = $archive->extract(PCLZIP_OPT_PATH, $temp_directory . "/");
			
			/*
				At this point there are two files in the /temp directory. The .zip we uploaded
				and the unzipped version to copy to the /plugins directory.
			*/
			
			//validate the unziped folder contains the necessary parts...
			$objTempPlugin = new Plugin("", "");
			$errors = array();
			$extractedFolderName = str_replace(".zip", "", $tempSaveAsFileName); 
			
			if(strlen($extractedFolderName) > 3){
				if(is_dir($extractedFolderName)){
					$errors = $objTempPlugin->fnCheckPluginContents($extractedFolderName);
				}else{
					$bolPassed = false;
					$strMessage = "<br><b>Error Un-Zipping Archive? (1)</b><br/>This error is sometimes caused by the type of software that was used to create the .zip archive, it could not be opened.";
				}
			}else{
				$bolPassed = false;
				$strMessage = "<br><b>Error Un-Zipping Archive? (2)</b><br/>This error is sometimes caused by the type of software that was used to create the .zip archive, it could not be opened.";
			}

			//if we have error the contents of the zip are not valid!
			if(count($errors) > 0){
				$bolPassed = false;
				for($e = 0; $e < count($errors); $e++){
					$strMessage .= "<br>" . $errors[$e];
				}
				$strMessage = "<br><b>This plugin package is invalid...</b>" . $strMessage;
			}
		
			//see if we can move the file to the /plugins directory...
			if($bolPassed){
					
				//fill up an array with the plugins details...	
				$info = $objTempPlugin->fnGetPluginInfo($extractedFolderName);
				$uniquePluginId = $info["uniquePluginId"];
				$displayAs = $info["displayAs"];
				$bolPluginExists = false;
				
				//make sure the uniquePluginId does not exist in the database...
				if($overwiteDuplicate != "1"){
					if($bolPassed){
						$tmp = "SELECT guid FROM " . TBL_BT_PLUGINS . " WHERE uniquePluginId = '" . $uniquePluginId . "' LIMIT 0, 1";
						$existingPluginGuid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						if($existingPluginGuid != ""){
							$bolPassed = false;
							$bolPluginExists = true;
							$strMessage .= "<br/>This plugin already exists. ";
							$strMessage .= " To update this plugin, check the \"Update Existing Plugin\" box ";
							$strMessage .= " then upload it again.";
						}
					}

					//if we are still good, make sure the displayAs does not exist in the database...
					if($bolPassed){
						$tmp = "SELECT guid FROM " . TBL_BT_PLUGINS . " WHERE displayAs = '" . fnFormInput($displayAs) . "' LIMIT 0, 1";
						$existingPluginGuid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						if($existingPluginGuid != ""){
							$bolPassed = false;
							$strMessage .= "<br/>Duplicate plugin. The config.txt file for this plugin includes ";
							$strMessage .= " a displayAs value <b>\"" . fnFormInput($displayAs) . "\"</b>. You already have a plugin with ";
							$strMessage .= " this displayAs value and duplicates are not allowed. You can either check the \"Update Existing Plugin\" box ";
							$strMessage .= " then upload it again. Or, you can change the displayAs value in it's config.txt file.";
						}
					}
				}//overwriteDuplicate
	
				////////////////////////////////////////////////////////////////////////////
				//validate "info" for this plugin...
				
				//displayAs....
				if(strlen($info["displayAs"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>displayAs not found in config.txt file";
				}

				//category....
				if(strlen($info["category"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>category not found in config.txt file";
				}

				//loadClassOrActionName....
				if(strlen($info["loadClassOrActionName"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>loadClassOrActionName not found in config.txt file";
				}
				
				//hasChildItems....
				if(strlen($info["hasChildItems"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>hasChildItems not found in config.txt file";
				}

				//supportedDevices....
				if(strlen($info["supportedDevices"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>supportedDevices not found in config.txt file";
				}
				
				//authorName....
				if(strlen($info["authorName"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>authorName not found in config.txt file";
				}
				
				//versionNumber....
				if(strlen($info["versionNumber"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>versionNumber not found in config.txt file";
				}
				
				//versionString....
				if(strlen($info["versionString"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>versionString not found in config.txt file";
				}
				
				//updateURL....
				if(strlen($info["updateURL"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>updateURL not found in config.txt file";
				}
				
				//downloadURL....
				if(strlen($info["downloadURL"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>downloadURL not found in config.txt file";
				}
				
				//defaultJSONData....
				if(strlen($info["defaultJSONData"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>defaultJSONData not found in config.txt file";
				}
				
				//shortDescription....
				if(strlen($info["shortDescription"]) < 1){
					$bolPassed = false;
					$strMessage .= "<br/>shortDescription not found in config.txt file";
				}
			
				//done validating "info" for this plugin...
				////////////////////////////////////////////////////////////////////////////
			
			
				////////////////////////////////////////////////////////////////////////////
				//uploads cannot contain php files...
				if($bolPassed){
					if(fnContainsPHP($extractedFolderName)){
						$bolPassed = false;
						$strMessage .= "<br/>.PHP files are not allowed in plugin packages";
					}
				}
				////////////////////////////////////////////////////////////////////////////
			
					
				//if still good...
				if($bolPassed){
		
					//if still good make sure we don't have folder with this name...
					$plugInFolderName = str_replace(".zip", "", $userfile_name);
					
					if(is_dir($plugin_directory . "/" . $plugInFolderName)){
						
						//plugin exists...warn if checkbox not checked
						if($overwiteDuplicate != "1"){
							$bolPassed = false;
							$strMessage .= "<br>A plugin directory already exists at <b>/plugins/" . $filename . "</b> ";
							$strMessage .= "<br>Please confirm that you want to overwrite the existing plugin with this new one by checking the box then ";
							$strMessage .= "uploading the same plugin again.";
						}
						
					}//directory exists...
			
					//if still good move from /temp to the /plugins folder..
					if(is_writable($plugin_directory)){
						
						//remove possible existing folder...
						if(is_dir($plugin_directory . "/" . $plugInFolderName)){
							fnRemoveDirectory($plugin_directory . "/" . $plugInFolderName);
						}
						//move the uploaded folder...
						if(@rename($extractedFolderName, $plugin_directory . "/" . $plugInFolderName)){
								chmod($plugin_directory . "/" . $plugInFolderName, 0755);
								fnChmodDirectory($plugin_directory . "/" . $plugInFolderName, 0755);
								
							//all done!
								if($bolPassed){
									$bolDone = true;
									
									$strMessage = "<b>" . fnFormOutput($filename, true) . "</b> (" . fnFormatBytes($userfile_size) . ") ";
									$strMessage .= "uploaded successfully.";
									
								}
								
								
						}else{
						
							$bolPassed = false;
							$strMessage .= "<br>There was a problem moving the uploaded plugin folder from the /temp directory to the /plugins directory.";
							$strMessage .= "<br>Uploaded: " . $extractedFolderName . " To: " . $plugin_directory . "/" . $plugInFolderName;
							$strMessage .= "<div style='padding-top:5px;'><b>This means there is a file ownership permission problem.</b> Check to make sure the ";
							$strMessage .= $plugin_directory . " folder is readable and writeable by .PHP";
						
						}
					}else{
						$bolPassed = false;
						$strMessage .= "<br>The " . APP_DATA_DIRECTORY . "/plugins folder is not writable. This means you'll need to upload plugins manually using an FTP client.";
					}
				}//bolPassed
			}//bolPassed
		}//bolPassed		
	
	}
	//done uploading
	///////////////////////////////////////////////////////////////////////////////////////////
	
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>
	
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="search" id="search" value="<?php echo $search;?>">
<input type="hidden" name="command" id="command" value="">
<input type="hidden" name="pluginGuid" id="pluginGuid" value="">
<input type="hidden" name="uniquePluginId" id="uniquePluginId" value="">
<input type="hidden" name="webDirectoryName" id="webDirectoryName" value="">
<input type="hidden" name="pluginURL" id="pluginURL" value="">
    
<!--load the jQuery files using the Google API -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    
    
<script type="text/javascript">
	
	//refreshPlugins...
	function refreshPlugins(){
		document.forms[0].command.value = 'refreshPlugins';
		saveScreenData('refreshBox');
	}
	
	
	//downloadPlugins...
	function downloadPlugins(){
		document.forms[0].command.value = 'getInstalledPlugins';
		saveScreenData('download');
	}
	
	//installPlugin...
	function installPlugin(pluginGuid, uniquePluginId, directoryName, downloadURL){
		document.forms[0].pluginGuid.value = pluginGuid;
		document.forms[0].uniquePluginId.value = uniquePluginId;
		document.forms[0].pluginURL.value = downloadURL;
		document.forms[0].webDirectoryName.value = directoryName;
		document.forms[0].command.value = 'installPlugin';
		
		var controlsDiv = document.getElementById("controls_" + pluginGuid);
		controlsDiv.style.display = "none";

		var resDiv = document.getElementById("submit_" + pluginGuid);
		resDiv.style.color = "red";
		resDiv.innerHTML = "...loading";
		var theURL = "pluginUpdate_AJAX.php?pluginGuid=" + pluginGuid + "&command=installPlugin&downloadURL=" + downloadURL + "&uniquePluginId=" + uniquePluginId + "&webDirectoryName=" + directoryName;
		$.ajax({
		url: theURL,
		success:function(data){
			if(data != ""){
				resDiv.innerHTML = data;
			}
		}
		});
	}	
	
	//upload plugin...
	function fnUploaddPlugin(){
		document.getElementById("uploadButton").disabled = true;
		document.getElementById("isLoadingImage").style.visibility = "visible";
		document.getElementById("isLoadingText").style.visibility = "visible";
		document.forms[0].command.value = 'uploadPlugin';
		document.forms[0].submit();	
	}

	//fills div with selected file value, runs in loop..
	var theTimer = null;
	function fileUploadLabel(){
		var theValueEl = document.getElementById("fileUpload");
		var theDisplayEl = document.getElementById("fileUploadValue");
		if(theValueEl.value != ""){
			theDisplayEl.value = theValueEl.value;
		}
		theTimer = setTimeout("fileUploadLabel()", 100);
	}	

</script>



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
                  Add Plugins
                </div>
                
                <div style='padding:10px;'>
                          
                    <table cellspacing='0' cellpadding='0' width="99%" style='margin-left:10px;margin-bottom:10px;'>
                        <tr>
                            <td style='vertical-align:middle;'>
                                <a href="plugins.php?addingPlugin=true<?php echo $qVars;?>" title="Back to Plugins"><img src="../images/arr_right.gif" alt='arrow'/>Back to Plugins</a>
                            </td>
                            <td nowrap style='padding-left:10px;'>
    
                            </td>
                        </tr>
                    </table>
                          
                    <div style='padding:10px;'>
                    	<b>Plugins</b> are small .zip archives that contain source code for your control panel and for your
                        iOS and / or Android apps. Each plugin needs to be installed in your control panel before you can use
                        it in your control panel or in your applications.
                    </div>
                    
                          
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_refresh');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Refresh Plugins</a>
                        <div id="box_refresh" style="display:<?php echo $downloadBoxCSS;?>;">
                            
                            <div style='padding-top:10px;'>
								It is sometimes necessary to "Refresh" the plugin list. This is useful when adding plugins
                                by way of an FTP program. After you FTP a plugin package to your backend, use the Refresh button
                                to synchronize the file system with the database.
                            </div>
                            
                            <div style='padding-top:10px;'>
                                <input type='button' title="refresh" value="refresh" align='absmiddle' class="buttonSubmit" onClick="refreshPlugins();return false;">
                            </div>
                            <div id="submit_refreshBox" class="submit_working" style='padding-left:0px;'>&nbsp;</div>
                        	<div style="height:20px;">&nbsp;</div>
                        </div>
                    </div>
                          
                          
                          
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_download');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Download Plugins from your buzztouch.com Control Panel</a>
                        <div id="box_download" style="display:<?php echo $downloadBoxCSS;?>;">
                            
                            <div style='padding-top:10px;'>
								When you click the "Download" button a connection will be made to the buzztouch.com API to
                                downoad a list of plugins you installed at buzztouch.com. 
                               	The Buzztouch account it connects with is setup in this software's <b>config.php</b> file.
                            </div>
                            
                            <div style='padding-top:10px;'>
                                <input type='button' title="download" value="download" align='absmiddle' class="buttonSubmit" onClick="downloadPlugins();return false;">
                            </div>
                            <div id="submit_download" class="submit_working" style='padding-left:0px;'>&nbsp;</div>
                            
                        	<div style="height:20px;">&nbsp;</div>
                        </div>
                    </div>
                          
                          
                          
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_upload');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Upload Plugin Package</a>
                        <div id="box_upload" style="display:<?php echo $uploadBoxCSS;?>;">
                            
                            <div style='padding-top:10px;'>
                            	
                                <div style='padding-top:5px;'>
                                    If your webserver does not allow large file uploads, you could also use an FTP program to
                                    upload plugins to the <b><?php echo APP_DATA_DIRECTORY;?>/plugins</b> directory. 
	                                When using this approach, do not FTP the .zip archive itself, un-zip the archive then upload it's contents.
                                    After uploading the plugin contents, use the 
                                    <a href="plugins.php?addingPlugin=true<?php echo $qVars;?>&command=refreshPlugins" title="Refresh Plugins">Refresh Plugins</a> link to 
                                    rebuild the list of plugins on the previous screen.
                                </div>
        
                                <?php if($strMessage != "" && !$bolDone){ ?>
                                    <div class='errorDiv' style='margin-top:15px;'>
                                        <?php echo $strMessage;?>                                
                                    </div>
                                <?php } ?> 
                        
                              
                                <?php if($strMessage != "" && $bolDone){ ?>
                                    <div class='doneDiv' style='margin-top:15px;'>
                                        <?php echo $strMessage;?> 
                                        <div style='padding-top:5px;'>
                                        	(use the Refresh Plugins option after uploading plugin packages)
                                        </div>
                                    </div>
                                <?php } ?>   
                                                  
                            
                        
                                <div style='padding-top:15px;'>
                                    <b>Choose a .zip archive</b>
                                </div>
                                
                                <div class="fileinputs">
                                    <input type="file" id="fileUpload" name="fileUpload" class="file"/>
                                    <div class="fakefile">
                                        <input id="fileUploadValue" name="fileUploadValue" style="width:115px;height:18px;display:inline;vertical-align:middle;"/>
                                        <img src="../images/plus.png" alt="select" style='display:inline;vertical-align:middle;margin-top:-8px;cursor:pointer;'/>
                                    </div>
                                </div>                                
            
                                <div class="pcheckbox" style='margin-top:5px;'>        
                                    <input type="checkbox" name="overwiteDuplicate" id="overwiteDuplicate" value="1"/>
                                    Update Existing Plugin
                                </div>
            
                                <div style='padding-top:5px;float:left;'>
                                    <input type='button' title="upload" value="upload" id="uploadButton" name="uploadButton" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnUploaddPlugin();return false;">
                                </div>
                                <div id="isLoadingImage" style='float:left;visibility:hidden;'>
                                    <img   src="../images/gif-loading-small.gif" style="height:40px;width:40px;margin:5px;margin-top:-3px;">
                                </div>
                                <div style="clear:both;"></div>
                                <div id="isLoadingText" style='font-size:9pt;color:red;margin-top:-10px;visibility:hidden;'>
                                    uploading...please wait...
                                </div>
                        
                        
                        		<div style="height:20px;">&nbsp;</div>
                            </div>
                            
                        </div>
                    </div>
                          
                          
                    <div class='cpExpandoBox colorDarkBg'>
                    <a href='#' onClick="fnShowHide('box_remove');return false;"><img src='<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif' alt='arrow' />Remove Plugins</a>
                        <div id="box_remove" style="display:<?php echo $downloadBoxCSS;?>;">
                            
                            <div style='padding-top:10px;'>
								It's sometimes necessary to remove plugins from your control panel. You cannot remove plugins that are
                                used by existing applications. This means if you're using a plugin, and you want to delete it from
                                your control panel, you'll need to remove all the screens using that plugin first. After making
                                sure the plugin is not in use, remove it from your webserver using an FTP program. This somewhat
                                tedius task helps insure you don't remove anything accidentally.
                            </div>
                            
                        	<div style="height:20px;">&nbsp;</div>
                        </div>
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
