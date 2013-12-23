
	function fnShowHide(){
		try{
			if(document.getElementById("addPluginBox").style.display == "none"){
				document.getElementById("addPluginBox").style.display = "block";
				document.getElementById("dataBox").style.display = "none";
				document.getElementById("headerBox").style.display = "none";
			}else{
				document.getElementById("addPluginBox").style.display = "none";
				document.getElementById("dataBox").style.display = "block";
				document.getElementById("headerBox").style.display = "block";
			}
		}catch(er){
			//ignore...
		}
	}
	function fnAddScreen(){
		try{
			var theForm = document.forms[0];
			var bolPassed = true;
			var strMessage = "";
			document.getElementById("addScreenMessage").style.visibility = "visible";
			if(theForm.addNickname.value == "" || theForm.addNickname.value == "Nickname..."){
				bolPassed = false;
				strMessage += "Enter a nickname. ";
			}
			if(theForm.addPluginUniqueId.options[theForm.addPluginUniqueId.selectedIndex].value == ""){
				bolPassed = false;
				strMessage += "Choose a plugin type. ";
			}
			if(!bolPassed){
				document.getElementById("addScreenMessage").innerHTML = "Not Added: " + strMessage;
			}else{
				document.getElementById("addScreenMessage").innerHTML = "working, please wait...";
				theForm.command.value = "addItem";
				theForm.submit();
			}
		}catch(er){
			//ignore...	
		}
	}
