
	/*
		variables from the underlying page (bt_screen.php), these methods are 
		called inside the document.ready method...
	*/
	
	var userGuid;
	var appGuid;
	var BT_itemId;
	var screenDataURL;
	var JSONString;
	var loadedSections = new Array();
	
	//sets page variables, .php creates the values passed to to this method...
	function fnSetJavascriptVariables(theUserGuid, theAppGuid, theBT_itemId, theScreenDataURL, thePropertiesSections, theJSONString){
		userGuid = theUserGuid;
		appGuid = theAppGuid;
		BT_itemId = theBT_itemId;
		screenDataURL = theScreenDataURL;
		JSONString = theJSONString;
	
		//split comma separted list of sections...
		loadedSections = thePropertiesSections.split(",");
	
		//pre-populate form field's in included sections...
		fnFillFormValues(JSONString);

	}
	
	
	//pre-populates form fields with existing values...
	function fnFillFormValues(JSONString){
		
		var json = null;
		try{
			json = $.parseJSON(JSONString);
		}catch(err){
			$("#errorBox").show("fast");
			$("#errorBox").html("<br>There was a problem parsing the JSON data for this screen.<br>" + JSONString);
		}
		

		if(json != null){
			
			var inputs = document.getElementsByTagName("select");
			for(var i = 0; i < inputs.length; i++){
				inputs[i].selectedIndex = 0;
			}
			
			//loop through the json properties and populate form fields named json_[property]...
			var count = 0;
			for (var thisItem in json){
				if(json.hasOwnProperty(thisItem)){
					var theEl = document.getElementById("json_" + thisItem);
					if(theEl != null){
						
						//set value's for text types...
						if(theEl.type.toUpperCase() == "TEXT" 
							|| theEl.type.toUpperCase() == "HIDDEN"
							|| theEl.type.toUpperCase() == "TEXTAREA"){
							
							/*
								the value for this property may contain pipe characters that
								were inserted by .php for all occurances of apostrophes ('). replace
								these as needed.
							*/
							var propertyValue = json[thisItem];
							propertyValue = propertyValue.replace(/\|/g, "'");

							//set the value in the form element...
							theEl.value = propertyValue;	
							
							
						}else{
							
							//select box...
							if(theEl.type.toUpperCase() == "SELECT" 
								|| theEl.type.toUpperCase() == "SELECT-ONE"){
								
								//select the proper index element...
								fnSetSelectedIndex(document.getElementById(theEl.name), json[thisItem]);
							}
							
						}
						
					}
					count++;
				}//hasOwnProperty
			}//for...
			
			var currentJson = json;
			
			//show the JSON string in the screenJson text area....
			if(document.getElementById("screenJson")){
				
				/*
					the JSON string for this screen may contain pipe characters that
					were inserted by .php for all occurances of apostrophes ('). replace
					these as needed.
				*/
				var showJson = JSON.stringify(currentJson, null, 1);
				showJson = showJson.replace(/\|/g, "'");
				document.getElementById("screenJson").value = showJson;
			}
			
			
		}//json != null
	}
	

	//helper method to allow legacy calls to "fnExpandCollapse()"...
	function fnExpandCollapse(hideOrExpandElementId){
		$("#" + hideOrExpandElementId).toggle("fast");
	}
	function fnTogglePropertyBox(hideOrExpandElementId){
		$("#" + hideOrExpandElementId).toggle("fast");
	}

	//helper method to allow legacy calls to "saveAdvancedProperty()"...
	function saveAdvancedProperty(showResultsInElementId){
		saveJSONProperties(showResultsInElementId);
	}
	
	//saves JSON properties in each form field...
	function saveJSONProperties(showResultsInElementId){
	
		//hide all previous "saved result" containers...
		var divs = document.getElementsByTagName('div');
		for(var i = 0; i < divs.length; i++){ 
			if(divs[i].id.indexOf("saveResult_", 0) > -1){
				divs[i].innerHTML = "&nbsp;";
			}
		} 	
	
		//show working message in the appropriate save result container...
		resultsDiv = document.getElementById(showResultsInElementId)
		resultsDiv.innerHTML = "<div class='loading' style='height:30px;'><img src='../../images/spinner.png' alt='spinner'></div>";
		resultsDiv.className = "submit_working";
		
		//make sure jQuery is loaded...
		if(jQuery){  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain");
			
			//set the value of the command field...
			$("#command").val("saveJSONProperties");
			
			//if this is the screenData value in the textarea holding all the json...
			if(showResultsInElementId == "saveResult_screenJson"){
				$("#command").val("saveJSONScreenData");
			}
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea");
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			//disable the inputs while we wait for results...
			$inputs.attr("disabled", "disabled");
			
			//POST the ajax request...
		   $.ajax({
				url: "bt_JSON_API_AJAX.php",
				type: "post",
				data: serializedData,
				success:function(response, textStatus, jqXHR){
					
					//response must be valid JSON...
					var json = null;
					try{
						json = $.parseJSON(response);
					}catch(err){
					}
	
					//json loaded...
					if(json == null){
						resultsDiv.className = "submit_working";
						resultsDiv.innerHTML = "A problem occurred while saving the entries (1). The results were not valid JSON.<br>" + response;
					}else{
						
						//parse the JSON, look for success or errors...
						var status = "";
						if(typeof(json.result) != "undefined") status = json.result;
						if(status.toUpperCase() != "SUCCESS"){
						
							//message...
							var message = "<img src='../../images/red_dot.png' style='margin-right:5px;'><b>Not Saved!</b>";
							message += "<div style='padding-top:5px;padding-bottom:5px;color:#000000;font-family:monospace;'>";
							message += response;
							message += "</div>";
						
							//show each error...
							resultsDiv.className = "submit_working";
							resultsDiv.innerHTML = message;
							
							//hide the server side error message...
							$("#errorBoxServer").hide("fast");
							
							
						}else{
							
							
							//hide a possible server side error message...
							$("#errorBoxServer").hide("fast");
							$("#errorBox").hide("fast");
							
							//message...
							var message = "<div>";
							message += "<img src='../../images/green_dot.png' style='margin-right:5px;'><b>Saved!</b>";
							message += "</div>";

							//success...	
							resultsDiv.className = "submit_done";
							resultsDiv.innerHTML = message;
							
							//get the "savedJson" from the response...
							var savedJson = "";
							if(typeof(json.savedJSON) != "undefined") savedJson = json.savedJSON;
							savedJson = JSON.stringify(savedJson, null, 1);

							//set the screenJson text area....
							if(document.getElementById("screenJson")){
								document.getElementById("screenJson").value = savedJson;
							}
							
							//if we saved the screenJson values using the textarea at end of screen, we need to
							//re-populate all the form fields again. This allows users to manually edit the json...
							if(showResultsInElementId == "saveResult_screenJson"){
								JSONString = savedJson;
								fnFillFormValues(savedJson);	
							}
						
						}
						
					}//json == null...
				},
				
				//function that will be called on error...
				error: function(jqXHR, textStatus, errorThrown){
					resultsDiv.className = "submit_working";
					resultsDiv.innerHTML = "A problem occurred while saving the entries (2). There was a problem getting the results.";
				},
				
				//function that will be called on completion (success or error)...
				complete: function(){
					$inputs.removeAttr("disabled");
					$("#command").val("");
				}
			});		  
		
		}else{
			//jQuery is not loaded?
			resultsDiv.innerHTML = "jQuery not loaded?";
		}		
		
	}


	//set selected index for a drop-down by value...
	function fnSetSelectedIndex(theEl, theValue) {
		if(theValue != ""){
			for(var i = 0, j = theEl.options.length; i < j; ++i) {
				if(theEl.options[i].value === theValue){
				   theEl.selectedIndex = i;
				   break;
				}
			}
		}
	}
	
	//showChildItemPropertiesJSON()...
	function showChildItemPropertiesJSON(childItemId){
		Shadowbox.open({ 
        	content:    "bt_screen_json.php?appGuid=" + appGuid + "&BT_itemId=" + childItemId, 
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}	
	
	//showChildItemProperties()...
	function showChildItemProperties(childItemId){
		Shadowbox.open({ 
        	content:    "bt_childItem.php?appGuid=" + appGuid + "&BT_itemId=" + childItemId, 
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}	
	
	//pick color...
	function fnPickColor(formFieldId){
		Shadowbox.open({ 
        	content:    "bt_pickerColor.php?appGuid=" + appGuid + "&BT_itemId=" + BT_itemId + "&formElVal=" + formFieldId, 
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}

	//pick screen...
	function fnPickScreen(BT_itemIdElementName, nicknameFormElementName){
		Shadowbox.open({ 
        	content:    "bt_pickerScreen.php?appGuid=" + appGuid + "&BT_itemId=" + BT_itemId + "&formElVal=" + BT_itemIdElementName + "&formElLabel=" + nicknameFormElementName,
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}

	//pick menu...
	function fnPickMenu(BT_itemIdElementName, nicknameFormElementName){
		Shadowbox.open({ 
        	content:    "bt_pickerMenu.php?appGuid=" + appGuid + "&BT_itemId=" + BT_itemId + "&formElVal=" + BT_itemIdElementName + "&formElLabel=" + nicknameFormElementName,
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}

	//pick file name...
	function fnPickFileName(fileNameFormFieldName, fileManagerFolder){
		Shadowbox.open({ 
        	content:    "bt_pickerFile.php?appGuid=" + appGuid + "&BT_itemId=" + BT_itemId + "&formEl=" + fileNameFormFieldName + "&fileNameOrURL=FileName&searchFolder=" + fileManagerFolder, 
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}

	//pick file URL...
	function fnPickFileURL(fileNameFormFieldName, fileManagerFolder){
		Shadowbox.open({ 
        	content:    "bt_pickerFile.php?appGuid=" + appGuid + "&BT_itemId=" + BT_itemId + "&formEl=" + fileNameFormFieldName + "&fileNameOrURL=URL&searchFolder=" + fileManagerFolder, 
        	player:     "iframe", 
        	height:     550, 
        	width:      950 
    	}); 
	}
	
	//opens a shadobox...
	function fnOpenShadowbox(theURL, height, width){
		Shadowbox.open( { content:    theURL, 
        	type: "iframe", 
            player: "iframe",
            options: {initialHeight:width, initialWidth:height} 
        });
	}


	//executes remove command...
	function fnExecuteBackendCommand(theCommand){
		
		//return this...
		var ret = "";
		
		//make sure jQuery is loaded...
		if(jQuery){  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain");
			
			//set the value of the command field...
			$("#command").val(theCommand);
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea");
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			$.ajax({
				url: "bt_JSON_API_AJAX.php",
				type: "post",
				data: serializedData,
				async: false,
				success:function(response, textStatus, jqXHR){
					ret = response;
				},
				error: function(jqXHR, textStatus, errorThrown){
					ret = "";
				}
				
			});	
		
		}//jQuery
		
					
		//clear the form...
		$("#command").val("");
		$("#childItemId").val("");
					
		//return...
		return ret;
		
	}

	/*

	//get fnGetChildItems for a screen, returns 300 rows from startIndex...
	function fnGetChildItems(){
		
		//return this...
		var ret = "";
		
		//make sure jQuery is loaded...
		if(jQuery){  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain");
			
			//set the value of the command field...
			$("#command").val("getChildItems");
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea");
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			$.ajax({
				url: "bt_JSON_API_AJAX.php",
				type: "post",
				data: serializedData,
				async: false,
				success:function(response, textStatus, jqXHR){
					ret = response;
				},
				error: function(jqXHR, textStatus, errorThrown){
					ret = "";
				}
				
			});	
		
		}//jQuery
		
		//return...
		return ret;
	
	} //fnGetChildItems...


	//add a child item to a screen...
	function fnAddChildItem(childItemJson){
		
		//return this...
		var ret = "";
		
		//make sure jQuery is loaded...
		if(jQuery){  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain");
			
			//set the value of the command field...
			$("#command").val("addChildItem");
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea");
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			$.ajax({
				url: "bt_JSON_API_AJAX.php",
				type: "post",
				data: serializedData,
				async: false,
				success:function(response, textStatus, jqXHR){
					ret = response;
				},
				error: function(jqXHR, textStatus, errorThrown){
					ret = "";
				}
				
			});	
		
		}//jQuery
		
		//return...
		return ret;		
		
	} //fnAddChildItem...
	
	//delete a child item...
	function fnDeleteChildItem(childItemId){
		
		//return this...
		var ret = "";
		
		//make sure jQuery is loaded...
		if(jQuery){  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain");
			
			//set the value of the command field...
			$("#command").val("removeChildItem");
			
			//set the value of the childItemId we're removing...
			$("#childItemId").val(childItemId);
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea");
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			$.ajax({
				url: "bt_JSON_API_AJAX.php",
				type: "post",
				data: serializedData,
				async: false,
				success:function(response, textStatus, jqXHR){
					ret = response;
				},
				error: function(jqXHR, textStatus, errorThrown){
					ret = "";
				}
				
			});	
		
		}//jQuery
		
		//return...
		return ret;
		
	}//fnDeleteChildItem...
	
	//update child items order...
	function fnUpdateChildItemOrder(){
		
		//return this...
		var ret = "";
		
		//make sure jQuery is loaded...
		if(jQuery){  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain");
			
			//set the value of the command field...
			$("#command").val("updateChildItemsOrder");
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea");
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			$.ajax({
				url: "bt_JSON_API_AJAX.php",
				type: "post",
				data: serializedData,
				async: false,
				success:function(response, textStatus, jqXHR){
					ret = response;
				},
				error: function(jqXHR, textStatus, errorThrown){
					ret = "";
				}
				
			});	
		
		}//jQuery
		
		//return...
		return ret;		
		
	}

	*/











	

