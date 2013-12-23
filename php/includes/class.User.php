<?php 

class User {
	public $infoArray;
		
   function __construct($guid = ""){
   		$tmp = "id, guid, userType, firstName, lastName, email,";
		$tmp .= "logInId, logInPassword, dateStampUTC, modifiedUTC, timeZone, contextVars, ";
		$tmp .= "lastPageRequest, isLoggedIn, sessionGuid, status, hideFromControlPanel, pageRequests";
		$fields = explode(",", $tmp);
		$this->infoArray = array();

		for($i = 0; $i < count($fields); $i++){
			$field = trim($fields[$i]);
			if($field != "") $this->infoArray[$field] = "";
		}

		//fill infoArray values if possible..
		if($guid != ""){
			$strSql = "SELECT ";
			for($i = 0; $i < count($fields); $i++){
				$field = trim($fields[$i]);
				if($field != "") $strSql .= $field . ", ";
			}
			$strSql = fnRemoveLastChar($strSql, ",");			
			$strSql .= " FROM " . TBL_USERS;
			$strSql .= " WHERE guid =  '" . $guid . "' ";
			$strSql .= " LIMIT 0, 1";
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($res){
				$numRows = mysql_num_rows($res);	
				if($numRows == 1){
					$row = mysql_fetch_array($res);
					//loop fields, add each field-value to info array
					$numFields = mysql_num_fields($res);
					for($i = 0; $i < $numFields; $i++){
						$this->infoArray[mysql_field_name($res, $i)] = $row[$i];
					} 
				}//if num rows
			}//end res
		}//guid = ""
  	}		
	
