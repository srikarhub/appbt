<?php 

/*
	PHP Utility Functions
*/	

//###################################################################################
//database utilities
$MYSQL_ERRNO = 0;
$MYSQL_ERROR = "";
function fnSqlError(){
	global $MYSQL_ERRNO, $MYSQL_ERROR;
	if(empty($MYSQL_ERROR)){
		$MYSQL_ERRNO = mysql_errno();
		$MYSQL_ERROR = mysql_error();
	}
	if($MYSQL_ERRNO == "1044"){ //db name doesn't exist..
		return "Cannot find database. " . $MYSQL_ERROR;
	}else{
		return "Database Error!<br/>Error #:" . $MYSQL_ERRNO . "<br/>Error: " . $MYSQL_ERROR;
	}
}


//Db Processes.
function db_connect($host, $db, $user, $pass){
	$conn  = mysql_connect($host, $user, $pass);
	
	if(!$conn){
		if(APP_ERROR_REPORTING > 0){
			die("mysql_connect, connection.<br>" . fnSqlError());
		}else{
			die("An error ocurred in the db_connect() method in utilityFunctions.php (1)");
		}
	}else{
		if(!mysql_select_db($db)){
			if(APP_ERROR_REPORTING > 0){
				die("mysql_connect, connection.<br>" . fnSqlError());
			}else{
				die("An error ocurred in the db_connect() method in utilityFunctions.php (2)");
			}				
		}else{
		
			mysql_query ('SET NAMES utf8', $conn);
			return $conn;
		
		}
	}
}

//returns one string.
function fnGetOneValue($strSql, $host, $db, $user, $pass){
		$r = "";
		$row = NULL;
		$result = fnDbGetResult($strSql, $host, $db, $user, $pass);
		if($result){
			$row = mysql_fetch_row($result);
			$r = $row[0];
		}
		if($r == NULL){
			return "";
		}else{
			return $r;
		}
}

// return query.
function fnDbGetResult($theSql, $host, $db, $user, $pass){
	
	$conn = db_connect($host, $db, $user, $pass);
	if(!$conn ){
		if(APP_ERROR_REPORTING > 0){
			die("fnDbGetResult, connection.<br>" . fnSqlError());
		}else{
			die("An error ocurred in the fnDbGetResult() method in utilityFunctions.php (3)");
		}
	}else{ // fetch array
		
		$result = mysql_query($theSql, $conn);
		if($result){
			return $result;
		}else{
			if(APP_ERROR_REPORTING > 0){
				die("<hr>" . $theSql . "<hr>" . fnSqlError());
			}else{
				die("An error ocurred in the fnDbGetResult() method in utilityFunctions.php (4)");
			}
		}			
	} // end if connected
}
// execute sql, DOES NOT return Id .
function fnExecuteNonQuery($theSql, $host, $db, $user, $pass){
	$conn = db_connect($host, $db, $user, $pass);
	if(!$conn ){
		if(APP_ERROR_REPORTING > 0){
			die("fnExecuteNonQuery, connection.<br>" . fnSqlError());
		}else{
			die("An error ocurred in the fnExecuteNonQuery() method in utilityFunctions.php (5)");
		}
	}else{ // fetch array
		
		$result = mysql_query($theSql, $conn);
		if($result){
			return 1;
		}else{
			if(APP_ERROR_REPORTING > 0){
				die("fnExecuteNonQuery :: fnDbGetResult (get result)<br>" . fnSqlError());
			}else{
				die("An error ocurred in running the fnExecuteNonQuery() method in utilityFunctions.php (6)");
			}
		}
	} // end if connected
}

// execute sql, RETURN Id of newly created record.
function fnInsertReturnId($theSql, $host, $db, $user, $pass){
	$conn = db_connect($host, $db, $user, $pass);
	if(!$conn ){
		if(APP_ERROR_REPORTING > 0){
			die("fnExecuteReturnId, connection.<br>" . fnSqlError());
		}else{
			die("An error ocurred in running the fnInsertReturnId() method in utilityFunctions.php (7)");
		}
	}else{ // fetch array
		$result = mysql_query($theSql, $conn);
		if($result){
			$tmp = mysql_insert_id();
			return $tmp;
		}else{
			if(APP_ERROR_REPORTING > 0){
				die("fnInsertReturnId :: fnDbGetResult (get result)<br>" . fnSqlError());
			}else{
				die("An error ocurred in running the fnInsertReturnId() method in utilityFunctions.php (8)");
			}
		}
	} // end if connected
}
/* end db work */


/* end MYSQL functions */
//###################################################################################


//###################################################################################
//generic form processing. $myRequestVars holds all varialbes in $_GET or $_POST
$isFormPost = false;
$reqMethod = $_SERVER['REQUEST_METHOD'];
$myRequestVars = array();
switch (strtoupper($reqMethod)){
	case "GET":
		foreach($_GET as $key => $value){
			$myRequestVars[$key] = fnFormInput($value);
		}
		break;
	case "POST":
		$isFormPost = true;
		foreach($_POST as $key => $value){
			$myRequestVars[$key] = fnFormInput($value);
		}
		break;
	default:
		foreach($_GET as $key => $value){
			$myRequestVars[$key] = fnFormInput($value);
		}
		break;
}

//returns value from request, or passed in default val.
function fnGetReqVal($theVariable, $theDefaultVal, $myRequestVars){
	if(array_key_exists($theVariable, $myRequestVars)){
		return $myRequestVars[$theVariable];
	}else{
		return fnFormOutput($theDefaultVal);	
	}
}
//###################################################################################

//sets a vatiable to default. Used instead of isset() to prevent E_Notice warnings..
function fnGetJsonProperyValue($propertyName, $jsonString){
	$ret = "";
	if($jsonString != ""){
		$json = new Json; 
		$decoded = $json->unserialize($jsonString);
		if(is_object($decoded)){
			foreach ($decoded as $key => $value){
				if(strtoupper($key) == strtoupper($propertyName)){
					return $value;
					break;
				}
			}
		}
	}	
	return $ret;
}		

function fnDebugVars(){
	$reqMethod = $_SERVER['REQUEST_METHOD'];
	switch (strtoupper($reqMethod)){
		case "GET":
			echo "GET";
			foreach($_GET as $key => $value){
				echo "<br>" . $key . ":" . $value;
			}
			break;
		case "POST":
			echo "POST";
			foreach($_POST as $key => $value){
				echo "<br>" . $key . ":" . $value;
			}
			break;
		default:
			echo "GET";
			foreach($_GET as $key => $value){
				echo "<br>" . $key . ":" . $value;
			}
			break;
	}

}


