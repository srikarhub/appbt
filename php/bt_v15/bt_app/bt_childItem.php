<?php   require_once("../../config.php");



	//who's logged in
	$loggedInUserGuid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $loggedInUserGuid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//User Object
	$thisUser = new User($loggedInUserGuid);
	$thisUser -> fnLoggedInReq($loggedInUserGuid);
	$thisUser -> fnUpdateLastRequest($loggedInUserGuid);

	//init page object
	$thisPage = new Page();
	
	//javascript files in <head>...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/jquery.1.9.1.min.js, bt_v15/bt_scripts/app_screen.js";
	
	//add some inline css (in the <head>) for 100% width...
	$inlineCSS = "";
	$inlineCSS .= "html{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= "body{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= ".contentWrapper, .contentWrap{height:100%;width:100%;margin:0px;padding:0px;} ";
	$thisPage->cssInHead = $inlineCSS;
	
	$strErrorMessage = "";
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appName = fnGetReqVal("appName", "", $myRequestVars);
	$BT_itemId = fnGetReqVal("BT_itemId", "", $myRequestVars);
	$screenNickname = fnGetReqVal("screenNickname", "", $myRequestVars);

	//app object...
	if($appGuid == ""){
	
		echo "invalid request (1)";
		exit();

	}
	
	//screen object, what type of plugin is this...
	if($BT_itemId == ""){
		echo "invalid request (2)";
		exit();
	}

	//app object, make sure user can manage this app...
	$objApp = new App($appGuid);
	$appName = $objApp->infoArray["name"];

	//make sure user can manage this app...
	$objApp->fnCanManageApp($thisUser->infoArray["guid"], $thisUser->infoArray["userType"], $appGuid, $objApp->infoArray["ownerGuid"]);
	
	//data object, using this child item's guid...
	$objBT_item = new Bt_item($BT_itemId);
	$itemType = $objBT_item->infoArray["itemType"];
	$parentItemGuid = $objBT_item->infoArray["parentItemGuid"];
	$jsonVars = $objBT_item->infoArray["jsonVars"];

	//screen object, using this child item'sparentItemGuid...
	$objBT_itemParent = new Bt_item($parentItemGuid);
	$uniquePluginId = $objBT_itemParent->infoArray["uniquePluginId"];

	//plugin object using the parent screen's uniquePluginId...
	$objPlugin = new Plugin("", $uniquePluginId);
	$webDirectoryName = $objPlugin->infoArray["webDirectoryName"];

	//create JSON vars manually so we are sure they always contains itemId, itemType and itemNickname...
	$tmpJson = "{";
		
		//loop through all the other properties (ignore first three)...
		$json = new Json; 
		$decoded = $json->unserialize($jsonVars);
		if(is_object($decoded)){

			//itemId comes from the database, not the JSON...
			$tmpJson .= "\"itemId\":\"" . $BT_itemId . "\", ";
			
			//must have item type...
			if($itemType == ""){
				if(is_object($decoded->itemType)){
					$itemType = $decoded->itemType;
				}
			}
			
	
			//add the itemType from the JSON.....
			$tmpJson .= "\"itemType\":\"" . $itemType . "\"";
			
			//append the nickname if this is NOT a childItem...
			if(strlen($parentItemGuid) < 1){
			
				//nickaname from the database, not the JSON...
				$tmpJson .= ", \"itemNickname\":\"" . $screenNickname . "\"";
			
			}
			
			//add the rest of the properties...
			foreach($decoded as $key => $val){
				
				//ignore these keys...
				if(strtoupper($key) != "ITEMID" && strtoupper($key) != "ITEMTYPE" && strtoupper($key) != "ITEMNICKNAME"){
					
					//if the value is NOT an array...(ignore child items)...
					if(!is_array($val)){
						$tmpJson .= ",\"" . $key . "\":\"" . $val . "\" ";
					}
				}
				
			}//for each...
			$tmpJson = rtrim(trim($tmpJson), ",");	
			$tmpJson .= "}";

			
		}else{
		
			//the previously saved json has some errors...use the minimum required..
			$tmpJson = "{";
				$tmpJson .= "\"itemId\":\"" . $BT_itemId . "\", ";
				$tmpJson .= "\"itemType\":\"" . $itemType . "\"";
				
				//append the nickname if this is NOT a childItem...
				if(strlen($parentItemGuid) < 1){
				
					//nickaname from the database, not the JSON...
					$tmpJson .= ", \"itemNickname\":\"" . $screenNickname . "\" ";
				
				}
				
			$tmpJson .= "}";
			
			//show error...
			$strErrorMessage .= "<br/>There was a problem reading the saved JSON data for this screen.";
			
		}
		
	//re-assign value of JSON vars to manually created version...
	$jsonVars = $tmpJson;
	
	//web page title is appName + nickname...
	$thisPage -> pageTitle = $appName . ": " . $screenNickname;


	//path to this plugins directory....
	$pluginDirPath = rtrim(APP_PHYSICAL_PATH, "/") . "/" . rtrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($webDirectoryName, "/");
	
	//URL to control panel for this screen's dataURL (some plugins use this, some do not)...
	$screenDataURL = rtrim(APP_URL, "/") . "/api/app/?command=getChildItems&appGuid=" . $objApp->infoArray['guid'] . "&screenId=" . $BT_itemId . "&apiKey=" . $objApp->infoArray['apiKey'] . "&apiSecret=" . $objApp->infoArray['appSecret'];

	//childItem screen only contain one section and it must be named childItem.html and by in the plugin package...
	$cpSectionsList = $pluginDirPath . "/childItem.html";


	/*
		The json vars for this screen may contain one or more apostrophes ('). 
		This will break the javascript. Replace all occurances of each apostrophe 
		character with a pipe (|) character. The javascript routine that pre-populates the
		form elements will replace the pipe character with the apostrophe.		
	*/
	$jsonVars = str_replace("'", "|", $jsonVars);
	$jsonVars = fnStripWhiteSpace($jsonVars);
	$jsonVars = fnNoLineBreaks($jsonVars);
	
	///////////////////////////////////////////////////////////////////////////////
	//Javascript in <head> section...
	$onLoadScript = "";
	$onLoadScript .= "\n$(document).ready(function(){";
		$onLoadScript .= "\nfnSetJavascriptVariables('" . $thisUser->infoArray["guid"] . "', '" . $appGuid . "', '" . $BT_itemId . "', '" . $screenDataURL . "', '" . $cpSectionsList . "', '" . $jsonVars . "');";
	$onLoadScript .= "\n});";
	
	$thisPage->jsInHead = $onLoadScript;
	///////////////////////////////////////////////////////////////////////////////

	//DEPRECATED. qVars is no longer used but it's still referenced in inc_screenBreadcrumbs.php...
	$qVars = "";
	
	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();

	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_itemId" id="BT_itemId" value="<?php echo $BT_itemId;?>">
<input type="hidden" name="childItemStartIndex" id="childItemStartIndex" value="">
<input type="hidden" name="command" id="command" value="">

<div class='content'>
        
           
    	<div class='colorLightBg minHeightShadowbox'>
               
            <div class="contentBand colorBandBg" style='-moz-border-radius:0px;border-radius:0px;'>
            	Item Properties
            </div>
               
                <!--error box showing error if saved json on server is invalid -->
                <?php 
					if(strlen($strErrorMessage) > 1){
                		echo "<div id='errorBoxServer' class='errorDiv' style='margin-left:10px;margin-right:10px;'>" . $strErrorMessage . "</div>";
                	}
				?>
                
                <!--error box showing javasript errrors -->
                <div id="errorBox" class="errorDiv" style="display:none;margin-left:10px;margin-right:10px;"></div>

                <!--sections for each JSON property are filled by AJAX calls to includes -->
                <div id="sectionBox"></div>
                
				<?php
					
					//include each section with readFile() NOT include. We must prevent .php code execution in include files..
					$cpSections = explode(",", $cpSectionsList);
					for($s = 0; $s < count($cpSections); $s++){
                    	if(is_file(trim($cpSections[$s]))){
                        	echo file_get_contents(trim($cpSections[$s]));
                    	}else{
							echo "<div class='errorDiv' style='margin-left:10px;margin-right:10px;'>";
								echo "<br><b>Missing File: </b>\"" . trim($cpSections[$s]) . "\"";
								echo "<div style='padding-top:5px;'>";
									echo "File not found?";
								echo "</div>";
							echo "</div>";
						}
					}
				
				?>

            <div style='clear:both;'></div>
        	
        </div>
        
        


</div>

<?php 
	//print the closing body and html tags using the Page Class..
	echo $thisPage->fnGetBodyEnd(); 
?>


