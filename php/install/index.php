<?php if(false){
		echo "Your webserver is not running .PHP so you can't install this software.";
		exit();
	}else{
	
	
		//error reporting is always on during install...
		error_reporting(E_ALL);

	
		//Cleans up form entries	
		function fnFormInput($theVal){
			if(!is_array($theVal)){
				if(trim($theVal) == ""){
					return "";
					break;
				}else{
				
					//replace of all this!!!!!
					$theVal = str_replace("/*", "", $theVal);
					$theVal = str_replace("\"", "", $theVal);
					$theVal = strip_tags($theVal); //strip html tags
				
					//trim spaces off end
					$theVal = trim($theVal); //trim leading and trailing spaces.
				
					//if magic quotes is already on, don't do anyting
					if(get_magic_quotes_gpc() == 1){
						return $theVal;
						break;
					}else{
						$theVal = addslashes($theVal);
						return $theVal;
						break;
					}
				}
			}//not an array
		}
					
		//Cleans up form values for display	
		function fnFormOutput($theVal, $showHTMLEntities = false){
			
    		$r = utf8_encode($theVal); 
			
			if(trim($r) == ""){
				return $r;
				break;
			}else{
			
				$r = stripslashes($theVal);
				$r = str_replace("&quo;", "\"", $r);
				$r = trim($r);
				
				return $r;

			}
			
			return "";
		}
	
	
		//validation on form post...
		$bolDone = false;
		$bolPassed = true;
		$strMessage = "";
		$img = "../images/num_1.png";
		

		/*
			determine the APP_PHYSICAL_PATH for the app...We need something like this
			/var/www/vhosts/domain.com/httpdocs/BT-server-dev for the settings database
		*/

		$path = "";
		if(isset($_SERVER["SCRIPT_FILENAME"])) $path = $_SERVER["SCRIPT_FILENAME"];
		if(strlen($path) > 18){
			$path = substr($path, 0, strlen($path) - 18);
		}
		
		$appURL = "";
		$dbServer = "localhost";
		$dbName = "";
		$dbUser = "";
		$dbPass = "";
		$dbTablePrefix = "bt_";
		$bolPosted = false;
		
		//if we are coming back from step2 these will be in the URL...
		if(isset($_GET["appURL"])) $appURL = fnFormInput($_GET["appURL"]);
		if(isset($_GET["path"])) $path = fnFormInput($_GET["path"]);
		if(isset($_GET["dbServer"])) $dbServer = fnFormInput($_GET["dbServer"]);
		if(isset($_GET["dbName"])) $dbName = fnFormInput($_GET["dbName"]);
		if(isset($_GET["dbUser"])) $dbUser = fnFormInput($_GET["dbUser"]);
		if(isset($_GET["dbPass"])) $dbPass = fnFormInput($_GET["dbPass"]);
		if(isset($_GET["dbTablePrefix"])) $dbTablePrefix = fnFormInput($_GET["dbTablePrefix"]);
		
		//if we are posting the form these will be in the form post...
		if(isset($_POST["appURL"])) $appURL = fnFormInput($_POST["appURL"]);
		if(isset($_POST["path"])) $path = fnFormInput($_POST["path"]);
		if(isset($_POST["dbServer"])) $dbServer = fnFormInput($_POST["dbServer"]);
		if(isset($_POST["dbName"])) $dbName = fnFormInput($_POST["dbName"]);
		if(isset($_POST["dbUser"])) $dbUser = fnFormInput($_POST["dbUser"]);
		if(isset($_POST["dbPass"])) $dbPass = fnFormInput($_POST["dbPass"]);
		if(isset($_POST["dbTablePrefix"])) $dbTablePrefix = fnFormInput($_POST["dbTablePrefix"]);
		

		//when form is posted..
		if(isset($_POST["step"])){
			$bolPosted = true;
			
			//include database functions...
			include("db.php");
	
			//validate...
			if(strlen($path) < 1){
				$bolPassed = false;
				$strMessage .= "<br>Please enter the full PHYSICAL path on the file system where this app is installed. Do not include the last slash.";
			}else{
			
				//use path to verify that known files exist. If they don't, the path must be wrong...
				//lose the last slash off the path if they entered one...
				$path = rtrim($path, "/");
				if(is_file($path . "/install/index.php")){
					//all good
				}else{
					
					$bolPassed = false;
					$strMessage .= "<br>The PHYSICAL PATH entered below is wrong. This is tricky and you may need to get some assistance ";
					$strMessage .= "if you're unsure about how to find this. We think it's wrong because we used the path entered below to look for a known file in this package. Becuase the ";
					$strMessage .= " file didn't exist, the PHYSICAL PATH must be incorrect. ";
					$strMessage .= "If you're installing this software in a hosted environment, you may need to check your online hosting control panel for this information. ";
					$strMessage .= "You cannot continue until you figure this out. ";
					$strMessage .= "<div style='padding-top:5px;'>";
						$strMessage .= "<b>Sample PHYSICAL PATH:</b> /var/www/vhosts/domain.com/httpdocs/BT-server";
					$strMessage .= "</div>";
				}
			
			
			}
			
			//remove the last slash off the URL if they entered one...
			$appURL = rtrim($appURL, "/");
			if(strlen($appURL) < 1){
				$bolPassed = false;
				$strMessage .= "<br>Please enter the full URL to this install. Do not include the last slash.";
			}

			if(strlen($dbServer) < 1){
				$bolPassed = false;
				$strMessage .= "<br>Please enter a database server name.";
			}
			if(strlen($dbName) < 1){
				$bolPassed = false;
				$strMessage .= "<br>Please enter the name of an existing database.";
			}
			if(strlen($dbUser) < 1){
				$bolPassed = false;
				$strMessage .= "<br>Please enter the database User Name.";
			}
			if(strlen($dbPass) < 1){
				$bolPassed = false;
				$strMessage .= "<br>Please enter the password for this database user.";
			}
			
			//passed?
			if($bolPassed){
				
				//try to connect to the database server....
					if(!fnDoesDatabaseServerExist($dbServer, $dbUser, $dbPass)){
						$bolPassed = false;
						$strMessage .= "<br>There was a problem connecting to the database server. Are you sure you entered the proper user credentials? ";
						$strMessage .= "Server is usually <b>localhost</b>, User Name and Password could be just about anything! Ask your server's administrator if you're unsure.";
					}else{
						if(!fnDoesDatabaseExist($dbServer, $dbName, $dbUser, $dbPass)){
							$bolPassed = false;
							$strMessage .= "<br>There is not a database on this server with that name. You need to create a new, empty database (no tables) ";
							$strMessage .= "before installing this software.";
						}else{	
				
				
							//create default tables...
							if(!fnCreateTables($dbServer, $dbName, $dbUser, $dbPass, $dbTablePrefix, $appURL, $path)){
								$bolPassed = false;
								$strMessage .= "<br>There was a problem creating the necessary tables to support this software. Are you sure the user ";
								$strMessage .= "you entered as CREATE privileges?";
							}else{
							
								//inserted default database values already in the fnCreateTables method...
								$bolDone = true;
								$img = "../images/num_2.png";
								
							}
							
							
						}//select db
					}//connect to db
				
				
			}//bolPassed
		}//form not posted...
	
	//closing } curly brace at end of HTML on this page...	
	
?>



<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" xmlns:og="http://ogp.me/ns#" xmlns:fb="http://www.facebook.com/2008/fbml" >

<head>
	<title>Buzztouch 2.1.9</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta http-equiv="imagetoolbar" content="false" />
	<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body style="background:url(../images/texture.png);">
<form id="myForm" method="POST" action="index.php" target="_self">
<input type="hidden" name="step" id="step" value="2"/>

	<div class='contentWrapper'>
		<div class='contentWrap'>
			<div class="content" >
            	
                <div style='text-align:middle;margin-left:0px;'>
                	<img src="../images/logo.png" alt="logo"/>
                </div>	
				<div class='contentBox colorLightBg minHeight'>
        			<div class='contentBand colorBandBg'>
            			Buzztouch v2.1.9 Installation.
               		</div>
                
                	<div style='padding:10px;margin-bottom:50px;padding-bottom:50px;'>
            			
                        <div style='margin-top:10px;'>
                        	<img id="step_1" src="../images/<?php echo $img;?>" style='vertical-align:middle;margin-right:20px;'/>

							<?php if(!$bolPosted){ ?>
                                
                                <div style='margin-left:80px;margin-top:-45px;font-size:9pt;'>
                                    
                                        <b>You should have already created an empty database and user as described in the <a href="../bt-server-installation.pdf" target="_blank">installation instructions.pdf</a> included with this software</b>
                                        <div style='padding-top:5px'>
                                            The database should have <b>utf8_unicode_ci</b> as the collation (see image).
                                            The database user must have SELECT, INSERT, UPDATE, DELETE, and CREATE privileges.
                                            If you're unsure what this means, ask your system administrator for assistance. 
                                        </div>
                                        
                                        <div style='padding-top:5px;'>
                                            This installation process does not create an empty database. If you're on a shared host your
                                            account type may not allow you to create new databases. In this case, they probably already 
                                            provided you with a database and login. This is OK. This software can run on the existing database.
                                        </div>
                                        
                                        <div style='padding-top:5px;'>
                                            If you're unsure what a <b>table prefix</b> is, use the default "bt_" prefix.                                  
                                        </div>
                                </div>
                            
							<?php } //posted ?>
                        
							<?php if($strMessage != "" && !$bolDone){?>
                                <div class='errorDiv' style='background:url(../images/att.png);background-position:5px 5px;background-repeat:no-repeat;margin-top:-45px;margin-left:80px;margin-right:80px;margin-bottom:10px;'>
                                    <?php echo $strMessage;?>
                                </div>
                            <?php } ?>

							<?php if($bolDone){?>
                                <div class='doneDiv' style='background:url(../images/ok.png);background-position:5px 5px;background-repeat:no-repeat;margin-top:-45px;margin-left:80px;margin-right:80px;margin-bottom:10px;'>
                                    OK. Your PHYSICAL path and database are figured out. Lets make sure you entered the correct 
                                    ROOT URL too. We can do this by using the URL you entered to look for a known image
                                    in this package. If we can't find the image, you must have made a mistake...
                                </div>
                                
                                <div class='infoDiv' style='background:url(../images/info.png);background-position:5px 5px;background-repeat:no-repeat;margin:30px;margin-left:80px;margin-right:80px;margin-top:0px;'>
                                    Have a look at the box below this text, the one with the <span style='color:red;'>red border</span>
                                    around it. An image of a checkmark should appear 
                                    inside the box. If no checkmark is in the box then you didn't enter the correct ROOT URL 
                                    on the previous screen and we'll need to go back and try again.
                                </div>
                                <div style="background-color:#FFFFFF;border:2px solid red;margin-top:-30px;margin-left:auto;margin-right:auto;height:50px;width:50px;background:url('<?php echo $appURL;?>/images/check.png');background-repeat:no-repeat;background-position:50% 50%;">
                                    &nbsp;
                                </div>
                                
                                <div style='padding-top:5px;margin-left:auto;margin-right:auto;width:80%;'>
                                	<div style='float:left;'>
                                        <input type="button" name="back" value="< back" class="buttonSubmit" onclick="document.location.href='index.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>';return false" />
                                	</div>
                                    <div style='float:right;'>
                                        <input type="button" name="next" value="next >" class="buttonSubmit" onclick="document.location.href='install_2.php?appURL=<?php echo $appURL;?>&dbTablePrefix=<?php echo $dbTablePrefix;?>&dbPass=<?php echo $dbPass;?>&dbUser=<?php echo $dbUser;?>&dbName=<?php echo $dbName;?>&dbServer=<?php echo $dbServer;?>&path=<?php echo $path;?>';return false" />
                                    </div>
                                    <div style='clear:both;'></div>
                                </div>
                                
                                
                            <?php } ?>
    
                            <?php if(!$bolDone){?>
                                <div style='margin-left:80px;margin-top:20px;font-size:9pt;'>
                                    <table style='width:90%;'>
                                    
                                        <tr>
                                            <td colspan='2' style="vertical-align:top;">
                                                
                                                <div style='color:#FF822E;'>
                                                    Application PHYSICAL PATH like <span style='color:red;font-weight:bold;'>/var/vhosts/domain/httpdocs/BT-server (not a URL)</span><br />
                                                    <input type="text" name="path" id="path" value="<?php echo fnFormOutput($path);?>" style="width:97%;"/>
                                                </div>
    
                                                <div style='color:#FF822E;'>
                                                    Application ROOT URL like <span style='color:red;font-weight:bold;'>http://www.domain.com/BT-server (not /install)</span><br />
                                                    <input type="text" name="appURL" id="appURL" value="<?php echo fnFormOutput($appURL);?>" style="width:97%;"/>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="vertical-align:top;">
                                                <div style='color:#FF822E;white-space:nowrap;'>
                                                    Database Server like "localhost"<br />
                                                    <input type="text" name="dbServer" id="dbServer" value="<?php echo fnFormOutput($dbServer);?>" style="width:300px;;"/>
                                                </div>
                                                <div style='color:#FF822E;white-space:nowrap;'>
                                                    Database Name: We have no idea...<br />
                                                    <input type="text" name="dbName" id="dbName" value="<?php echo fnFormOutput($dbName);?>" style="width:300px;"/>
                                                </div>
                                                <div style='color:#FF822E;white-space:nowrap;'>
                                                    Database User Name: and won't try to guess...<br />
                                                    <input type="text" name="dbUser" id="dbUser" value="<?php echo fnFormOutput($dbUser);?>" style="width:300px;"/>
                                                </div>
                                                <div style='color:#FF822E;white-space:nowrap;'>
                                                    Database Password: so you'll have to know it.<br />
                                                    <input type="password" name="dbPass" id="dbPass" value="<?php echo fnFormOutput($dbPass);?>" style="width:300px;"/>
                                                </div>
                                                <div style='color:#FF822E;white-space:nowrap;'>
                                                    Database Table Prefix: This may not matter to you<br />
                                                    <input type="text" name="dbTablePrefix" id="dbTablePrefix" value="<?php echo fnFormOutput($dbTablePrefix);?>" style="width:300px;"/>
                                                </div>
                                                
                                                <div style='margin-top:10px;'>
                                                    <input type="button" name="dbSubmit" value="next" class="buttonSubmit" onclick="document.forms[0].submit();return false" />
                                                </div>
                                            </td>
                                            <td style="vertical-align:padding:10px;padding-left:30px;padding-top:5px;">
                                                <div>
                                                    <img src="../images/mysql.png" alt="mysql"/>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                    </table>
                                </div>
                            <?php } ?>
                        
                        </div> 
                    </div>
                        
                
                </div>
            	<br/><br/><br/><br/>
            
            </div>
        	
    	</div>
    </div>
</form>

</body>

<?php } ?>