//returns array of allowed mime-types for uploading...
function fnGetAllowedUploadMimeTypes(){
	$allowed_file_types = array();
	$allowed_file_types[] = array("image/pjpeg", "jpg");
	$allowed_file_types[] = array("image/pjpeg", "jpeg");
	$allowed_file_types[] = array("image/jpeg", "jpg");
	$allowed_file_types[] = array("image/jpg", "jpg");
	$allowed_file_types[] = array("image/png", "png");
	$allowed_file_types[] = array("application/pdf", "pdf");
	$allowed_file_types[] = array("application/zip",  "zip");
	$allowed_file_types[] = array("application/x-gzip", "zip");
	$allowed_file_types[] = array("audio/mpeg", "mp3");
	$allowed_file_types[] = array("audio/x-aiff", "aif");
	$allowed_file_types[] = array("audio/x-mpegurl", "mov");
	$allowed_file_types[] = array("video/quicktime", "mov");
	$allowed_file_types[] = array("video/mp4", "mp4");
	$allowed_file_types[] = array("video/mpeg", "mov");
	$allowed_file_types[] = array("text/html", "html");
	$allowed_file_types[] = array("text/plain", "txt");
	$allowed_file_types[] = array("text/plain", "csv");
	$allowed_file_types[] = array("text/plain", "php");
	$allowed_file_types[] = array("application/msword", "doc");
	$allowed_file_types[] = array("application/vnd.openxmlformats-officedocument.wordprocessingml.document", "docx");
	$allowed_file_types[] = array("application/vnd.ms-excel", "xls");
	$allowed_file_types[] = array("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", "xlsx");
	$allowed_file_types[] = array("application/vnd.ms-powerpoint", "ppt");
	$allowed_file_types[] = array("application/vnd.openxmlformats-officedocument.presentationml.presentation", "pptx");
	return $allowed_file_types;
}

//returns mime-type of file from a extenstion for downloading
function fnGetMimeTypeFromExtention($fileExtension){
	$ret = "";
	switch (strtolower($fileExtension)){
		case "jpg": $ret = "image/jpeg"; break;
		case "jpeg": $ret = "image/jpeg"; break;
		case "png": $ret =  "image/png"; break;
		case "pdf": $ret =  "application/pdf"; break;
		case "zip": $ret =  "application/zip"; break;
		case "mov": $ret =  "video/quicktime"; break;
		case "mp3": $ret =  "audio/mpeg"; break;
		case "mp4": $ret =  "video/mp4"; break;
		case "html": $ret =  "text/html"; break;
		case "txt": $ret =  "text/plain"; break;
		case "csv": $ret =  "text/plain"; break;
		case "php": $ret = "text/plain"; break;
		case "doc": $ret =  "application/msword"; break;
		case "docx": $ret =  "application/vnd.openxmlformats-officedocument.wordprocessingml.document"; break;
		case "xls": $ret =  "application/vnd.ms-excel"; break;
		case "xlsx": $ret =  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"; break;
		case "ppt": $ret =  "application/vnd.ms-powerpoint"; break;
		case "pptx": $ret =  "application/vnd.openxmlformats-officedocument.presentationml.presentation"; break;
	}
	return $ret;
}


//trims string for display
function fnMaxLength($theString, $theLength, $app = ""){
	if(strlen($theString) > $theLength){
		return substr($theString,0,$theLength) . $app;
	}else{
		return $theString;
	}
}

//formats string for javascript
function fnFormatJavascript($theString){
	if($theString == ""){
		return "";
	}else{
		$theString = str_replace("\"", "\''", $theString);
		$theString = str_replace("'", "\'", $theString);
		return $theString;
	}
}

//swaps from ASC to DESC
function fnToggleSort($sortUpDown){
	$r = "DESC";
	if(strtoupper($sortUpDown) == "DESC") $r = "ASC";
	return $r;
}


//shows current sorted colum indicator
function fnSortIcon($currentColumn, $thisDirection, $thisColumn){

	//need app's URL to figure out path to images...
	$r = "<img src='" . fnGetSecureURL(APP_URL) . "/images/sort_down.gif' style='visibility:hidden;vertical-align:middle;' border='0' align='absmiddle' alt='sort arrow' />";
	if(strtoupper($currentColumn) == strtoupper($thisColumn)){
		$r = (strtoupper($thisDirection) == "DESC") ? "<img src='" . fnGetSecureURL(APP_URL) . "/images/sort_up.gif' border='0' alt='sort arrow' />" : "<img src='" . fnGetSecureURL(APP_URL). "/images/sort_down.gif' border='0' style='visibility:visible;vertical-align:middle;' align='absmiddle' alt='sort arrow' />" ;
	}	
	return $r;
}

//get file type icon
function fnFileTypeIcon($fileName){
	$ret = "";
	$file_ext = strtolower(substr($fileName, strrpos($fileName, '.') + 1));
	switch ($file_ext){
		case "doc":
		case "docx":
			$ret = "doc.gif";
			break;
		case "xls":
		case "xlsx":	
			$ret = "xls.gif";
			break;
		case "ppt":
		case "pptx":	
			$ret = "ppt.gif";
			break;
		case "pdf":
			$ret = "pdf.gif";
			break;
		case "jpg":
		case "jpeg":
		case "png":
		case "gif":
			$ret = "jpg.gif";
			break;
		case "mp3":
		case "aif":
			$ret = "wav.gif";
			break;
		case "mov":
		case "mp4":
			$ret = "mov.gif";
			break;
		case "txt":
		case "csv":
			$ret = "txt.gif";
			break;
		case "php":
			$ret = "php.gif";
			break;
		case "html":
			$ret = "html.gif";
			break;
		case "zip":
			$ret = "zip.gif";
			break;

	}
	if($ret != ""){
		return "<img src='" . fnGetSecureURL(APP_URL) . "/images/file-type-icons/" . $ret . "' alt='icon' style='vertical-align:middle;'/>";
	}else{
		return "<img src='" . fnGetSecureURL(APP_URL). "/images/blank.gif' style='width:20px;height:20px;vertical-align:middle;' alt='icon' />";
	}
}




//removed extra carriage returns
function fnRemoveCarriageReturns($theVal){
	$r = $theVal;
	$r = str_replace("\n","", $r);
	return $r;
}


//removes all files from a directory then the directory itself
function fnRemoveDirectory($dir) {
	if(is_dir($dir)){
  		if($handle = opendir("$dir")) {
		   while (false !== ($item = readdir($handle))) {
			 if ($item != "." && $item != "..") {
			   if (is_dir("$dir/$item")) {
				 fnRemoveDirectory("$dir/$item");
			   } else {
				 @unlink("$dir/$item");
				 //echo " removing $dir/$item<br>\n";
			   }
			 }
		   }
			//clean up
			closedir($handle);
   			@rmdir($dir);
  		}
	}//dir doestn't exists
}

//deletes files from a directory but not the directory 
function fnEmptyDirectory($dir){
	$mydir = opendir($dir);
	 while(false !== ($file = readdir($mydir))){
	 	if($file != "." && $file != ".."){
			if(!is_dir($dir . $file)){
				unlink($dir . $file);
			}
		}
	  }//end while
	closedir($mydir);
}

//chmod directory recusively
function fnChmodDirectory($path = ".", $permissionLevel = 0755){  
	if($dh = opendir($path)){
		$ignore = array("cgi-bin", ".", ".."); 
		while(false !== ($file = readdir($dh))){ 
			if(!in_array($file, $ignore)){
				if(is_dir($path . "/" . $file)){
					chmod($path . "/" . $file, $permissionLevel);
					fnChmodDirectory($path . "/" . $file, $permissionLevel);
				}else{
					chmod($path . "/" . $file, $permissionLevel);
				}//elseif 
			}//if in array 
		}//while 
		closedir( $dh ); 
	}
}

//Strips white space chars.
function fnStripWhiteSpace($theVal){
	$result = trim($theVal,"\x7f..\xff\x0..\x1f");
	return $result;
}

