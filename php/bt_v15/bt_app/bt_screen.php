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
	
	//plugins table constant is different on self hosted servers..
	if(!defined("TBL_PLUGINS")){
		if(defined("TBL_BT_PLUGINS")){
			define("TBL_PLUGINS", TBL_BT_PLUGINS);
		}
	}


	//////////////////////////////////////////////////////////////////////////////
	/*
		The includePath and controlPanelURL variables in this script is used in the HTML on this page to
		account for differences between Self Hosted control panels and buzztouch.com control panels.
	*/
	$includePath = "";
	$controlPanelURL = "";
	if(defined("APP_CURRENT_VERSION")){
		$includePath = rtrim(APP_PHYSICAL_PATH, "/") . "/bt_v15/bt_includes";
		$controlPanelURL = "../../../bt_v15/bt_app";
	}else{
		$includePath = rtrim(APP_PHYSICAL_PATH, "/") . "/app/cp_v20/bt_includes";
		$controlPanelURL = "../../../app/cp_v20/bt_app";
	}
	//////////////////////////////////////////////////////////////////////////////

	$strErrorMessage = "";
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	$appName = fnGetReqVal("appName", "", $myRequestVars);
	$BT_itemId = fnGetReqVal("BT_itemId", "", $myRequestVars);
	$parentItemGuid = fnGetReqVal("parentItemGuid", "", $myRequestVars);
	$screenNickname = fnGetReqVal("screenNickname", "", $myRequestVars);
	$uniquePluginId = fnGetReqVal("uniquePluginId", "", $myRequestVars);
	$webDirectoryName = fnGetReqVal("webDirectoryName", "", $myRequestVars);

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
	
	//screen object, using this screen's guid...
	$objBT_item = new Bt_item($BT_itemId);
	$uniquePluginId = $objBT_item->infoArray["uniquePluginId"];
	$screenNickname = $objBT_item->infoArray["nickname"];
	$jsonVars = $objBT_item->infoArray["jsonVars"];

	//plugin object using this screen's uniquePluginId...
	$objPlugin = new Plugin("", $uniquePluginId);
	$itemType = $objPlugin->infoArray["loadClassOrActionName"];
	$hasChildItems = $objPlugin->infoArray["hasChildItems"];
	$webDirectoryName = $objPlugin->infoArray["webDirectoryName"];
	$shortDescription = $objPlugin->infoArray["shortDescription"];
	
	//create JSON vars manually so we are sure they always contains itemId, itemType and itemNickname...
	$tmpJson = "{";
		$tmpJson .= "\"itemId\":\"" . $BT_itemId . "\", ";
		$tmpJson .= "\"itemType\":\"" . $itemType . "\", ";
		$tmpJson .= "\"itemNickname\":\"" . $screenNickname . "\"";

		//loop through all the other properties (ignore first three)...
		$json = new Json; 
		$decoded = $json->unserialize($jsonVars);
		if(is_object($decoded)){

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
				$tmpJson .= "\"itemType\":\"" . $itemType . "\", ";
				$tmpJson .= "\"itemNickname\":\"" . $screenNickname . "\" ";
			$tmpJson .= "}";
			
			//show error...
			$strErrorMessage .= "<br/>There was a problem reading the saved JSON data for this screen.";
			
		}
		
	//re-assign value of JSON vars to manually created version...
	$jsonVars = $tmpJson;
	
	//web page title is appName + nickname...
	$thisPage -> pageTitle = $appName . ": " . $screenNickname;


	///////////////////////////////////////////////////////////////////////////////
	/*
		control panel sections...most plugins share some common properties. Plugin devs
		can include (or remove) properties as needed. The sections that are included
		in this page are configured in this plugins config_cp.txt file in the plugin
		package. The standard plugin sections are included if this file does not exist. 
	*/
	
	//comma separted list holds .html files to include...ajax calls insert the sections, nickname always first...
	$cpSectionsList = "../bt_includes/btSection_nickname.html, ";
	
	//path to this plugins directory....
	$pluginDirPath = rtrim(APP_PHYSICAL_PATH, "/") . "/" . rtrim(APP_DATA_DIRECTORY, "/") . "/plugins/" . ltrim($webDirectoryName, "/");

	//holds the contents of config_cp.txt if it exists in the plugin package...
	$cpConfigData = "";
	
	//look for the cpConfig.txt file in the plugin paackage...
	if(file_exists(rtrim($pluginDirPath, "/") . "/config_cp.txt")){
		
		//get the JSON config data from the config_cp.txt file...
		$configJSON = file_get_contents(rtrim($pluginDirPath, "/") . "/config_cp.txt");
		$configJSON = fnStripWhiteSpace($configJSON);
		$configJSON = fnNoLineBreaks($configJSON);
		
		//parse the "propertySections" array in this file...
		$json = new Json; 
		$decoded = $json->unserialize($configJSON);
		if(is_object($decoded)){
			
			//must have key we're looking for...
			if(array_key_exists("propertySections", $decoded)){
			
				$sections = $decoded->propertySections;
				foreach($sections as $section => $options){
					
					/*
						Section Examples: 
						{"fileType":"bt_section", "fileName":"btSection_navBar.html"},
						{"fileType":"customInclude", "fileName":"customSectionTwo.html"},
					
						fileType options: "bt_section" or "custom"
						fileName is a filName only. NOT a file path. If the fileType is "bt_section" then
						the file will come from bt_includes. Else, it will come from the plugin package.
						
						fileName cannot contain any slashes or path information, it cannot be in a sub-directory.
					*/
					
					$section = "";
					if(array_key_exists("fileType", $options) && array_key_exists("fileName", $options)){
						if(strtoupper($options->fileType) == "CUSTOMINCLUDE" || strtoupper($options->fileType) == "CUSTOMSECTION" || strtoupper($options->fileType) == "CUSTOM" || strtoupper($options->fileType) == "SECTION"){
							$section = rtrim($pluginDirPath, "/") . "/" . str_replace("/", "", $options->fileName);
						}else{
						
							//do not include the nickname box again (developers may not know it's automatically included)...
							if(strtoupper($options->fileName) != "BTSECTION_NICKNAME.HTML"){
								$section = "../bt_includes/" . str_replace("/", "", $options->fileName);
							}
							
						}
						
						//add to comma separted list of $cpSectionsList...
						if($section != ""){
							$cpSectionsList .= $section . ",";
						}
						
					}
					
				}
				
			}else{
				$strErrorMessage .= "<br/>The config_cp.txt file for this plugin is invalid (1).";
			}
			
		}else{
			$strErrorMessage .= "<br/>The config_cp.txt file for this plugin is invalid (2).";
		}//last_error...		
		
	//no custom config_cp.txt file found for this plugin...
	}else{ 
	
		//add additional standard sections... 
		$cpSectionsList .= "../bt_includes/btSection_navBar.html, ";
		
		//if this plugin uses child items....
		if($hasChildItems == "1"){
			$cpSectionsList .= "../bt_includes/btSection_dataURL.html, ";
		}
		
		$cpSectionsList .= "../bt_includes/btSection_login.html, ";
		$cpSectionsList .= "../bt_includes/btSection_backgroundColor.html, ";
		$cpSectionsList .= "../bt_includes/btSection_backgroundImage.html, ";
		$cpSectionsList .= "../bt_includes/btSection_search.html, ";
		$cpSectionsList .= "../bt_includes/btSection_tabBar.html, ";
		$cpSectionsList .= "../bt_includes/btSection_screenJson.html, "; 

	}//config_cp.txt exists...
	
	//remove last command from cpSectionsList...
	$cpSectionsList = rtrim(trim($cpSectionsList), ",");

	//URL to control panel for this screen's dataURL (some plugins use this, some do not)...
	$screenDataURL = rtrim(APP_URL, "/") . "/api/app/?command=getChildItems&appGuid=" . $objApp->infoArray['guid'] . "&screenId=" . $BT_itemId . "&apiKey=" . $objApp->infoArray['apiKey'] . "&apiSecret=" . $objApp->infoArray['appSecret'];

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
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);

	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="BT_itemId" id="BT_itemId" value="<?php echo $BT_itemId;?>">
