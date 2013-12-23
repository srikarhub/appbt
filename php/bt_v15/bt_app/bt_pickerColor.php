<?php   require_once("../../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnUpdateLastRequest($guid);

	//init page object
	$thisPage = new Page();
	
	//javascript files in header...
	$thisPage->scriptsInHeader = "bt_v15/bt_scripts/app_utilities.js";	

	//add some inline css (in the <head>) for 100% width...
	$inlineCSS = "";
	$inlineCSS .= "html{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= "body{height:100%;width:100%;margin:0px;padding:0px;} ";
	$inlineCSS .= ".contentWrapper, .contentWrap{height:100%;width:100%;margin:0px;padding:0px;} ";
	$thisPage->cssInHead = $inlineCSS;


	//javascript inline in head section...
	$thisPage->jsInHead = "";

	$headers = $thisPage->fnGetPageHeaders();	
	$bodyStart = $thisPage->fnGetBodyStart();
	$topNavBar = $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	$bottomNavBar = $thisPage->fnGetBottomNavBar();
	$bodyEnd = $thisPage->fnGetBodyEnd();

	//form element to insert color into (from opener)
	$formElVal = fnGetReqVal("formElVal", "", $myRequestVars);

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	
?>

<script type='text/javascript'>
	function returnColor(color){
		try{
			parent.document.forms[0].<?php echo $formElVal;?>.value = color;
			parent.Shadowbox.close();
		}catch(er){
		}
	}
</script>

<div class='content'>

    <fieldset class='colorLightBg minHeightShadowbox' style='-moz-border-radius:0px;border-radius:0px;'>
    
       	<div class='contentBox colorLightBg' style='-moz-border-radius:0px;border-radius:0px;border-bottom:0px;'>
        
            <div class="contentBand colorBandBg" style='-moz-border-radius:0px;border-radius:0px;'>
                    Select a Color (or a keyword)
            </div>
            <div style='padding:10px;'>
            
					<div style="padding:10px;">
						
                        <div style='margin-bottom:10px;'>
							For backgrounds you can also select two special keywords:
							<a href="#" onclick="returnColor('clear');">clear</a>					   
							or
							<a href="#" onclick="returnColor('stripes');">stripes</a> for a native iOS look</a>
						</div>
	
						<script language="javascript">
						  fixed="B";
						  left="R";
						  upper="G";
						  value=Array;
						  value[0]="N/A";
						  value[1]="00";
						  value[2]="33";
						  value[3]="66";
						  value[4]="99";
						  value[5]="CC";
						  value[6]="FF";
						
						
						  for (fixedamount=1; fixedamount<=6;fixedamount=fixedamount+1)
							{
							  fixedvalue=value[fixedamount];
							  document.write("<table border='0' style='width:100%;background-color:transparent;'><tr><td>"); 
							
							  for (row=0; row<=6;row=row+1)
								 { document.write("<tr>");
								  for (table=1; table<=3; table=table+1)
									{
									  if (table==1) {fixed="B"; left="R";  upper="G";}
									  if (table==2) {fixed="R"; left="G";  upper="B";}
									  if (table==3) {fixed="G"; left="B";  upper="R";}
									
									  for (column=0; column<=6; column=column+1)
									  {
									   if (fixed=="R") {redvalue=fixedvalue; fixedcolor="#CC0000"};
									   if (fixed=="G") {greenvalue=fixedvalue; fixedcolor="#009900"};
									   if (fixed=="B") {bluevalue=fixedvalue; fixedcolor="#0000CC"};
									   if (left=="R") {redvalue=value[row]; leftcolor="#CC0000"};
									   if (left=="G") {greenvalue=value[row]; leftcolor="#009900"};
									   if (left=="B") {bluevalue=value[row]; leftcolor="#0000CC"};
									   if (upper=="R") {redvalue=value[column]; uppercolor="#CC0000"};   
									   if (upper=="G") {greenvalue=value[column]; uppercolor="#009900"};   
									   if (upper=="B") {bluevalue=value[column]; uppercolor="#0000CC"};   
									
									   if (column==0 && row==0) {document.write("<td bgColor="+fixedcolor+"><font face='Verdana' size=1 color='#FFFFFF' size='1'>" + value[fixedamount] + "</font></td>")};
									   if (column==0 && row!=0) {document.write("<td bgColor="+leftcolor+"><font face='Verdana' size=1 color='#FFFFFF'  size='1'>" + value[row] + "</font></td>")};
									   if (column!=0 && row==0) {document.write("<td bgColor="+uppercolor+"><font face='Verdana' size=1 color='#FFFFFF' size='1'>" + value[column] + "</font></td>")};
									   styleclause="cursor:pointer;width:20;height:20;background-color:#" + redvalue + greenvalue + bluevalue + ";";
									   onclickclause="returnColor(\"#" + redvalue + greenvalue + bluevalue +  "\");";					   
									   if (column != 0 && row != 0){document.write("<td><button value=' ' style='" + styleclause + "' onClick='" + onclickclause + "' title='#" + redvalue + greenvalue + bluevalue + "'></td>")};
									  } if (table < 3) {document.write("<td bgColor=#FFFFF4>&nbsp;&nbsp;&nbsp;&nbsp;</td>")};
									 }
								 document.write("</font></tr>");
								 }  
							 document.write("</td></tr></table>");
						}
					</script>

				</div>
    
            </div>
    	</div>
    </fieldset>

</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
