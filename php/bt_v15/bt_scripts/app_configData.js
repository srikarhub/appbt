

function getConfigData(){
	if(getConfigDataURL != ""){
		if(window.XMLHttpRequest){
			xmlhttp = new XMLHttpRequest();
		} else {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		}
		xmlhttp.onreadystatechange=function(){
			if(xmlhttp.readyState == 4 && xmlhttp.status == 200){
				var result = xmlhttp.responseText;
				var result = xmlhttp.responseText;
				document.getElementById("isLoading").style.display = "none";
				document.getElementById("doneLoading").style.display = "block";
				document.getElementById("configDataResults").innerHTML = result;
			}
		}
		xmlhttp.open("GET",getConfigDataURL, true);
		xmlhttp.send();
	}
}

function startConfigDownload(theURL){
	getConfigDataURL = theURL;
	document.getElementById("isLoading").style.display = "block";
	document.getElementById("doneLoading").style.display = "none";
	window.setTimeout(getConfigData, 1000, true);
	return;
}







