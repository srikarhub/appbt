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
	
	//css files in header...
	$thisPage->cssIncludes = "styles/imgareaselect-animated.css";	
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, scripts/jquery-1.3.2.min.js, scripts/jquery.imgareaselect.pack.js";	

	$thisPage->customBody = "onload=\"init();\"";

	//form does uploads...
	$thisPage->formEncType = "multipart/form-data";

	//vars...
	$dtNow = fnMySqlNow();
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$bolIsUploaded = false;
	
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$command = fnGetReqVal("command", "", $myRequestVars);
	$iconUrl = fnGetReqVal("iconUrl", "", $myRequestVars);
	$large_image_location = fnGetReqVal("large_image_location", "", $myRequestVars);
	$small_image_location = fnGetReqVal("small_image_location", "", $myRequestVars);
	$large_imageURL = fnGetReqVal("large_imageURL", "", $myRequestVars);
	$small_imageURL = fnGetReqVal("small_imageURL", "", $myRequestVars);
	
	
	$imgGuid = fnGetReqVal("imgGuid", strtoupper(fnCreateGuid()), $myRequestVars);
	$upload_dir = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/temp"; 					
	$upload_path = $upload_dir . "/";				
	$large_image_prefix = "icon_full_"; 		
	$small_image_prefix = "icon_crop_";			
	$large_image_name = $large_image_prefix . $imgGuid;     
	$small_image_name = $small_image_prefix . $imgGuid;     
	$max_width = "700";							
	$small_width = "72";						
	$small_height = "72";						
	$allowed_image_types = array('image/pjpeg'=>"jpg",'image/jpeg'=>"jpg",'image/jpg'=>"jpg", 'image/png'=>"png", 'image/x-png'=>"png");
	$allowed_image_ext = array_unique($allowed_image_types); 
	$image_ext = "";	
	foreach ($allowed_image_ext as $mime_type => $ext) {
		$image_ext .= strtoupper($ext) . " ";
	}


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
	
	//app name...
	$appName = $objApp->infoArray["name"];
			

	////////////////////////////////////////////////////////////////////////////////////
	//image functions
	
	//image height
	function getHeight($image){
		$size = getimagesize($image);
		$height = $size[1];
		return $height;
	}
	
	//image width
	function getWidth($image) {
		$size = getimagesize($image);
		$width = $size[0];
		return $width;
	}


	
	//large image upload
	if(strtoupper($command) == "UPLOAD_LARGE"){
	
		//Get the file information
		$userfile_name = $_FILES['image']['name'];
		$userfile_tmp = $_FILES['image']['tmp_name'];
		$userfile_size = $_FILES['image']['size'];
		$userfile_type = $_FILES['image']['type'];
		$filename = basename($_FILES['image']['name']);
		$file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
		
		//only process if the file is acceptable and below the allowed limit
		if((!empty($_FILES["image"])) && ($_FILES['image']['error'] == 0)) {
			
			foreach ($allowed_image_types as $mime_type => $ext) {
				//loop through the specified image types and if they match the extension
				if($file_ext == $ext && $userfile_type == $mime_type){
					$strMessage = "";
					break;
				}else{
					$strMessage = "<br/>Only <b>.JPG</b> or <b>.PNG</b> images are allowed. This message means you tried to upload an image that ";
					$strMessage .= "was not an acceptable format. Tip: Replace .jpeg file extensions with .jpg before uploading. ";
				}
			}
			//if file size
			if($bolPassed){
				//check if the file size is above the allowed limit
				if ($userfile_size > APP_MAX_UPLOAD_SIZE) {
					$bolPassed = false;
					$strMessage .= "<br/>Uploaded file is too large. Max allowed: " . fnFormatBytes(APP_MAX_UPLOAD_SIZE);
				}
			}//bolPassed
			
			
		}else{
			$strMessage .= "<br/>Please select an image to upload";
		}
		
		//Everything is ok, so we can upload the image.
		if (strlen($strMessage) == 0){
			
			if (isset($_FILES['image']['name'])){
				
				//this file could now have an unknown file extension (we hope it's one of the ones set above!)
				$large_image_location = $upload_path . $large_image_name . "." . $file_ext;
				$large_imageURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/temp/" . $large_image_name . "." . $file_ext; 

				$small_image_location = $upload_path . $small_image_name . "." . $file_ext;
				$small_imageURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/temp/" . $small_image_name . "." . $file_ext; 

				//save image then make sure it is large enough
				if(move_uploaded_file($userfile_tmp, $large_image_location)){
					chmod($large_image_location, 0755);
					$bolPassed = true;
					$width = getWidth($large_image_location);
					$height = getHeight($large_image_location);
				}else{
					$bolPassed = false;
					$strMessage .= "<br/>The image you uploaded could not be saved. Please contact a system administrator. ";
				}
				
				//still good?
				if($bolPassed){
					if($width < $small_width || $height < $small_height){
						$bolPassed = false;
						$strMessage .= "<br/>The image you uploaded is too small. Choose an image that is at least ";
						$strMessage .= $small_width . " x " . $small_height;
					}
				}
					
				if($bolPassed){

					//figure out scale for new image
					$scale = 1;
					if ($width > $max_width){
						$scale = $max_width / $width;
					}

					//make sure scaled is still large enough
					$newImageWidth = ceil($width * $scale);
					$newImageHeight = ceil($height * $scale);
					if($newImageWidth < $small_width || $newImageHeight < $small_height){
						$bolPassed = false;
						$strMessage .= "<br/>The image you uploaded is too small. Choose an image that is at least ";
						$strMessage .= $small_width . " x " . $small_height;
					}

					//still passed?0
					if($bolPassed){

						//scale the image if it is greater than the width set above
						if ($width > $max_width){
							$scale = $max_width / $width;
							$bolDidResize = resizeImage($large_image_location, $width, $height, $scale);
						}else{
							$scale = 1;
							$bolDidResize = resizeImage($large_image_location, $width, $height, $scale);
						}
						
						
						//delete the thumbnail file so the user can create a new one
						if($small_image_location != ""){
							if(file_exists($small_image_location)){
								@unlink($small_image_location);
							}
						} //thumb == ""
						
						//resizeImage may return error
						if(!$bolDidResize){
							$bolPassed = false;
							$strMessage .= "<br/>There was an error processing the image you uploaded? The file size may be too large? Please try again. Select a smaller image if you continue to have problems.";
						}else{
							//flag as uploaded
							$bolIsUploaded = true;
							$bolDone = false;
						}
						
						
						
					}//if bolPassed
				}//if bolPased
				
				
			} //have file name
		} //message == ""
	}
	//end large image upload
	
	
	//small image upload, after crop.
	if(strtoupper($command) == "UPLOAD_SMALL"){
	
		//new coordinates to crop the image.
		$x1 = $_POST["x1"];
		$y1 = $_POST["y1"];
		$x2 = $_POST["x2"];
		$y2 = $_POST["y2"];
		$w = $_POST["w"];
		$h = $_POST["h"];
		
		//scale the image to the small_width set above
		$scale = $small_width / $w;
		$cropped = resizeThumbnailImage($small_image_location, $large_image_location, $w, $h, $x1, $y1, $scale);
		
		//if cropped was successful
		if($cropped){
		
			//update app details...
			$objApp = new App($appGuid);
			
			//extension...
			$file_ext = strtolower(substr($small_image_location, strrpos($small_image_location, '.') + 1));

			//complete path to image
			$imgUrl = APP_URL . APP_DATA_DIRECTORY . "/applications/" . $appGuid . "/images/" . $imgGuid . "_icon." . $file_ext;
			$small_imageURL = fnGetSecureURL(APP_URL) . APP_DATA_DIRECTORY . "/applications/" . $appGuid . "/images/" . $imgGuid . "_icon." . $file_ext; 
			$imgName = basename($imgUrl);
		
			//move selected image to this app's image directory
			$moveFromPath = $small_image_location;
			$moveToPath = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/applications/" . $appGuid . "/images/" . $imgName;

			if(is_file($moveFromPath)){
	
				//remove previously uploaded image
				$dir = APP_PHYSICAL_PATH . APP_DATA_DIRECTORY . "/applications/" . $appGuid . "/images";
				if(is_dir($dir)){
								
					//if(!$dh = @opendir($dir)) return;
					if($handle = opendir($dir)){
    					while(false !== ($fileObj = readdir($handle))) {
							if(strlen($fileObj) > 5){
								if(strrpos($fileObj,"_icon") > 0){
									@unlink($dir . "/" . $fileObj);
								}//is image the icon
							}
						}//while
					
					}else{
					
						$bolPassed = false;
						$strMessage .= "<br>There was a problem opening the image directory for this app?";
					
					}//handle
						
				}else{		
					$bolPassed = false;
					$strMessage .= "<br>This app does not have an image directory?";
				}//isDir
			
			}else{
				$bolPassed = false;
				$strMessage .= "<br>There was a problem saving your uploaded image?";
			}
				
			//still good?
			if($bolPassed){
				
				//copy the newly selected image to the directory we just emptied.
				if(copy($moveFromPath, $moveToPath)){
					
					//because we moved it, set it's owner
					if(chmod($moveToPath, 0755)){
					
						//update the app data
						$objApp->infoArray['iconUrl'] = $imgUrl;
						$objApp->infoArray['iconName'] = $imgName;
						$objApp->infoArray['modifiedUTC'] = $dtNow;
						$objApp->fnUpdate();
					
						//flag as done
						$bolDone = true;
						
					}else{
						
						$bolPassed = false;
						$strMessage .= "<br>There was a problem configuring the uploaded image?";
					
					}
					
				}else{
				
					$bolPassed = false;
					$strMessage .= "<br>There was a problem copying the new image?";
				
				}
			
			}//bolPassed
		
			
		}else{
			$bolPassed = false;
			$strMessage .= "<br>There was a problem saving the resized image?";
		}//objApp

	} //UPLOAD_SMALL


	//load vals
	$appName = $objApp->infoArray['name'];
	$iconUrl = $objApp->infoArray['iconUrl'];
	
	//get file name from URL, see if it exists.
	if($iconUrl != ""){
		$filename = basename($iconUrl);	
		$appFilePath = $objApp->fnGetAppDataDirectory($appGuid);
		if(!is_file(rtrim($appFilePath, "/") . "/images/" . $filename)){
			$iconUrl = "../../images/default_app_icon.png";
		}								
	}else{
		$iconUrl = "../../images/default_app_icon.png";
	}		
	
	//icon URL may need to be secure...
	$iconUrl = fnGetSecureURL($iconUrl);

	
	//if we have a large image location, set image_preview url to this
	$image_preview_url = $iconUrl;
	if($bolDone){
		 $image_preview_url = $imgUrl;
	}else{
		if(is_file($large_image_location) && $strMessage == ""){
			$image_preview_url = $large_imageURL;
		}		
	}
	
	//preview image may need to be secure...
	$image_preview_url = fnGetSecureURL($image_preview_url);
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>


