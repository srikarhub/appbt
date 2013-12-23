<?php 

class Appuser {
	public $infoArray;
		
   function __construct($guid = ""){
   		$tmp = "id, guid, appGuid, userType, displayName, email, encLogInPassword, status,";
		$tmp .= "numRequests, lastRequestUTC, lastLoginUTC, dateStampUTC, modifiedUTC";
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
			$strSql .= " FROM " . TBL_APP_USERS;
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
			$strSql = "DELETE FROM " . TBL_APP_USERS . " WHERE guid = '". $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}
	
	function fnInsert(){
		$strSql = "INSERT INTO " . TBL_APP_USERS . " (";
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
		
	function fnUpdate(){
		if($this->infoArray['guid'] != ""){
			$strSql = "UPDATE " . TBL_APP_USERS . " SET ";
			//loop field names
			foreach ($this->infoArray as $key=>$value){
				if(strtoupper($key) != "ID"){

					//apostrophe's may already be escaped.
					$value = str_replace("\'", "'", $value);
					$value = str_replace("'", "\'", $value);
					$strSql .= $key . " = '" . $value . "', ";
					
				}
			}
			$strSql = fnRemoveLastChar($strSql, ",");
			$strSql .= " WHERE guid = '" . $this->infoArray['guid'] . "'";
			//echo $strSql;
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}

	

  
	  
} //end class
?>