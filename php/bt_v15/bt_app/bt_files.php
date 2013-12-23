<?php   require_once("../../config.php");
		require_once("../../includes/zip.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//User Object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	//init page object
	$thisPage = new Page();
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_fileManager.js";	

	//javascript in footer
	$thisPage->scriptsInFooter = "bt_v15/bt_scripts/app_fileManagerFooter.js";	

	//form does uploads...
	$thisPage->formEncType = "multipart/form-data";
	
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$BT_fileId = fnGetReqVal("BT_fileId", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$uploadFolder = fnGetReqVal("uploadFolder", "", $myRequestVars);
	$doWhat = fnGetReqVal("doWhat", "", $myRequestVars);
	$searchFolder = fnGetReqVal("searchFolder", "", $myRequestVars);


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
	//end selected id's
	
	//is row selected?
	function fnIsChecked($theId){
		global $selectedIds;
		if(in_array($theId, $selectedIds)){
			return "checked";
		}else{
			return "";
		}	
	}
	
	//is folder selected?
	function fnSelectedFolder($folderName, $selectedFolder){
		$r = "<img src='../../images/arr_right.gif' alt='folder' style='visibility:hidden;'/>";
		if(strtoupper($folderName) == strtoupper($selectedFolder)){
			$r = "<img src='../../images/arr_right.gif' alt='folder' style='visibility:visible;'/>";
		}
		return $r;
	}

	//counts files in a folder
	function fnCountFileInFolder($folderName, $appGuid){
		$tmpSql = "SELECT Count(id) FROM " . TBL_BT_FILES . " WHERE appGuid = '" . $appGuid . "' AND status != 'deleted '";
		if(strlen($folderName) > 1){
			$tmpSql .= "AND filePath = '" . $folderName . "'";
		}
		return fnGetOneValue($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	}


	//paths to this applications files
	$appDataURL = $objApp->fnGetAppDataURL($appGuid);
	$appDataURL = fnGetSecureURL($appDataURL);
	
	$appDataDirectory = $objApp->fnGetAppDataDirectory($appGuid);
	$docsDirectory = $appDataDirectory . "/documents";
	$imagesDirectory = $appDataDirectory . "/images";
	$audioDirectory = $appDataDirectory . "/audio";
	$videoDirectory = $appDataDirectory . "/video";
	$scriptsDirectory = $appDataDirectory . "/phpscripts";
	
	//////////////////////////////////////////////////////////////////////////////////
	//apps data directories must be writable...
	if(!is_dir(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY)){
		$bolPassed = false;
		$strMessage .= "<br>This control panel's data directory could not be found so this file manager will not work.";
	}else{
		if(!is_writable(APP_PHYSICAL_PATH . APP_DATA_DIRECTORY)){
			$bolPassed = false;
			$strMessage .= "<br>This control panel's data directory is not writable by PHP so this file manager will not work.";
		}else{
			if(!is_dir($appDataDirectory) || !is_writable($appDataDirectory)){
				$bolPassed = false;
				$strMessage .= "<br>This app's data directory is not writable by PHP so this file manager will not work.";
			}else{
				if(!is_dir($docsDirectory) || !is_writable($docsDirectory)){
					$bolPassed = false;
					$strMessage .= "<br>This app's Documents directory is not writable by PHP so this file manager will not work.";
				}
				if(!is_dir($imagesDirectory) || !is_writable($imagesDirectory)){
					$bolPassed = false;
					$strMessage .= "<br>This app's Images directory is not writable by PHP so this file manager will not work.";
				}
				if(!is_dir($audioDirectory) || !is_writable($audioDirectory)){
					$bolPassed = false;
					$strMessage .= "<br>This app's Audio directory is not writable by PHP so this file manager will not work.";
				}
				if(!is_dir($videoDirectory) || !is_writable($videoDirectory)){
					$bolPassed = false;
					$strMessage .= "<br>This app's Video directory is not writable by PHP so this file manager will not work.";
				}
				if(!is_dir($scriptsDirectory) || !is_writable($scriptsDirectory)){
					$bolPassed = false;
					$strMessage .= "<br>This app's Scripts directory is not writable by PHP so this file manager will not work.";
				}
			}					
		}
	}
	//done verifying directories...
	//////////////////////////////////////////////////////////////////////////////////
	
	//allowed upload file types...
	$allowed_file_types = fnGetAllowedUploadMimeTypes();
	
	//allowed mime-types and extentions...
	$allowed_mime_types = array(); 
	$allowed_file_ext = array(); 
	foreach ($allowed_file_types as $theType){
		$allowed_mime_types[] = $theType[0];
		$allowed_file_ext[] = $theType[1];
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////
	//if we chose something from actions menu we must have at least one id selected...
	if(strlen($doWhat) > 3 || strtoupper($command) == "CONFIRMDELETE" && $bolPassed){
		if(count($selectedIds) < 1){
			$bolPassed = false;
			$strMessage = "<br>Please select at least one file using the checkboxes before selecting an operation from the actions menu.";
		}else{
			
			//if we selected removeFiles
			if(strtoupper($doWhat) == "REMOVEFILES"){
				$command = "delete";
				$strMessage = "delete";
			}
			
			//if we confirmed the delete...
			if(strtoupper($command) == "CONFIRMDELETE"){
				$tmpSql = "SELECT guid, fileName, filePath FROM " . TBL_BT_FILES . " WHERE guid IN (" . $inClauseSQL . ")";
				$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($res){
					while($row = mysql_fetch_array($res)){
						
						//delete from database...
						$strSql = "DELETE FROM " . TBL_BT_FILES . " WHERE appGuid = '" . $appGuid . "' ";
						$strSql .= " AND guid = '" . $row["guid"] . "'";
						fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);

						//remove files from file system...filePath in database needs leading slash (/)
						$deletePath = $appDataDirectory . "/" . ltrim($row["filePath"], "/") . "/" . ltrim($row["fileName"], "/");
						
						if(is_file($deletePath)){
							@unlink($deletePath);
						}
						
					}
				}                                                                
				//flag as deleted
				$bolDeleted = true;
				$bolDone = true;
				$strMessage = "Removed <b>" . count($selectedIds) . " files.</b>";
				$selectedIds = array();
			}	//done deleting..	
			
            //create zipArchive
			if(strtoupper($doWhat) == "DOWNLOADZIP"){
				
				//create zipped archive
				$zipDate = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m-d-y");
				$savePath = APP_PHYSICAL_PATH . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp";
				$archive_name = "archive-" . $zipDate . "-" . $appGuid . ".zip";
				
				//remove previously created archive with this name..
				if(is_file($savePath . "/" . $archive_name)){
					unlink($savePath . "/" . $archive_name);
				}
				
				//create an empty temp folder to store files to then zip
				$archive = new PclZip($savePath . "/" . $archive_name);
				$filesToAdd = array();
				
				//add the selected files...
				$tmpSql = "SELECT guid, fileName, filePath FROM " . TBL_BT_FILES . " WHERE guid IN (" . $inClauseSQL . ") AND appGuid = '" . $appGuid . "'";
				$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
				if($res){
					while($row = mysql_fetch_array($res)){
						
						//add each file to the zip
						$filesToAdd[] = $appDataDirectory . "/" . ltrim($row["filePath"], "/") . "/" . ltrim($row["fileName"], "/");
						
					}
				}             
				            
				//add the files
				if(count($filesToAdd) > 0){
				
				
					$archive->add($filesToAdd, PCLZIP_OPT_REMOVE_PATH, APP_PHYSICAL_PATH . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/applications");

					//flag as done, show link to newly created zip...
					$bolDone = true;
					$strMessage = "Zip Archive Created and Ready for Download.";
					$strMessage .= "<div style='padding-top:10px;'>";
						$strMessage .= fnFileTypeIcon("fake.zip") . "<a href='" . fnGetSecureURL(APP_URL) . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/temp/" . $archive_name . "'>" . $archive_name . "</a>";
					$strMessage .= "</div>";
					$selectedIds = array();
				
				}else{
					$bolPassed = false;
					$strMessage .= "<br>Zip No Created. No files were selected";
				}
				
			}			
			
			//move to a new different folder...
			$doWhat = strtoupper($doWhat);
			$newFolderName = "";
			if($doWhat == "MOVETODOCUMENTS" || $doWhat == "MOVETOIMAGES" || $doWhat == "MOVETOAUDIO" || $doWhat == "MOVETOVIDEO" || $doWhat == "MOVETOPHPSCRIPTS"){
				$moveToFolder = "";
				switch (strtoupper($doWhat)){
					case "MOVETODOCUMENTS":
						$moveToFolder = $docsDirectory;
						$newFoldereName = "documents";
						break;
					case "MOVETOIMAGES":
						$moveToFolder = $imagesDirectory;
						$newFolderName = "images";
						break;
					case "MOVETOAUDIO":
						$moveToFolder = $audioDirectory;
						$newFolderName = "audio";
						break;
					case "MOVETOVIDEO":
						$moveToFolder = $videoDirectory;
						$newFolderName = "video";
						break;
					case "MOVETOPHPSCRIPTS":
						$moveToFolder = $scriptsDirectory;
						$newFolderName = "phpscripts";
						break;
					default:
						$moveToFolder = "";
						$newFolderName = "";
						break;						
				}
				if($moveToFolder == ""){
					$bolPassed = false;
					$strMessage .= "<br>Please select a folder to move the documents to.";
				}else{
				
					$tmpSql = "SELECT id, guid, fileName, filePath FROM " . TBL_BT_FILES . " WHERE guid IN (" . $inClauseSQL . ") AND appGuid = '" . $appGuid . "'";
					$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
					if($res){
						while($row = mysql_fetch_array($res)){
							
							$oldLocation = $appDataDirectory . "/" . ltrim($row["filePath"], "/") . "/" . ltrim($row["fileName"], "/");
							$newLocation = $moveToFolder . "/" . ltrim($row["fileName"], "/");
							
							//if file already exists in new location be sure the database is correct...
							if(is_file($oldLocation)){
								if(rename($oldLocation, $newLocation)){
									$tmpSql = "UPDATE " . TBL_BT_FILES . " SET modifiedUTC = '" . $dtNow . "', filePath = '/" . $newFolderName .  "' ";
									$tmpSql .= " WHERE guid IN (" . $inClauseSQL . ") AND appGuid = '" . $appGuid . "'";
									fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
								}
							}
							
						}//while
					}//res                                                     
					
					//flag as done
					$bolDone = true;
					$strMessage = "Moved <b>" . count($selectedIds) . " files</b> to the "  . fnFormatProperCase($newFolderName) . " Folder.";
					$selectedIds = array();
				
				}//moveToFolder
			}//move files...		
		}//selected Id's
	}
	//end doWhat / actionsMenu selection
	/////////////////////////////////////////////////////////////////////////////////
	
	/////////////////////////////////////////////////////////////////////////////////
	//un-zip uploaded file... 
	if(strtoupper($command) == "UNZIP" && $BT_fileId != ""){
		
		//get .zip archive path...
		$unzipFolderPath = "";
		$unzipFolderName = "";
		$tmpSql = "SELECT guid, fileName, filePath FROM " . TBL_BT_FILES . " WHERE guid = '" . $BT_fileId . "' AND appGuid = '" . $appGuid . "'";
		$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		if($res){
			while($row = mysql_fetch_array($res)){
				$unzipFolderPath = $appDataDirectory . "/" . ltrim($row["filePath"], "/") . "/" . ltrim($row["fileName"], "/");
				$unzipFolderName = $row["filePath"];
			}
		}             
					
		//make sure .zip exists..
		if(!is_file($unzipFolderPath)){
			$bolPassed = false;
			$strMessage .= "<br>The .zip archive you selected to unzip does not exist on the file system?";
		}
		
		//good still?
		if($bolPassed){
		
			//create archive object to unzip...
			$archive = new PclZip($unzipFolderPath);
			if(($list = $archive->listContent()) == 0){
				$bolDone = false;
				$bolPassed = false;
				$strMessage = "<b>Error Un-Zipping Archive?</b><br/>There are two common causes for this. 1) The type of software that was used to create the archive is not compatible (non standard). 2) Your server is not configured to handle file compression functions.";
			}
		}
		
		//look at each file in the archive...
		if($bolPassed){
			$countOfExtractedFiles = 0;
			for($i = 0; $i < sizeof($list); $i++){
				
				//info for each file..
				$fileInfo = $list[$i];
				$fileName = $fileInfo["filename"];
				$fileStoredName = $fileInfo["stored_filename"];
				$fileSize = $fileInfo["size"];
				
				//save file?
				$bolSaveFile = true;
				
				//must have a file size..and a file name...
				if($fileSize == "0" || $fileSize == 0 || $fileName == ""){
					$bolSaveFile = false;
				}
				
				//cannot be __MACOSX hidden file
				if(strtoupper(substr($fileName, 0, 8)) == "__MACOSX"){
					$bolSaveFile = false;
				}

				//cannot be Thumbs.db
				if(strtoupper(substr($fileName, -9)) == "THUMBS.DB"){
					$bolSaveFile = false;
				}
				
				//make sure it's a valid file extension for the types we allow
				if($fileSize < 1){
					$bolSaveFile = false;
				}else{
					$file_ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
					if(!in_array($file_ext, $allowed_file_ext)){
						$bolSaveFile = false;
						$strMessage .= "<br>" . basename($fileName) . "  " . fnFormatBytes($fileSize) . " <span style='color:red;'> not extracted, file type not allowed</span>";
					}
					//get the mime type...
					$mime_type = fnGetMimeTypeFromExtention($file_ext);
				}
				
				//flag here so message always shows green
				$bolDone = true;
				
				//can't have a file with this name already...
				if($bolSaveFile){
					$tmpSql = "SELECT Count(id) FROM " . TBL_BT_FILES . " WHERE appGuid = '" . $appGuid . "'";
					$tmpSql .= " AND fileName = '" . basename($fileName) . "' AND status != 'deleted'";
					$extCount = fnGetOneValue($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
					if($extCount == "") $extCount = 0;
					if($extCount > 0){
						$bolSaveFile = false;
						$strMessage .= "<br>" . basename($fileName) . "  " . fnFormatBytes($fileSize) . " <span style='color:red;'> not extracted, duplicate file-name in manager</span>";
					}
				}
				
				//if we could not get mime-type?
				if($bolSaveFile){
					if($mime_type == ""){
						$bolSaveFile = false;
						$strMessage .= "<br>" . basename($fileName) . "  " . fnFormatBytes($fileSize) . " <span style='color:red;'> not extracted, could not determine the mime-type of the file?</span>";
					}
				}
				
				//are saving the file?
				if($bolSaveFile){
				
					//extract this file by name
					$thisFile = $archive->extract(PCLZIP_OPT_PATH, $appDataDirectory . "/" . ltrim($unzipFolderName, "/"), PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_BY_NAME, $fileName);
    				if($thisFile == 0){
						//could not extract file..
					}else{
						
						$countOfExtractedFiles++;
						
						//if this is an image, get the image's width and height
						$fileWidth = 0;
						$fileHeight = 0;
						if($file_ext == "png" || $file_ext == "jpg" || $file_ext == "jpeg" || $file_ext == "gif"){
							list($fileWidth, $fileHeight) = getimagesize($appDataDirectory . "/" . ltrim($unzipFolderName, "/") . "/" . basename($fileName));
						}						
						
						$tmpSql = " INSERT INTO " . TBL_BT_FILES . "(guid, appGuid, fileName, filePath, fileType, ";
						$tmpSql .= "fileSize, fileWidth, fileHeight, status, dateStampUTC, modifiedUTC) VALUES ( ";
						$tmpSql .= "'" . strtoupper(fnCreateGuid()) . "', '" . $appGuid . "', '" . basename($fileName) . "',";
						$tmpSql .= "'" . $unzipFolderName . "', '" . $mime_type . "', '" . $fileSize . "','" . $fileWidth . "',";
						$tmpSql .= "'" . $fileHeight . "','active','" . $dtNow . "','" . $dtNow . "')";
						fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
						
						//message...
						$strMessage .= "<br>" . basename($fileName) . " " . fnFormatBytes($fileSize);
						
					}
				
					
					
				}//file type OK to save..
			
		  	}	
			
			//process .zip complete
			if($countOfExtractedFiles > 0){
				$bolDone = true;
				$strMessage = "<b>Extracted</b> " . $countOfExtractedFiles . " files to the <b>" . fnFormatProperCase($unzipFolderName) . "</b> folder</b>" . $strMessage;
			}else{
				$bolPassed = false;
				$bolDone = false;
				$strMessage .= "<br>No files were extracted from the archive.";
			}
		}
		
	}
	//end un-zip link			
	/////////////////////////////////////////////////////////////////////////////////
		
	
	/////////////////////////////////////////////////////////////////////////////////
	//upload submit...
	if(strtoupper($command) == "UPLOADFILE"  && $bolPassed){
		
		//Get the file information
		$userfile_name = $_FILES['fileUpload']['name'];
		$userfile_tmp = $_FILES['fileUpload']['tmp_name'];
		$userfile_size = $_FILES['fileUpload']['size'];
		$userfile_type = $_FILES['fileUpload']['type'];
		$filename = basename($_FILES['fileUpload']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
		$saveAsFileName = "";
		
		//save as file name..
		$saveAsFileName = $appDataDirectory . "/" . ltrim($uploadFolder, "/") . "/" . $userfile_name;
				
		//only process if the file is acceptable and below the allowed file size limit constant...
		if((!empty($_FILES["fileUpload"])) && ($_FILES['fileUpload']['error'] == 0)) {
				
			//mime-type and extenstion must be allowed...
			if(!in_array($userfile_type, $allowed_mime_types) && !in_array($file_ext, $allowed_file_ext)){
				$bolPassed = false;
				$strMessage = "<br/><b>Invalid File Type</b>. Please choose another file to upload. ";
				$strMessage .= "<br/>You tried to upload a file named <b>" . $filename . "</b> with type <b>" . $userfile_type . "</b> with file extention <b>." . $file_ext . "</b>";
			}
			
		}else{
			$bolPassed = false;
			$strMessage .= "<br/>Please select a file before clicking upload";
		}
		
		//must have an upload folder selected
		if(strlen($uploadFolder) < 3){
			$bolPassed = false;
			$strMessage .= "<br/>Please select a folder to upload the files to";
		}
		
		//check if the file size is above the allowed limit
		if($bolPassed){
			if ($userfile_size > APP_MAX_UPLOAD_SIZE) {
				$bolPassed = false;
				$strMessage .= "<br/>Uploaded file is too large. The maximum allowed size is " . fnFormatBytes(APP_MAX_UPLOAD_SIZE) . " and you ";
				$strMessage .= "<br/>tried to upload a file that is " . fnFormatBytes(userfile_size);
			}
		}		

		//check for duplicates
		if($bolPassed){
			$tmpSql = "SELECT Count(id) FROM " . TBL_BT_FILES . " WHERE appGuid = '" . $appGuid . "'";
			$tmpSql .= " AND fileName = '" . $userfile_name . "' AND status != 'deleted'";
			$extCount = fnGetOneValue($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($extCount == "") $extCount = 0;
			if($extCount > 0){
				$bolPassed = false;
				$strMessage .= "<br/><b>Duplicate File</b>. The file manager already has a file named <b>" . $userfile_name . "</b>, duplicates are not allowed.";
			}
		}
		
		//if this is the phpscripts directory then only allow .php file's..
		if($bolPassed){
			if(strtoupper(str_replace("/", "", $uploadFolder)) == "PHPSCRIPTS"){
				if(strtoupper($file_ext) != "PHP"){
					$bolPassed = false;
					$strMessage .= "<br/><b>Invalid File Type</b>. You can only upload .PHP files to this folder. You uploaded <b>" . $userfile_name . "</b>.";
				}
			}
		}
		
		//move file from temp. upload folder to app files folder..
		if($bolPassed){
			if(!move_uploaded_file($userfile_tmp, $saveAsFileName)){
				$bolPassed = false;
				$strMessage .= "<br/><b>Error Saving File</b>. The file uploaded OK but it could not be saved to the selected folder?";
			}else{
				chmod($saveAsFileName, 0777);
			}
		}
		
		//add database record for new file...
		if($bolPassed){
		
			//if this is an image, get the image's width and height
			$fileWidth = 0;
			$fileHeight = 0;
			if($file_ext == "png" || $file_ext == "jpg" || $file_ext == "jpeg" || $file_ext == "gif"){
				list($fileWidth, $fileHeight) = getimagesize($saveAsFileName);
			}
			
			$tmpSql = " INSERT INTO " . TBL_BT_FILES . "(guid, appGuid, fileName, filePath, fileType, ";
			$tmpSql .= "fileSize, fileWidth, fileHeight, status, dateStampUTC, modifiedUTC) VALUES ( ";
			$tmpSql .= "'" . strtoupper(fnCreateGuid()) . "', '" . $appGuid . "', '" . $userfile_name . "', ";
			$tmpSql .= "'" . $uploadFolder . "', '" . $userfile_type . "', '" . $userfile_size . "','" . $fileWidth . "',";
			$tmpSql .= "'" . $fileHeight . "','active','" . $dtNow . "','" . $dtNow . "')";
			fnExecuteNonQuery($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			
		}	
		
		//if passed
		if($bolPassed){
			$bolDone = true;
			$strMessage = "<b>" . fnFormOutput($filename, true) . "</b> (" . fnFormatBytes($userfile_size) . ") ";
			$strMessage .= "uploaded to the <b>" . fnFormatProperCase(str_replace("/", "", $uploadFolder)) . "</b> folder.";
		}
	
	}	
	//done uploading
	/////////////////////////////////////////////////////////////////////////////////

	//list vars
	$search = fnGetReqVal("searchInput", "Filename...", $myRequestVars);
	if($search == "Nickname..." || $search == "Display name...") $search = "Filename...";
	
	//set default upload folder if we are not posted...
	if(!$isFormPost){
		$uploadFolder = $searchFolder;
	}
	
	
	$scriptName = "bt_files.php";
	$recsPerPage = 50;
	$totalPages = 1;
	$currentPage = 1;
	$firstRec = 0;
	$lastRec = 0;
	$defaultSort = "F.fileName";
	$defaultUpDown = "DESC";
	$colCount = 7;

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
	if(strpos($sortColumn, "I.") > -1 || strpos($sortColumn, "U.") > -1){
		$sortColumn = $defaultSort;
		$sortUpDown = $defaultUpDown;
	}
	
	//current page 	
	$currentPage = fnGetReqVal("currentPage", 1, $myRequestVars);
		//if we clicked next/prev page
		$currentPage = fnGetReqVal("nextPage", $currentPage, $myRequestVars);
			if(!is_numeric($currentPage)) $currentPage = 1;
		
	//WHERE CLAUSE
	$whereClause = " WHERE F.appGuid = '" . $appGuid . "'";
	$whereClause .= " AND F.status != 'Deleted' ";
	if($searchFolder != "") $whereClause .= " AND filePath = '" . $searchFolder . "'";
	
	//if searching
	$searchHint = "";
	if( (strtoupper($search) != "ALL" && strtoupper($search) != "FILENAME..." && $search != "") ){
		if(strlen($search) == 1){ // clicked a letter for "last name"
			$whereClause .= " AND F.fileName LIKE '" . $search . "%'";
			$searchHint = "File name starts with";
			if($searchFolder != ""){
			 	$searchHint = "You searched in <span style='color:black;'>" . fnFormatProperCase(str_replace("/", "", $searchFolder)) . "</span> where Filename starts with";
			}
		}else{
			$whereClause .= " AND F.fileName LIKE '%" . $search . "%' ";
			$searchHint = "Filename contains";
			if($searchFolder != ""){
				 $searchHint = "You searched in <span style='color:black;'>" . fnFormatProperCase(str_replace("/", "", $searchFolder)) . "</span> where Filename contains";
			}
		}
	}
			
	//querystring for links
	$qVars = "&searchInput=" . fnFormOutput($search) . "&appGuid=" . $appGuid;
	$qVars .= "&sortColumn=" . $sortColumn . "&sortUpDown=" . $sortUpDown . "&currentPage=" . $currentPage;
	$qVars .= "&searchFolder=" . $searchFolder;
	
	//get total recs.
	$totalSql = "  SELECT Count(F.id) AS TotalRecs  ";
	$totalSql .= " FROM " . TBL_BT_FILES . " AS F ";
	
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
    $strSql = " SELECT F.guid, F.appGuid, F.fileName, F.filePath, F.fileType, ";
	$strSql .= "F.fileSize, F.fileWidth, F.fileHeight, F.status, F.dateStampUTC, F.modifiedUTC ";
	$strSql .= "FROM " . TBL_BT_FILES . " AS F ";
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
	if($search == "" || strtoupper($search) == "ALL") $search = "Filename...";

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_fileId" id="BT_fileId" value="<?php echo $BT_fileId;?>">
<input type="hidden" name="recsPerPage" id="recsPerPage" value="<?php echo $recsPerPage;?>">
<input type="hidden" name="currentPage" id="currentPage" value="<?php echo $currentPage;?>">
<input type="hidden" name="totalPages" id="totalPages" value="<?php echo $totalPages;?>">
<input type="hidden" name="firstRec" id="firstRec" value="<?php echo $firstRec;?>">
<input type="hidden" name="lastRec" id="lastRec" value="<?php echo $lastRec;?>">
<input type="hidden" name="totalRecs" id="totalRecs" value="<?php echo $totalRecs;?>">
<input type="hidden" name="sortColumn" id="sortColumn" value="<?php echo $sortColumn;?>">
<input type="hidden" name="sortUpDown" id="sortUpDown" value="<?php echo $sortUpDown;?>">
<input type="hidden" name="command" id="command" value="">
<input type="hidden" name="searchFolder" id="searchFolder" value="<?php echo $searchFolder;?>">

<div class='content'>

	    <fieldset class='colorLightBg'>
           
        <!-- app control panel navigation--> 
        <div class='cpNav'>
        	<span style='white-space:nowrap;'><a href="<?php echo fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $appGuid;?>" title="Application Control Panel">Application Home</a></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
			<?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "inline", ""); ?>
        </div>

       	<div class='contentBox colorLightBg minHeight'>
            
            <div class="contentBand colorBandBg">
            	File Manager for <?php echo fnFormOutput($objApp->infoArray["name"], true);?>
            </div>
            <div style='padding:10px;'>
                   
                    <table cellspacing='0' cellpadding='0' width="97%">
                    	<tr>
    						<td style='vertical-align:top;padding-right:10px;padding-top:3px;border-right:1px solid #999999;'>
                                
                                
                                <table cellspacing='0' cellpadding='0' width="100%">
                                    <tr>
                                        <td nowrap style='padding-left:0px;border-bottom:1px solid #999999;padding-bottom:5px;vertical-align:bottom;'>
                                           Folders
                                        </td>
                            		</tr>
                                    
                                    <tr>
                                        <td nowrap style='padding:5px;padding-right:10px;'>
                                   			<a title="Show" href="<?php echo $scriptName . "?appGuid=" . $appGuid . "&searchFolder=";?>"><?php echo fnSelectedFolder("", $searchFolder);?>All Files (<?php echo fnCountFileInFolder("", $appGuid);?>)</a> 
										</td>
                                    </tr>

                                    <tr>
                                        <td nowrap style='padding:5px;padding-right:10px;'>
                                   			<a title="Show" href="<?php echo $scriptName . "?appGuid=" . $appGuid . "&searchFolder=/documents";?>"><?php echo fnSelectedFolder("/documents", $searchFolder);?>Documents (<?php echo fnCountFileInFolder("/documents", $appGuid);?>)</a> 
										</td>
                                    </tr>
                                    <tr>
                                        <td nowrap style='padding:5px;padding-right:10px;'>
                                   			<a title="Show" href="<?php echo $scriptName . "?appGuid=" . $appGuid . "&searchFolder=/images";?>"><?php echo fnSelectedFolder("/images", $searchFolder);?>Images (<?php echo fnCountFileInFolder("/images", $appGuid);?>)</a> 
										</td>
                                    </tr>
                                    <tr>
                                        <td nowrap style='padding:5px;padding-right:10px;'>
                                   			<a title="Show" href="<?php echo $scriptName . "?appGuid=" . $appGuid . "&searchFolder=/audio";?>"><?php echo fnSelectedFolder("/audio", $searchFolder);?>Audio (<?php echo fnCountFileInFolder("/audio", $appGuid);?>)</a> 
										</td>
                                    </tr>
                                    <tr>
                                        <td nowrap style='padding:5px;padding-right:10px;'>
                                   			<a title="Show" href="<?php echo $scriptName . "?appGuid=" . $appGuid . "&searchFolder=/video";?>"><?php echo fnSelectedFolder("/video", $searchFolder);?>Video (<?php echo fnCountFileInFolder("/video", $appGuid);?>)</a> 
										</td>
                                    </tr>
                                    <tr>
                                        <td nowrap style='padding:5px;padding-right:10px;'>
                                   			<a title="Show" href="<?php echo $scriptName . "?appGuid=" . $appGuid . "&searchFolder=/phpscripts";?>"><?php echo fnSelectedFolder("/phpscripts", $searchFolder);?>PHP Scripts (<?php echo fnCountFileInFolder("/phpscripts", $appGuid);?>)</a> 
										</td>
                                    </tr>

                                </table>
                                
                                    <div nowrap style='margin-top:20px;font-size:11pt;padding-left:0px;border-bottom:1px solid #999999;padding-bottom:5px;vertical-align:bottom;'>
                                    	Upload File
                                    </div>
                        
                                    <select id="uploadFolder" name="uploadFolder" style='vertical-align:middle;width:150px;margin-top:10px;' align='absmiddle'>
                                        <option value="">...upload to....</option>
                                        <option value="/documents" <?php echo fnGetSelectedString("/documents", $uploadFolder);?>>Upload to Documents</option>
                                        <option value="/images" <?php echo fnGetSelectedString("/images", $uploadFolder);?>>Upload to Images</option>
                                        <option value="/audio" <?php echo fnGetSelectedString("/audio", $uploadFolder);?>>Upload to Audio</option>
                                        <option value="/video" <?php echo fnGetSelectedString("/video", $uploadFolder);?>>Upload to Video</option>
                                        <option value="/phpscripts" <?php echo fnGetSelectedString("/phpscripts", $uploadFolder);?>>Upload to PHP Scripts</option>

                                    </select>
                                    
                                    <div class="fileinputs">
                                        <input type="file" id="fileUpload" name="fileUpload" class="file"/>
                                        <div class="fakefile">
                                            <input id="fileUploadValue" name="fileUploadValue" style="width:115px;height:18px;display:inline;vertical-align:middle;"/>
                                            <img src="../../images/plus.png" alt="select" style='display:inline;vertical-align:middle;margin-top:-8px;cursor:pointer;'/>
                                        </div>
                                    </div>                                

                                    <div style='padding-top:5px;'>
                                        <input type='button' title="upload" value="upload" id="uploadButton" name="uploadButton" align='absmiddle' class="buttonSubmit" style='display:inline;margin:0px;' onClick="fnUploadBT_item();return false;">
                                    </div>
                                    <div id="isLoadingImage" class="cpExpandoBox" style='float:left;visibility:hidden;'>
                                        <img src="../../images/gif-loading-small.gif" style="height:40px;width:40px;margin-top:5px;">
                                    </div>
                                    <div id="isLoadingText" style='margin-top:15px;font-size:9pt;color:red;visibility:hidden;'>
                            			uploading...
                                        <br/>
                                        please wait...
                            		</div>
                                    <div class="clear"></div>
                            </td>
    
    						<td style='vertical-align:top;padding-left:10px;'>
                            

                           	<?php if($strMessage == "") { ?>
                            
                                    <table cellspacing='0' cellpadding='0' width="100%" style='margin-bottom:3px;'>
                                        <tr>
                                            <td>
                                                <div style='float:right;margin:0px;'>
                                                    &nbsp;
                                                    <input type='button' title="search" value="search" align='absmiddle' class="buttonSubmit" onClick="top.fnRefresh(document);return false;" style='display:inline;vertical-align:middle;margin:0px;'>
                                                </div>
                                                <div style='float:right;margin:0px;'>
                                                    <input name='searchInput' id='searchInput' onFocus="top.fnClearSearch('Filename...',this);" type='text' value="<?php echo fnFormOutput($search, true);?>" class='searchBox' style='margin:0px;display:inline;vertical-align:middle;overflow:hidden;' onkeyup="document.forms[0].currentPage.value='1';">
                                                </div>
                                            <td/>
                                        </tr>
                                    </table>
    
                                    <table cellspacing='0' cellpadding='0' style='width:100%;overflow:hidden;'>
                                        <tr>
                                            <script>top.fnWriteAlphabet(document, "<?php echo $search;?>");</script>
                                        </tr>
                                    </table>
                        		
								<?php } ?>
 
 
                             <table cellspacing='0' cellpadding='0' width="100%" style='margin-top:5px;'>
                                    
                            		<?php if($strMessage == "") { ?>
									
										<?php if( strtoupper($search) != "ALL" && strtoupper($search) != "FILENAME..." && $search != ""){ //show message?>
                                        <tr>
                                            <td class='searchDiv' colspan='<?php echo $colCount;?>' style='padding-left:0px;'>
                                                <span style='color:red;'><?php echo $searchHint;?>:</span> <?php echo fnFormOutput($search, true);?>
                                             </td>
                                        </tr>
                                        <?php } ?>
                
                                        <tr>
                                            
                                            <td nowrap class="tdSort" style='padding-left:5px;'>
                                               <a title="Sort" href="#" onclick="top.fnSort(document, 'F.fileName');return false;">Filename</a> <?php echo fnSortIcon("F.fileName", $tmpSort, $sortColumn); ?>
                                            </td>
                                            <td nowrap class="tdSort">
                                                
                                            </td>
                                            <td nowrap class="tdSort">
                                                <a title="Sort" href="#" onclick="top.fnSort(document, 'F.filePath');return false;">Folder</a> <?php echo fnSortIcon("F.filePath", $tmpSort, $sortColumn); ?>
                                            </td>
                
                                            <td nowrap class="tdSort">
                                                <a title="Sort" href="#" onclick="top.fnSort(document, 'F.fileType');return false;">Mime-Type</a> <?php echo fnSortIcon("F.fileType", $tmpSort, $sortColumn); ?>
                                            </td>
                
                                            <td nowrap class="tdSort">
                                                <a title="Sort" href="#" onclick="top.fnSort(document, 'F.fileSize');return false;">Size</a> <?php echo fnSortIcon("F.fileSize", $tmpSort, $sortColumn); ?>
                                            </td>
                            
                                            <td nowrap class="tdSort">
                                                <a title="Sort" href="#" onclick="top.fnSort(document, 'F.modifiedUTC');return false;">Modified</a> <?php echo fnSortIcon("F.modifiedUTC", $tmpSort, $sortColumn); ?>
                                            </td>
    
                                            <td class="tdSort" style='text-align:right;vertical-align:top;padding:0px;padding-right:5px;' >
                                                <input id="checkAll" type='checkbox' style="width:12px;height:12px;margin:0px;display:inline;" onclick="top.fnCheckAll(self);">
                                            </td>
                            
                                        </tr>
                                        
									<?php } else { //strMessage == ""?>
                                    
										<?php if($strMessage != "" && $bolDone){ ?>
                                            <div class='doneDiv'>
                                                <?php echo $strMessage;?> 
                                                <div style='padding-top:10px;'>
                                                    <a href="<?php echo $scriptName;?>?unused=true&<?php echo $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                                </div>
                                            </div>
                                        <?php } ?>                     
                                        
                                        <?php if($strMessage != "" && !$bolDone && strtoupper($command) != "DELETE"){ ?>
                                            <div class='errorDiv'>
                                                <?php echo $strMessage;?>                                
                                                <div style='padding-top:10px;'>
                                                    <a href="<?php echo $scriptName;?>?unused=true&<?php echo $qVars;?>"><img src="../../images/arr_right.gif" alt='arrow'/>OK, hide this message</a>
                                                </div>
                                            </div>
                                        <?php } ?> 
                                    
									   	<?php if(strtoupper($command) == "DELETE"){ ?>
                                            <div class="errorDiv">
                                                <br/>
                                                <b>Delete selected files?</b>
                                                <div style='padding-top:5px;'>
                                                    Are you sure you want to do this? This cannot be undone! When you
                                                    confirm this operation the selected files will be deleted and you will not be able to recover them.
                                                </div>
                                                <div style='padding-top:10px;'>
                                                    <a href="#" onclick="fnCancelDelete();return false;"><img src="../../images/arr_right.gif" alt='arrow'/>No, do not delete the selected files</a>
                                                </div>
                                                <div style='padding-top:5px;'>
                                                    <a href="#" onclick="fnConfirmDelete();return false;"><img src="../../images/arr_right.gif" alt='arrow'/>Yes, permanently delete the selected files</a>
                                                </div>
                                            </div>
                                        <?php } ?>                                    
                                    
                                    
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
                                                    
                                                        
                                                    $BT_fileId = $row['guid'];
                                                    $fileName = $row['fileName'];
                                                    $filePath = fnFormOutput($row['filePath']);
													$filePath = fnFormatProperCase(str_replace("/", "", $filePath));
                                                    $fileType = fnFormOutput($row['fileType']);
                                                    $fileSize = fnFormOutput($row['fileSize']);
													
													//if file path is Phpscripts, re-format it so it looks good...
													$folderLabel = $filePath;
													if($folderLabel == "Phpscripts"){
														$folderLabel = "PHP Scripts";
													}            
													//truncate long mimi-types...
													if(strlen($fileType) > 25){
														$fileType = substr($fileType, 0, 25) . "...";
													
													}
													
			                                    	//show image size if we have it...
                                                    $imgDim = "";
                                                    if($row["fileWidth"] > 0 && $row["fileHeight"] > 0){
                                                        $imgDim = " (" . $row["fileWidth"] . " x " . $row["fileHeight"] . ")";
                                                    }
            
                                                    $modDate = fnFromUTC($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");
                                                    $modHours = fnDateDiff("h", $modDate, $dtToday);
                                                    $modLabel = fnSinceLabel($modHours, $row['modifiedUTC'], $thisUser->infoArray["timeZone"]);
													
													//truncate file name...
													$label = $fileName;
													if(strlen($label) > 20){
														$label = substr($label, 0, 20) . "...";
													}
													
													//open / unzip URL's..
													$openLink = "<a href='" . $appDataURL . "/" . strtolower($filePath) . "/" . $fileName . "' target='_blank'>" . fnFileTypeIcon($fileName) . " " . $label . "</a>";
													$unzipLink = "";
													            										
													//zip files have different open / download URL's
													if(strtoupper($fileType) == "APPLICATION/ZIP"){
														$unzipLink = "<a href='" . $scriptName . "?unused=true" . $qVars . "&command=unzip&BT_fileId=" . $BT_fileId . "'>unzip</a>";
													}
			
			
                                                    $pad = "&nbsp;&nbsp;|&nbsp;&nbsp;";
                                                    echo "\n\n<tr id='i_" . $BT_fileId . "' class='" . $css . "'>";
                                                        echo "\n<td class='data' style='padding-left:0px;vertical-align:middle;'>";
                                                            echo $openLink;
                                                        echo "</td>";
                                                        echo "\n<td class='data' style='vertical-align:middle;'>";
                                                            echo $unzipLink;
														echo "</td>";

                                                        echo "\n<td class='data' style='padding-left:10px;vertical-align:middle;'>" . $folderLabel . "</td>";
                                                        echo "\n<td class='data' style='padding-left:10px;vertical-align:middle;'>" . $fileType . "</td>";
                                                        echo "\n<td class='data' style='padding-left:10px;vertical-align:middle;'>" . fnFormatBytes($fileSize) . " " . $imgDim . "</td>";
                                                        echo "\n<td class='data' style='padding-left:10px;vertical-align:middle;'>" . $modLabel . "</td>";
                                                   		echo "\n<td class='data' style='padding:0px;padding-right:5px;vertical-align:middle;text-align:right;'><input id='selected[]' name='selected[]' type='checkbox' style='height:12px;width:12px;margin:0px;vertical-align:middle;' value='" . $BT_fileId . "' " . fnIsChecked($BT_fileId) . "></td>";
                                                        echo "</td>";
                                                    echo "\n</tr>";
                                                    
                                                    
                                                }//end while
                                            }//no res
                                        }//no records
                            
                                    ?>
                        
                                    <tr>
                                        <td colspan='<?php echo $colCount;?>'>&nbsp;</td>
                                    </tr>
                            
                                	<tr>
										<td colspan='<?php echo $colCount;?>' style-'text-align:right;'>
                                        
                                    		<?php if($totalRecs > 0){?>
                                               
                                                <div style='padding:5px;float:right;'>
                                                    <select id='doWhat' name='doWhat' onChange="document.forms[0].submit();" style='vertical-align:middle;width:250px;' align='absmiddle'>
                                                        <option value="">&nbsp;Actions menu...</option>
                                                        <option value="removeFiles">&nbsp;Remove files (permanent)</option>
                                                        <option value="downloadZip">&nbsp;Create and Download Zip Archive</option>
                                                        <option value="moveToDocuments">&nbsp;Move Files to the Document folder</option>
                                                        <option value="moveToImages">&nbsp;Move Files to the Images folder</option>
                                                        <option value="moveToAudio">&nbsp;Move Files to the Audio folder</option>
                                                        <option value="moveToVideo">&nbsp;Move Files to the Video folder</option>
                                                        <option value="moveToPhpScripts">&nbsp;Move Files to the PHP Scripts folder</option>
                                                    </select>
                                                </div>
                                               	<div style='clear:both;'></div>
                                                <div style='padding:5px;float:right;'>
                                                    <?php 
                                                        echo ($firstRec + 1) . " - " . $lastRec . " of " . $totalRecs;
                                                        if($totalRecs > $recsPerPage){
                                                            echo "<span>&nbsp;</span>";
                                                            echo $prevPageLink . $nextPageLink;
                                                        }
                                                    ?>
                                                </div>
                                               	<div style='clear:both;'></div>
            
		                                    <?php } ?>
                                            
                                        </td>
                                    </tr>
                                </table>
  
 
                               <div class='infoDiv' style='margin-top:20px;'>
                                    <b>About the File Manager</b>
                                    <div style='padding-top:5px;'>
                                        The File Manager allows you to keep all the
                                        assets for the application organized. It also allows you to stream images, 
                                    	audio, video, and other documents from the server to the mobile device.
                                    </div>
                                        
                                    <div style='padding-top:5px;'>
                                        <b>Uploading:</b> Choose a file on your computer then click Upload. Be sure to select a folder to
                                        upload to. You can move uploaded files to another folder after uploading using the Actions Menu. 
                                        To upload multiple files at once, upload a .zip archive then use the un-zip function.
                                        <div style='padding-top:5px;padding-bottom:5px;'>
                                            <b>Allowed File Types</b>:
                                            <?php
                                                //build unique array of allowed file types to show
                                                $tmp = array();
                                                for($x = 0; $x < count($allowed_file_ext); $x++){
                                                    if(!in_array($allowed_file_ext[$x], $tmp)){
                                                        $tmp[] = $allowed_file_ext[$x];
                                                    }
                                                }
                                                for($x = 0; $x < count($tmp); $x++){
                                                    if($tmp[$x] != ""){
                                                        echo "." . $tmp[$x];
                                                        if($x < count($tmp) - 1) echo ", ";
                                                    }
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div style="padding-top:5px;">
                                         <b>Searching:</b>
                                         Click a letter to find files that begin with that letter or enter a filename in the 
                                         search box to find filenames that contain the search term. Tip: Enter a file extention (such as .pdf) to
                                    	 find files.
                                    </div>
                                    
                                    <div style='padding-top:5px;'>
    	                                <b>Sorting:</b> Click column headers to order by that column. Click again to reverse the order.
                                    </div>
                   
 
                               </div>
 
 
 
 							</td>
                    	</tr>
                    </table>


			</div>                
       </div> 
    </fieldset>


                                    
</div>
        
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>