	function fnDelete($guid = ""){
		if($guid != ""){
			$strSql = "DELETE FROM " . TBL_USERS . " WHERE guid = '". $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}
	
	function fnInsert(){
		$strSql = "INSERT INTO " . TBL_USERS . " (";
		//loop field names
		foreach ($this->infoArray as $key=>$value){
			if(strtoupper($key) != "ID"){
				$strSql .= $key . ", ";
			}
		}
		$strSql = fnRemoveLastChar($strSql, ",");
		$strSql .= ") VALUES (";
		//loop values
		foreach ($this->infoArray as $key=>$value){
			if(strtoupper($key) != "ID"){
	
					//apostrophe's may already be escaped.
					$value = str_replace("\'", "'", $value);
					$value = str_replace("'", "\'", $value);
					$strSql .=  "'" . $value . "', ";



			}
		}
		$strSql = fnRemoveLastChar($strSql, ",");
		$strSql .= ")";
		fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
	}
		

	function fnIsEmailInUse($email = "", $guid = ""){
		$r = false;
		if($email != ""){
			$strSql = "SELECT U.email FROM " . TBL_USERS . " AS U ";
			$strSql .= " WHERE U.email =  '" . $email . "' AND U.status != 'deleted' ";
			if($guid != "") $strSql .= " AND guid != '" . $guid . "'";
			$strSql .= " LIMIT 0, 1";
			$tmp = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($tmp  != "" && $tmp  != "0"){
				$r = true;
			}
		}
	  	return $r;
	}

	function fnUpdateLastRequest($guid = "", $isLoggedIn = ""){
		if($guid != ""){
			$dtNow = fnMySqlNow();
			$strSql = "UPDATE " . TBL_USERS . " SET ";
			$strSql .= "lastPageRequest = '" . $dtNow . "', pageRequests = (pageRequests + 1) ";
			if($isLoggedIn != "") $strSql .= ", isLoggedIn = " . $isLoggedIn;
			$strSql .= " WHERE guid = '" . $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		}
	}

	function fnUpdateContextVars($guid = "", $contextVars = ""){
		if($guid != ""){
			$dtNow = fnMySqlNow();
			$strSql = "UPDATE " . TBL_USERS . " SET ";
			$strSql .= "contextVars = '" . $contextVars . "'";
			$strSql .= " WHERE guid = '" . $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		}
	}



	function fnUpdatePassword($guid = "", $logInPassword = ""){
		if($guid != "" && $logInPassword != ""){
			$dtNow = fnMySqlNow();
			$strSql = "UPDATE " . TBL_USERS . " SET ";
			$strSql .= "modifiedUTC = '" . $dtNow . "', ";
			$strSql .= "logInPassword = '" . md5($logInPassword) . "' ";
			$strSql .= " WHERE guid = '" . $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
		}
	}

	function fnIsValidLogin($logInId, $logInPassword, $isIphoneLogin = false){
		$r = "";
		$guid = "";
		$sessionGuid = "";
		$userId = "";
		$dtNow = fnMySqlNow();
		if($logInId != "" && $logInPassword != ""){
			//validate login
			if($isIphoneLogin == false){
				//return only the guid
				$strSql = "SELECT MD5(UNIX_TIMESTAMP() + U.id + RAND(UNIX_TIMESTAMP())) AS myUnique, ";
				$strSql .= "U.id AS userId, U.guid AS userGuid FROM " . TBL_USERS . " AS U ";
			}else{
				//return multiple values for iPhone application login
				$strSql = " SELECT CONCAT(U.guid,'|',U.email) AS userGuid FROM " . TBL_USERS . " AS U ";
			}
			$strSql .= " WHERE U.logInId =  '" . $logInId . "' ";
			$strSql .= " AND U.logInPassword =  '" . md5($logInPassword) . "' ";
			$strSql .= " AND U.status != 'deleted' ";
			$strSql .= " LIMIT 0, 1";
			$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($res){
				$numRows = mysql_num_rows($res);	
				if($numRows > 0){
					$row = mysql_fetch_array($res);
					$guid = $row["userGuid"];
					if($isIphoneLogin == false){
						$userId = $row["userId"];
						$sessionGuid = $row["myUnique"];
					}
				}
			}//res
			if($guid  != "" && $guid  != "0"){
				//return this guid
				$r = $guid;
				
				//update this persons record to "is logged in" and track their session value
				if($isIphoneLogin == false){
					$strUpdate = "UPDATE " . TBL_USERS . " SET isLoggedIn = 1, sessionGuid = '" . $sessionGuid . "', lastPageRequest = '" . $dtNow . "'";
					$strUpdate .= " WHERE id = " . $userId;
					fnExecuteNonQuery($strUpdate, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
				}

			}
		}
	  	return $r;
	}

	//assumes user is logged out...
	function fnLoggedInReq($guid = ""){
		$isLoggedIn = false;
		if($guid != ""){
		
			//see if we are logged in (in the database, has zero to do with sessions or cookies)...
			$strSql = "SELECT isLoggedIn FROM " . TBL_USERS;
			$strSql .= " WHERE guid =  '" . $guid . "'";
			$strSql .= " LIMIT 0, 1";
			$tmpLoggedIn = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($tmpLoggedIn == "1" || $tmpLoggedIn == 1){
				$isLoggedIn = true;
			}
		
		}//guid != ""
		
		if($isLoggedIn){
			return true;
		}else{
			header("Location:" . APP_URL . "/?timedOut=1");
			echo "Your logged in session has ended.";
			echo "<br><br>";
			echo "<a href='" . APP_URL . "' target='_self'>Click here</a> if you are not automatically redirected.";
			exit();
		}
	}

	//must be admin
	function fnAdminRequired($guid = ""){
		//not logged in redirect...
		$isAdmin = false;
		if($guid != ""){
			//see if we are logged in...
			$strSql = "SELECT userType FROM " . TBL_USERS;
			$strSql .= " WHERE guid =  '" . $guid . "'";
			$strSql .= " LIMIT 0, 1";
			$tmpAdmin = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if(strtoupper($tmpAdmin) == "ADMIN"){
				$isAdmin = true;
			}
		}//guid != ""
		if($isAdmin){
			return true;
		}else{
			header("Location:" . APP_URL . "?timedOut=1");
			echo "Your logged in session has ended.";
			echo "<br><br>";
			echo "<a href='" . APP_URL . "' target='_self'>Click here</a> if you are not automatically redirected.";
			exit();
		}
	}
	
	//is admin...
	function fnIsAdmin($userType){
		if(strtoupper($userType) == "ADMIN"){
			return true;
		}else{
			return false;
		}
	}

	
	//is logged in?
	function fnIsLoggedIn($guid = ""){
		//not logged in...
		$isLoggedIn = false;
		if($guid != ""){
			//see if we are logged in...
			$strSql = "SELECT isLoggedIn FROM " . TBL_USERS;
			$strSql .= " WHERE guid =  '" . $guid . "'";
			$strSql .= " LIMIT 0, 1";
			$isLoggedIn = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if($isLoggedIn == "1"){
				$isLoggedIn = true;
			}
		}//guid != ""
		
		return $isLoggedIn;
		
	}
	
	

  
	  
} //end class
?>