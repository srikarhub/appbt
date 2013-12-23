<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);

	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	//app vars...
	$command = fnGetReqVal("command", "", $myRequestVars);
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);


	//plugins table constant is different on self hosted servers..
	if(!defined("TBL_PLUGINS")){
		if(defined("TBL_BT_PLUGINS")){
			define("TBL_PLUGINS", TBL_BT_PLUGINS);
		}
	}	

	//init page object
	$thisPage = new Page();
	
	//javascript files in <head>...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js, bt_v15/bt_scripts/app_package.js";	
	
	//javascript inline in <head>...
	$thisPage->jsInHead = "var whatPlatform = \"\"; var buildURL = \"bt_appPackage_AJAX.php?guid=" . $guid . "&appGuid=" . $appGuid . "\";";
	
	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();
	
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
	
	//init app object
	$appName = $objApp->infoArray["name"];
	
	/*
		Use the project name in the download URL
	*/
	
	$canDownloadMessage = "";
	$projectName = fnCleanProjectName($objApp->infoArray["projectName"]);
	$projectName = strtolower($projectName);
	$bolCanDownload = false;
	if($projectName != ""){
		$bolCanDownload = true;
	}else{
		$bolCanDownload = false;
		$canDownloadMessage .= "<br>There is no project name entered for this application. Project names are required.";
		$canDownloadMessage .= "You can enter a project name in the <a href='bt_core.php?appGuid=" . $appGuid . "'>Core Settings</a>.";
	}
	
	//iconUrl / iconName
	if($objApp->infoArray["iconUrl"] == "" || $objApp->infoArray["iconName"] == ""){
		$bolCanDownload = false;
		$canDownloadMessage .= "<br>This application does not have an icon. An icon is required.";
		$canDownloadMessage .= "You can <a href='bt_icon.php?appGuid=" . $appGuid . "'>upload an icon here</a>.";
	}
	
	//if we don't have a license key...
	if(!defined("APP_BT_SERVER_API_KEY")){
		$bolCanDownload = false;
		$canDownloadMessage .= "<br>The control panel does not have a buzztouch.com API Key entered. ";
		$canDownloadMessage .= "<a href='http://www.buzztouch.com/account' target='_blank'>Visit your account at buzztouch.com</a> to obtain an API key. ";
		$canDownloadMessage .= "After obtaining a key, enter it using the admin screen in this software.";
	}else{
		if(strlen(APP_BT_SERVER_API_KEY) < 5 || APP_BT_SERVER_API_KEY == "xxxxxxxxxx"){
			$bolCanDownload = false;
			$canDownloadMessage .= "<br>The control panel does not have a buzztouch.com API secret entered. ";
			$canDownloadMessage .= "<a href='http://www.buzztouch.com/account' target='_blank'>Visit your account at buzztouch.com</a> to obtain an API key. ";
			$canDownloadMessage .= "After obtaining a key, enter it using the admin screen in this software.";
		}
	}
	
	
	///////////////////////////////////////////////////////////////
	//init plugins arrays for all, installed, and each category...
	$installedPluginsLoadClassNames = array();
	$requiredPluginsLoadClassNames = array();
	
	$actionPlugins = array();
	$menuPlugins = array();
	$screenPlugins = array();
	$settingsPlugins = array();
	$splashPlugins = array();
	
	$strSql = " SELECT P.webDirectoryName, P.guid, P.displayAs, P.loadClassOrActionName, P.category, P.supportedDevices ";
	$strSql .= " FROM " . TBL_PLUGINS . " AS P ORDER BY P.displayAs ASC ";
	$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	if($res){
		while($row = mysql_fetch_array($res)){
		
			//list of all installed plugins...
			$installedPluginsLoadClassNames[] = $row["loadClassOrActionName"];
			
			//image for supported devices...
			$imgURL = "";
			$ios = "hidden";
			$android = "hidden";
			if(strtoupper($row["supportedDevices"]) == "IOS" || strtoupper($row["supportedDevices"]) == "IOSANDANDROID"){
				$ios = "visible";
			}
			if(strtoupper($row["supportedDevices"]) == "ANDROID" || strtoupper($row["supportedDevices"]) == "IOSANDANDROID"){
				$android = "visible";
			}
			$imgiOS = "<img src='../../images/badge-ios.png' alt='works on iOS' title='works on iOS' style='visibility:" . $ios . ";height:20px;width:20px;margin-top:-3px;vertical-align:middle;' />";
			$imgAndroid = "<img src='../../images/badge-android.png' alt='works on Android' title='works on Android' style='visibility:" . $android . ";height:20px;width:20px;margin-top:-3px;margin-left:-5px;vertical-align:middle;' />";
			$imgURL = $imgiOS . $imgAndroid;

			//JSON list of plugin guid, displayAs, loadClassOrActionName, webDirectoryName
			$JSON = "{";
				$JSON .= "\"guid\":\"" . $row["guid"] . "\", ";
				$JSON .= "\"loadClassOrActionName\":\"" . $row["loadClassOrActionName"] . "\", ";
				$JSON .= "\"displayAs\":\"" . fnFormOutput($row['displayAs']) . "\", ";
				$JSON .= "\"imgURL\":\"" . $imgURL . "\", ";
				$JSON .= "\"webDirectoryName\":\"" . str_replace("/", "", $row["webDirectoryName"]) . "\"";
			$JSON .= "}";
			
			switch (strtoupper($row["category"])){
				case "ACTION":
					$actionPlugins[] = $JSON;
					break;
				case "MENU":
					$menuPlugins[] = $JSON;
					break;
				case "SCREEN":
					$screenPlugins[] = $JSON;
					break;
				case "SETTINGS":
					$settingsPlugins[] = $JSON;
					break;
				case "SPLASH":
					$splashPlugins[] = $JSON;
					break;
			}//end switch
			
			
		}
	}
	///////////////////////////////////////////////////////////////
	
	
	///////////////////////////////////////////////////////////////
	//get list of plugins used in this app...
	$strSql = " SELECT loadClassOrActionName FROM " . TBL_BT_ITEMS;
	$strSql .= " WHERE appGuid = '" . $appGuid . "'";
	$strSql .= " AND controlPanelItemType = 'screen' ";
	$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
	if($res){
		while($row = mysql_fetch_array($res)){
			if(!in_array($row["loadClassOrActionName"], $requiredPluginsLoadClassNames)){
				$requiredPluginsLoadClassNames[] = $row["loadClassOrActionName"];
			}
		}
	}
	///////////////////////////////////////////////////////////////
	
	///////////////////////////////////////////////////////////////
	//get partners HTML...
	function fnGetPartners(){
		
		//count the troubles...
		$errors = array();
		$html = "";
	
		//key/value pairs to send in the request...
		$postVars = "";
		$fields = array(
		
			//needed by the buzztouch.com api to validate this request
			"apiKey" => urlencode(APP_BT_SERVER_API_KEY),
			"apiSecret" => urlencode(APP_BT_SERVER_API_KEY_SECRET),
			"command" => urlencode("getPartners"),
				
		);
		
		//prepare the data for the POST
		foreach($fields as $key => $value){ 
			$postVars .= $key . "=" . $value . "&"; 
		}
		
		//setup api url
		$apiURL = rtrim(APP_BT_SERVER_API_URL, "/");
		
		//init a cURL object, set number of POST vars and the POST data
		$ch = curl_init($apiURL . "/partners/");
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
		
		//get JSON result from buzztouch.com  API
		$jsonResult = curl_exec($ch);
		
		//close connection
		curl_close($ch);
		
		//decode json vars
		if($jsonResult != ""){
			$json = new Json; 
			$decoded = $json->unserialize($jsonResult);
			if(is_object($decoded)){

				//must have a result...
				if(array_key_exists("result", $decoded)){
					$results = $decoded -> result;
					
					//must have a status...
					if(array_key_exists("status", $results)){
						$status = $results -> status;
					}else{
						$bolPassed = false;
						$strMessage .= "<br>API result does not contain a required field: status missing.";
					}
					
				}else{
					$bolPassed = false;
					$strMessage .= "<br>API result does not contain a required field: result missing.";
				}

				//still no errors?
				if(count($errors) < 1){
				
					//success means we'll get a package URL from the API...
					if(strtoupper($status) == "VALID"){
						
						if(array_key_exists("partners", $results)){

							//loop...
							$partnerList = $results -> partners;
							for($x = 0; $x < count($partnerList); $x++){
							
								$sdkName = fnFormOutput($partnerList[$x] -> sdkName);
								$sdkImage = $partnerList[$x] -> sdkImage;
								$sdkURL = $partnerList[$x] -> sdkURL;
								$sdkDescription = fnFormOutput($partnerList[$x] -> sdkDescription);
								$iosSupport = $partnerList[$x] -> iosSupport;
								$androidSupport = $partnerList[$x] -> androidSupport;
								$iosInstructions = $partnerList[$x] -> iosInstructions;
								$androidInstructions = $partnerList[$x] -> androidInstructions;
								
								//ios support...
								$iosDisabled = "disabled";
								$iosNA = "n/a";
								if($iosSupport == "1"){
									$iosDisabled = "";
									$iosNA = "";
								}
								
								//android support...
								$androidDisabled = "disabled";
								$androidNA = "n/a";
								if($androidSupport = "1"){
									$androidDisabled = "";
									$androidNA = "";
								}
							
								//show the partner...
								$html .= "\n<div style='border-bottom:1px solid gray;border-right:1px solid gray;vertical-align:top;padding:10px;width:260px;height:150px;margin:5px;overflow:hidden;text-align:center;float:left;'>";
									$html .=  "\n<input type=\"checkbox\" " . $iosDisabled . " name=\"includeSDKPdfs[]\" id=\"includeSDKPdfs[]\" value=\"" . $iosInstructions . "\">";
									$html .=  "&nbsp;iOS " . $iosNA;
									$html .=  "&nbsp;";
									$html .=  "\n<input type=\"checkbox\" " . $androidDisabled . " name=\"includeSDKPdfs[]\" id=\"includeSDKPdfs[]\" value=\"" . $androidInstructions . "\">";
									$html .=  "\n&nbsp;Android " . $androidNA;
									$html .=  "\n<br/>";
									$html .=  "\n<a href=\"" . $sdkURL . "\" target=\"_blank\" title='" . $sdkName . "'><img src=\"" . $sdkImage . "\" alt=\"" . $sdkName . "\" style=\"margin-top:5px;\"/></a>";
									$html .=  "\n<br/>";
									$html .=  "\n<a href=\"" . $sdkURL . "\" target=\"_blank\" title='" . $sdkName . "'>" . fnFormOutput($sdkDescription) . "</a>";
								$html .=  "</div>";
							
							}//for...
						}//parnters key exists...
							
						
					}else{
				
						//show the errors...
						if(array_key_exists("errors", $results)){
							$errorList = $results -> errors;
							for($e = 0; $e < count($errorList); $e++){
								$html .= "<br/>" . $errorList[$e] -> message;
							}

						}
						
					}//valid
				
				}//errors...
				
				
			}else{
				$html .= "<div style='padding:10px;color:red;'>buzztouch.com API return invalid JSON while fetching partner list (1).</div>";
			}
		}else{
			$html .= "<div style='padding:10px;color:red;'>buzztouch.com API return invalid JSON while fetching partner list (2).</div>";
		} //jsonResult...
		
		//return...
		return $html;
	
	}
	///////////////////////////////////////////////////////////////


	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);