//makes url clickable
function fnMakeClickURL($matches) {
	$ret = '';
	$url = $matches[2];
	if ( empty($url) )
		return $matches[0];
	// removed trailing [.,;:] from URL
	if ( in_array(substr($url, -1), array('.', ',', ';', ':')) === true ) {
		$ret = substr($url, -1);
		$url = substr($url, 0, strlen($url)-1);
	}
	return $matches[1] . "<a href=\"$url\" target=\"_blank\" rel=\"nofollow\">$url</a>" . $ret;
}
 
//makes FTP url clickable..
function fnMakeClickFTP($matches){
	$ret = '';
	$dest = $matches[2];
	$dest = 'http://' . $dest;
 
	if ( empty($dest) )
		return $matches[0];
	// removed trailing [,;:] from URL
	if ( in_array(substr($dest, -1), array('.', ',', ';', ':')) === true ) {
		$ret = substr($dest, -1);
		$dest = substr($dest, 0, strlen($dest)-1);
	}
	return $matches[1] . "<a href=\"$dest\" target=\"_blank\" rel=\"nofollow\">$dest</a>" . $ret;
}
 
//makes EMAIL address clickable
function fnMakeClickEMAIL($matches){
	$email = $matches[2] . '@' . $matches[3];
	return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
}
 
//uses three functions above to produce links from plain-text data...
function fnFormatClickableLinks($ret){
	$ret = ' ' . $ret;
	// in testing, using arrays here was found to be faster
	$ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'fnMakeClickURL', $ret);
	$ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', 'fnMakeClickFTP', $ret);
	$ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', 'fnMakeClickEMAIL', $ret);
 
	// this one is not in an array because we need it to run last, for cleanup of accidental links within links
	$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
	$ret = trim($ret);
	return $ret;
}

//sets cookie val
function fnSetCookie($cookieName, $cookieVal){
	if(setcookie($cookieName, $cookieVal, time()+60*60*24*90, "/", "", 0)){ /*expires in 90 days */
		return true;
	}else{
		return false;
	}
}	

//Creates GUID	
function fnCreateGuid(){
	$RandomStr_1 = md5(microtime()); 
	$RandomStr_2 = md5(microtime()); 
	$randomString = substr($RandomStr_2, 4, 4) . substr($RandomStr_1, 0, 15) . substr($RandomStr_2, 0, 4); 
	return $randomString;
}

//creates dir
function fnCreateDirectory($path){
	if(is_dir($path)){
		return true;
	}else{
		if(@mkdir($path)){
			return true;
		}else{
			return false;
		}
	}
}


//Cleans up form entries	
function fnFormInput($theVal){
	if(!is_array($theVal)){
		if(trim($theVal) == ""){
			return "";
			break;
		}else{
			//replace of all this!!!!!
			$theVal = str_replace("/*", "", $theVal);
			$theVal = str_replace("\"", "", $theVal);
			$theVal = strip_tags($theVal); //strip html tags
			//trim spaces off end
			$theVal = trim($theVal); //trim leading and trailing spaces.
			
			//if magic quotes is already on, don't do anyting
			if(get_magic_quotes_gpc() == 1){
				return $theVal;
				break;
			}else{
				$theVal = addslashes($theVal);
				return $theVal;
				break;
			}
		}
	}//not an array
}

//cleans up HTML editor input. Different than regular form input because of HTML tags.
function fnHTMLInput($theVal){
	if($theVal == ""){
		return "";
		break;
	}else{
		//get rid of this!!!!!
		$theVal = str_replace("/*", "", $theVal);
		$theVal = str_replace("\"", "", $theVal);
		$theVal = trim($theVal); //trim leading and trailing spaces.
		if(get_magic_quotes_gpc() == 1){
			return $theVal;
			break;
		}else{
			$theVal = addslashes($theVal);
			return $theVal;
			break;
		}
	}
}

//Cleans up form values for display	
function fnFormOutput($theVal, $showHTMLEntities = false){
	
   	$r = utf8_encode($theVal); 
	
			
	if(trim($r) == ""){
		return $r;
		break;
	}else{
	
		$r = stripslashes($theVal);
		$r = str_replace("&quo;", "\"", $r);
		$r = trim($r);
		
		return $r;
		break;
	}
}

//Cleans up form values for display	
function fnHTMLFormOutput($theVal){
	
	//output non-ASCII characters to UTF-8
	if(preg_match('/[\x80-\xFF]/', $theVal)){
	  # String has non-ASCII characters
	  $theVal = mb_convert_encoding($theVal , "UTF-8");
	}
	
	
	$r = $theVal;
	if(trim($r) == ""){
		return $r;
		break;
	}else{
	
		$r = stripslashes($theVal);
		$r = str_replace("&quo;", "\"", $r);
		$r = trim($r);
		return $r;
		break;
	}
}



//used to "pre-check" checkboxes	
function fnGetChecked($theVal1, $theVal2){
	if( strtoupper(strval($theVal1)) == strtoupper(strval($theVal2))  ){
		return " checked ";
	}else{
		return "";
	}
}

//used to "pre-select" list boxes	
function fnGetSelectedString($theVal1, $theVal2){
	if(strtoupper(strval($theVal1)) == strtoupper(strval($theVal2))  ){
		return " selected ";
	}else{
		return "";
	}
}

//formats currency
function fnFormatCurrency($theVal,$showCents = false){
	if(is_numeric($theVal)){
		if($showCents){
			return "$" . number_format($theVal,2);
		}else{
			return "$" . number_format($theVal,0);
		}
	}else{
		return $theVal;
	}
}

//formats percent
function fnFormatPercent($theVal, $numDigits = 2){
	if(is_numeric($theVal)){
		return (number_format($theVal,$numDigits) * 100) . "%";
	}else{
		return $theVal;
	}
}



//formats phone number (800)888-8888	
function fnFormatPhone($theVal){
	//strip everything, if 10 digits, format, else return empty.
	$theVal = fnStripIllegal($theVal);
	$theVal = str_replace(" ", "", $theVal);
	$theVal = strtoupper($theVal);
	if(strlen($theVal) == 10){
		$r = "";
		//area code
		$r =  "" . substr($theVal, 0, 3) . "-";
		//prefix
		$r .= substr($theVal, 3, 3) . "-";
		//suffix
		$r .= substr($theVal, 6, 4);
		return $r;
	}else{
		return "";
	}
}


//formats zip 93950 or 93950-2345	
function fnFormatZip($theVal){
	//strip first
	$r = "";
	$theVal = fnStripIllegal($theVal);
	$theVal = str_replace(" ", "", $theVal);
	if(strlen($theVal) == 5 || strlen($theVal) == 9){
		$r = $theVal;
	}
	if(strlen($r) == 5){
		$r = $r;
	}	
	if(strlen($r) == 9){
		$r = substr($r, 0, 5) . "-" . substr($r, 5, 4);
	}
	return $r;
}


//strips phone number (800)888-8888	
function fnStripPhone($theVal){
	$r = $theVal;
	$r = str_replace(")", "", $r);
	$r = str_replace("(", "", $r);
	$r = str_replace("-", "", $r);
	$r = str_replace("x", "", $r);
	$r = str_replace(",", "", $r);
	$r = str_replace("+", "", $r);
	$r = str_replace("=", "", $r);
	$r = str_replace(" ", "", $r);
	$r = str_replace("/", "", $r);
	$r = str_replace("\\", "", $r);
	return $r;
}

