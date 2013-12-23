<?php require_once("../config.php");

	$id = fnGetReqVal("id", "", $myRequestVars);
	$appGuid = $id;
	$platform = fnGetReqVal("platform", "", $myRequestVars);
	$strMessage = "";
	$bolPassed = true;
	
	$pageTitle = "";
	$pListOrAPKURL = "";
	$iconURL = fnGetSecureURL(APP_URL) . "/images/icon_blank.png";
	$instructions = "";
	
	//must have appGuid
	if(strlen($appGuid) < 1){
		$strMessage .= "<br/>Invalid App Id";
		$bolPassed = false;
	}
	
	//get the plaform from the USER agent if we did not pass it in...
	if($platform == ""){
		$tmpUserAgent = @$_SERVER["HTTP_USER_AGENT"];
		$pos = strrpos(strtoupper($tmpUserAgent), "SAFARI");
		if($pos === false){
    		
			// not found, look for android...
			$pos = strrpos(strtoupper($tmpUserAgent), "ANDROID");
			if($pos === false){
				//android not found either!
			}else{
				$platform = "android";
			}
			
		}else{
		
			//is safari...
			$platform = "iOS";
		
		}
	}
	
	//app object...
	$objApp = new App($appGuid);
	$pageTitle = "Install " . fnFormOutput($objApp->infoArray["name"]);
	$tmpIconURL = fnGetSecureURL($objApp->infoArray["iconUrl"]);
	
	//default icon if not provided...
	if($tmpIconURL == ""){
		$tmpIconURL = fnGetSecureURL(APP_URL) . "/images/default_app_icon.png";
	}
	
	
	$iconFileName = basename($tmpIconURL);
	$dataDir = $objApp->infoArray["dataDir"];
	
	
	//must have a platform...
	if(strtoupper($platform) != "IOS" && strtoupper($platform) != "ANDROID"){
		$bolPassed = false;
		$strMessage .= "<br/>The version you are trying to install (iOS or Android) could not be determined. Hint: Append &platform=iOS (or android) to the end of the URL to force a version";
	}

	//verify we have the application's download directory structure...
	$bolPassed = false;
	$strMessage .= "<br>This applications download directory could not be found?";
	$appDirectoryPath = APP_PHYSICAL_PATH . $dataDir;
	if(is_dir($appDirectoryPath . "/install-ios/")){
		if(is_dir($appDirectoryPath . "/install-android/")){
			if(is_writable($appDirectoryPath . "/install-ios/")){
				if(is_writable($appDirectoryPath . "/install-android/")){
					$strMessage = "";
					$bolPassed = true;
				}
			}
		}
	}
	
	//convert .jpg icon in this app's images directory to .png version ...
	$iconFilePath = $appDirectoryPath . "/images/" . $iconFileName;
	if(is_file($iconFilePath)){

		//ignore jpeg errors...
		@ini_set("gd.jpeg_ignore_warning", 1);

		//try to load .jpg first, then try .png
		$imageObject = @imagecreatefromjpeg($iconFilePath);
		if(!$imageObject){
			$imageObject = @imagecreatefrompng($iconFilePath);
		}
		
		//copy / resize image...
		if($imageObject){
			
			list($width_orig, $height_orig) = @getimagesize($iconFilePath);
			$newImageWidth = 57;
			$newImageHeight = 57;
			$newImage = @imagecreatetruecolor($newImageWidth, $newImageHeight);
			
			//required to save transparency...
			@imagealphablending($newImage, false );
			@imagesavealpha($newImage, true );
			@imagecopyresampled($newImage, $imageObject, 0, 0, 0, 0, $newImageWidth, $newImageHeight, $width_orig, $height_orig);
	
			//save the resized image..
			@imagepng($newImage, $appDirectoryPath . "/install-ios/icon.png");
			@imagepng($newImage, $appDirectoryPath . "/install-android/icon.png");
	

		}
		
	}else{
		$bolPassed = false;
		$strMessage .= "<br/>This applications icon could not be found";
	}
	
	//platform variables...
	if($bolPassed){
		
		if(strtoupper($platform) == "IOS"){
			$iconURL = fnGetSecureURL(APP_URL) . $dataDir . "/install-ios/icon.png";
			$instructions = "If your device is not on the authorized list of testing devices this application will not install.";
			$pListOrAPKURL = "itms-services://?action=download-manifest&url=" . fnGetSecureURL(APP_URL) . "/" . $appDirectoryPath . "/install-ios/app.plist";
		}
		
		//Android variables...
		if(strtoupper($platform) == "ANDROID"){
			$iconURL = fnGetSecureURL(APP_URL) . $dataDir . "/install-android/icon.png";
			$instructions = "Your device must be set to allow \"non-market apps\" or this install will not work.";
			$pListOrAPKURL = fnGetSecureURL(APP_URL) . $dataDir . "/install-android/app.apk";
		} 
	
		//check for necessary iOS files...
		if(strtoupper($platform) == "IOS"){
		
			//need app.plist, app.ipa, icon.png
			if(!is_file($appDirectoryPath . "/install-ios/app.ipa")){
				$bolPassed = false;
				$strMessage .= "<br/>iOS requires a file that could not be found: app.plist";	
			}
			if(!is_file($appDirectoryPath . "/install-ios/app.plist")){
				$bolPassed = false;
				$strMessage .= "<br/>iOS requires a file that could not be found: app.ipa";	
			}
			if(!is_file($appDirectoryPath . "/install-ios/icon.png")){
				$bolPassed = false;
				$strMessage .= "<br/>iOS requires a file that could not be found: icon.png";	
			}
			
		}
		
		//check for necessary Android files...
		if(strtoupper($platform) == "ANDROID"){

			//need app.apk,icon.png
			if(!is_file($appDirectoryPath . "/install-android/app.apk")){
				$bolPassed = false;
				$strMessage .= "<br/>Android requires a file that could not be found: app.apk";	
			}
			if(!is_file($appDirectoryPath . "/install-android/icon.png")){
				$bolPassed = false;
				$strMessage .= "<br/>Android requires a file that could not be found: icon.png";	
			}
	
		}	
	
	}else{ //bolPassed
		$iconURL = $tmpIconURL;
	}
	
	
	

