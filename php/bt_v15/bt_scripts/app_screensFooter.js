
try{
	
	//show the search type as selected if we have a selected item (value comes from calling page)...
	if(searchPluginTypeUniqueId != ""){
		var theEl = document.forms[0].searchPluginTypeUniqueId;
		for(var x = 0; x < theEl.options.length; x++){
			var theVal = theEl.options[x].value;
			if(theVal == searchPluginTypeUniqueId){
				document.forms[0].searchPluginTypeUniqueId.options[x].selected = true;
			}
		}
	}
	
	
	//show the previous plugin as selected if we have a selected item (value comes from calling page)...
	if(addPluginUniqueId != ""){
		var theEl = document.forms[0].addPluginUniqueId;
		for(var x = 0; x < theEl.options.length; x++){
			var theVal = theEl.options[x].value;
			if(theVal == addPluginUniqueId){
				document.forms[0].addPluginUniqueId.options[x].selected = true;
			}
		}
	}

}catch(er){
	//ignore error...	
}