?>

<input type="hidden" name="posted" id="posted" value="1"/>
<input type="hidden" name="command" id="command" value=""/>
<input type="hidden" name="whatPlatform" id="whatPlatform" value=""/>
<input type="hidden" name="whatVersion" id="whatVersion" value=""/>
<input type="hidden" name="guid" id="guid" value="<?php echo $guid;?>"/>
<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>"/>

<!--load the jQuery files using the Google API -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>

<script>

//required plugins...
var menuPlugins = new Array();
var screenPlugins = new Array();
var splashPlugins = new Array();
var settingsPlugins = new Array();
var actionPlugins = new Array();

$(document).ready(function() {
	document.getElementById("menuPlugins_req").innerHTML = "(" + $("#pluginsMenus :checked").length + ")";
	document.getElementById("screenPlugins_req").innerHTML = "(" + $("#pluginsScreens :checked").length + ")";
	document.getElementById("splashPlugins_req").innerHTML = "(" + $("#pluginsSplash :checked").length + ")";
	document.getElementById("settingsPlugins_req").innerHTML = "(" + $("#pluginsSettings :checked").length + ")";
	document.getElementById("actionPlugins_req").innerHTML = "(" + $("#pluginsActions :checked").length + ")";
});



function packageProject(thePlatform){
	document.forms[0].whatPlatform.value = thePlatform;
	document.getElementById("responseError").innerHTML = "";
	document.getElementById("startPackaging").style.display = "none";
	document.getElementById("isLoading").style.display = "block";
	document.getElementById("doneLoading").style.display = "none";
	document.getElementById("downloadiOSBox").innerHTML = "";
	document.getElementById("downloadAndroidBox").innerHTML = "";
	window.setTimeout(buildProject, 5000, true);
	return;
}