?>

<!DOCTYPE html>
<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" /> 
        <title><?php echo fnFormOutput($pageTitle);?></title>
        <style type="text/css">
                
				<?php if(strtoupper($platform) == "IOS"){ ?>
				
					body{
						background: url(../images/ios-stripes.png) repeat #c5ccd4;
						font-family: Helvetica, arial, sans-serif;
					}
					.pageHeader{
						font-size: 16pt;
						padding: 6px;
						text-align: center;
					}
					.about{
						background: white;
						border: 1px #ccc solid;
						border-radius: 14px;
						padding: 4px 10px;
						margin: 10px 0;
					}
					.icon{
						border: 1px #ccc solid;
						border-radius: 8px;
						width:57px;
						height:57px;
						margin-right:10px;
					}   
					.instructions{
						padding:5px;
						font-size: 12pt;
					}
					table{
						width: 100%;
					}
				
				<?php } ?>
				<?php if(strtoupper($platform) == "ANDROID"){ ?>
					
					body{
						background-color:#000000;
						color:#FFFFFF;
						font-family: Helvetica, arial, sans-serif;
					}
					.pageHeader{
						font-size: 16pt;
						padding: 6px;
						text-align: center;
					}
					.about{
						border: 1px #ccc solid;
						padding: 10px;
						background-color:#FFFFFF;
						color:#000000;
					}
					.icon{
						border: 1px #ccc solid;
						width:57px;
						height:57px;
					}   
					.instructions{
						padding:5px;
						font-size: 12pt;
					}
					table{
						width: 100%;
					}
				
				
				<?php } ?>
        </style>
</head>
<body>
 
<div class="pageHeader"><?php echo fnFormOutput($pageTitle);?></div>

<?php if(!$bolPassed){?>

	<?php echo "<div style='background-color:#FFFFFF;padding:10px;border:1px solid red;color:red;'>There was a problem processing your request.<span style='color:black;'>" . $strMessage . "</span></div>";?>

<?php }else{ ?>


    <div class="about">
        <table>
            <tr>
                <td style="width:57px;height:57px;overflow:hidden;">
                    <a href="<?php echo $pListOrAPKURL;?>">
                        <img src="<?php echo $iconURL;?>" class="icon" alt="icon"/>
                    </a>
                </td>
                <td class="instructions">Tap the icon to install</td>
            </tr>
            <tr>
                <td colspan='2' class="instructions">
                    <?php echo $instructions;?>
                </td>
            </tr>
        </table>
    </div>

<?php } ?>


</body>
</html>








