
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
		
		//add the editor's content (we don't replace anything in the editor)
		if(theSection == "htmlEditorFlag"){
			parameters += "editorContent=" + encodeURIComponent(theForm.editorContent.value);
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
	
	

