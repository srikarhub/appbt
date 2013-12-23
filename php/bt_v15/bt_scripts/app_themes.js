
	function fnShowHide(){
		try{
			if(document.getElementById("addThemeBox").style.display == "none"){
				document.getElementById("addThemeBox").style.display = "block";
				document.getElementById("dataBox").style.display = "none";
				document.getElementById("headerBox").style.display = "none";
			}else{
				document.getElementById("addThemeBox").style.display = "none";
				document.getElementById("dataBox").style.display = "block";
				document.getElementById("headerBox").style.display = "block";
			}
		}catch(er){
			//ignore...
		}
	}
	function fnAddTheme(){
		try{
			var theForm = document.forms[0];
			var bolPassed = true;
			var strMessage = "";
			document.getElementById("addThemeMessage").style.visibility = "visible";
			if(theForm.addNickname.value == "" || theForm.addNickname.value == "Nickname..."){
				bolPassed = false;
				strMessage += "Enter a nickname. ";
			}
			if(!bolPassed){
				document.getElementById("addThemeMessage").innerHTML = "Not Added: " + strMessage;
			}else{
				document.getElementById("addThemeMessage").innerHTML = "working, please wait...";
				theForm.command.value = "addItem";
				theForm.submit();
			}
		}catch(er){
			//ignore...	
		}
	}
	
	<!--shows or hides advanced property section -->
	function fnExpandCollapse(hideOrExpandElementId){
		var theBoxToExpandOrCollapse = document.getElementById(hideOrExpandElementId);
		if(theBoxToExpandOrCollapse.style.display == "none"){
			theBoxToExpandOrCollapse.style.display = "block";
		}else{
			theBoxToExpandOrCollapse.style.display = "none";
		}
	}
	
	<!--saves advanced property -->
	function saveAdvancedProperty(showResultsInElementId){
	
		//the "advanced" box behaves a bit different...
		if(showResultsInElementId == "saveResult_advanced"){
			document.forms[0].advancedEdit.value = "1";
		}else{
			document.forms[0].advancedEdit.value = "0";
		}
	
	
		//hide all the previous "saved result" messages...
		var divs = document.getElementsByTagName('div');
		for(var i = 0; i < divs.length; i++){ 
			if(divs[i].id.indexOf("saveResult_", 0) > -1){
				divs[i].innerHTML = "&nbsp;";
			}
		} 	
	
		//show working message in the appropriate <html> element...
		resultsDiv = document.getElementById(showResultsInElementId)
		resultsDiv.innerHTML = "saving entries...";
		resultsDiv.className = "submit_working";
		
		//make sure jQuery is loaded...
		if(jQuery) {  
			
			//vars for the form, it's fields, and it's values...
			var $form = $("#frmMain"),
			
			//select and cache all form fields...
			$inputs = $form.find("input, select, button, textarea"),
			
			//serialize the data in the form...
			serializedData = $form.serialize();
		
			//disable the inputs while we wait for results...
			$inputs.attr("disabled", "disabled");
			
			//POST the ajax request...
		   $.ajax({
				url: "bt_theme_AJAX.php",
				type: "post",
				data: serializedData,
				
				//function that will be called on success...
				success:function(response, textStatus, jqXHR){
					
					//show response...
					if(response == "invalid request"){
						resultsDiv.className = "submit_working";
					}else{
						resultsDiv.className = "submit_done";
					}
					
					//show below the save button and re-load the JSON in the advanced box?
					if(showResultsInElementId != "saveResult_advanced"){
						
						//results will include the "saved" message AND the json data to re-populate the "advanced edit" box...
						var parts = response.split("^");
						resultsDiv.innerHTML = parts[0];
						document.forms[0].advanced.value = parts[1];
						
					}else{
						resultsDiv.innerHTML = response;
					}
					
					
				},
				
				//function that will be called on error...
				error: function(jqXHR, textStatus, errorThrown){
					resultsDiv.className = "submit_working";
					resultsDiv.innerHTML = "A problem occurred while saving the entries (2)";
				},
				
				//function that will be called on completion (success or error)...
				complete: function(){
					$inputs.removeAttr("disabled");
				}
			});		  
		
		}else{
			//jQuery is not loaded?
			resultsDiv.innerHTML = "jQuery not loaded?";
		}		
		
		
		
	}
	
	