//strips zip code	
function fnStripZip($theVal){
	$r = $theVal;
	$r = str_replace(")", "", $r);
	$r = str_replace("(", "", $r);
	$r = str_replace("-", "", $r);
	$r = str_replace("x", "", $r);
	$r = str_replace(",", "", $r);
	$r = str_replace("+", "", $r);
	$r = str_replace("=", "", $r);
	$r = str_replace(" ", "", $r);
	$r = str_replace("/", "", $r);
	$r = str_replace("\\", "", $r);
	return $r;
}

//returns Date, formatted with timezone offset, defaults to PST
function fnDtNow($offset = "7", $dateFormatString = "m/d/Y h:i:s A"){
	/* 	offset = timezone offset. 7 = PST, 4 = EST, etc.
		dateFormatString = "m/d/y h:m:s A" (01/01/2006 03:23:00 AM)
		pass different string as needed; */
	//$offset = settype($offset, integer);
	$hm = $offset * 60; 
	$ms = $hm * 60;
	$gmdate = gmdate($dateFormatString, time()-($ms)); 
	return $gmdate; //	01/01/2006 03:23:00 AM
}

//Returns Date +- time zone
function fnFromUtc($theDate, $offset = "", $dateFormatString='m/d/Y h:i:s A'){
	if(!is_numeric($offset)) $offset = 1;
	$hm = $offset * 60; 
	$ms = $hm * 60;
	if($theDate == ""){
		return "";
	}
	$gmdate = strtotime($theDate); 
	$gmdate = date($dateFormatString, $gmdate - (3600*($offset)) );
	return $gmdate; 
}

//Returns 01/01/2006
function fnDateShort(){
	$dateFormatString = "m/d/Y";
	$gmdate = date($dateFormatString); 
	return $gmdate; //	01/01/2006
}
//Returns Monday, January 3th 2006
function fnDateLong(){
	$dateFormatString = "l, F jS Y";
	$gmdate = date($dateFormatString); 
	return $gmdate; //Monday, January 3th 2006
}

//format date for mySql
function fnMySqlDate($stringDate, $dateFormatString = "Y-m-d H:i:s"){
	return date($dateFormatString, strtotime($stringDate));
}


//Returns GMT, formatted for MySql
function fnMySqlNow($dateFormatString = "Y-m-d H:i:s"){
	$gmdate = gmdate($dateFormatString); 
	return $gmdate; //	2006-01-01 23:32:01
}

//returns day name week from integer
function fnDayName($dayInteger, $abbr = false){
	if($abbr){
		$dayArray = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sun");
	}else{
		$dayArray = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday");
	}
	return $dayArray[$dayInteger];
}

//returns month name from integer
function fnMonthName($monthInteger, $abbr = false){
	if($abbr){
		$monthArray = array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	}else{
		$monthArray = array("Janurary","February","March","April","May","June","July","August","September","October","November","December");
	}
	return $monthArray[$monthInteger];
}

//add date like the vb dateAdd function
function fnDateAdd($interval, $number, $date) {
    $date_time_array = getdate($date);
    $hours = $date_time_array['hours'];
    $minutes = $date_time_array['minutes'];
    $seconds = $date_time_array['seconds'];
    $month = $date_time_array['mon'];
    $day = $date_time_array['mday'];
    $year = $date_time_array['year'];

    switch ($interval) {
        case 'yyyy':
            $year += $number;
            break;
        case 'q':
            $year += ($number*3);
            break;
        case 'm':
            $month+=$number;
            break;
        case 'y':
        case 'd':
        case 'w':
			$day+=$number;
            break;
        case 'ww':
            $day+=($number*7);
            break;
        case 'h':
            $hours+=$number;
            break;
        case 'n':
            $minutes+=$number;
            break;
        case 's':
            $seconds+=$number; 
            break;            
    }
    $timestamp = mktime($hours,$minutes,$seconds,$month,$day,$year);
    return $timestamp;
}
//end dateAdd




function fnDateDiff($interval, $datefrom, $dateto, $using_timestamps = false) {
  /*
    $interval can be:
    yyyy - Number of full years
    q - Number of full quarters
    m - Number of full months
    y - Difference between day numbers
      (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
    d - Number of full days
    w - Number of full weekdays
    ww - Number of full weeks
    h - Number of full hours
    n - Number of full minutes
    s - Number of full seconds (default)
  */
  
  if (!$using_timestamps) {
    $datefrom = strtotime($datefrom, 0);
    $dateto = strtotime($dateto, 0);
  }
  $difference = $dateto - $datefrom; // Difference in seconds
  switch($interval) {
    case 'yyyy': // Number of full years
      $years_difference = floor($difference / 31536000);
      if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
        $years_difference--;
      }
      if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
        $years_difference++;
      }
      $datediff = $years_difference;
      break;
    case "q": // Number of full quarters
      $quarters_difference = floor($difference / 8035200);
      while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
        $months_difference++;
      }
      $quarters_difference--;
      $datediff = $quarters_difference;
      break;
    case "m": // Number of full months
      $months_difference = floor($difference / 2678400);
      while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
        $months_difference++;
      }
      $months_difference--;
      $datediff = $months_difference;
      break;
    case 'y': // Difference between day numbers
      $datediff = date("z", $dateto) - date("z", $datefrom);
      break;
    case "d": // Number of full days
      $datediff = floor($difference / 86400);
      break;
    case "w": // Number of full weekdays
      $days_difference = floor($difference / 86400);
      $weeks_difference = floor($days_difference / 7); // Complete weeks
      $first_day = date("w", $datefrom);
      $days_remainder = floor($days_difference % 7);
      $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
      if ($odd_days > 7) { // Sunday
        $days_remainder--;
      }
      if ($odd_days > 6) { // Saturday
        $days_remainder--;
      }
      $datediff = ($weeks_difference * 5) + $days_remainder;
      break;
    case "ww": // Number of full weeks
      $datediff = floor($difference / 604800);
      break;
    case "h": // Number of full hours
      $datediff = floor($difference / 3600);
      break;
    case "n": // Number of full minutes
      $datediff = floor($difference / 60);
      break;
    default: // Number of full seconds (default)
      $datediff = $difference;
      break;
  }    
  return $datediff;
} // end date diff function

//shows since label
function fnSinceLabel($modHours, $modifiedUTC, $timeZoneOffset = "0", $showTime = false){
	if(!is_numeric($timeZoneOffset)) $timeZoneOffset = 0;
	$r = fnFromUTC($modifiedUTC, $timeZoneOffset, "m/d h:i A");
	if($modHours < 25){
		$r = fnFromUTC($modifiedUTC, $timeZoneOffset, "m/d h:i A") . " <span style='color:red;font-size:7pt;padding-left:5px;'><i>recent</i></span>";
	}else{
		if($modHours > 1000){
			$r = fnFromUTC($modifiedUTC, $timeZoneOffset, "m/d/Y");
		}
	}
	return $r;
}

