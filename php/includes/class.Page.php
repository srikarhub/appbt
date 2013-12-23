<?php class Page{
	
	public $docType = "";			//defaults to transitional (see __contruct function)
	public $contentType = "";		//defaults to text/html
	public $pageTitle = "";			//shows in browser	
	public $description = ""; 		//page description
	public $keywords = ""; 			//comma separated keywords
	public $cssIncludes = "";		//comma separted css file urls
	public $cssInHead = "";			//ex: .body{width:95%;}, don't include the <style> tags
	public $jsInHead = "";			//ex: alert('here'); DO NOT INCLUDE THE SCRIPT TAG
	public $scriptsInHeader = "";	//comma separated javascript file urls to include in header
	public $jsRelativeInHead = "";	//comma separated javascript file relative URL's to include in header
	public $scriptsInFooter = "";	//comma separated javascript file urls to include in footer
	public $customHeaders = "";		//comma list of custom headers inside <head> tag
	public $customBody = "";		//html to insert into body tag
	public $includeForm = "";		//0 means no form, 1 means include form
	public $formId = "";			//every page has a form. 
	public $formAction = "";		//what page does form post to 
	public $formMethod = "";		//POST or GET
	public $formEncType = "";		//application/x-www-form-urlencoded, change for file uploads 
	public $formJavaScript = "";	//custom javascript to add to form tag 
	public $shadowBoxJS = "";		//custom shadowbox javascript to add to head tag
	
	
	function __construct(){
		if($this->docType == "") $this->docType = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"https://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\" >";
		if($this->contentType == "") $this->contentType = "text/html; charset=utf-8";
		if($this->pageTitle == "") $this->pageTitle = APP_DEFAULT_PAGE_TITLE;
		if($this->description == "") $this->description = APP_DEFAULT_PAGE_DESCRIPTION;
		if($this->keywords == "") $this->keywords = APP_DEFAULT_KEYWORDS;
		if($this->includeForm == "") $this->includeForm = "1";
		if($this->formId == "") $this->formId = "frmMain";
		if($this->formAction == "") $this->formAction = $_SERVER['PHP_SELF'];
		if($this->formMethod == "") $this->formMethod = "post";
		if($this->formEncType == "") $this->formEncType = "application/x-www-form-urlencoded";
	}
	  
	function fnGetPageHeaders(){
		
		//used when building asset paths...
		$tmpPath = fnGetSecureURL(rtrim(APP_URL, "/"));
		
		//docType
		$r = $this->docType;
		
		//start HTML tag
		$r .= "\n<html xmlns=\"https://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" >";
		
			
			//start header tag
			$r .= "\n<head>";
				
				//page title
				$r .= "\n<title>" . $this->pageTitle . "</title>";
				
				//contentType
				$r .= "\n<meta http-equiv=\"Content-Type\" content=\"" . $this->contentType . "\" />";
				
				//keywords
				$r .= "\n<meta name=\"Keywords\" content=\"" . $this->keywords . "\" />";
				
				//description
				$r .= "\n<meta name=\"Description\" content=\"" . $this->description . "\" />";
				
				//mobile device scrolling..
				//$r .= "\n<meta name=\"viewport\" content=\"width=device-width; initial-scale=1.0; minimum-scale=0.35; maximum-scale=1.6; user-scalable=1;\"/>";
				
				//IE hacks for imagetoolbars, rounded corners, etc...
				$r .= "\n<meta http-equiv=\"imagetoolbar\" content=\"no\" />";
				$r .= "\n<meta http-equiv=\"imagetoolbar\" content=\"false\" />";
				$r .= "\n<meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\" />";
				
				//shortcut icon (all pages get this)...
				$r .= "\n<link rel=\"shortcut icon\" href=\"" . $tmpPath . "/" . ltrim(APP_THEME_PATH, "/") . "/favicon.png\" />";
				
				//custom headers
				$tmp = explode(",", $this->customHeaders);
				for($x = 0; $x < count($tmp); $x++){
					if(trim($tmp[$x]) != "") $r .= "\n" . trim($tmp[$x]);
				}	
				
				//standard style sheets (all pages get these)...
				$r .= "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $tmpPath . "/" . ltrim(APP_THEME_PATH, "/") . "/style.css\" />";
				$r .= "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $tmpPath . "/scripts/shadowbox-build-3.0b/shadowbox.css\" />";
				$r .= "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $tmpPath . "/scripts/shadowbox-build-3.0b/shadowbox.skin2.css\" />";

				//additional style sheets (added at page level)...
				$tmp = explode(",", $this->cssIncludes);
				for($x = 0; $x < count($tmp); $x++){
					if(trim($tmp[$x]) != "") $r .= "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $tmpPath . "/" . trim($tmp[$x]) . "\" />";
				}	
				
				//standard scripts in <head> (all pages get these)...
				$r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/scripts/utilities.js\"></script>";
				$r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/scripts/swfobject.js\"></script>";
				$r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/scripts/shadowbox-build-3.0b/shadowbox.js\"></script>";
				$r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/scripts/shadowbox-build-3.0b/shadowbox.skin2.js\"></script>";
				
				//additional scripts in <head> (included at page level)...
				$tmp = explode(",", $this->scriptsInHeader);
				for($x = 0; $x < count($tmp); $x++){
					if(trim($tmp[$x]) != "") $r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/" . trim($tmp[$x]) . "\"></script>";
				}	
				
				//js include in <head>, with the <script> tag, not relative...
				if($this->jsRelativeInHead != ""){
					$tmp = explode(",", $this->jsRelativeInHead);
					for($x = 0; $x < count($tmp); $x++){
						if(trim($tmp[$x]) != "") $r .= "\n<script type=\"text/javascript\" src=\"" . trim($tmp[$x]) . "\"></script>";
					}
				}
				
				//js code in <head>, inline, without the <script> tag...
				if($this->jsInHead != ""){
					$r .= "\n<script type=\"text/javascript\">" . $this->jsInHead . "</script>";
				}
				
				
				//shadow box initialization
				if($this->shadowBoxJS == ""){
					$r .= "\n<script type=\"text/javascript\">Shadowbox.init({players:[\"img\", \"iframe\"]});</script>";
				}else{
					$r .= $this->shadowBoxJS;
				}
				
				//extra inline styles
				if($this->cssInHead != ""){
					$r .= "\n\n<style type=\"text/css\">";
					$r .= "\n\* additional inline styles *\\";
					$r .= "\n" . $this->cssInHead . "";
					$r .= "\n</style>";
				}

				
		//end header tag
		$r .= "\n</head>";
		
		return $r;
	
	}  
	
	//body tag
	function fnGetBodyStart(){
		$r = "\n\n<!-- class.Page.fnGetBodyStart -->";
		if($this->customBody != ""){
			$r = "\n\n<body " . $this->customBody . " >";
		}else{
			$r = "\n\n<body>";
		}
	
		//start form tag
		if($this->includeForm == "1"){
			$r .= "\n<form id=\"" . $this->formId . "\" action=\"" . $this->formAction . "\" method=\"" . $this->formMethod . "\" enctype=\"" . $this->formEncType . "\" " . $this->formJavaScript . " >";
		}
	
	
		//start content wrappers
		$r .= "\n\n<!-- begin outer content wrap -->";
		$r .= "\n<div class='contentWrapper'>";
		$r .= "\n\n<!-- begin innter content wrap -->";
		$r .= "\n<div class='contentWrap'>\n\n\n";
	
		return $r;
	}
	

	  
	  
	//returns top right HTML with login or logout links as needed
	function fnGetTopNavBar($guid = ""){
		
		$logOutName = "";
		$isLoggedIn = false;
		$userType = "";
		if($guid != ""){
			$logOutName = "";
			$strSql = "SELECT id AS userId, firstName, lastName, userType ";
			$strSql .= " FROM " . TBL_USERS . " AS U ";
			$strSql .= " WHERE U.guid =  '" . $guid . "' AND isLoggedIn = 1 ";
			$strSql .= " LIMIT 0, 1";
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($res){
				$numRows = mysql_num_rows($res);	
				if($numRows == 1){
					$row = mysql_fetch_array($res);
					$isLoggedIn = true;
					$logOutName = fnFormOutput($row['firstName']);
					$userType = trim($row['userType']);
				}//num rows
			}//if res
		}


		//used when building asset paths...
		$tmpPath = fnGetSecureURL(rtrim(APP_URL, "/"));

		//accountLinks?
		$accountLinks = "";
		if($isLoggedIn){
			$accountLinks .= "<a href='" . $tmpPath . "/account/' title='Account' class='topRightLink'>" . $logOutName . "'s Account</a>";
			if(strtoupper($userType) == "ADMIN"){
				$accountLinks .= "&nbsp | &nbsp;";
				$accountLinks .= "<a href='" . $tmpPath . "/admin/' title='Admin' class='topRightLink'>Admin</a>";
			}
			$accountLinks .= "&nbsp | &nbsp;";
			$accountLinks .= "<a href='" . $tmpPath . "/?logOut=1' title='logout' class='topRightLink'>Logout</a>";
			
		}


		$r = "\n\n<!-- class.Page.fnGetTopNavBar -->";
		$r .= "\n<div class='topNavBar colorLightBg'>";
			$r .= "\n<table cellspacing='0' cellpadding='0' class='topNavBarTable'>";
				$r .= "\n<tr>";
					$r .= "\n<td class='logo'>";
					
						$r .="\n<a href='" . $tmpPath . "' title='" . APP_APPLICATION_NAME . "'><img src='" . $tmpPath . "/" . ltrim(APP_THEME_PATH, "/") . "/logo.png' alt='logo' /></a>";

					$r .= "\n</td>";
					$r .= "\n<td class='logout'>";
						
						//logout
						$r .= "\n" . $accountLinks;
							
					$r .= "\n</td>";
				$r .= "\n</tr>";
			$r .= "\n</table>";
		$r .= "\n</div>";
		$r .= "\n<!-- end class.Page.fnGetTopNavBar -->";
		$r .= "\n\n";

    	return $r;
	}  
	
	//returns footer nav bar with optional html inside....
	function fnGetBottomNavBar($optionalHTML = ""){
		
		$r = "\n\n<!-- class.Page.fnGetBottomNavBar -->";
			$r .= "\n<div class='bottomNavBar'>";
				
				//links / text in lower left...
				$r .= "\n<div class='bottomNavBarLeft'>";

				$r .= "\n</div>";
				
				//links / text in lower right...
				$r .= "\n<div class='bottomNavBarRight'>";
					$r .= "\n<a href='http://www.buzztouch.com' target='_blank' style='color:#FF822E;'><i>powered by</i> Buzztouch<i>&trade; v" . APP_CURRENT_VERSION . "</i></a>";
				$r .= "\n</div>";
				
				$r .= "<div style='clear:both;'></div>";
			$r .= "\n</div>";

			
			//clear all
			$r .= "<div style='clear:both;'></div>";
			
			
		$r .= "\n\n<!-- end class.Page.fnGetBottomNavBar -->";
		return $r;
	}  
	
	function fnGetBodyEnd(){
		$r = "";
		
		//used when building asset paths...
		$tmpPath = fnGetSecureURL(rtrim(APP_URL, "/"));
		
		//end content wrapper
		$r .= "\n";
		$r .= "\n<!--end inner content wrap-->";
		$r .= "\n</div>";
		
		$r .= "\n";
		$r .= "\n<!--end outer content wrap-->";	
		$r .= "\n</div>";
	
		//end form
		$r .= "\n\n";
		$r .= "<!-- end form -->";
		if($this->includeForm == "1") $r .= "\n</form>";
	
		//footer scripts...
		$r .= "\n\n";
		$r .= "<!-- footer scripts -->";
		
		//scripts in footer (all pages get these)...
		$r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/scripts/footer.js\"></script>";
		
		//additional scripts in footer (included at page level)...
		$tmp = explode(",", $this->scriptsInFooter);
		for($x = 0; $x < count($tmp); $x++){
			if(trim($tmp[$x]) != "") $r .= "\n<script type=\"text/javascript\" src=\"" . $tmpPath . "/" . trim($tmp[$x]) . "\"></script>";
		}	
				
		//end body
		$r .= "\n\n";
		$r .= "<!-- end body, html -->";
		$r .= "\n</body>";
		
		//end html
		$r .= "\n</html>";
		return $r;
	}	


	//gets links for control panel (left side or along the top)...
	function fnGetControlPanelLinks($linkType = "", $appGuid = "", $display = "block", $viewStyle = ""){
		
		//returns links...
		$r = "";
		
		//used when building asset paths...
		$tmpPath = fnGetSecureURL(rtrim(APP_URL, "/"));

		//get links for the appropriate control panel menu...
		if($linkType != ""){
			$strSql = "SELECT linkType, linkLabel, linkURL, linkTarget ";
			$strSql .= " FROM " . TBL_CP_LINKS;
			$strSql .= " WHERE linkType = '" . $linkType . "'";
			$strSql .= " ORDER BY orderIndex ";
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($res){
				$cnt = 0;
				$numRows = mysql_num_rows($res);
				while($row = mysql_fetch_array($res)){
					$cnt++;
					
					//append the appGuid if we have one...
					$url = fnFormOutput($row["linkURL"]);
					
					//append appGuid if we have one...
					if($appGuid != ""){
						$url .= "?appGuid=" . $appGuid;
					}
					
					//user created links may be outside the app...
					$pos = strpos($url, "http");
					if($pos === false){
						$url = $tmpPath . "/" . ltrim($url, "/");
					}
					
					//publish link gets a red or green dot...
					$pubDot = "";
					if($row["linkURL"] == "/bt_v15/bt_app/bt_appVersion.php"){
						
						//compare modified to published date...
						$objCompareApp = new App($appGuid);
						$pubDot = "<img id=\"publishDot\" src='" . $tmpPath . "/images/green_dot.png' style='vertical-align:middle;margin:0px;margin-bottom:2px;margin-left:5px;'>";
						if($objCompareApp->infoArray["modifiedUTC"] != $objCompareApp->infoArray["currentPublishDate"]){
							$pubDot = "<img id=\"publishDot\" src='" . $tmpPath . "/images/red_dot.png' style='vertical-align:middle;margin:0px;margin-bottom:2px;margin-left:5px;'>";
						}
						
					}				
					
					//display (block or inline)
					if(strtoupper($display) == "BLOCK"){
						$r .= "\n<div style='white-space:nowrap;'><a href='" . $url . "' target='" . fnFormOutput($row["linkTarget"]) . "' title='" . fnFormOutput($row["linkLabel"]) . "' style='white-space:nowrap;'><img src='" . $tmpPath . "/images/arr_right.gif' alt='arrow'/>" . fnFormOutput($row["linkLabel"]) . "</a>" . $pubDot . "</div>";
					}else{
						$r .= "\n<span style='white-space:nowrap;'><a href='" . $url . "' target='" . fnFormOutput($row["linkTarget"]) . "' title='" . fnFormOutput($row["linkLabel"]) . "' style='white-space:nowrap;'>" . fnFormOutput($row["linkLabel"]) . "</a>" . $pubDot . "</span>";
						if($cnt < $numRows){
							$r .= "&nbsp;&nbsp;|&nbsp;&nbsp;";
						}
					}
				
				}
			}//end res
		}//guid = ""
		
		//return...
		return $r;
	}	
	
	  
} //end class
?>