<input type="hidden" name="childItemId" id="childItemId" value="">
<input type="hidden" name="childItemAddJson" id="childItemAddJson" value="">
<input type="hidden" name="command" id="command" value="">

<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- app control panel navigation--> 
        <div class='pluginNav'>
            <span style='white-space:nowrap;'><a href="<?php echo $controlPanelURL . "/?appGuid=" . $appGuid;?>" title="Application Control Panel">Application Home</a></span>
            &nbsp;&nbsp;|&nbsp;&nbsp;
			<?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "inline", ""); ?>
        </div>

       	<div class='pluginContent colorDarkBg minHeight'>
               
            <!-- breadcrumbs back to screens and actions or this screens parent screen -->
            <div class='contentBand colorBandBg' style='font-size:10pt;'>
            	<?php 
					
					//link back to screens and actions...
					echo "<a href='bt_screens.php?appGuid=" . $appGuid . "' title='Screens / Actions'>Screens / Actions</a>";
					
					//show link to parent screen if needed...
					if($parentItemGuid != ""){
						
						$tmpSql = " SELECT nickname FROM " . TBL_BT_ITEMS . " WHERE guid = '" . $parentItemGuid . "' ";
						$parentScreenNickname = fnGetOneValue($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		
						//link to parent screen...
						echo "\n&nbsp;&nbsp;>&nbsp;&nbsp;";
    					echo "<a href=\"bt_screen.php?appGuid=" . $appGuid . "&BT_itemId=" . $parentItemGuid . "\" title=\"" . fnFormOutput($parentScreenNickname) . "\">" . fnFormOutput($parentScreenNickname) . "</a>";
					
					
					}
						
					//show this screens nickname...
					echo "\n&nbsp;&nbsp;>&nbsp;&nbsp;";
					echo fnFormOutput($objBT_item -> infoArray["nickname"]);
					
								
				?>
            </div>

            <!--plugin icon and details on right -->
            <div class="pluginRight colorLightBg">
                <?php include($includePath . "/inc_pluginDetails.php");?>                    
            </div>
                
            <!--box to hold HTML for this plugins properties page-->
            <div class="pluginLeft minHeight">
				
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

            </div>
            <div style='clear:both;'></div>
        	
        </div>
        
    </fieldset>
        


<?php 
	//print the bottom navigation bar using the Page Class.
	echo $thisPage->fnGetBottomNavBar();
?>

</div>

<?php 
	//print the closing body and html tags using the Page Class..
	echo $thisPage->fnGetBodyEnd(); 
?>


