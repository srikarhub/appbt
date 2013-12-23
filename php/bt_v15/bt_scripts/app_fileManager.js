	
	
	function fnUploadBT_item(){
		document.getElementById("uploadButton").disabled = true;
		document.getElementById("isLoadingImage").style.visibility = "visible";
		document.getElementById("isLoadingText").style.visibility = "visible";
		document.forms[0].command.value = 'uploadFile';
		document.forms[0].submit();	
	}
	function fnConfirmDelete(){
		document.forms[0].command.value = 'confirmDelete';
		document.forms[0].submit();	
	}	
	function fnCancelDelete(){
		document.forms[0].command.value = '';
		document.forms[0].submit();	
	}	
	
	//fills div with selected file value, runs in loop..
	var theTimer = null;
	function fileUploadLabel(){
		var theValueEl = document.getElementById("fileUpload");
		var theDisplayEl = document.getElementById("fileUploadValue");
		if(theValueEl.value != ""){
			theDisplayEl.value = theValueEl.value;
		}
		theTimer = setTimeout("fileUploadLabel()", 100);
	}	
	