function buildProject(){
	
	//make sure jQuery is loaded...
	if(jQuery) {  
		
		//show / hide ui...
		document.getElementById("isLoading").style.display = "none";
		document.getElementById("doneLoading").style.display = "block";
		
		//vars for the form, it's fields, and it's values...
		var $form = $("#frmMain"),
		
		//select and cache form fields...
		$inputs = $form.find("input, select, button, textarea"),
		
		//serialize the data in the form...
		serializedData = $form.serialize();
	
		//POST the ajax request...
	    $.ajax({
			url: "bt_appPackage_AJAX.php",
			type: "post",
			data: serializedData,
			
			//function that will be called on ajax success...
			success:function(response, textStatus, jqXHR){
				
				//parse JSON result...
				try{
					var obj = JSON.parse(response);
					
					//if valid...
					if(obj.result.toUpperCase() == "VALID"){
					
						//get download URL and size from JSON...
						var url = obj.projectURL;
						var size = obj.projectSize;
					
						//show try again / download choices...
						if(document.forms[0].whatPlatform.value == "ios" || document.forms[0].whatPlatform.value == "iosLatest"){
							
							var ios = "";
							ios += "<a href='" + url + "' title='Download Project'><img src='<?php echo APP_URL;?>/images/download_ios.png' alt='Download iOS Project'/></a>";
							ios += "<div style='padding-top:5px;'><a href='" + url + "' title='Download Project'><img src='../../images/arr_right.gif' alt='arrow' />Download .zip archive (" + size + ")</a></div>";
							ios += "<div style='padding-top:5px;'><a href='#' title='Prepare Project' onclick=\"packageProject('" + document.forms[0].whatPlatform.value + "');return false;\"><img src='../../images/arr_right.gif' alt='arrow' />Do over, prepare project again</a></div>";
							document.getElementById("downloadiOSBox").innerHTML = ios;
							
							document.getElementById("tryAgainiOSBox").style.display = "none";
							document.getElementById("downloadiOSBox").style.display = "block";
							document.getElementById("tryAgainAndroidBox").style.display = "block";
							document.getElementById("downloadAndroidBox").style.display = "none";
							
						}
						
						if(document.forms[0].whatPlatform.value == "android" || document.forms[0].whatPlatform.value == "androidLatest"){
							
							var android = "";
							android += "<a href='" + url + "' title='Download Project'><img src='<?php echo APP_URL;?>/images/download_android.png' alt='Downoad Android Project'/></a>";
							android += "<div style='padding-top:5px;'><a href='" + url + "' title='Download Project'><img src='../../images/arr_right.gif' alt='arrow' />Download .zip archive (" + size + ")</a></div>";
							android += "<div style='padding-top:5px;'><a href='#' title='Prepare Project' onclick=\"packageProject('" + document.forms[0].whatPlatform.value + "');return false;\"><img src='../../images/arr_right.gif' alt='arrow' />Do over, prepare project again</a></div>";
							
							document.getElementById("downloadAndroidBox").innerHTML = android;
							document.getElementById("tryAgainiOSBox").style.display = "block";
							document.getElementById("downloadiOSBox").style.display = "none";
							document.getElementById("tryAgainAndroidBox").style.display = "none";
							document.getElementById("downloadAndroidBox").style.display = "block";
							
							
						}	
						
					
					
					}else{
					
						//show each error...
						var errors = obj.errors;
						var message = "";
						for (var i = 0; i < errors.length; i++) {
					  		message += "<br/>" + (i + 1) + ") " + errors[i];
						}
						document.getElementById("responseError").innerHTML = "<div style='padding:5px;color:red;'>Errors occurred processing your request..." + message + "</div>";
					
						//show try again boxes...
						document.getElementById("tryAgainiOSBox").style.display = "block";
						document.getElementById("tryAgainAndroidBox").style.display = "block";
						document.getElementById("downloadiOSBox").style.display = "none";
						document.getElementById("downloadAndroidBox").style.display = "none";
					
					}//valid
					
					

				}catch(e){
					document.getElementById("responseError").innerHTML = "<div style='padding:5px;color:red;'>Package result is not valid JSON?</div>";
				
					//show try again boxes...
					document.getElementById("tryAgainiOSBox").style.display = "block";
					document.getElementById("tryAgainAndroidBox").style.display = "block";
					document.getElementById("downloadiOSBox").style.display = "none";
					document.getElementById("downloadAndroidBox").style.display = "none";
				
				}


			
			},
			
			//function that will be called on ajax error...
			error: function(jqXHR, textStatus, errorThrown){
				document.getElementById("responseError").innerHTML = "<div style='padding:5px;color:red;'>There was an error processing the results?</div>";
			
				//show try again boxes...
				document.getElementById("tryAgainiOSBox").style.display = "block";
				document.getElementById("tryAgainAndroidBox").style.display = "block";
				document.getElementById("downloadiOSBox").style.display = "none";
				document.getElementById("downloadAndroidBox").style.display = "none";
			
			},
			
			//function that will be called on ajax completion (success or error)...
			complete: function(){

			}
			
		});		  
	
	}else{
		//jQuery is not loaded?
		alert("The download process cannot complete because your browser could not perform this task?");
	}		
	
	
} //buildProject...

