<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	//add some inline css (in the <head>) for 100% width...
	$inlineCSS = "";
	$inlineCSS .= "html{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= "body{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= ".contentWrapper, .contentWrap{height:100%;width:100%;margin:0px;padding:0px;} ";
	$thisPage->cssInHead = $inlineCSS;

	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$pluginGuid = fnGetReqVal("pluginGuid", "", $myRequestVars);
	
	//objPlugin object...
	$objPlugin = new Plugin($pluginGuid);

	//icon URL...
	$iconURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/icon.png";
			   
	//readme URL...
	$readmeURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/readme.txt";

	//config URL...
	$configURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/config.txt";

	//added...  
	$addDat = fnFromUTC($objPlugin->infoArray['dateStampUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");

	//modified...  
	$modDate = fnFromUTC($objPlugin->infoArray['modifiedUTC'], $thisUser->infoArray["timeZone"], "m/d/y h:i A");

	//displayAs gets truncated if needed...
	$displayAs = $objPlugin->infoArray['displayAs'];
                                    
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	
?>

<div class='content'>
        
    <fieldset class='colorLightBg minHeightShadowbox'>
        
       	<div class='contentBox colorLightBg minHeightShadowbox' style='-moz-border-radius:0px;border-radius:0px;'>
        
            <div class="contentBand colorBandBg" style='-moz-border-radius:0px;border-radius:0px;'>
             	Plugin Details
            </div>
            <div style='padding:10px;'>
                        
                <table cellspacing='0' cellpadding='0' width="99%" style='margin-bottom:10px;'>
                    <tr>
                        <td style='vertical-align:top;width:40%;'>
                                
                            <div class='pluginBox'>
                                <div class="pluginIcon">
                                    <img src="<?php echo $iconURL;?>" alt="plugin"/>
                                </div>
                                <div class='pluginNickname'>
                                    <?php echo fnFormOutput($displayAs);?>
                                </div>
                            </div>

                            <div style='padding-top:10px;float:left;'>
                                <div><b><?php echo fnFormOutput($objPlugin->infoArray['displayAs']);?></b></div>
                                <div>Version: <?php echo fnFormOutput($objPlugin->infoArray['versionString']);?></div>
                                <div>Category: <?php echo fnFormatProperCase($objPlugin->infoArray['category']);?></div>
                                <div>Installed: <?php echo $addDat;?></div>
                                <div>Updated: <?php echo $modDate;?></div>
            				</div>

                            <div style='clear:both;'>&nbsp;</div>
	
                        </td>
                        <td style='vertical-align:top;width:60%;'>
                            
                            <div style='padding-left:10px;padding-top:5px;border-bottom:1px solid #999999;'>
                                <b>Description</b>
                            </div>
                            <div style='padding-left:10px;padding-top:5px;'>
                                <?php echo fnFormOutput($objPlugin->infoArray['shortDescription']);?>
                            </div>

                            
                            <div style='padding-left:10px;padding-top:10px;border-bottom:1px solid #999999;'>
                                <b>Plugin Developer:</b>
                                <?php echo fnFormOutput($objPlugin->infoArray['authorName']);?>
                            </div>
                            <div style='padding-left:10px;padding-top:5px;'>
                                
                                        <?php
											
											//array of contact methods...
											$contactMethods = array();
											if($objPlugin->infoArray['authorBuzztouchURL'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorBuzztouchURL'] . "' target='_blank' title='Author at buzztouch'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-bt.png' alt='buzztouch'/></a>";
											}
											if($objPlugin->infoArray['authorEmail'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='mailto:" . strtolower($objPlugin->infoArray['authorEmail']) . "' target='_blank' title='Author by Email'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-email.png' alt='email'/></a>";
											}
											if($objPlugin->infoArray['authorYouTubeURL'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorYouTubeURL'] . "' target='_blank' title='Author on YouTube'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-youtube.png' alt='youtube'/></a>";
											}	
											if($objPlugin->infoArray['authorWebsiteURL'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorWebsiteURL'] . "' target='_blank' title='Author Website'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-website.png' alt='website'/></a>";
											}
											if($objPlugin->infoArray['authorTwitterURL'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorTwitterURL'] . "' target='_blank' title='Author on Twitter'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-twitter.png' alt='twitter'/></a>";
											}
											if($objPlugin->infoArray['authorFacebookURL'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorFacebookURL'] . "' target='_blank' title='Author on Facebook'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-fb.png' alt='facebook'/></a>";
											}
											if($objPlugin->infoArray['authorLinkedInURL'] != ""){
												$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorLinkedInURL'] . "' target='_blank' title='Author on LinkedIn'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . fnGetSecureURL(APP_URL) . "/images/social-linkedin.png' alt='linkedin'/></a>";
											}

											for($x = 0; $x < count($contactMethods); $x++){
												echo $contactMethods[$x];
											}
											if(count($contactMethods) < 1){
												echo "<i>no contact information provided</i>";
											}
									?>                                
                                
                            </div>


                            <div style='padding-left:10px;padding-top:10px;border-bottom:1px solid #999999;'>
                                <b>Resources</b>
                            </div>
                            
                            <div style='padding-left:10px;padding-top:5px;'>
                                <a href="<?php echo $configURL;?>" target="_blank">config.txt</a>
                                &nbsp;&nbsp;|&nbsp;&nbsp;                                    
                                <a href="<?php echo $readmeURL;?>" target="_blank">readme.txt</a>
                            </div>
                        
                            
						</td>
             		</tr>
                    
                    <tr>    
                        <td colspan='2' style='vertical-align:top;'>
                                    
                            <div style='padding-left:10px;padding-top:5px;border-bottom:1px solid #999999;'><b>Screenshots</b></div>
                            <?php
                            
                                //show images in screenshots folder...
                                $screenshotsFolder = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/screenshots";
                                if($handle = opendir($screenshotsFolder)){
									$cnt = 0;
                                    while (false !== ($filename = readdir($handle))){
                                        if(strlen($filename) > 5){
											
											$isImg = false;
											if(strtoupper(substr($filename, -4)) == ".JPG") $isImg = true;
											if(strtoupper(substr($filename, -5)) == ".JPEG") $isImg = true;
											if(strtoupper(substr($filename, -4)) == ".PNG") $isImg = true;
											
											//image?
											if($isImg){
							
											
											
												$cnt++;
                                            	$imageURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/screenshots/" . $filename;
                                            	echo "<a href='" . $imageURL. "' rel='shadowbox[screenshots]'><img src='" . $imageURL . "' style='height:100px;width:60px;margin:10px;float:left;'></a>";
                                        
											}
										}
                                    }
                                    closedir($handle);
                                    
                                }
								if($cnt < 1){
                                    echo "<div style='padding-left:10px;'><i>no screenshots available</i></div>";
                                }
                            ?>
                            <div style='clear:both;'>&nbsp;</div>
                                    
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan='2' style='vertical-align:top;'>
                                
                            <div style='padding-left:10px;padding-top:5px;border-bottom:1px solid #999999;'><b>readme.txt contents</b></div>
                            <div style='padding:10px;font-family:monospace;font-size:9pt;'>
								<?php
                                    //if we have a readme.txt file...
                                    $plainText = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/readme.txt";
                                    $plainText = fnFormOutput(file_get_contents($plainText));
                                    $plainText = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $plainText);
                                    $plainText = trim(fnLineBreaks($plainText));
                                   
                                    // replace multiple spaces with single spaces
                                    $plainText = fnFormatClickableLinks($plainText);
                                    
                                    if(strlen($plainText) < 5){
                                        echo "This plugin's readme.txt file is blank?";
                                    }else{
                                    	echo $plainText;
									}
                                
                                ?>
                            </div>

                        </td>
	                </tr>
                </table>
                        
            </div>
    	</div>
        
    </fieldset>
    
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