//Days in month
function fnDaysInMonth($Year,$MonthInYear){
   if ( in_array ( $MonthInYear, array ( 1, 3, 5, 7, 8, 10, 12 ) ) )
       return 31; 
   if ( in_array ( $MonthInYear, array ( 4, 6, 9, 11 ) ) )
       return 30; 
   if ( $MonthInYear == 2 )
       return ( checkdate ( 2, 29, $Year ) ) ? 29 : 28;
   return false;
}


//Gets last day of month 30, 31 or 28
function fnGetLastDay($Year,$MonthInYear){
   if ( in_array ( $MonthInYear, array ( 1, 3, 5, 7, 8, 10, 12 ) ) )
       return 31; 
   if ( in_array ( $MonthInYear, array ( 4, 6, 9, 11 ) ) )
       return 30; 
   if ( $MonthInYear == 2 )
       return ( checkdate ( 2, 29, $Year ) ) ? 29 : 28;
   return false;
}


//converts relative URL's to absolute URL's
function absolute_url($txt, $base_url){ //$base_url is like http://www.google.com
	$needles = array('href="', 'src="', 'background="'); 
  	$new_txt = ''; 
  	if(substr($base_url,-1) != '/') $base_url .= '/'; 
  	$new_base_url = $base_url; 
  	$base_url_parts = parse_url($base_url); 

  	foreach($needles as $needle){ 
		while($pos = strpos($txt, $needle)){ 
		  	$pos += strlen($needle); 
	  		if(substr($txt,$pos,7) != 'http://' && substr($txt,$pos,8) != 'https://' && substr($txt,$pos,6) != 'ftp://' && substr($txt,$pos,9) != 'mailto://'){ 
			if(substr($txt,$pos,1) == '/') $new_base_url = $base_url_parts['scheme'].'://'.$base_url_parts['host']; 
				$new_txt .= substr($txt,0,$pos).$new_base_url; 
	  		} else { 
				$new_txt .= substr($txt,0,$pos); 
	  		} 
	  		$txt = substr($txt,$pos); 
		} 
		$txt = $new_txt.$txt; 
		$new_txt = ''; 
  	} 
  return $txt; 
}  
		
//returns true/false for is SSL
function fnIsSecure(){
	$ret = false;
	if(isset($_SERVER['REMOTE_PORT'])){
		if($_SERVER['REMOTE_PORT'] == "443"){
			$ret = true;	
		}
	}
	if(isset($_SERVER['HTTPS'])){
		if(strtolower($_SERVER['HTTPS']) == "on"){
			$ret = true;
		}
	}
	return $ret;
}

//fixes up URL for secure / non-secure connections...
function fnGetSecureURL($theURL = ""){
	if(fnIsSecure()){
		$theURL = str_replace("http:", "https:", $theURL);
	}else{
		$theURL = str_replace("https:", "http:", $theURL);
	}
	return $theURL;
}

function fnGetSecureHTTP(){
	if(fnIsSecure()){
		return "https:";
	}else{
		return "http:";
	}
}


//Removes last comma
function fnRemoveLastComma($theVal){
	//if the last char is a comma
	if(substr($theVal, (strlen($theVal) - 1), 1) == ","){
		return substr($theVal, 0, strlen($theVal) - 1);
	}else{
		return $theVal;
	}
}
//Removes last char
function fnRemoveLastChar($theVal, $theChar){
	$theVal = trim($theVal);
	//if the last char is the char parameter
	if(substr(strtoupper($theVal), (strlen($theVal) - 1), 1) == strtoupper($theChar)){
		return substr($theVal, 0, strlen($theVal) - 1);
	}else{
		return $theVal;
	}
}
//Removes first char
function fnRemoveFirstChar($theVal, $theChar){
	$theVal = trim($theVal);
	if(substr(strtoupper($theVal), 0, 1) == strtoupper($theChar)){
		return substr($theVal, 1, strlen($theVal));
	}else{
		return $theVal;
	}
}

//formats proper case.
function fnProperCase($theVal){
	return	ucwords(strtolower($theVal));
}
//formats proper case (psuedo override).
function fnFormatProperCase($theVal){
	return	ucwords(strtolower($theVal));
}


//valid CC number (luhn algorithm)
function fnIsValidCCNumber($theVal){
	/*
		1) take the original number
		2) Reverse the number
		3) Take every-other-number
		4) add the results
		If the result is divisible by 10 the CC is valid
	*/
	return false;
}


//validate email address
function fnIsEmailValid($email){
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex){
      $isValid = false;
   }else{
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64){
         // local part length exceeded
         $isValid = false;
      }else if ($domainLen < 1 || $domainLen > 255){
         // domain part length exceeded
         $isValid = false;
      }else if ($local[0] == '.' || $local[$localLen-1] == '.'){
         // local part starts or ends with '.'
         $isValid = false;
      } else if (preg_match('/\\.\\./', $local)){
         // local part has two consecutive dots
         $isValid = false;
      }else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)){
         // character not valid in domain part
         $isValid = false;
      }else if (preg_match('/\\.\\./', $domain)){
         // domain part has two consecutive dots
         $isValid = false;
      }else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))){
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))){
            $isValid = false;
         }
      }
      
		//DNS test  
	  	if($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
        	 // domain not found in DNS
         	$isValid = false;
      	}
   
   }
   return $isValid;
}


//valid zip
function fnIsZipValid($zip){
	$zip = fnStripZip($zip);
	if(!is_numeric($zip) || strlen($zip) > 10 || strlen($zip) < 5){
		return false;
    }else{
		if(strlen($zip) != 5 && strlen($zip) != 9){
			return false;
		}else{
			return true;
		}	
	}
} 



//valid URL
function fnIsUrlValid($url){
	//must start with http:// or https://
	$bolIsValid = true;
	if(strlen($url) < 8){
		$bolIsValid = false;
	}else{
		$http = substr($url, 0, 7);
		$https = substr($url, 0, 8);
		$market = substr($url, 0, 9);
		if($http == "http://" || $https == "https://" || $market = "market://"){
			$bolIsValid = true;
		}else{
			$bolIsValid = false;
		}	
	}
	return $bolIsValid;
}		

//is valid date
function fnIsValidDate($strdate){
	//needs to come in format of mm/dd/yyyy
	$isValid = true;
	if((substr_count($strdate,"/"))<>2 || strlen($strdate) != 10){
		//echo("Enter the date in 'mm/dd/yyyy' format");
		$isValid = false;
	}else{
		$pos=strpos($strdate,"/");
		$month=substr($strdate,0,($pos));
		$result = preg_match("/^[0-9]+$/",$month,$trashed);
		
		if(!($result)){
			//echo "Enter a Valid Month";
			$isValid = false;
		}else{
			if(($month<=0) || ($month>12)){
				//echo "Enter a Valid Month";
				$isValid = false;
			}
		}//end good month
		$date=substr($strdate,($pos+1),($pos));
		if(($date<=0) || ($date>31)){
			//echo "Enter a Valid date";
			$isValid = false;
		}else{
			$result = preg_match("/^[0-9]+$/", $date, $trashed);
			if(!($result)){
				//echo "Enter a Valid date";
				$isValid = false;
			}
		}//end good date
	
		$year=substr($strdate,($pos+4),strlen($strdate));
		$result = preg_match("/^[0-9]+$/",$year,$trashed);
		if(!($result)){
			//echo "Enter a Valid year";
			$isValid = false;
		}else{
			if(($year<1900) || ($year>2200)){
				//echo "Enter a year between 1900-2200";
				$isValid = false;
			}
		}//end good year
	}
	//if valid so far, check february
	if($isValid){
		if($month == "02" && $date > 28){
			//twenty nine days in a leapyear
			if(fnIsLeapYear($year)){
				//all good
			}else{
				//not good
				$isValid = false;
			}				
		}
	}
	return $isValid;
}

