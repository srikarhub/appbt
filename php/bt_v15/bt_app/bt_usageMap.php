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

    $mapScripts = "<script type='text/javascript' src=\"https://maps.google.com/maps?file=api&amp;v=2.177&amp;sensor=false&amp;key=" . APP_GOOGLE_MAPS_API_KEY . "\"></script>";

	//add map js to page headers
	$thisPage->customHeaders = $mapScripts;

	//inline in js in head
	$mapScripts = "\n var map;";
 	$mapScripts .= "\n var streetviewclient;";
  	$mapScripts .= "\n var streetviewpanorama;";
    $mapScripts .= "\n var geocoder;";
    $mapScripts .= "\n var address;";
		
	$mapScripts .= "\nfunction initMap(){";
		$mapScripts .= "\n map = new GMap2(document.getElementById(\"map_canvas\"));";
		$mapScripts .= "\n map.setCenter(new GLatLng(39.0997222,-94.5783333), 2);";
		$mapScripts .= "\n map.addControl(new GSmallMapControl());";
		$mapScripts .= "\n map.addControl(new GScaleControl());";
		$mapScripts .= "\n map.enableContinuousZoom();";
		$mapScripts .= "\n map.enableScrollWheelZoom();";
		$mapScripts .= "\n map.getInfoWindow();";
		
		//triggers initial load
		$mapScripts .= "\n window.setTimeout('fnInitData()', 500);";
		
	$mapScripts .= "\n}";
	$thisPage->jsInHead = $mapScripts;
	
	//custom body tag
	$thisPage->customBody = "onload=\"initMap()\" onunload=\"GUnload()\"";

	//vars...
	$dtNow = fnMySqlNow();
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$bolDeleted = false;
	$appGuid = fnGetReqVal("appGuid", "", $myRequestVars);
	
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
	
	//app vars...
	$appName = $objApp->infoArray["name"];



	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<input type="hidden" name="appGuid" id="appGuid" value="<?php echo $appGuid;?>">
<input type="hidden" name="command" id="command" value="">

<style type="text/css">
	#detailview {
		width: 280px;
		height:200px;
		margin:0px;
		padding:0px;
		text-align:left;
		font-size:12pt;
	}
	#detailmap {
		width: 280px;
		height:190px;
		margin:0px;
		padding:0px;
	}
	#streetview {
		width: 280px;
		height:200px;
		margin:0px;
		padding:0px;
	}
</style>

