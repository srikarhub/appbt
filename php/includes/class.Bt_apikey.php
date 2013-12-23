<?php 

class Bt_apikey {
	public $infoArray;
		
   function __construct($guid = "", $apiKey = ""){
   		$tmp = "id, guid, apiKey, apiSecret, ownerName, email, ";
		$tmp .= "allowedIPAddress, expiresDate, lastRequestUTC, requestCount, ";
		$tmp .= "dateStampUTC, modifiedUTC, status ";
		$fields = explode(",", $tmp);
		$this->infoArray = array();

		for($i = 0; $i < count($fields); $i++){
			$field = trim($fields[$i]);
			if($field != "") $this->infoArray[$field] = "";
		}

		//fill infoArray values if possible..
		if($guid != "" || $apiKey != ""){
			$strSql = "SELECT ";
			for($i = 0; $i < count($fields); $i++){
				$field = trim($fields[$i]);
				if($field != "") $strSql .= $field . ", ";
			}
			$strSql = fnRemoveLastChar($strSql, ",");			
			$strSql .= " FROM " . TBL_API_KEYS;
			$strSql .= " WHERE id > 0 ";
			if($guid != "") $strSql .= " AND guid =  '" . $guid . "' ";
			if($apiKey != "") $strSql .= " AND apiKey =  '" . $apiKey . "' ";
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
			$strSql = "DELETE FROM " . TBL_API_KEYS . " WHERE guid = '". $guid . "'";
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}
	
	function fnInsert(){
		if($this->infoArray['guid'] != ""){
			$strSql = "INSERT INTO " . TBL_API_KEYS . " (";
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
	}
		

	function fnUpdate(){
		if($this->infoArray['guid'] != ""){
			$strSql = "UPDATE " . TBL_API_KEYS . " SET ";
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
			fnExecuteNonQuery($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
		}
	}

	function fnIsKeyAvailable($apiKey = "", $guid = ""){
		$r = false;
		if($apiKey != ""){
			$strSql = "SELECT apiKey FROM " . TBL_API_KEYS;
			$strSql .= " WHERE apiKey =  '" . $apiKey . "' AND status != 'deleted' ";
			if($guid != "") $strSql .= " AND guid != '" . $guid . "' ";
			$strSql .= " LIMIT 0, 1";
			$tmp = fnGetOneValue($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
			if(strlen($tmp) < 1){
				$r = true;
			}
		}
	  	return $r;
	}

	  
} //end class
?>