//cleans up project name for XCode, Eclipse, etc...
function fnCleanProjectName($theName){
	$r = $theName;
	$r = str_replace(" ","", $r);
	$r = str_replace("`","", $r);
	$r = str_replace("~","", $r);
	$r = str_replace("!","", $r);
	$r = str_replace("@","", $r);
	$r = str_replace("#","", $r);
	$r = str_replace("$","", $r);
	$r = str_replace("%","", $r);
	$r = str_replace("^","", $r);
	$r = str_replace("&","", $r);
	$r = str_replace("*","", $r);
	$r = str_replace("(","", $r);
	$r = str_replace(")","", $r);
	$r = str_replace("-","", $r);
	$r = str_replace("_","", $r);
	$r = str_replace("=","", $r);
	$r = str_replace("+","", $r);
	$r = str_replace("{","", $r);
	$r = str_replace("}","", $r);
	$r = str_replace("[","", $r);
	$r = str_replace("]","", $r);
	$r = str_replace("\\","", $r);
	$r = str_replace("|","", $r);
	$r = str_replace(",","", $r);
	$r = str_replace(".","", $r);
	$r = str_replace("/","", $r);
	$r = str_replace("<","", $r);
	$r = str_replace(">","", $r);
	$r = str_replace("?","", $r);
	return strtolower($r);		
}


//is passed in year  leap year
function fnIsLeapYear($y){
	return $y % 4 == 0 && ($y % 400 == 0 || $y % 100 != 0);
}

//formats date to sql
function fnShortMySqlDate($date){
	//needs to come in format of mm/dd/yyyy
	$isValid = false;
	if(!fnIsValidDate($date)){ //not 10 digits
		return "0000-00-0000 00:00:00";
	}else{
		//good so far, use chekdate
		$month = substr($date, 0, 2);
		$day = substr($date, 3, 2);
		$year = substr($date, 6, 4);
		return $year . "-" . $month . "-" . $day . " 00:00:00";
	} // not 10 digits
} 

//formats birthday for form
function fnFormatDate($date){
	if($date == "0000-00-00" || $date == "00/00/0000"){
		return "";
	}else{
		return $date;
	}
}

//returns how long it's been
function fnAgo($timestamp){
       $difference = time() - $timestamp;
       if($difference < 60)
           return $difference." seconds ago";
       else{
           $difference = round($difference / 60);
           if($difference < 60)
               return $difference." minutes ago";
           else{
               $difference = round($difference / 60);
               if($difference < 24)
                   return $difference." hours ago";
               else{
                   $difference = round($difference / 24);
                   if($difference < 7)
                       return $difference." days ago";
                   else{
                       $difference = round($difference / 7);
                       return $difference." weeks ago";
                   }
               }
           }
       }
   }


//Replaces or inserts <BR> tags / vbCrlf	
function fnLineBreaks($theVal, $addOrRemoveBrs = "Add"){
	$r = $theVal;
	if(strtoupper($addOrRemoveBrs) == "ADD" ){
		$r = str_replace("\n", "<br>", $r);
		$r = str_replace("vbCrlf", "<br>", $r);
	}else{
		$r = str_replace("<br>", "\n", $r);
	}
	return $r;
}
//NO LINE BREAKS tags / vbCrlf	
function fnNoLineBreaks($theVal){
	$r = $theVal;
	$r = str_replace("\n", "", $r);
	$r = str_replace("\r", "", $r);
	$r = str_replace("vbCrlf", "", $r);
	return $r;
}


//removes startig and ending line breaks
function fnRemoveExtraBreaks($theVal){
	$r = $theVal;
	//remove start breaks
	if(strtoupper(substr($theVal, 0, 4)) == "<BR>"){
		$r = strtoupper(substr($theVal, 0, strlen($theVal - 4)));
	}
	return $r;
}
    

//contains non alpha numeric chars
function fnIsAlphaNumeric($theVal, $allowSpaces = true){
	$r = false;
	if(!$allowSpaces){
		if(!preg_match("/([^a-z0-9])+/i", $theVal)){
			$r = true;
		}
	}else{
		if(!preg_match("/([^a-z0-9 \" \"])+/i", $theVal)){
			$r = true;
		}
	}
	return $r;
}

//removes non alpha-numeric chars
function fnRemoveNonAlpha($theVal, $allowSpaces = true){
	if(!$allowSpaces){
		$theVal = preg_replace("/([^a-z0-9])+/i", "", $theVal);
	}else{
		$theVal = preg_replace("/([^a-z0-9 \" \"])+/i", "", $theVal);
	}
	return $theVal;
}


//build year opts
function fnGetYearOpts($selectedYear, $startYearsBack = 100, $goYearsForward = 101){
	$r = "";
	$x = 0;
	$curYear = date("Y");
	$curYear = $curYear - $startYearsBack;
	for($x = $curYear;$x < ($curYear + $goYearsForward);$x++){
		$r .= "<option value='" . $x . "' " . fnGetSelectedString($x, $selectedYear) . ">" . $x . "</option>\n";
	}
	return $r;
}

//build state opts
function fnGetStateOpts($selectedState, $optionCSS = ""){
	$states = "AL,AK,AS,AZ,AR,CA,CO,CT,DE,DC,FM,FL,GA,GU,HI,ID,IL,IN,IA,KS,KY,LA,ME,MH,MD,MA,MI,MN,MS,MO,MT,NE,NV,NH,NJ,NM,NY,NC,ND,OH,OK,OR,PW,PA,PR,RI,SC,SD,TN,TX,UT,VT,VI,VA,WA,WV,WI,WY";
	$arrStates = explode(",",$states);
	$x = 0;
	$r = "";
	$css = "";
	if($optionCSS != ""){
		$css = "class='" . $optionCSS . "'";
	}
	for($x = 0;$x < count($arrStates);$x++){
		$state = $arrStates[$x];
		$r .= "<option " . $css . " value='" . $state . "' " . fnGetSelectedString($state, $selectedState) . ">" . $state . "</option>\n";
	}
	return $r;
}

