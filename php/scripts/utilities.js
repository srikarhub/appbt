

//#############################################################
function fnSearch(theDoc, theVal){
	try{
		theDoc.forms[0].currentPage.value = 1;
	}catch(er){
	 //do nothing
	}
	try{
		theDoc.forms[0].searchInput.value = theVal;
		fnRefresh(theDoc);
	}catch(er){
		alert(er.description);//do nothing
	}
}

//#############################################################
function fnClearSearch(theVal,theEl){
	try{
		if(theEl.value == theVal){
			theEl.value = "";
			theEl.focus();
		}
	}catch(er){
		alert(er.description);//do nothing
	}
}

//#############################################################
function fnSort(theDoc, theCol){
	try{
		theDoc.forms[0].sortColumn.value = theCol;
		theDoc.forms[0].currentPage.value = 1;
		if(theDoc.forms[0].sortUpDown.value == "DESC"){
			theDoc.forms[0].sortUpDown.value = "ASC"
		}else{
			theDoc.forms[0].sortUpDown.value = "DESC"
		}
		fnRefresh(theDoc);
	}catch(er){
		alert(er.description);//do nothing
	}
}

//#############################################################
function fnRefresh(theDoc){
	try{
		if(theDoc.getElementById('noRefresh')){
			return;
		}else{
			theDoc.forms[0].submit();
		}
	}catch(er){
		alert(er.description);//do nothing
	}
}	

//#############################################################
function fnWriteAlphabet(theDoc, theSearchVal){
	theSearchVal = theSearchVal.toUpperCase();
	var alpha = new Array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
	for(var i = 0; i < alpha.length; i++){
		if(theSearchVal == alpha[i]){
			theDoc.write("<td class=\"alphabetOn\" style='border-right:0px;' onclick=\"top.fnSearch(document,'" + alpha[i] + "');\" onmouseover=\"this.className='alphabetOff'\" onmouseout=\"this.className='alphabetOn'\" title=' Show all, no search'>" + alpha[i] + "</td>");
		}else{
			theDoc.write("<td class=\"alphabetOff\" style='border-ight:0px;' onclick=\"top.fnSearch(document,'" + alpha[i] + "');\" onmouseover=\"this.className='alphabetOn'\" onmouseout=\"this.className='alphabetOff'\" title=' Search starting with " + alpha[i] + " '>" + alpha[i] + "</td>");
		}					
	}
}

//#############################################################
function fnCheckAll(theDocument){
	try{
		var frm = null; 
		frm = theDocument.document.forms[0];
		var onOff = frm.checkAll.checked;
		for(i=0;i<frm.elements.length;i++){
			if(frm.elements[i].type == 'checkbox'){
				frm.elements[i].checked = onOff;
			}
		}
	}catch(er){
		//do nothing
	}
}