<script type="text/javascript">
	
	var lastImage = "";
	function fnLoadImage(imageUrl){
		if(document.images["screenImg"].src != imageUrl){
			document.images["screenImg"].src = imageUrl;
		}
		lastImage = imageUrl;
	}
	
	// img onLoad
	function imgLoaded(){
		//set image after background image loads
		window.setTimeout("fnDelayImage()", 100);
	}
	
	//delayed image display
	function fnDelayImage(){
		fnSetImage('<?php echo $image_preview_url;?>');

		//document.getElementById("iconCanvas").style.visibility = "visible";
		//document.getElementById("previewImage").style.visibility = "visible";
		//document.getElementById("iconOverlay").style.visibility = "visible";
	}
	
	//load image
	function fnLoadIcon(theUrl){
		document.images["image_preview"].src = theUrl;
	}
	
	//sets app icon then rememberd it
	function fnSetImage(theUrl){
		document.images["image_preview"].src = theUrl;
		document.forms[0].iconUrl.value = theUrl;
	}
	
	//onLoad
	function init(){
		fnLoadImage('../../images/screen_icon.png');
	}
	
	//depends on action
	function fnUpload(theCommand){
		document.forms[0].command.value = theCommand;
		document.getElementById("loading").style.visibility = "visible";
		document.forms[0].submit();
	}
