<?php
	
	/*
		this file should be included in plugin /index.php files so users can see some basic
		information about the plugin while configuring screen properties
	*/
	
	if(!isset($uniquePluginId)){
		echo "Error: No plugin id?";
		exit();	
	}
	if($uniquePluginId != ""){
	
		//get plugin details from database...
		$objPlugin = new Plugin("", $uniquePluginId);
		$pluginGuid = $objPlugin->infoArray['guid'];
		
		//icon URL...
		$iconURL = APP_URL . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/icon.png";
				   
		//plugin details URL...
		$detailsURL = APP_URL . "/bt_v15/bt_app/bt_pickerPluginDetails.php?pluginGuid=" . $pluginGuid;
	
		//readme.txt URL...
		$readmeURL = APP_URL . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/readme.txt";

		//config.txt URL...
		$configURL = APP_URL . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/config.txt";
	
		
	}
?>


<?php if($uniquePluginId != ""){ ?>
	
     <div class='pluginInfo'>
        
        <div class='pluginBox' style='float:none;'>
        	<div class="pluginIcon">
				<a href="<?php echo $detailsURL;?>" rel="shadowbox;height=550;width=950"><img src="<?php echo $iconURL;?>" style='height:50px;width:50px;' alt='Plugin icon'/></a>
            </div>
            <div class='pluginNickname'>
                <?php echo fnFormOutput($objPlugin->infoArray['displayAs']);?>
            </div>
        </div>
		<div style='padding-left:10px;'>
        	<a href="<?php echo $detailsURL;?>" rel="shadowbox;height=550;width=950"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt="arrow"/>plugin details</a>
        </div>
		<div style='padding-left:10px;padding-top:5px;'>
        	<a href="<?php echo $readmeURL;?>" target="_blank"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt="arrow"/>readme.txt</a>
        </div>
		<div style='padding-left:10px;padding-top:5px;'>
        	<a href="<?php echo $configURL;?>" target="_blank"><img src="<?php echo APP_URL;?>/images/arr_right.gif" alt="arrow"/>config.txt</a>
        </div>

		<?php
			//array of contact methods...
			$contactMethods = array();
			if($objPlugin->infoArray['authorBuzztouchURL'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorBuzztouchURL'] . "' target='_blank' title='Author at buzztouch'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-bt.png' alt='buzztouch'/></a>";
			}
			if($objPlugin->infoArray['authorEmail'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='mailto:" . strtolower($objPlugin->infoArray['authorEmail']) . "' target='_blank' title='Author by Email'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-email.png' alt='email'/></a>";
			}
			if($objPlugin->infoArray['authorYouTubeURL'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorYouTubeURL'] . "' target='_blank' title='Author on YouTube'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-youtube.png' alt='youtube'/></a>";
			}	
			if($objPlugin->infoArray['authorWebsiteURL'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorWebsiteURL'] . "' target='_blank' title='Author Website'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-website.png' alt='website'/></a>";
			}
			if($objPlugin->infoArray['authorTwitterURL'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorTwitterURL'] . "' target='_blank' title='Author on Twitter'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-twitter.png' alt='twitter'/></a>";
			}
			if($objPlugin->infoArray['authorFacebookURL'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorFacebookURL'] . "' target='_blank' title='Author on Facebook'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-fb.png' alt='facebook'/></a>";
			}
			if($objPlugin->infoArray['authorLinkedInURL'] != ""){
				$contactMethods[] = "<a style='white-space:nowrap;' href='" . $objPlugin->infoArray['authorLinkedInURL'] . "' target='_blank' title='Author on LinkedIn'><img style='width:25px;height:25px;margin-right:2px;margin-top:2px;' src='" . APP_URL . "/images/social-linkedin.png' alt='linkedin'/></a>";
			}

	
		?>
        <div style='margin-top:10px;width:115px;font-size:8pt;'>
			<div style='padding-left:5px;margin-bottom:4px;'>Plugin Developer</div>
            <?php
				for($x = 0; $x < count($contactMethods); $x++){
					echo $contactMethods[$x];
					if($x == 3){
						echo "<br/>";
					}
				}
			
			?>
		</div>
        
        <div style='padding-left:18px;padding-top:5px;padding-bottom:5px;margin-top:10px;'>
			<div style='padding-left:5px;margin-bottom:4px;'>screenshots</div>
			<?php
            
                //show images in screenshots folder...
                $screenshotsFolder = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/screenshots";
                if($handle = opendir($screenshotsFolder)){
					$cnt = 0;
                    while (false !== ($filename = readdir($handle))){
                        if(strlen($filename) > 5){
                            $cnt++;
							
							$isImg = false;
							if(strtoupper(substr($filename, -4)) == ".JPG") $isImg = true;
							if(strtoupper(substr($filename, -5)) == ".JPEG") $isImg = true;
							if(strtoupper(substr($filename, -4)) == ".PNG") $isImg = true;
							
							//image?
							if($isImg){
							
							
								$tmpURL = APP_URL . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/screenshots/" . $filename;
								$imagePath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/plugins" . $objPlugin->infoArray['webDirectoryName'] . "/screenshots/" . $filename;
								$imgSrc = APP_URL . "/bt_v15/bt_app/bt_pluginScreenshot.php?filePath=" . $imagePath;
								echo "<a href='" . $tmpURL. "' rel='shadowbox[screenshots]'><img src='" . $imgSrc . "' style='margin:10px;'></a><br>";
							
							}

						}
                    }
                    closedir($handle);
					if($cnt < 1){
						echo "<div style='font-size:8pt;'><i>no screenshots</i></div>";
					}
                }else{
                    echo "<div style='font-size:8pt;'><i>no screenshots</i></div>";
                }
            ?>
		</div>
    </div>
    
    
<?php } ?>








