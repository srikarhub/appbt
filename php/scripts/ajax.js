

/* AJAX Functions */

//returns XML object, different for different broswers.
function getXMLHTTPObject(){
		// code for IE
		if(window.ActiveXObject){
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			xmlhttp.onreadystatechange = AjaxStateChange;
			return xmlhttp;
		}
		// code for Mozilla, etc.
		if(window.XMLHttpRequest){
			xmlhttp=new XMLHttpRequest();
			xmlhttp.onreadystatechange = AjaxStateChange;
			return xmlhttp;
		}
		//returns null if no xml avail.
		return null;

}
//on state change
function AjaxStateChange(){
	if(objhttp.readyState==4){
		try{
			var theHeaders = objhttp.getAllResponseHeaders();
			var theStatusInteger = objhttp.status;
			var theStatus = objhttp.statusText;
			var theText	= objhttp.responseText;
			if(theStatusInteger == 200){
				//process result function in HEAD of including page
				fnProcessResult(theText);
			}else{
				//do nothing...
			}
		}catch(er){
			//do nothing...
		}
	}
	return;

}

//sends request
function fnSendAjaxRequest(url,data,method){
	//data is key=value pair with & in between, use the fnGetFormValues function to build it
	//from form elements, else, pass in a querystring.
	// get XMLHTTP object
    objhttp = getXMLHTTPObject();
    // set default values
    if(!url){url='about:blank'};
    if(!data){data='ajaxRequest=true'};
    if(!method){method='GET'};
   // open socket connection in asynchronous mode
    objhttp.open(method,url,true);
    // send header
	if(method == "POST"){
		objhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    }
	// send data
    objhttp.send(data);
    // return xmlhttp object
    return objhttp;
}
//gets all form element names and values, sends with request
function fnGetFormValues(){
    var form = document.forms[0];
	var d = new Date();
	var uid = d.getSeconds();
	var str='ajaxRequest=true&secs=' + uid + '&';
	for(var i=0;i< form.elements.length;i++){
        str+=form.elements[i].id+'='+ escape(form.elements[i].value)+'&';
    }
    str=str.substr(0,(str.length-1));
	return str;
}    

////////////////////////////////////