</script>

<style type="text/css">
	.iphoneBg{
		position:relative;
		z-index:10;
		float:right;
		height:737px;
		width:389px;
		overflow:hidden;
		background:url('../../images/iphone_icon_builder.png');
		background-repeat:no-repeat;
	}
	.screenBg{
		z-index:10;
		height:480px;
		width:320px;
		position:relative;
		left:36px;
		top:128px;
	}
	
	.screenImg{
		width:320px;
		height:480px;
		visibility:visible;
	}
	.image_preview{
		z-index:2;
		width:100%;
		height:100%;
		overflow:hidden;	
	}

	.iconOverlay_icon{
		height:72px;
		width:72px;
		position:relative;
		left:0px;
		top:-72px;
		z-index:22;
		padding:0px;
		background:url('../../images/icon_overlay_icon.png');
		background-position:50% 50%;
		
	}

</style>

<?php //this javacript only displays after an upload
if(is_file($large_image_location)){
	$current_large_image_width = getWidth($large_image_location);
	$current_large_image_height = getHeight($large_image_location);?>
	<script type="text/javascript">
		function preview(img, selection){ 
			var scaleX = <?php echo $small_width;?> / selection.width; 
			var scaleY = <?php echo $small_height;?> / selection.height; 
			
			$("#image_preview").css({ 
				width: Math.round(scaleX * <?php echo $current_large_image_width;?>) + 'px', 
				height: Math.round(scaleY * <?php echo $current_large_image_height;?>) + 'px',
				marginLeft: '-' + Math.round(scaleX * selection.x1) + 'px', 
				marginTop: '-' + Math.round(scaleY * selection.y1) + 'px' 
			});
			
			$('#x1').val(selection.x1);
			$('#y1').val(selection.y1);
			$('#x2').val(selection.x2);
			$('#y2').val(selection.y2);
			$('#w').val(selection.width);
			$('#h').val(selection.height);
		} 
    
		$(document).ready(function () { 
			$("#save_thumb").click(function() {
				var x1 = $('#x1').val();
				var y1 = $('#y1').val();
				var x2 = $('#x2').val();
				var y2 = $('#y2').val();
				var w = $('#w').val();
				var h = $('#h').val();
				if(x1 == "" || y1 == "" || x2 == "" || y2 == "" || w == "" || h == ""){
					alert("Please select what part of the image to crop by dragging the square.");
					return false;
				}else{
					//submit form
					fnUpload('upload_small');
				}
			});
		}); 
    
		$(window).load(function () { 
			$("#fullSize").imgAreaSelect({x1: 0, y1: 0, x2: <?php echo $small_width;?>, y2: <?php echo $small_height;?>, handles:'corners', minWidth:<?php echo $small_width;?>, minHeight:<?php echo $small_height;?>, aspectRatio: '1:<?php echo $small_height / $small_width;?>', onSelectChange:preview });

		});
    
    </script>
<?php }?>


