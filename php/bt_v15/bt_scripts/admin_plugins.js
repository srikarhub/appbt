
/*
	This file is used by the control panel. The including file must
	create a javascript variable in the <head> section named:
	
	ajaxURL = "AJAX....php";

*/
	function fnShowHide(theEl){
		var theBox = document.getElementById(theEl);
		if(theBox.style.display == "none"){
			theBox.style.display = "block";
		}else{
			theBox.style.display = "none";
		}
	}
	
	//ignore these form fields when posting screen data...
	function fnIgnoreField(theVal){
		var ignore = new Array();
		ignore[0] = "sortUpDown";  
		ignore[1] = "sortColumn";  
		ignore[2] = "currentPage";  
		ignore[3] = "viewStyle";  
		ignore[4] = "search";  
		ignore[5] = "searchPluginTypeUniqueId";  
		ignore[9] = "na";  
    	for(var i = 0; i < ignore.length; i++) {
        	if(ignore[i] == theVal) return true;
    	}
    	return false;
	}

	
	var http_request = false;
	var theForm;
	var resultsDiv;
	function saveScreenData(theSection){
	
		//hide all the previous "saved" or "working" messages on the calling screen...
		var divs = document.getElementsByTagName('div');
		for(var i = 0; i < divs.length; i++){ 
			if(divs[i].id.indexOf("submit_", 0) > -1){
				divs[i].innerHTML = "&nbsp;";
			}
		} 	
	
		//show working message in the appropriate <html> element on the calling screen...
		resultsDiv = document.getElementById("submit_" + theSection)
		resultsDiv.innerHTML = "working...";
		resultsDiv.className = "submit_working";
		
		//url to post to is declared in the <head> section of the calling screen...
		var theURL = ajaxURL;
		
		//setup post
		http_request = false;
		theForm = document.forms[0];
		
		//build params for the post
		var parameters = "";
		for(i = 0; i < theForm.elements.length; i++){
			var elName = theForm.elements[i].name;
			if(elName == "") elName = "na";
			var elVal = encodeURIComponent(theForm.elements[i].value);
			
			//some fields get ignored because they are not part of a screen...
			if(!fnIgnoreField(elName) && elName != undefined){
				
				//only append checkbox values if "checked"
				if(theForm.elements[i].type == "checkbox"){
				
					if(theForm.elements[i].checked){
						//add to params
						parameters += elName + "=" + elVal + "&";
					}
					
				}else{
				
					//add to params.
					parameters += elName + "=" + elVal + "&";
			
				}
			
			}
			
		}
		
		if(theURL != ""){
			//post the request		
			if(window.XMLHttpRequest){ // Mozilla, Safari,...
				http_request = new XMLHttpRequest();
				if(http_request.overrideMimeType){
					http_request.overrideMimeType('text/html');
				}
			}else if(window.ActiveXObject) { // IE
				try{
					http_request = new ActiveXObject("Msxml2.XMLHTTP");
				}catch(e){
					try{
					   http_request = new ActiveXObject("Microsoft.XMLHTTP");
					}catch(e){}
				}
			}
			if(!http_request) {
				theDiv.innerHTML = "error saving?";
				return false;
			}
		}
		
	 	//submit
		http_request.onreadystatechange = handleAJAXResult;
		http_request.open('POST', theURL, true);
		http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		http_request.send(parameters);
		
	}//saveScreenData
	
	
	//processes results
	function handleAJAXResult(){
		if(http_request.readyState == 4) {
			if(http_request.status == 200) {
				result = http_request.responseText;
				
				//alert(result);
				
				//set the color...
				if(result == "invalid request"){
					resultsDiv.className = "submit_working";
				}else{
					resultsDiv.className = "submit_done";
				}
				
				//set the text...
				resultsDiv.innerHTML = result;
				
				
			}else{
				
				resultsDiv.className = "submit_working";
				resultsDiv.innerHTML = "Error saving?";  
				
			}
		}
	}
	
	
	//checkForUpdates...
	function checkForUpdates(pluginGuid){
		document.forms[0].pluginGuid.value = pluginGuid;
		document.forms[0].command.value = "checkForUpdates";
		saveScreenData(pluginGuid);
	}
	
	//installPlugin...
	function installPlugin(pluginGuid, uniquePluginId, directoryName, downloadURL){
		var resDiv = document.getElementById("submit_" + pluginGuid);
		resDiv.style.color = "red";
		resDiv.innerHTML = "...loading";
		var theURL = "pluginUpdate_AJAX.php?pluginGuid=" + pluginGuid + "&command=installPlugin&downloadURL=" + downloadURL + "&uniquePluginId=" + uniquePluginId + "&webDirectoryName=" + directoryName;
		$.ajax({
		url: theURL,
		success:function(data){
			if(data != ""){
				resDiv.innerHTML = data;
			}
		}
		});
	}	
	
	//count plugins...
	function fnCountPlugins(){
		var pluginGuidArray = document.getElementsByName('pluginBatch');
		return pluginGuidArray.length;
		
	}
	
	//check all plugins for updates...
	function fnCheckAllPluginsForUpdates(){
		
		//make sure jQuery is loaded...
		if(typeof jQuery == 'undefined'){
			document.getElementById("batchUpdateProgress").style.display = "block";
			document.getElementById("batchUpdateProgress").innerHTML = "<div style='padding:10px;margin:10px;color:red;border:1px solid gray;'>An error occurred trying to check for updates</div>";
		}else{
			document.getElementById("batchUpdateProgress").style.display = "none";
			document.getElementById("batchUpdateProgress").innerHTML = "";
			pluginGuidArray = document.getElementsByName("pluginBatch");
			for(var i = 0; i < pluginGuidArray.length; i++){
				var tmpGuid = pluginGuidArray[i].value;
				if(tmpGuid.length > 0){
					
					//load div for this plugin's update result...
					if(i < pluginGuidArray.length){
						
						var theURL = "pluginUpdate_AJAX.php?pluginGuid=" + tmpGuid + "&command=checkForUpdates";
						document.getElementById("controls_" + tmpGuid).innerHTML = "working...";
						document.getElementById("submit_" + tmpGuid).innerHTML = "";
						$("#controls_" + tmpGuid).load(theURL + "#controls_" + tmpGuid);
						
						
					}
						
				}
			}
		}
		
		
	}
	
	//shows "check all for updates" option...
	function fnShowUpdateAllOption(){
		var updateLink = "";
		updateLink += "&nbsp;|&nbsp;";
        updateLink += "<a href=\"#\" onclick=\"fnCheckAllPluginsForUpdates();return false;\" style='vertical-align:middle;white-space:nowrap;' title=\"Check for Updates\">Check All " + fnCountPlugins() + " Plugins for Updates</a>";
		document.getElementById("checkAllForUpdates").innerHTML = updateLink;	
	}

	
	
	