//build country options
function fnGetCountryOpts($selectedCountry, $optionCSS = ""){
	$countries = "Afghanistan,Land Islands,Albania,Algeria,American Samoa,Andorra,Angola,Anguilla,Antarctica,Antigua And Barbuda,Argentina,";
	$countries .= "Armenia,Aruba,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bermuda,";
	$countries .= "Bhutan,Bolivia,Bosnia And Herzegovina,Botswana,Bouvet Island,Brazil,British Indian Ocean Territory,Brunei Darussalam,Bulgaria,";
	$countries .= "Burkina Faso,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Cayman Islands,Central African Republic,Chad,Chile,China,Christmas Island,";
	$countries .= "Cocos (Keeling) Islands,Colombia,Comoros,Congo,The Democratic Republic Of The Cook Islands,Costa Rica,Cote D-Ivoire,Croatia,Cuba,";
	$countries .= "Cyprus,Czech Republic,Denmark,Djibouti,Dominica,Dominican Republic,Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,";
	$countries .= "Ethiopia,Falkland Islands (Malvinas),Faroe Islands,Fiji,Finland,France,French Guiana,French Polynesia,French Southern Territories,";
	$countries .= "Gabon,Gambia,Georgia,Germany,Ghana,Gibraltar,Greece,Greenland,Grenada,Guadeloupe,Guam,Guatemala,Guernsey,Guinea,Guinea-Bissau,";
	$countries .= "Guyana,Haiti,Heard Island And Mcdonald Islands,Holy See (Vatican City State),Honduras,Hong Kong,Hungary,Iceland,India,Indonesia,";
	$countries .= "Iran,Iraq,Ireland,Isle Of Man,Israel,Italy,Jamaica,Japan,Jersey,Jordan,Kazakhstan,Kenya,Kiribati,Korea (Democratic Peoples Republic Of),";
	$countires .= "Korea (Republic Of),Kuwait,Kyrgyzstan,Lao Peoples Democratic Republic,Latvia,Lebanon,Lesotho,Liberia,Libyan Arab Jamahiriya,Liechtenstein,";
	$countries .= "Lithuania,Luxembourg,Macao,Macedonia, The Former Yugoslav Republic Of,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,";
	$countries .= "Martinique,Mauritania,Mauritius,Mayotte,Mexico,Micronesia (Federated States Of),Moldova (Republic Of),Monaco,Mongolia,Montserrat,Morocco,";
	$countries .= "Mozambique,Myanmar,Namibia,Nauru,Nepal,Netherlands,Netherlands Antilles,New Caledonia,New Zealand,Nicaragua,Niger,Nigeria,Niue,Norfolk Island,";
	$countries .= "Northern Mariana Islands,Norway,Oman,Pakistan,Palau,Palestinian Territory,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Pitcairn,Poland,";
	$countries .= "Portugal,Puerto Rico,Qatar,Reunion,Romania,Russian Federation,Rwanda,Saint Helena,Saint Kitts And Nevis,Saint Lucia,Saint Pierre And Miquelon,";
	$countries .= "Saint Vincent And The Grenadines,Samoa,San Marino,Sao Tome And Principe,Saudi Arabia,Senegal,Serbia And Montenegro,Seychelles,Sierra Leone,";
	$countires .= "Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,South Georgia And The South Sandwich Islands,Spain,Sri Lanka,Sudan,Suriname,";
	$countries .= "Svalbard And Jan Mayen,Swaziland,Sweden,Switzerland,Syrian Arab Republic,Taiwan, Province Of China,Tajikistan,Tanzania (United Republic Of),";
	$countries .= "Thailand,Timor-Leste,Togo,Tokelau,Tonga,Trinidad And Tobago,Tunisia,Turkey,Turkmenistan,Turks And Caicos Islands,Tuvalu,Uganda,Ukraine,";
	$countries .= "United Arab Emirates,United Kingdom,United States,United States Minor Outlying Islands,Uruguay,Uzbekistan,Vanuatu,Venezuela,Viet Nam,";
	$countries .= "Virgin Islands British,Virgin Islands (U.S.),Wallis And Futuna,Western Sahara,Yemen,Zambia,Zimbabwe";
	$arrCountries = explode(",",$countries);
	$x = 0;
	$r = "";
	$css = "";
	if($optionCSS != ""){
		$css = "class='" . $optionCSS . "'";
	}
	for($x = 0;$x < count($arrCountries);$x++){
		$country = $arrCountries[$x];
		$r .= "<option" . $css . " value='" . $country . "' " . fnGetSelectedString($country, $selectedCountry) . ">" . $country . "</option>\n";
	}
	return $r;
}


//build month opts
function fnGetMonthOpts($selectedMonth){
	$months = "'',Jan.,Feb.,Mar.,Apr.,May,Jun.,Jul.,Aug.,Sep.,Oct.,Nov.,Dec.";
	$arrMonths = explode(",",$months);
	$x = 0;
	$r = "";
	for($x = 1;$x < count($arrMonths);$x++){
		$month = $arrMonths[$x];
		$val = "";
		if($x < 10){
			$val = "0" . $x;
		}else{
			$val = $x;
		}
		$r .= "<option value='" . $val . "' " . fnGetSelectedString($val, $selectedMonth) . ">" . $month . "</option>\n";
	}
	return $r;
}

//gets month name from 1-12 interger
function fnGetMonthName($monthInteger){
	$x = 0;
	$r = "";
	$months = "Jan.,Feb.,Mar.,Apr.,May,Jun.,Jul.,Aug.,Sep.,Oct.,Nov.,Dec.";
	$arrMonths = explode(",",$months);
	if($monthInteger == 0 || $monthInteger > 12){
		return "";
		break;
	}
	if($monthInteger == 12){
		$r = $arrMonths[11];
	}else{
		$r = $arrMonths[($monthInteger-1)];
	}
	return $r;
}

//build day opts
function fnGetDayOpts($selectedDay){
	$x = 0;
	$r = "";
	for($x = 1;$x < 32 ;$x++){
		$val = "";
		if($x < 10){
			$val = "0" . $x;
		}else{
			$val = $x;
		}
		$r .= "<option value='" . $val . "' " . fnGetSelectedString($val, $selectedDay) . ">" . $val . "</option>\n";
	}
	return $r;
}


//formats bytes for display
function fnFormatBytes($nBytes){
        if ($nBytes>= pow(2,40))
        {
            $strReturn = round($nBytes / pow(1024,4), 2);
            $strSuffix = "TB";
        }
        elseif ($nBytes>= pow(2,30))
        {
            $strReturn = round($nBytes / pow(1024,3), 2);
            $strSuffix = "GB";
        }
        elseif ($nBytes>= pow(2,20))
        {
            $strReturn = round($nBytes / pow(1024,2), 2);
            $strSuffix = "MB";
        }
        elseif ($nBytes>= pow(2,10))
        {
            $strReturn = round($nBytes / pow(1024,1), 2);
            $strSuffix = "KB";
        }
        else
        {
            $strReturn = $nBytes;
            $strSuffix = "Byte";
        }

        if ($strReturn == 1)
        {
            $strReturn .= " " . $strSuffix;
        }
        else
        {
            $strReturn .= " " . $strSuffix . "s";
        }

        return $strReturn;
}//end format bytes




//gets distance from lat/long
function fnGetRiemannDistance($lat_from, $long_from, $lat_to, $long_to, $unit='k'){
	 /*** distance unit ***/
	 switch ($unit):
	 /*** miles ***/
	 case 'm':
		$unit = 3963;
		break;
	 /*** nautical miles ***/
	 case 'n':
		$unit = 3444;
		break;
	 default:
		/*** kilometers ***/
		$unit = 6371;
	 endswitch;
	
	 /*** 1 degree = 0.017453292519943 radius ***/
	 $degreeRadius = deg2rad(1);
	 
	 /*** convert longitude and latitude to radians ***/
	 $lat_from  *= $degreeRadius;
	 $long_from *= $degreeRadius;
	 $lat_to    *= $degreeRadius;
	 $long_to   *= $degreeRadius;
	 
	 /*** apply the Great Circle Distance Formula ***/
	 $dist = sin($lat_from) * sin($lat_to) + cos($lat_from)
	 * cos($lat_to) * cos($long_from - $long_to);
	 
	 /*** radius of earth * arc cosine ***/
	 return ($unit * acos($dist));
	
	
	/*** example usage
	/*** Barcelona
	$lat_from = 18.4525;
	$long_from = -66.538889;
	
	/*** High Springs Florida 
	$lat_to = 29.841022;
	$long_to = -82.615628;
	
	/*** calculate the distance in miles 
	$distance = getRiemannDistance($lat_from, $long_from, $lat_to, $long_to, 'm');
	
	/*** round it off and echo 
	echo round( $distance ) . ' miles';
	*/

}