<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="command" id="command" value="<?php echo $command;?>">
<input type="hidden" name="iconUrl" id="iconUrl" value="<?php echo $iconUrl;?>"/>
<input type="hidden" name="imgGuid" id="imgGuid" value="<?php echo $imgGuid;?>">
<input type="hidden" name="large_image_location" id="large_image_location" value="<?php echo $large_image_location;?>" />
<input type="hidden" name="small_image_location" id="small_image_location" value="<?php echo $small_image_location;?>" />
<input type="hidden" name="large_imageURL" id="large_imageURL" value="<?php echo $large_imageURL;?>" />
<input type="hidden" name="small_imageURL" id="small_imageURL" value="<?php echo $small_imageURL;?>" />
   
   
<!--filled oncrop event -->
<input type="hidden" name="x1" value="" id="x1" />
<input type="hidden" name="y1" value="" id="y1" />
<input type="hidden" name="x2" value="" id="x2" />
<input type="hidden" name="y2" value="" id="y2" />
<input type="hidden" name="w" value="" id="w" />
<input type="hidden" name="h" value="" id="h" />


<div class='content'>
        
    <fieldset class='colorLightBg'>
        
        <!-- app control panel navigation--> 
        <div class='cpNav'>
        	<span style='white-space:nowrap;'><a href="<?php echo fnGetSecureURL(APP_URL) . "/bt_v15/bt_app/?appGuid=" . $appGuid;?>" title="Application Control Panel">Application Home</a></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
			<?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "inline", ""); ?>
        </div>
            
       	<div class='contentBox colorLightBg'>
        
            <div class="contentBand colorBandBg">
                Manage Icon for <?php echo fnFormOutput($appName, true);?>
            </div>

                <table cellspacing='0' cellpadding='0' style='margin-top:15px;'>
                <tr>
                    <td style='padding-left:10px;padding-right:10px;'>
                        
                        <?php if(is_file($large_image_location) && $bolIsUploaded && !$bolDone){ ?>
                            
                            <!-- after uploading the full-size image-->
                            Use the crop-tool to select the portion of the image you want to keep.
                            You can resize the crop-tool by dragging the tiny arrows diagonally upwards or downwards. 
                            If the image you uploaded is exactly 72 x 72, you may not see the handles...in this case simply click the image
                            to select it.
                            Click Save when done.<br/><br/>
                       
                            <div style='padding-top:5px;'>
                                <input type="button" name="save_thumb" id="save_thumb" value="save" class="buttonSubmit" />
                                <input type="button" value="cancel" class="buttonCancel" onclick="document.location.href='index.php?appGuid=<?php echo $appGuid;?>';return false;" />
                                <span id="loading" style='padding-left:10px;vertical-align:middle;color:red;visibility:hidden;'>...saving, please wait</span>
                            </div>
                        
                            <div style='padding-top:5px;'>
                                <img src="<?php echo $large_imageURL;?>" style="border:1px solid gray;" alt="preview" id="fullSize" />
                            </div>
                        
                        <?php } else { ?>
                            
                                <?php if(!$bolDone)	{ ?>
                                    <div>
                                        Choose a .JPG or .PNG image to upload.
                                        After uploading the image, you'll be asked to "crop" the image to 72 x 72 pixels.
                                        If you upload an image that is
                                        exactly 72 x 72 (because you created it in an image editor first), click it to "select it" 
                                        before clicking the "Save" button.
                                    </div>
                                <?php } ?>
                    
                                    <div style='padding:10px;'>
                                        
                                        <?php if($bolDone && is_file($small_image_location)){ ?>
                                            <div class="doneDiv">
                                                <b>Icon Updated Successfully</b>. Upload another image (overwrite the one you 
                                                just uploaded) or click done.
                                            </div>                    
                                        <?php } ?>
                                        
                                        <?php if(!$bolDone && $strMessage != ""){ ?>
                                            <div class="errorDiv">
                                                <?php echo $strMessage;?>
                                            </div>                    
                                        <?php } ?>
    
    
                                        <div style='padding-top:10px;white-space:nowrap;'>
                                            <input type="file" name="image" size="30" />
                                        </div>
                        
                                        <div style='padding-top:10px;white-space:nowrap;'>
                                            <input type="button" name="upload" value="upload" class="buttonSubmit" onclick="fnUpload('upload_large');return false;"/>
                                            <?php if(!$bolDone){ ?>
                                                <input type="button" value="cancel" class="buttonCancel" onclick="document.location.href='index.php?appGuid=<?php echo $appGuid;?>';return false;" />
                                            <?php }else{ ?>
                                                <input type="button" value="done" class="buttonSubmit" onclick="document.location.href='index.php?appGuid=<?php echo $appGuid;?>';return false;" />
                                            <?php } ?>
                                        </div>
                                        
                                        
                                        <div id="loading" style="white-space:nowrap;color:red;visibility:hidden;padding-top:10px;">
                                            uploading....please don't click anything...
                                        </div>
                                        
                                     </div>
    
                            <?php }  ?>
                    </td>
                    <td style="text-align:right;">
                
                		<?php
							//if we don't have an image don't show the overlay...
							$overlayCSS = "visibility:hidden;";
							if($iconUrl != fnGetSecureURL(APP_URL) . "/images/icon_blank.png"){
								$overlayCSS = "visibility:visible;";
							}
						?>
                
                        <div class="iphoneBg">
                            
                            <div class="screenBg">
                                <img id="screenImg" name="screenImg" class="screenImg" onload="imgLoaded();" src="<?php echo fnGetSecureURL(APP_URL);?>/images/blank.gif" alt="sample screen"/>
                            </div>
                            
                            <div class='iconOuter' style='position:relative;left:165px;top:-110px;'>
                                <div id="iconImage" class="iconImage">
                                    <img  id="image_preview" src="<?php echo $iconUrl;?>" alt="app icon" class="rounded" />
                                </div>
                                <div id="iconOverlay_icon" class="iconOverlay_icon" style="display:block;<?php echo $overlayCSS;?>" >
                                    &nbsp;
                                </div>
                                                  
                            </div>
                        
                        
                        </div>
                                               
                    </td>
                  </tr>
              </table>
                
    	</div>
    
    
    </fieldset>


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
