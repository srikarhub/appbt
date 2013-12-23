<?php   require_once("../../config.php");
		require_once("../../includes/zip.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);

	$dtNow = fnMySqlNow();
	$code = fnGetReqVal("code", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$screenGuid = fnGetReqVal("screenGuid", "", $myRequestVars);
	$BT_fileId = fnGetReqVal("BT_fileId", "", $myRequestVars);
	$mode = fnGetReqVal("mode", "", $myRequestVars);
	
	/*
	##################################################################################################
		If we do not have a guid then we are not looking at this file from the control panel.
		If screenGuid is not empty we are requesting this file from the mobile app.
		This means the mobile app expects to get output that isin JSON format. The format of the
		JSON output will depend on the type of screen being loaded in the app.
	##################################################################################################
	*/
	
	//must have appGuid
	if(strlen($appGuid) < 5){
		echo "Invalid Request.";
		exit();
	}
	
	//if we are not logged in, must have code or screenGuid
	if(strlen($guid) < 5){
		if($screenGuid == "" && $code == ""){
			echo "Invalid Request.";
			exit();
		}
	}

	//App Object
	if($appGuid == "" || $BT_fileId == ""){
		echo "invalid request";
		exit();
	}		

	//app's files directory
	$objApp = new App($appGuid);
	$appDataDirectory = $objApp->fnGetAppDataDirectory($appGuid);

	//assume we are viewing and not downloading...
	$contentDisposition = "inline";
	if(strtoupper($mode) == "DOWNLOAD"){
		$contentDisposition = "attachment";
	}
	$fileName = "";
	$fileSize = "";
	$fileType = "";
			
	//fetch file details from database...
    $strSql = " SELECT F.guid, F.appGuid, F.fileName, F.filePath, F.fileType, ";
	$strSql .= "F.fileSize, F.fileWidth, F.fileHeight, F.status, F.dateStampUTC, F.modifiedUTC ";
	$strSql .= "FROM " . TBL_BT_FILES . " AS F ";
	$strSql .= "WHERE guid = '" . $BT_fileId . "' AND appGuid = '" . $appGuid . "'";
	$strSql .= " LIMIT 0, 1";
    $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
    if($res){
    	while($row = mysql_fetch_array($res)){
			$fileName = $row['fileName'];
			$fileSize = $row['fileSize'];
			$fileType = $row['fileType'];
			$filePath = $appDataDirectory . "/" . ltrim($row['filePath'], "/") . "/" . ltrim($fileName, "/");
		}
	}
	
  	//file Exists? 
  	if(file_exists($filePath) ){ 
    
		//parse info / get extension 
		$fsize = filesize($filePath); 
		$path_parts = pathinfo($filePath); 
		$ext = strtolower($path_parts["extension"]); 
		$contentType = fnGetMimeTypeFromExtention($ext);
		
		//allowed upload file types...
		$allowed_file_types = fnGetAllowedUploadMimeTypes();
		
		//allowed mime-types and extentions...
		$allowed_mime_types = array(); 
		$allowed_file_ext = array(); 
		foreach ($allowed_file_types as $theType){
			$allowed_mime_types[] = $theType[0];
			$allowed_file_ext[] = $theType[1];
		}
		
		//if accectable...
		if(!in_array($contentType, $allowed_mime_types)){
			
			echo "This file type is not supported";
			exit();
		
		}else{
		
			//output headers..
			header("Pragma: public"); 
			header("Expires: 0"); 
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
			header("Cache-Control: private", false); 
			header("Content-Type:" . $contentType); 
			header("Content-Transfer-Encoding: binary"); 
			header("Content-Length: " . $fsize); 
			header("Content-Disposition: " . $contentDisposition . "; filename=\"". basename($filePath) ."\";" ); 
			
			//output file contents...
			echo file_get_contents($filePath);
			
			//end
			exit();
			
		}//unsupported file type
  }else{
  	echo "file not found?";
	exit();
  }


?>