//select all plugins...
function checkAllPlugins(theSection, trueOrFalse){
	$("#" + theSection + " input[type=checkbox]").each(function(){
		if(Object.prototype.toString.call(trueOrFalse) === '[object Array]' ){
			if(trueOrFalse.length < 1){
				$(this).attr("checked", false);
			}else{
				var exists =  trueOrFalse.indexOf($(this).val());
				if(exists > -1){
					$(this).attr("checked", true);
				}else{
					$(this).attr("checked", false);
				}
			}
		}else{	
			if(trueOrFalse == true || trueOrFalse == false){
				$(this).attr("checked", trueOrFalse);
			}
		}
    });
}


</script>



<div class='content'>
        
    <fieldset class='colorLightBg'>
           
            <!-- left side--> 
            <div class='boxLeft'>
                <div class='contentBox colorDarkBg minHeight' style='min-height:500px;'>
                    <div class='contentBand colorBandBg'>
                        Application Options
                    </div>
                    <div id="leftNavLinkBox" style='padding:10px;padding-bottom:25px;white-space:nowrap;'>
                        
                        <div><a href="index.php?appGuid=<?php echo $appGuid;?>" title="Application Home"><img src="../../images/arr_right.gif" alt="arrow"/>Application Home</a></div>
                        
                        <div><hr></div>
                        
                            <?php echo $thisPage->fnGetControlPanelLinks("application", $appGuid, "block", ""); ?>

                            <div><hr></div>
                            <div><a href='bt_usageMap.php?appGuid=<?php echo $appGuid;?>' title='Usage Map'><img src='../../images/arr_right.gif' alt='arrow'/>Usage Map</a></div>
                            <div><a href='bt_pushNotifications.php?appGuid=<?php echo $appGuid;?>' title='Push Notifications'><img src='../../images/arr_right.gif' alt='arrow'/>Push Notifications</a></div>
                            <div><a href='bt_appPackage.php?appGuid=<?php echo $appGuid;?>' title='Prepare Project Download'><img src='../../images/arr_right.gif' alt='arrow'/>Prepare Project Download</a></div>
                            <div><a href='bt_overTheAir.php?appGuid=<?php echo $appGuid;?>' title='Over the Air Distribution'><img src='../../images/arr_right.gif' alt='arrow'/>Over the Air Distribution</a></div>
                            <div><a href='bt_archives.php?appGuid=<?php echo $appGuid;?>' title='Application Archives'><img src='../../images/arr_right.gif' alt='arrow'/>Application Archives</a></div>
                            <div><a href="index.php?appGuid=<?php echo $appGuid;?>&command=delete" title="Permanently Delete App"><img src="../../images/arr_right.gif" alt="arrow"/>Permanently Delete App</a></div>
                        
                    </div>
                 </div>
            </div>
           
            <div class='boxRight'>
                
                <div class='contentBox colorLightBg minHeight' style='min-height:540px;'>
                    
                    <div class='contentBand colorBandBg'>
               			Download iOS or Android Project for <?php echo fnFormOutput($appName, true);?>
                    </div>
                        		
                   	<div style='padding:10px;'>


						<?php if($bolCanDownload){ ?>
        
                            <div class='cpExpandoBox colorLightBg'>
                            <a href='#' onClick="fnShowHide('box_plugins');return false;"><img src='../../images/arr_right.gif' alt='arrow' />1) Choose Plugins to Include (<?php echo count($requiredPluginsLoadClassNames);?> required, <?php echo count($installedPluginsLoadClassNames) - count($requiredPluginsLoadClassNames);?> optional)</a>
                                <div id="box_plugins" style="display:none;">
                                    
                                    <div style='padding-top:10px;'>
                                        This is a list of plugins installed in your control panel. 
                                        The pre-selected plugins are required to run your app.
                                    </div>
                                    
                                    <?php
                                    
                                        //////////////////////////
                                        //menu screens...
                                        echo "<div style='margin-top:20px;'>";
                                            echo "<div>";
                                                echo "<b>Menu Screens</b>";
                                            echo "</div>";
                                            echo "<hr/>";
                                            echo "<div style='float:right;'>";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsMenus', true);return false;\">check all</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsMenus', false);return false;\">none</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsMenus', menuPlugins);return false;\">required <span id='menuPlugins_req'></span></a>";
                                            echo "</div>";
                                            echo "<div style='clear:both;'></div>";
                                        echo "</div>";
                                        
                                        for($x = 0; $x < count($menuPlugins); $x++){
                                            
                                            //plugin vars...
                                            $guid = fnGetJsonProperyValue("guid", $menuPlugins[$x]);
                                            $loadClassOrActionName = fnGetJsonProperyValue("loadClassOrActionName", $menuPlugins[$x]);
                                            $displayAs = fnGetJsonProperyValue("displayAs", $menuPlugins[$x]);
                                            $imgURL = fnGetJsonProperyValue("imgURL", $menuPlugins[$x]);
                                            $webDirectoryName = fnGetJsonProperyValue("webDirectoryName", $menuPlugins[$x]);
                                            
                                            //pre-select checkbox if this plugin is required...
                                            $checked = "";
                                            if(in_array($loadClassOrActionName, $requiredPluginsLoadClassNames)){
                                                $checked = "checked";
                                                echo "\n\n<script>menuPlugins[" . $x . "] = \"" . $guid . "\";</script>";
                                            }
                                            
                                            echo "\n\n<div id='pluginsMenus' style='float:left;width:210px;margin:3px;white-space:nowrap;overflow:hidden;'>";
                                                echo "\n<input type='checkbox' " . $checked . " id='plugins[]' name='plugins[]' value='" . $guid . "' style=\"margin-top:6px;\"> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $imgURL . "</a> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a> ";
                                            echo "\n</div>";
                                            
                                        }//menus...
                                        
                                        //////////////////////////
                                        //content screens...
                                        echo "<div style='clear:both;'></div>";
                                        echo "<div style='margin-top:20px;'>";
                                            echo "<div>";
                                                echo "<b>Content Screens</b>";
                                            echo "</div>";
                                            echo "<hr/>";
                                            echo "<div style='float:right;'>";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsScreens', true);return false;\">check all</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsScreens', false);return false;\">none</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsScreens', screenPlugins);return false;\">required <span id='screenPlugins_req'></span></a>";
                                            echo "</div>";
                                            echo "<div style='clear:both;'></div>";
                                        echo "</div>";
                                        
                                        for($x = 0; $x < count($screenPlugins); $x++){
                                            
                                            //plugin vars...
                                            $guid = fnGetJsonProperyValue("guid", $screenPlugins[$x]);
                                            $loadClassOrActionName = fnGetJsonProperyValue("loadClassOrActionName", $screenPlugins[$x]);
                                            $displayAs = fnGetJsonProperyValue("displayAs", $screenPlugins[$x]);
                                            $imgURL = fnGetJsonProperyValue("imgURL", $screenPlugins[$x]);
                                            $webDirectoryName = fnGetJsonProperyValue("webDirectoryName", $screenPlugins[$x]);
                                            
                                            //pre-select checkbox if this plugin is required...
                                            $checked = "";
                                            if(in_array($loadClassOrActionName, $requiredPluginsLoadClassNames)){
                                                $checked = "checked";
                                                echo "\n\n<script>screenPlugins[" . $x . "] = \"" . $guid . "\";</script>";
                                            }
                                            
                                            echo "\n\n<div id='pluginsScreens' style='float:left;width:210px;margin:3px;white-space:nowrap;overflow:hidden;'>";
                                                echo "\n<input type='checkbox' " . $checked . " id='plugins[]' name='plugins[]' value='" . $guid . "' style=\"margin-top:6px;\"> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $imgURL . "</a> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a> ";
                                            echo "\n</div>";
                                            
                                        }//for
                                        
                
                                        //////////////////////////
                                        //splash screens...
                                        echo "<div style='clear:both;'></div>";
                                        echo "<div style='margin-top:20px;'>";
                                            echo "<div>";
                                                echo "<b>Splash Screens</b>";
                                            echo "</div>";
                                            echo "<hr/>";
                                            echo "<div style='float:right;'>";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsSplash', true);return false;\">check all</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsSplash', false);return false;\">none</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsSplash', splashPlugins);return false;\">required <span id='splashPlugins_req'></span></a>";
                                            echo "</div>";
                                            echo "<div style='clear:both;'></div>";
                                        echo "</div>";
                                        
                                        for($x = 0; $x < count($splashPlugins); $x++){
                                            
                                            //plugin vars...
                                            $guid = fnGetJsonProperyValue("guid", $splashPlugins[$x]);
                                            $loadClassOrActionName = fnGetJsonProperyValue("loadClassOrActionName", $splashPlugins[$x]);
                                            $displayAs = fnGetJsonProperyValue("displayAs", $splashPlugins[$x]);
                                            $imgURL = fnGetJsonProperyValue("imgURL", $splashPlugins[$x]);
                                            $webDirectoryName = fnGetJsonProperyValue("webDirectoryName", $splashPlugins[$x]);
                                            
                                            //pre-select checkbox if this plugin is required...
                                            $checked = "";
                                            if(in_array($loadClassOrActionName, $requiredPluginsLoadClassNames)){
                                                $checked = "checked";
                                                echo "\n\n<script>splashPlugins[" . $x . "] = \"" . $guid . "\";</script>";
                                            }
                                            
                                            echo "\n\n<div id='pluginsSplash' style='float:left;width:210px;margin:3px;white-space:nowrap;overflow:hidden;'>";
                                                echo "\n<input type='checkbox' " . $checked . " id='plugins[]' name='plugins[]' value='" . $guid . "' style=\"margin-top:6px;\"> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $imgURL . "</a> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a> ";
                                            echo "\n</div>";
                                            
                                        }//for
                
                
                                        //////////////////////////
                                        //settings screens...
                                        echo "<div style='clear:both;'></div>";
                                        echo "<div style='margin-top:20px;'>";
                                            echo "<div>";
                                                echo "<b>Settings Screens</b>";
                                            echo "</div>";
                                            echo "<hr/>";
                                            echo "<div style='float:right;'>";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsSettings', true);return false;\">check all</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsSettings', false);return false;\">none</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsSettings', settingsPlugins);return false;\">required <span id='settingsPlugins_req'></span></a>";
                                            echo "</div>";
                                            echo "<div style='clear:both;'></div>";
                                        echo "</div>";
                                        
                                        for($x = 0; $x < count($settingsPlugins); $x++){
                                            
                                            //plugin vars...
                                            $guid = fnGetJsonProperyValue("guid", $settingsPlugins[$x]);
                                            $loadClassOrActionName = fnGetJsonProperyValue("loadClassOrActionName", $settingsPlugins[$x]);
                                            $displayAs = fnGetJsonProperyValue("displayAs", $settingsPlugins[$x]);
                                            $imgURL = fnGetJsonProperyValue("imgURL", $settingsPlugins[$x]);
                                            $webDirectoryName = fnGetJsonProperyValue("webDirectoryName", $settingsPlugins[$x]);
                                            
                                            //pre-select checkbox if this plugin is required...
                                            $checked = "";
                                            if(in_array($loadClassOrActionName, $requiredPluginsLoadClassNames)){
                                                $checked = "checked";
                                                echo "\n\n<script>settingsPlugins[" . $x . "] = \"" . $guid . "\";</script>";
                                            }
                                            
                                            echo "\n\n<div id='pluginsSettings' style='float:left;width:210px;margin:3px;white-space:nowrap;overflow:hidden;'>";
                                                echo "\n<input type='checkbox' " . $checked . " id='plugins[]' name='plugins[]' value='" . $guid . "' style=\"margin-top:6px;\"> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $imgURL . "</a> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a> ";
                                            echo "\n</div>";
                                            
                                        }//for
                                        
                                        
                                        //////////////////////////
                                        //action screens...
                                        echo "<div style='clear:both;'></div>";
                                        echo "<div style='margin-top:20px;'>";
                                            echo "<div>";
                                                echo "<b>Action Screens</b>";
                                            echo "</div>";
                                            echo "<hr/>";
                                            echo "<div style='float:right;'>";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsActions', true);return false;\">check all</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsActions', false);return false;\">none</a>";
                                                echo "&nbsp;|&nbsp;";
                                                echo "<a href='#' onclick=\"checkAllPlugins('pluginsActions', actionPlugins);return false;\">required <span id='actionPlugins_req'></span></a>";
                                            echo "</div>";
                                            echo "<div style='clear:both;'></div>";
                                        echo "</div>";
                                        
                                        
                                        for($x = 0; $x < count($actionPlugins); $x++){
                                            
                                            //plugin vars...
                                            $guid = fnGetJsonProperyValue("guid", $actionPlugins[$x]);
                                            $loadClassOrActionName = fnGetJsonProperyValue("loadClassOrActionName", $actionPlugins[$x]);
                                            $displayAs = fnGetJsonProperyValue("displayAs", $actionPlugins[$x]);
                                            $imgURL = fnGetJsonProperyValue("imgURL", $actionPlugins[$x]);
                                            $webDirectoryName = fnGetJsonProperyValue("webDirectoryName", $actionPlugins[$x]);
                                            
                                            //pre-select checkbox if this plugin is required...
                                            $checked = "";
                                            if(in_array($loadClassOrActionName, $requiredPluginsLoadClassNames)){
                                                $checked = "checked";
                                                echo "\n\n<script>actionPlugins[" . $x . "] = \"" . $guid . "\";</script>";
                                            }
                                            
                                            echo "\n\n<div id='pluginsActions' style='float:left;width:210px;margin:3px;white-space:nowrap;overflow:hidden;'>";
                                                echo "\n<input type='checkbox' " . $checked . " id='plugins[]' name='plugins[]' value='" . $guid . "' style=\"margin-top:6px;\"> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $imgURL . "</a> ";
                                                echo "\n<a href=\"bt_pickerPluginDetails.php?pluginGuid=" . $guid . "\" style='color:black;' rel=\"shadowbox;height=550;width=950\">" . $displayAs . "</a> ";
                                            echo "\n</div>";
                                            
                                        }//menus...
                                        
                                        
                                    ?>
                                    <div class="clear"></div>
                                    
                                    
                                </div>
                            </div>


                            <div class='cpExpandoBox colorLightBg'>
                            <a href='#' onClick="fnShowHide('box_sdks');return false;"><img src='../../images/arr_right.gif' alt='arrow' />2) Include Optional SDK's</a>
                                <div id="box_sdks" style="display:none;">
                                    
                                    <div style='padding-top:10px;'>
                                        This is an alphabetically ordered list of optional third-party SDK's you may want to use in your application.
                                        Instructions for using each SDK will be included in the download.
                                    </div>
                                    
                                    <div style='padding-top:5px;'>
                                        <b>SDK Publishers:</b> See the 
                                        <a href="http://www.buzztouch.com/files/howtos/buzztouch-sdk-partner-program.pdf" target="_blank">Buzztouch SDK Partner Program</a>
                                        document to get your SDK included in Buzztouch control panels.
                                        <hr/>
                                    </div>
                                    
                                    <div style='margin-top:15px;padding-left:10px;'>
                                        <?php echo fnGetPartners(); ?>
                                        <div style="clear:both;"></div>
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <div class='cpExpandoBox colorLightBg'>
                            <a href='#' onClick="fnShowHide('box_platform');return false;"><img src='../../images/arr_right.gif' alt='arrow' />3) Select iOS or Android to begin the process</a>
                                <div id="box_platform" style="display:block;">
                                
                                    <div id="responseError"></div>
                                    
                                    <div id="isLoading" style="margin-top:10px;height:300px;padding:25px;text-align:center;display:none;">
                                        <img src="<?php echo APP_URL;?>/images/gif-loading.gif">
                                    </div>
                            
                                    <div id="startPackaging" style="margin-top:10px;height:300px;padding:25px;text-align:center;display:block;">
                                        <table cellspacing='0' cellpadding='0' style='width:96%;'>
                                            <tr>
                                                <td style='width:50%;border-right:1px solid gray;padding:25px;padding-top:5px;vertical-align:top;text-align:center;'>
                                                    <img src="<?php echo APP_URL;?>/images/package_ios.png" alt="Package iPhone project"/>
                                                    <br/>
                                                    <a href="#" title='Package Project' onclick="packageProject('ios');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Prepare iOS Project</a>
                                                
                                                    <!--use the latest release -->
                                                    <div style='padding-top:10px;'>
                                                        <a href="#" title='Package Project' onclick="packageProject('iosLatest');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Use the Buzztouch Core for iOS v3.0</a>
                                                        <br/>
                                                        (latest release, device requires iOS 5.0 or above)
                                                    </div>
                                                 
                                                
                                                </td>
                                                <td style='width:50%;padding:25px;padding-top:5px;vertical-align:top;text-align:center;'>
                                                    <img src="<?php echo APP_URL;?>/images/package_android.png" alt="Package Android project"/>
                                                    <br/>
                                                    <a href="#" title='Package Project' onclick="packageProject('android');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Prepare Android Project</a>
                                                
                                                	<!--use the latest release -->
                                                    <div style='padding-top:10px;'>
                                        				<a href="#" title='Package Project' onclick="packageProject('androidLatest');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Use the Buzztouch Core for Android v3.0</a>
                                        				<br/>
                                            			(latest, device requires Android 4.0 or above)
                                                    </div>
                                                
                                                
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                       
                                    <div id="doneLoading" style="margin-top:10px;height:300px;padding:25px;text-align:center;display:none;">
                                        <table cellspacing='0' cellpadding='0' style='width:96%;'>
                                            <tr>
                                                <td style='width:50%;border-right:1px solid gray;padding:25px;padding-top:5px;vertical-align:top;text-align:center;'>
                                                        
                                                    <div id="tryAgainiOSBox" style="display:none">
                                                        <img src="<?php echo APP_URL;?>/images/package_ios.png" alt="Prepare iOS Package"/>
                                                        <br/>
                                                        <a href="#" title='Package Project' onclick="packageProject('ios');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Package iOS Project</a>
                                                    </div>                                
                                                    
                                                    <div id="downloadiOSBox" style="display:none"></div>
                                                        
                                                </td>
                                                <td style='width:50%;padding:25px;padding-top:5px;vertical-align:top;text-align:center;'>
                                                
                                                    <div id="tryAgainAndroidBox" style="display:none">
                                                        <img src="<?php echo APP_URL;?>/images/package_android.png" alt="Prepare Android Package"/>
                                                        <br/>
                                                        <a href="#" title='Package Project' onclick="packageProject('android');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Package Android Project</a>
                                                    
                                                		<!--use the latest release -->
                                                    	<div style='padding-top:10px;'>
                                                            <a href="#" title='Package Project' onclick="packageProject('androidLatest');return false;"><img src='../../images/arr_right.gif' alt='arrow' />Use the Buzztouch Core for Android v3.0</a>
                                                            <br/>
                                            				(latest, device requires Android 4.0 or above)
                                                    	</div>
                                                    
                                                    </div>                                
                                                    
                                                    <div id="downloadAndroidBox" style="display:none"></div>
                                                        
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            
                            
                    * In almost all cases it's best to use the latest release of the Buzztouch Core for your projects. 
                	We offer the previous version for download so you can get a copy of an older project if needed.
                            
                            
                            </div>
                            
                            
                            
                        <?php } else { ?>
                            
                            <div class='errorDiv'>
                                <br><b>The source code for this project cannot be downloaded.</b>
                                <?php echo $canDownloadMessage;?>
                            </div>
                            
                        <?php } ?>



                        <div id="jsonResult" name="jsonResult" style="display:none;font-size:10pt;font-family:monospace;">
                            <!--used for debugging. Print jsonResults to this as needed (see buildProject() method) -->
                        </div>



                        
                                
                    </div>
            
            	</div>
                	

            
            
            </div>                      
           
           
	</fieldset>
    
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>

    



