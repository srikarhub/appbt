<?php   require_once("../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Admin Control Panel | Link Details";

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//########################################################################
	//link var...
	$linkGuid = fnGetReqVal("linkGuid","", $myRequestVars);
	$linkType = fnGetReqVal("linkType","", $myRequestVars);
	$linkLabel = fnGetReqVal("linkLabel","", $myRequestVars);
	$linkURL = fnGetReqVal("linkURL", "", $myRequestVars);
	$linkTarget = fnGetReqVal("linkTarget","", $myRequestVars);
	$orderIndex = fnGetReqVal("orderIndex","", $myRequestVars);
	$isEditable = fnGetReqVal("isEditable","", $myRequestVars);
	
	//if we have NO linkGuid and NO linkType, bail...
	if($linkGuid == ""){
		if($linkType == ""){
			echo "invalid request";
			exit();
		}
	}
	
	
	//########################################################################
	//form submit
	if($isFormPost){
		
		if(strlen($linkLabel) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Link Label Required";
		}
		if(strlen($linkURL) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Link URL Required";
		}
		if(strlen($linkTarget) < 1){
			$bolPassed = false;
			$strMessage .= "<br />Link Target Required";
		}
		
		//if passed
		if($bolPassed){

			//misc vars.
			$dtNow = fnMySqlNow();
			$newLinkGuid = strtoupper(fnCreateGuid());


			//insert or update?
			if($linkGuid == ""){
			
				$objLink = new Link("");
				$objLink->infoArray['guid'] = $newLinkGuid;
				$objLink->infoArray['linkType'] = $linkType;
				$objLink->infoArray['linkLabel'] = $linkLabel;
				$objLink->infoArray['linkURL'] = $linkURL;
				$objLink->infoArray['linkTarget'] = $linkTarget;
				$objLink->infoArray['orderIndex'] = "99";
				$objLink->infoArray['isEditable'] = "1";
				$objLink->infoArray['modifiedUTC'] = $dtNow;
				$objLink->infoArray['modifiedByGuid'] = $guid;
				$objLink->fnInsert();
				
				//remember new linkGuid...
				$linkGuid = $newLinkGuid;
				
			}else{
			
				//$objLink->fnUpdate();
				$objLink = new Link($linkGuid);
				$objLink->infoArray['linkType'] = $linkType;
				$objLink->infoArray['linkLabel'] = $linkLabel;
				$objLink->infoArray['linkURL'] = $linkURL;
				$objLink->infoArray['linkTarget'] = $linkTarget;
				$objLink->infoArray['modifiedUTC'] = $dtNow;
				$objLink->infoArray['modifiedByGuid'] = $guid;
				$objLink->fnUpdate();
			
			}
			
			//flag as done
			$bolDone = true;
			
		}//bolPassed
		
		
	}else{	
		
		//if we clicked a link...
		if($linkGuid != ""){
			$objLink = new Link($linkGuid);
			$linkType = $objLink->infoArray['linkType'];
			$linkLabel = $objLink->infoArray['linkLabel'];
			$linkURL = $objLink->infoArray['linkURL'];
			$linkTarget = $objLink->infoArray['linkTarget'];
			$isEditable = $objLink->infoArray['isEditable'];
		}else{
			$isEditable = "1"; //new links are editable...
		}
		
	}//form submit

	
	//title...
	$tmpTitle = "Create a New Link in <i>" . fnFormatProperCase($linkType) . " Control Panels</i>";
	if($linkGuid != "") $tmpTitle = "Link Details for an <i>" . fnFormatProperCase($linkType) . " Control Panel Link</i>";


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="linkGuid" id="linkGuid" value="<?php echo $linkGuid;?>"/>
<input type="hidden" name="linkType" id="linkType" value="<?php echo $linkType;?>"/>
<input type="hidden" name="isEditable" id="isEditable" value="<?php echo $isEditable;?>"/>



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
                   <?php echo $tmpTitle;?>
                </div>

                <div style='padding:10px;'>
                    
                    <?php if($bolDone ){ ?>
                        <div class='doneDiv'>
                            <b>Link Updated Successfully</b>
                            
                            <div style='padding-top:10px;'>
                                <a href="<?php echo fnGetSecureURL(APP_URL);?>/admin/admin_links.php"><img src="../images/arr_right.gif" alt="arrow"/>Back to Control Panel Links</a>
                            </div>

                        </div>
                    <?php }?>
                            
                    <?php if(!$bolDone) { ?>      
    
                        <?php if($strMessage != ""){ ?>
                            <div class='errorDiv'>
                                <?php echo $strMessage;?>
                            </div>
                        <?php } ?>
                        
                        <div style='padding:10px;float:left;margin-right:20px;'>                      
                                        
                            <label>Link Label</label>
                            <input type="text" value="<?php echo fnFormOutput($linkLabel);?>"  name="linkLabel" id="linkLabel" maxlength='75' style="width:300px;"/>


                            <?php if($isEditable == 1 || $isEditable == "1"){ ?>
                            	<label>Link URL</label>
                            	<input type="text" value="<?php echo fnFormOutput($linkURL);?>" name="linkURL" id="linkURL" maxlength='250' style="width:300px;"/>
                            <?php }else{ ?>
                            	<input type="hidden" value="<?php echo fnFormOutput($linkURL);?>" name="linkURL" id="linkURL"/>
                            <?php } ?>
                            
                            <?php if($isEditable == 1 || $isEditable == "1"){ ?>
                                <label>Opens In</label>
                                <select name="linkTarget" id="linkTarget" style='width:300px;'>
                                    <option value="_self" <?php echo fnGetSelectedString($linkTarget, "_self")?>>Same Window</option>
                                    <option value="_blank" <?php echo fnGetSelectedString($linkTarget, "_blank")?>>New Window</option>
                                </select>
                            <?php }else{ ?>
                            	<input type="hidden" value="<?php echo fnFormOutput($linkTarget);?>" name="linkTarget" id="linkTarget"/>
                            <?php } ?>
                            
                            <div style='padding-top:10px;'>
                                <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                                <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='<?php echo fnGetSecureURL(APP_URL);?>/admin/admin_links.php';">
                            </div>                                    
                            
                            
                            
                        </div>
                                    
                    <?php } ?>
                        
                
                             
                </div>
                    
                    
                    
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
