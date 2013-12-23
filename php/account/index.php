<?php   require_once("../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//This is the landing page after a successful login. PHP may or may not make the Session array available
	//after the login redirect (it depends on the server setup). In this case, $guid will still be empty after
	//inspecting the Session var above. For this reason we include an md5 hash in the URL when we do the redirect
	//so we can compare it to the users guid in the database. 
	if($guid == ""){
		if(isset($_GET["id"])){
			$tmp = "SELECT guid FROM " . TBL_USERS . " WHERE MD5(guid) = '" . fnFormInput($_GET["id"]) . "' AND isLoggedIn = '1'";
			$guid = fnGetOneValue($tmp, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			$_SESSION[APP_LOGGEDIN_COOKIE_NAME] = $guid;
		}
	} 
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	//user Object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);
	$userName = fnFormOutput($thisUser->infoArray["firstName"] . " " . $thisUser->infoArray["lastName"]);

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Account Control Panel | Home";


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- left side--> 
        <div class='boxLeft'>
            <div class='contentBox colorDarkBg minHeight'>
                <div class='contentBand colorBandBg'>
                    <?php echo fnFormOutput($userName);?>
                </div>
                <div id="leftNavLinkBox" style='padding:10px;white-space:nowrap;'>
					<?php echo $thisPage->fnGetControlPanelLinks("account", "", "block", ""); ?>
				</div>
             </div>
        </div>
        
        <!-- right side--> 
        <div class='boxRight'>
        	<div class='contentBox colorLightBg minHeight'>
                
                <div class='contentBand colorBandBg'>
                   Applications
                </div>
                
                <div style='padding:10px;'>
                    <?php
                    
                        $strSql = " SELECT A.id, A.guid, A.currentPublishVersion, A.version, A.name, A.iconUrl, A.dataDir, A.status, A.dateStampUTC, A.modifiedUTC, A.viewCount ";
                        $strSql .= " FROM " . TBL_APPLICATIONS . " AS A ";
                        $strSql .= " WHERE A.status != 'deleted' AND A.ownerGuid = '" . $guid . "'";
                        $strSql .= " ORDER BY A.dateStampUTC DESC";
                        $remRes = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
                        $cnt = 0;
                        if($remRes){
                            $numRows = mysql_num_rows($remRes);
                            if($numRows > 0){
                                while($row = mysql_fetch_array($remRes)){
                                    $cnt++;
                                    
									$created = fnFromUtc($row['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
                                    $modified = fnFromUtc($row['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/Y h:i A");
                                    $iconUrl = $row['iconUrl'];	
									
									//default icon...
									if($iconUrl == ""){
										$iconUrl = fnGetSecureURL(APP_URL) . "/images/default_app_icon.png";
									}
									
									//make sure icon URL is secure...
									$iconUrl = fnGetSecureURL($iconUrl);
									
                                    //link to application control panel...
                                    $cpUrl = fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $row['guid'];
									
                                    echo "\n<div class='appDiv colorDarkBg' title='App Control Panel' onclick=\"document.location.href='" . $cpUrl . "';\" style='cursor:pointer;'>";
                                        echo "\n<table class='appTable' cellspacing='0' cellpadding='0'>";
                                            echo "\n<tr>";
                                                echo "\n<td style='vertical-align:top;padding:0px;'>";
                                                    echo "\n<div class='iconOuter'>";
														echo "\n<div id=\"iconImage\" class=\"iconImage\" style=\"background:url('" . $iconUrl . "');background-position:50% 50%;background-repeat:no-repeat;\">";
															echo "<img src=\"" . fnGetSecureURL(APP_URL) . "/images/blank.gif\" alt=\"app icon\" />";
														echo "</div>";
														echo "\n<div id=\"iconOverlay\" class=\"iconOverlay\">";
															echo "&nbsp;";
														echo "</div>";
                                                    echo "</div>";
                                                echo "\n</td>";
                                                echo "\n<td style='vertical-align:top;padding:5px;'>";
                                                    echo "\n<b>" . fnFormOutput($row['name']) . "</b>";
                                                    echo "\n<br/>created: " . $created;
                                                    echo "\n<br/>modified: " . $modified;
                                                    echo "<br/>vers: " . $row['currentPublishVersion'] . " views: " . $row['viewCount'];
                                                echo "\n</td>";
                                            echo "\n</tr>";
                                        echo "\n</table>";
                                    echo "\n</div>";
                                    
                                }//end while
                            }//num rows
                        }//remRes
                        
                        if($cnt < 1){
                            echo "<div class='infoDiv'>";
								echo "This is your accounts home screen. It's purpose is to display a list of applications you have created. ";
								echo "You have not created any apps yet so the list is blank. ";
								echo "<div style='padding-top:5px;'>";
									echo "<a href='../bt_v15/bt_app/bt_appNew.php'><img src='" . fnGetSecureURL(APP_URL) . "/images/arr_right.gif' alt='arrow'/>Create a new application</a>"; 
								echo "</div>";
							
							echo "</div>";
                        }else{
                            if($cnt > 1){
                                echo "\n<div style='clear:both;padding-top:10px;'>";
                                    echo  $cnt . " apps</b>";
                                echo "</div>";
                            }
                        }	
                        
                        ?>  
                </div>
                
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>

