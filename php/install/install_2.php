<?php

	$bolDone = false;
	$bolPassed = true;
	$strMessage = "";

	//from previous page if we have it...
	$path = "";
	$appURL = "";
	$dbServer = "";
	$dbName = "";
	$dbUser = "";
	$dbPass = "";
	$dbTablePrefix = "";
	$physPath = "";
	
	if(isset($_GET["path"])) $path = $_GET["path"];
	if(isset($_GET["appURL"])) $appURL = $_GET["appURL"];
	if(isset($_GET["dbServer"])) $dbServer = $_GET["dbServer"];
	if(isset($_GET["dbName"])) $dbName = $_GET["dbName"];
	if(isset($_GET["dbUser"])) $dbUser = $_GET["dbUser"];
	if(isset($_GET["dbPass"])) $dbPass = $_GET["dbPass"];
	if(isset($_GET["dbTablePrefix"])) $dbTablePrefix = $_GET["dbTablePrefix"];
	if(isset($_GET["path"])) $physPath = $_GET["path"];
	
	//make sure we have a writeable /files directory...
	$directoryMessage = "<b>Your directory structure looks fine.</b>";

	$filePath = "../files";
	if(is_dir($filePath)){
		if(is_writable($filePath)){
			
			//make required directories....
			//DO NOT DO THIS IF THEY ALREADY EXIST
			if(!is_dir($filePath . "/temp")){
				mkdir($filePath . "/temp");
				@chmod($filePath . "/temp", 0755);
			}else{
				$directoryMessage .= "<br>The /files/temp directory already exists, not overwriting. You must be updating an existing install, cool.";
			}
			
			if(!is_dir($filePath . "/applications")){
				mkdir($filePath . "/applications");
				@chmod($filePath . "/applications", 0755);
			}else{
				$directoryMessage .= "<br>The /files/applications directory already exists, not overwriting.";
			}
			
			if(!is_dir($filePath . "/plugins")){
				mkdir($filePath . "/plugins");
				@chmod($filePath . "/plugins", 0755);
			}else{
				$directoryMessage .= "<br>The /files/plugins directory already exists, not overwriting.";
			}
			
			if(!is_dir($filePath . "/theme")){
				mkdir($filePath . "/theme");
				@chmod($filePath . "/theme", 0755);
			}else{
				$directoryMessage .= "<br>The /files/theme directory already exists, not overwriting.";
			}
			
			if(!is_dir($filePath . "/custom")){
				mkdir($filePath . "/custom");
				@chmod($filePath . "/custom", 0755);
			}else{
				$directoryMessage .= "<br>The /files/custom directory already exists, not overwriting.";
			}
			
			//copy files from this folder to "theme" folder...
			//DO NOT DO THIS IF THEY ALREADY EXIST!
			if(!is_file($filePath . "/theme/logo.png")){
				copy("logo.png", $filePath . "/theme/logo.png");
			}
			if(!is_file($filePath . "/theme/favicon.png")){
				copy("favicon.png", $filePath . "/theme/favicon.png");
			}
			if(!is_file($filePath . "/theme/style.css")){
				copy("style.css", $filePath . "/theme/style.css");
			}
			
			
			//flag as done...		
			$bolDone = true;
			
		}else{
			$bolPassed = false;
			$strMessage .= "<br>The " . $path . "<b>/files</b> directory exists but is not writable. PHP needs write-access to this directory. For techies,
				that means chmod 0755. For others, contact your webserver's administrator";
		}
	
	}else{
		$bolPassed = false;
		$strMessage .= "<br>The " . $path . "<b>/files</b> directory does not exist?";
	}
	
	



?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml" >

<head>
	<title>buzztouch v2.1.9</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta http-equiv="imagetoolbar" content="false" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body style="background:url(../images/texture.png);">

<input type="hidden" name="step" id="step" value="2"/>

	<div class='contentWrapper'>
		<div class='contentWrap'>
			<div class="content">
            	
                <div style='text-align:middle;margin-left:0px;'>
                	<img src="../images/logo.png" alt="logo"/>
                </div>	
				<div class='contentBox colorLightBg minHeight'>
        			<div class='contentBand colorBandBg'>
            			Buzztouch v2.1.9 Installation.
               		</div>
                
                	<div style='padding:10px;'>
            			
                        <div style='font-size:12pt;margin-top:10px;vertical-align:middle;'>
                        	<img id="step_1" src="../images/num_3.png" style='vertical-align:middle;margin-right:20px;'/>
                    	</div>
                        <div style='margin-left:80px;margin-top:-45px;font-size:9pt;'>
                            <b>Verify Directory Structure</b>:
                            <div style='padding-top:5px'>
                            	The <b>/files</b> directory in the root folder of the install needs to have "write permissions" for PHP. 
                            	This is necessary for a few reasons. Contact your system administrator if you don't understand what this means. 
                           		If this folder is writable this install process will create some additional sub-folders in the
                                /files folder. These too will be writable. In other words, everything in the /files folder needs to 
                                be writeable by PHP.
                                <div style='padding-top:5px;'>
                                	<b>If you are updating this software these files will not be overwritten if they already exist.</b>
                                </div>
                            </div>
                        	<?php if(!$bolDone){?>
                            	<div style='padding-top:10px;font-size:10pt;font-weight:bold;'>
                            		CHMOD the /files folder to 0755 then refresh this page if this test is failing.
                            	</div>
                            <?php } ?>
 						</div>
                        
                        <?php if($strMessage != "" && !$bolDone){?>
                        	<div class='errorDiv' style='background:url(../images/att.png);background-position:5px 5px;background-repeat:no-repeat;margin:30px;margin-top:10px;margin-left:80px;margin-right:80px;margin-bottom:10px;'>
                            	<?php echo $strMessage;?>
                     			<div style='padding-top:5px;'>
                                    <a href="install_2.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>">Try again, refresh and re-test</a>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if($bolDone){?>
                        	<div class='doneDiv' style='background:url(../images/ok.png);background-position:5px 5px;background-repeat:no-repeat;margin:30px;margin-top:10px;margin-left:80px;margin-right:80px;margin-bottom:10px;'>
                            	<?php echo $directoryMessage;?>. 
                                <div style='padding-top:5px;'>
                                	Only a few more things to do...
                                </div>
                            </div>
                            
                            
                            <div style='padding-top:15px;margin-left:80px;margin-right:80px;margin-bottom:50px;'>
                                <div style='float:left;'>
                                    <input type="button" name="back" value="< back" class="buttonSubmit" onclick="document.location.href='index.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>';return false" />
                                </div>
                                <div style='float:right;'>
                                    <input type="button" name="next" value="next > " class="buttonSubmit" onclick="document.location.href='install_3.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>';return false" />
                                </div>
                                <div style='clear:both;'></div>
                            </div>
						
						<?php } ?>

                          
                    </div>
                        
              </div>
            	<br/><br/><br/><br/>



            </div>
        	
    	</div>
    </div>

</body>