<script type="text/javascript">
		
	//vars
	var dataUrl = "bt_usageMap_AJAX.php";
	var loadUrl = dataUrl;
	var useIcon = 3;
	var searchAppGuid = "<?php echo $appGuid;?>";
	var recsPerPage = 100;
	var centerMap = true;
	var clearMap = true;
	
	
	
	function tinyIcon(){
	  var tiny = new GIcon();
	  tiny.image =  "../../images/map_marker_red.png";
	  tiny.shadow = "../../images/mm_20_shadow.png";
	  tiny.iconSize = new GSize(12, 20);
	  tiny.shadowSize = new GSize(22, 20);
	  tiny.iconAnchor = new GPoint(6, 20);
	  tiny.infoWindowAnchor = new GPoint(5, 1);
	  tiny.imageMap = [4,0,0,4,0,7,3,11,4,19,7,19,8,11,11,7,11,4,7,0];
	  tiny.transparent = "../../images/tiny_transparent.png"; 
	  return tiny;
	}
	
	
	//recalculates bounds
	GMap2.prototype.showBounds = function(bounds_, opt_options){
	  var opts = opt_options || {};
	  opts.top = opt_options.top *1 || 0;
	  opts.left = opt_options.left *1 || 0;
	  opts.bottom = opt_options.bottom *1 || 0;
	  opts.right = opt_options.right *1 || 0;
	  opts.save = opt_options.save || true;
	  opts.disableSetCenter = opt_options.disableSetCenter || false;
	  var ty = this.getCurrentMapType();
	  var port = this.getSize();
	  if(!opts.disableSetCenter){
		var virtualPort = new GSize(port.width - opts.left - opts.right, 
								port.height - opts.top - opts.bottom);
		this.setZoom(ty.getBoundsZoomLevel(bounds_, virtualPort));
		
		var xOffs = (opts.left - opts.right) / 2;
		var yOffs = (opts.top - opts.bottom) / 2 - 5;
		
		var bPxCenter = this.fromLatLngToDivPixel(bounds_.getCenter());
		var newCenter = this.fromDivPixelToLatLng(new GPoint(bPxCenter.x-xOffs, bPxCenter.y-yOffs));
		this.setCenter(newCenter);
		if(opts.save)this.savePosition();
	  }
	  var portBounds = new GLatLngBounds();
	  portBounds.extend(this.fromContainerPixelToLatLng(new GPoint(opts.left, port.height-opts.bottom)));
	  portBounds.extend(this.fromContainerPixelToLatLng(new GPoint(port.width-opts.right, opts.top)));
	  return portBounds;
	}
	
	//parse data
	String.prototype.parseCsv = function(opt_options){
	  var results = [];
	  var opts = opt_options || {};
	  var iLat = opts.lat || 0;
	  var iLng = opts.lng || 1;
	  var lines = this.split("\n");
	  for (var i = 0; i < lines.length; i++) {
		var blocks = lines[i].split('"');
		//finding commas inside quotes. Replace them with '::::'
		for(var j = 0; j < blocks.length; j++){
		  if(j % 2){
			blocks[j] = blocks[j].replace(/,/g,'::::');
		  }
		}
		lines[i] = blocks.join("");
		var lineArray = lines[i].split(",");
		var lat = parseFloat(lineArray[iLat]);
		var lng = parseFloat(lineArray[iLng]);
		var point = new GLatLng(lat,lng);
		//after splitting by commas, we put hidden ones back
		for(var cell in lineArray){
		  lineArray[cell] = lineArray[cell].replace(/::::/g,',');
		} //corrupted line step-over
		if(!isNaN(lat+lng)){
		  point.textArray = lineArray;
		  results.push(point);
		}
	  }
	  return results;
	}
	
	function addMarker(point) {
	
		//html for bubble
		var html = "<div style='margin:0px;padding:0px;'>";
			html += "<b><?php echo $appName;?></b>";
			html += "<br>Device: " + point.textArray[3];
			html += "<br>Timestamp: " + point.textArray[2];
			html += "<br>Location: " + point.textArray[0] + " / " + point.textArray[1];
			html += "<div id='description'></div>";
		html += "</div>";
		
		var marker = new GMarker(point,{title:point.textArray[0] + " / " + point.textArray[1], draggable:false, icon:tinyIcon()});
		GEvent.addListener(marker, "click", function() {
			
			var tab1 = new GInfoWindowTab("details", '<div id="detailview">' + html + '</div>');
			var tab2 = new GInfoWindowTab("zoom", '<div id="detailmap"></div>');
			var tab3 = new GInfoWindowTab("street-view", '<div id="streetview">&nbsp;</div>');
		
			var infoTabs = [tab1, tab2, tab3];
			marker.openInfoWindowTabsHtml(infoTabs);
			
	
			//detail map in second tab
			var dMapDiv = document.getElementById("detailmap");
			var detailMap = new GMap2(dMapDiv);
			detailMap.setCenter(point, 10);
			var marker_red = new GMarker(point,{title:point.textArray[0] + " / " + point.textArray[1],draggable:false, icon:tinyIcon()});
			detailMap.addOverlay(marker_red);
			detailMap.setUIToDefault();
			var CopyrightDiv = dMapDiv.firstChild.nextSibling;
			var CopyrightImg = dMapDiv.firstChild.nextSibling.nextSibling;
			CopyrightDiv.style.display = "none"; 
			CopyrightImg.style.display = "none";
		
		
			//street view stuff in third tab
			document.getElementById("streetview").innerHTML = "<div style='padding-left:5px;'><i>searching for street view data...</i></div>";
			streetviewpanorama = new GStreetviewPanorama(document.getElementById("streetview"));
			streetviewpanorama.setLocationAndPOV(point);
			GEvent.addListener(streetviewpanorama, 'error', streetviewpanorama_error);
			streetviewclient = new GStreetviewClient( );
			streetviewclient.getNearestPanorama(point, streetviewclient_callback);	
			
			//geocoder to find address info
			if(point.textArray[0] == "0" || point.textArray[1] == "0"){
				document.getElementById("description").innerHTML = "<div style='padding-top:5px;font-size:9pt;'>location description information not available.</div>";
			}else{
				geocoder = new GClientGeocoder();
				getAddress(point);
			}
			
			
			
			
		});
		map.addOverlay(marker);
	}
	
	//error on panorma
	function streetviewpanorama_error(code){
		if(code == GStreetviewPanorama.ErrorValues.FLASH_UNAVAILABLE){
		  alert( 'You need Flash player to use street view.' );
		  return;
		}
	  }
	
	function streetviewclient_callback(streetviewdata){
		if(streetviewdata.code != GStreetviewClient.ReturnValues.SUCCESS){
			document.getElementById("streetview").innerHTML = "<div style='padding-left:5px;'><i>street-view data available</i></div>";
			return;
		}else{
			document.getElementById("streetview").innerHTML = "";
			streetviewpanorama.setLocationAndPOV( streetviewdata.location.latlng );
		}
	}
	
	//get address
	function getAddress(latlng){
		if(latlng != null) {
			address = latlng;
			geocoder.getLocations(latlng, showAddress);
		 }else{
			document.getElementById("description").innerHTML = "<div style='padding-top:5px;font-size:9pt;'>location description information not available.</div>";
		 }
	}
	
	//displays address
	function showAddress(response) {
		  if(!response || response.Status.code != 200) {
			//alert("Status Code:" + response.Status.code);
			document.getElementById("description").innerHTML = "<div style='padding-top:5px;font-size:9pt;'>location description information not available.</div>";
		  }else{
			place = response.Placemark[0];
			var showIt = "Address: " + place.address;
			showIt += "<div style='padding-top:8px;font-size:9pt;'>Address accuracy: " + place.AddressDetails.Accuracy;
			showIt += " (0-9 accuracy, 9 is most accurate. 0 is useless).</div>";
			document.getElementById("description").innerHTML = showIt;
		  }
	}
	
	
	//adds markers to map
	function populateMap(points, opt_options){
	  var bounds = new GLatLngBounds();
	  var opts = opt_options || {};
	  for (var i=0; i < points.length; i++) {
		addMarker(points[i]);
		bounds.extend(points[i]);
	  }
	  var paddings = {top:30, right:10, bottom:10, left:50};
	  //do we re-center the map after adding all the markers?
	  if(centerMap && i > 0) map.showBounds(bounds,paddings); 
	  hideProgress(); 
	}
	
	
	
	//trigger data download
	function ajaxLoad(opt_options){
	  var opts = opt_options || {};
	  var iconNumber = opts.iconNumber || 2;
	  opts.icon = "";
	  //map.clearOverlays();
	  var process = function(material){
		var entries = material.parseCsv(opts);
		populateMap(entries, opts);
	  }
	  GDownloadUrl(loadUrl, process);
	}
	
	//clears map and resets
	function fnClearMap(){
		map.clearOverlays();
	}
	
	
	//load map
	function fnInitData(){
		if(clearMap) fnClearMap();
		showProgress();
		loadUrl = dataUrl + "?appGuid=" + searchAppGuid;
		//alert(loadUrl);
		ajaxLoad({iconNumber:useIcon});
	} 
	
	
	//shows recent
	function fnRecent(count){
		recsPerPage = count;
		fnInitData();
	}
	
	//hide progress
	function hideProgress(){
		document.getElementById("loadingDiv").innerHTML = "";
	}
	
	//show progress
	function showProgress(){
		document.getElementById("loadingDiv").style.color = "red";
		document.getElementById("loadingDiv").innerHTML = "loading map data...";
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
                
                <div class='contentBox colorLightBg minHeight' style='min-height:500px;'>
                    
                    <div class='contentBand colorBandBg'>
                   		<?php echo fnFormOutput($objApp->infoArray["name"]);?> Usage Map
                    </div>
                        		
                   	<div style='padding:10px;'>
                            <img src='../../images/arr_right.gif' alt='arrow'/><a href='bt_usageMapDownload.php?appGuid=<?php echo $appGuid;?>' title="dowload">Download Usage Data</a>
                            &nbsp; | &nbsp;
                            <a href='#' onclick="fnInitData();" title="refresh map">Reload map</a>
                            &nbsp;&nbsp;
                            <span id="loadingDiv" style='color:red;'>
                            	loading map data...
                        	</span>  
					</div>

                         <div id="map_canvas" style="height:450px;margin-top:0px;position:relative;z-index:0;"></div>
                            
                    <div class='cpExpandoBox colorDarkBg'>
                    	This maps shows the last 500 devices that are reporting location information.
                        Duplicate locations are not displayed. If no devices are showing, no devices
                        are reporting their location.
                    </div>


                    
                    
                    
            	</div>
            </div>                      
           
           
    </fieldset>


<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