//send html email
function fnSendHTMLEmail($toAddress, $toName, $fromAddress, $fromName, $subject, $body, $commaSeperatedAttachs = ""){
	
		//be sure mailer is included
		require_once("class.Phpmailer.php");	
	
		//mailer object
		$mail = new PHPMailer();
		
		//if we are sending using SMTP then these constants will be set...
		if(defined("APP_MAIL_USE_SMTP") && defined("APP_MAIL_SERVER") && defined("APP_MAIL_SERVER_USER") && defined("APP_MAIL_SERVER_PASS") ){
			if(strtoupper(APP_MAIL_USE_SMTP) == "YES"){
				$mail->IsSMTP(); 
				$mail->Host = APP_MAIL_SERVER;
				$mail->SMTPAuth = true;
				$mail->Username = APP_MAIL_SERVER_USER;
				$mail->Password = APP_MAIL_SERVER_PASS;
			}
		}
		
		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
		$mail->AddAddress($toAddress, $toName);
		$mail->AddReplyTo($fromAddress);
		if($commaSeperatedAttachs != ""){ // add attachments
			$arrAttachs = explode(",",$commaSeperatedAttachs);
			$x = 0;
			for($x = 0;$x < count($arrAttachs);$x++){
				$thisAtt = $arrAttachs[$x];
				$mail->AddAttachment($thisAtt);
			}
		} 
		$mail->IsHTML(true);    
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->AltBody = "Your email client does not appear to support HTML emails. Please upgrade your email client before viewing this email.";
	
		if($mail->Send()){
			return true;
		}else{//could not send email
			return false;
		}
}

//send html email
function fnSendTextEmail($toAddress, $toName, $fromAddress, $fromName, $subject, $body, $commaSeperatedAttachs = ""){
		
		//be sure mailer is included
		require_once("class.Phpmailer.php");	
		
		//mailer object
		$mail = new PHPMailer();
		
		//if we are sending using SMTP then these constants will be set...
		if(defined("APP_MAIL_USE_SMTP") && defined("APP_MAIL_SERVER") && defined("APP_MAIL_SERVER_USER") && defined("APP_MAIL_SERVER_PASS") ){
			if(strtoupper(APP_MAIL_USE_SMTP) == "YES"){
				$mail->IsSMTP(); 
				$mail->Host = APP_MAIL_SERVER;
				$mail->SMTPAuth = true;
				$mail->Username = APP_MAIL_SERVER_USER;
				$mail->Password = APP_MAIL_SERVER_PASS;
			}
		}		
		
		$mail->From = $fromAddress;
		$mail->FromName = $fromName;
		$mail->AddAddress($toAddress, $toName);
		$mail->AddReplyTo($fromAddress);
		if($commaSeperatedAttachs != ""){ // add attachments
			$arrAttachs = explode(",", $commaSeperatedAttachs);
			$x = 0;
			for($x = 0;$x < count($arrAttachs);$x++){
				$thisAtt = $arrAttachs[$x];
				$mail->AddAttachment($thisAtt);
			}
		} 
		$mail->Subject = $subject;
		$mail->Body = $body;
		$mail->IsHTML(false);    
		
		if($mail->Send()){
			return true;
		}else{
			return false;
		}
}

//map link, no address
function fnGetMapUrl($address1 = "", $city = "", $state = "", $zip = "", $lat = "", $lon = "", $title = ""){
	if($lat == "" && $lon == ""){
		$address1 = trim($address1);
		$city = trim($city);
		$state = trim($state);
		$zip = trim($zip);
		$r = "http://maps.google.com/maps?q=" . urlencode($address1) . "," . urlencode($city) . "," . urlencode($state) . "," . urlencode($zip);
	}else{
		$lat = trim($lat);
		$lon = trim($lon);
		$title = trim($title);
		$r = "http://maps.google.com/maps?q=" . $lat . ",+" . $lon . "+(" . $title . ")";
	}
	return $r;
}

	
//resize image
function resizeImage($originalImagePath, $width, $height, $scale){
	list($imagewidth, $imageheight, $imageType) = getimagesize($originalImagePath);
	$imageType = image_type_to_mime_type($imageType);
	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = @imagecreatetruecolor($newImageWidth, $newImageHeight);
	
	switch($imageType) {
		case "image/gif":
			$source = @imagecreatefromgif($originalImagePath); 
			break;
		case "image/pjpeg":
		case "image/jpeg":
		case "image/jpg":
			$source = @imagecreatefromjpeg($originalImagePath); 
			break;
		case "image/png":
		case "image/x-png":
			$source = @imagecreatefrompng($originalImagePath); 
			break;
	}
	
	// $source will be false on error
	if($source){
	
		//required to save transparency...
		@imagealphablending( $newImage, false );
		@imagesavealpha( $newImage, true );
	
		@imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newImageWidth, $newImageHeight, $width, $height);
	
		switch($imageType) {
			case "image/gif":
				imagegif($newImage, $originalImagePath); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage, $originalImagePath, 90); 
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage, $originalImagePath);  
				break;
		}

		//set file permissions
		chmod($originalImagePath, 0755);
		return true;
		
	}else{
		return false;
	}

}

//resize thumb
function resizeThumbnailImage($small_image_location, $image, $width, $height, $start_width, $start_height, $scale){
	list($imagewidth, $imageheight, $imageType) = getimagesize($image);
	$imageType = image_type_to_mime_type($imageType);
	
	$newImageWidth = ceil($width * $scale);
	$newImageHeight = ceil($height * $scale);
	$newImage = @imagecreatetruecolor($newImageWidth,$newImageHeight);
	
	switch($imageType) {
		case "image/gif":
			$source = @imagecreatefromgif($image); 
			break;
		case "image/pjpeg":
		case "image/jpeg":
		case "image/jpg":
			$source = @imagecreatefromjpeg($image); 
			break;
		case "image/png":
		case "image/x-png":
			$source = @imagecreatefrompng($image); 
			break;
	}
	
	// $source will be false on error
	if($source){
	
		//required to save transparency...
		@imagealphablending( $newImage, false );
		@imagesavealpha( $newImage, true );
	
		@imagecopyresampled($newImage,$source,0,0,$start_width,$start_height,$newImageWidth,$newImageHeight,$width,$height);
		
		switch($imageType) {
			case "image/gif":
				imagegif($newImage, $small_image_location); 
				break;
			case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				imagejpeg($newImage, $small_image_location, 90); 
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage, $small_image_location);  
				break;
		}
		
		chmod($small_image_location, 0755);
		return true;
		
	}else{
		return false;
	}
}






	

?>