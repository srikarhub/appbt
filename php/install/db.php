<?php
	
	
	//errors...
	define("APP_ERROR_REPORTING", "1");

	
	//Creates GUID	
	function fnCreateGuid(){
		$RandomStr_1 = md5(microtime()); 
		$RandomStr_2 = md5(microtime()); 
		$randomString = substr($RandomStr_2, 4, 4) . substr($RandomStr_1, 0, 15) . substr($RandomStr_2, 0, 4); 
		return $randomString;
	}

	//Returns GMT, formatted for MySql
	function fnMySqlNow($dateFormatString = "Y-m-d H:i:s"){
		$gmdate = gmdate($dateFormatString); 
		return $gmdate; //	like 2006-01-01 23:32:01
	}

	//does mySQL exist...
	function fnDoesDatabaseServerExist($dbServer, $dbUser, $dbPass){
		$ret = false;
		$conn  = @mysql_connect($dbServer, $dbUser, $dbPass);
		if(!$conn){
			$ret = false;
		}else{
			$ret = true;
		}
		return $ret;
	}


	//does db exist?
	function fnDoesDatabaseExist($dbServer, $dbName, $dbUser, $dbPass){
		$ret = true;
		$conn  = @mysql_connect($dbServer, $dbUser, $dbPass);
		if(!$conn){
			$ret = false;
		}else{
			if(!@mysql_select_db($dbName)){
				$ret = false;
			}else{	
				$ret = true;
			}
		}
		return $ret;
	}
	
	//creates tables...
	function fnCreateTables($dbServer, $dbName, $dbUser, $dbPass, $dbTablePrefix, $applicationURL, $physPath){
		$ret = false;
		
		//api_keys...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "api_keys (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  apiKey varchar(75) collate utf8_unicode_ci default NULL,
		  apiSecret varchar(75) collate utf8_unicode_ci default NULL,
		  ownerName varchar(100) collate utf8_unicode_ci default NULL,
		  email varchar(75) collate utf8_unicode_ci default NULL,
		  allowedIPAddress varchar(75) collate utf8_unicode_ci default NULL,
		  expiresDate datetime default NULL,
		  lastRequestUTC datetime default NULL,
		  requestCount int(11) default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  status varchar(50) collate utf8_unicode_ci default NULL,
		  PRIMARY KEY  (id),
		  UNIQUE KEY guid (guid),
		  UNIQUE KEY apiKey (apiKey),
		  UNIQUE KEY apiSecretEncrypted (apiSecret)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//api_requests...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "api_requests (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  appGuid varchar(75) collate utf8_unicode_ci default NULL,
		  clientApiKey varchar(75) collate utf8_unicode_ci default NULL,
		  clientRemoteAddress varchar(75) collate utf8_unicode_ci default NULL,
		  clientUserAgent varchar(250) collate utf8_unicode_ci default NULL,
		  requestDirectory varchar(75) collate utf8_unicode_ci default NULL,
		  requestCommand varchar(75) collate utf8_unicode_ci default NULL,
		  requestStatus varchar(75) collate utf8_unicode_ci default NULL,
		  errorMessage varchar(250) collate utf8_unicode_ci default NULL,
		  errorCode int(11) default NULL,
		  requestMethod varchar(75) collate utf8_unicode_ci default NULL,
		  appUserGuid varchar(75) collate utf8_unicode_ci default NULL,
		  deviceId varchar(100) collate utf8_unicode_ci default NULL,
		  deviceModel varchar(250) collate utf8_unicode_ci default NULL,
		  deviceLatitude float default NULL,
		  deviceLongitude float default NULL,
		  dateStampUTC datetime default NULL,
		  PRIMARY KEY  (id),
		  UNIQUE KEY guid (guid),
		  KEY clientApiKey (clientApiKey)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//apn_devices...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix. "apn_devices (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  appGuid varchar(50) collate utf8_unicode_ci default NULL,
		  appUserGuid varchar(50) collate utf8_unicode_ci default NULL,
		  deviceMode varchar(50) collate utf8_unicode_ci default NULL,
		  deviceType varchar(50) collate utf8_unicode_ci default NULL,
		  deviceModel varchar(100) collate utf8_unicode_ci default NULL,
		  deviceLatitude float default NULL,
		  deviceLongitude float default NULL,
		  deviceToken varchar(300) default NULL,
		  dateStampUTC datetime default NULL,
		  PRIMARY KEY  (id),
		  KEY guid (guid),
		  KEY appGuid (appGuid),
		  KEY deviceMode (deviceMode),
		  KEY deviceType (deviceType),
		  KEY deviceToken (deviceToken(50))
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//apn_queue...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix. "apn_queue (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  appGuid varchar(50) collate utf8_unicode_ci default NULL,
		  iosDeviceTokens text default NULL,
		  androidDeviceTokens text default NULL,
		  iosNumTokens int default NULL,
		  androidNumTokens int default NULL,
		  message varchar(300) collate utf8_unicode_ci default NULL,
		  sound varchar(50) collate utf8_unicode_ci default NULL,
		  badge varchar(10) collate utf8_unicode_ci default NULL,
		  dateStampUTC datetime default NULL,
		  iosDateSentUTC datetime default NULL,
		  androidDateSentUTC datetime default NULL,
		  sendToDevices varchar(75) collate utf8_unicode_ci default NULL,
		  status varchar(50) collate utf8_unicode_ci default NULL,
		  PRIMARY KEY (id),
		  KEY guid (guid),
		  KEY appGuid (appGuid)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//app_users...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "app_users (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  appGuid varchar(50) collate utf8_unicode_ci default NULL,
		  userType varchar(50) collate utf8_unicode_ci default NULL,
		  displayName varchar(75) collate utf8_unicode_ci default NULL,
		  email varchar(75) collate utf8_unicode_ci default NULL,
		  encLogInPassword varchar(75) collate utf8_unicode_ci default NULL,
		  status varchar(50) collate utf8_unicode_ci default NULL,
		  numRequests int(11) default '0',
		  lastRequestUTC datetime default NULL,
		  lastLoginUTC datetime default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  PRIMARY KEY  (id),
		  KEY guid (guid,appGuid)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//applications...		
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix. "applications (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  parentAppGuid varchar(50) collate utf8_unicode_ci default NULL,
		  ownerGuid varchar(50) collate utf8_unicode_ci default NULL,
		  apiKey varchar(50) collate utf8_unicode_ci default NULL,
		  appSecret varchar(50) collate utf8_unicode_ci default NULL,
		  version varchar(25) collate utf8_unicode_ci default NULL,
		  currentMode varchar(50) collate utf8_unicode_ci default NULL,
		  currentPublishDate datetime default NULL,
		  currentPublishVersion varchar(50) collate utf8_unicode_ci default NULL,
		  dataDir varchar(500) collate utf8_unicode_ci default NULL,
		  dataURL varchar(500) collate utf8_unicode_ci default NULL,
		  cloudURL varchar(500) collate utf8_unicode_ci default NULL,
		  registerForPushURL varchar(500) collate utf8_unicode_ci default NULL,
		  projectName varchar(75) collate utf8_unicode_ci default NULL,
		  startGPS int(1) default '1',
		  startAPN int(1) default '1',
		  allowRotation varchar(25) collate utf8_unicode_ci default NULL,
		  name varchar(50) collate utf8_unicode_ci default NULL,
		  iconUrl varchar(500) collate utf8_unicode_ci default NULL,
		  iconName varchar(100) collate utf8_unicode_ci default NULL,
		  applePushCertDevPassword varchar(100) collate utf8_unicode_ci default NULL,
		  applePushCertProdPassword varchar(100) collate utf8_unicode_ci default NULL,
		  googleProjectId varchar(100) collate utf8_unicode_ci default NULL,
		  googleProjectApiKey varchar(250) collate utf8_unicode_ci default NULL,
		  scringoAppId varchar(250) collate utf8_unicode_ci default NULL,
		  appAddress varchar(100) collate utf8_unicode_ci default NULL,
		  appCity varchar(50) collate utf8_unicode_ci default NULL,
		  appState varchar(50) collate utf8_unicode_ci default NULL,
		  appZip varchar(20) collate utf8_unicode_ci default NULL,
		  appLatitude float default NULL,
		  appLongitude float default NULL,
		  status varchar(50) collate utf8_unicode_ci default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  viewCount int(11) default NULL,
		  deviceCount int(11) default NULL,
		  PRIMARY KEY  (id),
		  KEY name (name),
		  KEY guid (guid),
		  KEY ownerGuid (ownerGuid),
		  KEY status (status),
		  KEY apiKey (apiKey)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//cp_links...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "cp_links (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  linkType varchar(50) collate utf8_unicode_ci default NULL,
		  linkLabel varchar(250) collate utf8_unicode_ci default NULL,
		  linkURL varchar(250) collate utf8_unicode_ci default NULL,
		  linkTarget varchar(50) collate utf8_unicode_ci default NULL,
		  orderIndex int(11) default NULL,
		  isEditable int(11) default NULL,
		  modifiedUTC datetime default NULL,
		  modifiedByGuid varchar(50) collate utf8_unicode_ci default NULL,
		  PRIMARY KEY  (id)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//files...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "files (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  appGuid varchar(50) collate utf8_unicode_ci default NULL,
		  fileName varchar(100) collate utf8_unicode_ci default NULL,
		  filePath varchar(100) collate utf8_unicode_ci default NULL,
		  fileType varchar(50) collate utf8_unicode_ci default NULL,
		  fileSize int(11) default '0',
		  fileWidth int(11) default '0',
		  fileHeight int(11) default '0',
		  status varchar(50) collate utf8_unicode_ci default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  PRIMARY KEY  (id),
		  KEY guid (guid,appGuid),
		  KEY appGuid (appGuid),
		  KEY fileLabel (fileName),
		  KEY fileType (fileType),
		  KEY modifiedUTC (modifiedUTC),
		  KEY status (status)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);
		
		//items...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "items (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  parentItemGuid varchar(50) collate utf8_unicode_ci default NULL,
		  uniquePluginId varchar(100) collate utf8_unicode_ci default NULL,
		  loadClassOrActionName varchar(100) collate utf8_unicode_ci default NULL,
		  hasChildItems int(11) default NULL,
		  loadItemGuid varchar(50) collate utf8_unicode_ci default NULL,
		  appGuid varchar(50) collate utf8_unicode_ci default NULL,
		  controlPanelItemType varchar(50) collate utf8_unicode_ci default NULL,
		  itemType varchar(50) collate utf8_unicode_ci default NULL,
		  itemTypeLabel varchar(50) collate utf8_unicode_ci default NULL,
		  nickname varchar(50) collate utf8_unicode_ci default NULL,
		  orderIndex int(11) default '0',
		  jsonVars text collate utf8_unicode_ci,
		  status varchar(50) collate utf8_unicode_ci default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  PRIMARY KEY  (id),
		  KEY guid (guid,appGuid),
		  KEY appGuid (appGuid),
		  KEY nickname (nickname),
		  KEY orderIndex (orderIndex),
		  KEY controlPanelItemType (controlPanelItemType),
		  KEY modifiedUTC (modifiedUTC),
		  KEY itemTypeLabel (itemTypeLabel),
		  KEY status (status),
		  KEY uniquePluginId (uniquePluginId),
		  KEY loadClassOrActionName (loadClassOrActionName)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);

		//plugins...
		$sql = "CREATE TABLE IF NOT EXISTS " . $dbTablePrefix . "plugins (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  category varchar(50) collate utf8_unicode_ci default NULL,
		  uniquePluginId varchar(250) collate utf8_unicode_ci default NULL,
		  displayAs varchar(100) collate utf8_unicode_ci default NULL,
		  versionNumber double default NULL,
		  versionString varchar(50) collate utf8_unicode_ci default NULL,
		  loadClassOrActionName varchar(100) collate utf8_unicode_ci default NULL,
		  hasChildItems int(11) default NULL,
		  defaultJsonVars text collate utf8_unicode_ci,
		  webDirectoryName varchar(250) collate utf8_unicode_ci default NULL,
		  landingPage varchar(50) collate utf8_unicode_ci default NULL,
		  shortDescription varchar(500) collate utf8_unicode_ci default NULL,
		  authorName varchar(100) collate utf8_unicode_ci default NULL,
		  authorWebsiteURL varchar(250) collate utf8_unicode_ci default NULL,
		  authorEmail varchar(250) collate utf8_unicode_ci default NULL,
		  authorBuzztouchURL varchar(250) collate utf8_unicode_ci default NULL,
		  authorTwitterURL varchar(250) collate utf8_unicode_ci default NULL,
		  authorFacebookURL varchar(250) collate utf8_unicode_ci default NULL,
		  authorLinkedInURL varchar(250) collate utf8_unicode_ci default NULL,
		  authorYouTubeURL varchar(250) collate utf8_unicode_ci default NULL,
		  updateURL varchar(250) collate utf8_unicode_ci default NULL,
		  downloadURL varchar(250) collate utf8_unicode_ci default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  modifiedByGuid varchar(50) collate utf8_unicode_ci default NULL,
		  supportedDevices varchar(50) collate utf8_unicode_ci default NULL,
		  PRIMARY KEY  (id),
		  KEY uniquePluginId (uniquePluginId)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
				
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);

		
		//users...
		$sql = "CREATE TABLE IF NOT EXISTS " .  $dbTablePrefix . "users (
		  id int(11) NOT NULL auto_increment,
		  guid varchar(50) collate utf8_unicode_ci default NULL,
		  userType varchar(50) collate utf8_unicode_ci default NULL,
		  email varchar(100) collate utf8_unicode_ci default NULL,
		  emailOptOut int(1) default '0',
		  firstName varchar(75) collate utf8_unicode_ci default NULL,
		  lastName varchar(75) collate utf8_unicode_ci default NULL,
		  logInId varchar(75) collate utf8_unicode_ci default NULL,
		  logInPassword varchar(75) collate utf8_unicode_ci default NULL,
		  status varchar(75) collate utf8_unicode_ci default NULL,
		  hideFromControlPanel int(11) default NULL,
		  timeZone int(11) default NULL,
		  contextVars varchar(250) collate utf8_unicode_ci default NULL,
		  lastPageRequest datetime default NULL,
		  isLoggedIn int(1) default NULL,
		  sessionGuid varchar(50) collate utf8_unicode_ci default NULL,
		  pageRequests int(11) default NULL,
		  dateStampUTC datetime default NULL,
		  modifiedUTC datetime default NULL,
		  PRIMARY KEY  (id),
		  UNIQUE KEY email (email),
		  UNIQUE KEY logInId (logInId),
		  KEY guid (guid),
		  KEY sessionGuid (sessionGuid),
		  KEY status (status)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
		
		//execute....
		fnExecuteNonQuery($sql, $dbServer, $dbName, $dbUser, $dbPass);


		/////////////////////////////////////////////////////////////////
		//for inserts..
		$dtNow = fnMySqlNow();
		
		//insert default user if one doesn't exist...
		$tmp = "SELECT Count(id) FROM " . $dbTablePrefix . "users ";
		$iUserCount = fnGetOneValue($tmp, $dbServer, $dbName, $dbUser, $dbPass);
		$userGuid = "";
		if($iUserCount < 1){
		
			//create a new user...
			$newUserGuid = strtoupper(fnCreateGuid());
			$userGuid = $newUserGuid;
			$tmp = "INSERT INTO " . $dbTablePrefix . "users (guid, userType, firstName, lastName, email, ";
			$tmp .= "logInId, logInPassword, dateStampUTC, modifiedUTC, lastPageRequest, isLoggedIn, timeZone, contextVars,";
			$tmp .= "pageRequests, status, hideFromControlPanel ) VALUES ( '" . $newUserGuid . "', ";
			$tmp .= "'admin', 'buzztouch', 'fan', 'fans@buzztouch.com', 'fans@buzztouch.com', '79b98fb57b34bacfee2130bdd8d23530', ";
			$tmp .= "'" . $dtNow . "', '" . $dtNow . "', '" . $dtNow . "', '0', '7', '', '0', 'active', '0')";
			fnExecuteNonQuery($tmp, $dbServer, $dbName, $dbUser, $dbPass);
		
		}
				
				
		//remove existing links that are "application owned" so we can replace them with the newest ones...
		$tmpSql = "DELETE FROM " . $dbTablePrefix . "cp_links WHERE isEditable = '0' ";
		fnExecuteNonQuery($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
				
		//required /account control panel links...
		$reqLinksAccount = array();
		$reqLinksAccount["Account Home"] = "/account/";
		$reqLinksAccount["Create New Application"] = "/bt_v15/bt_app/bt_appNew.php";
		$reqLinksAccount["Update Your Name"] = "/account/account_name.php";
		$reqLinksAccount["Update Email Address"] = "/account/account_email.php";
		$reqLinksAccount["Update Password"] = "/account/account_password.php";
		$reqLinksAccount["Update Time Zone"] = "/account/account_timeZone.php";
		
		//insert each /account control panel link if it doesn't exist...
		$cnt = 0;
		foreach ($reqLinksAccount as $key => $value){
			
			//check to see if it already exists...
			$tmpSql = "SELECT id FROM " . $dbTablePrefix . "cp_links WHERE linkURL = '" . fnFormInput($value) . "' ";
			$tmpId = fnGetOneValue($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
			if($tmpId == "" || $tmpId == "0"){
		
				$tmpSql = "INSERT INTO " . $dbTablePrefix . "cp_links (guid, linkType, linkLabel, linkURL, ";
				$tmpSql .= "linkTarget, orderIndex, isEditable, modifiedUTC, modifiedByGuid) VALUES ('" . strtoupper(fnCreateGuid()) . "', ";
				$tmpSql .= "'account', '" . fnFormInput($key) . "','" . fnFormInput($value) . "', '_self',";
				$tmpSql .= "'" . $cnt . "', '0', '" . $dtNow . "', '" . $userGuid . "')";
				fnExecuteNonQuery($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
				$cnt++;
				
			}
			
		}//for each account link
		
		//required /admin control panel links...
		$reqLinksAdmin = array();
		$reqLinksAdmin["Application List"] = "/admin/";
		$reqLinksAdmin["Control Panel Users"] = "/admin/users.php";
		$reqLinksAdmin["Manage Plugins"] = "/admin/plugins.php";
		$reqLinksAdmin["Server Settings"] = "/admin/settings.php";
		$reqLinksAdmin["Manage Data Access"] = "/admin/keys.php";
		$reqLinksAdmin["Control Panel Links"] = "/admin/admin_links.php";
		$reqLinksAdmin["System Maintenance"] = "/admin/maintenance.php";
		$reqLinksAdmin["Show PHP Info"] = "/admin/php_info.php";
		$reqLinksAdmin["Show Database Info"] = "/admin/db_info.php";
		$reqLinksAdmin["About This Software"] = "/admin/about.php";
		$reqLinksAdmin["Credits / Copyrights"] = "/admin/credits.php";

		//insert each /admin control panel link if it doesn't exist...
		$cnt = 0;
		foreach ($reqLinksAdmin as $key => $value){
			
			//check to see if it already exists...
			$tmpSql = "SELECT id FROM " . $dbTablePrefix . "cp_links WHERE linkURL = '" . fnFormInput($value) . "' ";
			$tmpId = fnGetOneValue($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
			if($tmpId == "" || $tmpId == "0"){
		
				$tmpSql = "INSERT INTO " . $dbTablePrefix . "cp_links (guid, linkType, linkLabel, linkURL, ";
				$tmpSql .= "linkTarget, orderIndex, isEditable, modifiedUTC, modifiedByGuid) VALUES ('" . strtoupper(fnCreateGuid()) . "', ";
				$tmpSql .= "'admin', '" . fnFormInput($key) . "','" . fnFormInput($value) . "', '_self',";
				$tmpSql .= "'" . $cnt . "', '0', '" . $dtNow . "', '" . $userGuid . "')";
				fnExecuteNonQuery($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
				$cnt++;
				
			}
			
		}//for each admin link
		
		//11/18/2011, update "Manage API Keys" link to "Manage Data Access"
		$tmpSql = "UPDATE " . $dbTablePrefix . "cp_links SET linkLabel = 'Manage Data Access' ";
		$tmpSql .= "WHERE linkLabel = 'Manage API Keys' AND linkType = 'admin' ";
		fnExecuteNonQuery($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);


		//required /app control panel links...
		$reqLinksApp = array();
		$reqLinksApp["App Icon"] = "/bt_v15/bt_app/bt_icon.php";
		$reqLinksApp["Core"] = "/bt_v15/bt_app/bt_core.php";
		$reqLinksApp["Layout"] = "/bt_v15/bt_app/bt_layout.php";
		$reqLinksApp["Theme"] = "/bt_v15/bt_app/bt_theme.php";
		$reqLinksApp["Screens"] = "/bt_v15/bt_app/bt_screens.php";
		$reqLinksApp["Menus"] = "/bt_v15/bt_app/bt_menus.php";
		$reqLinksApp["App Users"] = "/bt_v15/bt_app/bt_users.php";
		$reqLinksApp["Files / Media"] = "/bt_v15/bt_app/bt_files.php";
		$reqLinksApp["JSON Data"] = "/bt_v15/bt_app/bt_configData.php";
		$reqLinksApp["Publish Changes"] = "/bt_v15/bt_app/bt_appVersion.php";

		//insert each /admin control panel link if it doesn't exist...
		$cnt = 0;
		foreach ($reqLinksApp as $key => $value){
			
			//check to see if it already exists...
			$tmpSql = "SELECT id FROM " . $dbTablePrefix . "cp_links WHERE linkURL = '" . fnFormInput($value) . "' ";
			$tmpId = fnGetOneValue($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
			if($tmpId == "" || $tmpId == "0"){
		
				$tmpSql = "INSERT INTO " . $dbTablePrefix . "cp_links (guid, linkType, linkLabel, linkURL, ";
				$tmpSql .= "linkTarget, orderIndex, isEditable, modifiedUTC, modifiedByGuid) VALUES ('" . strtoupper(fnCreateGuid()) . "', ";
				$tmpSql .= "'application', '" . fnFormInput($key) . "','" . fnFormInput($value) . "', '_self',";
				$tmpSql .= "'" . $cnt . "', '0', '" . $dtNow . "', '" . $userGuid . "')";
				fnExecuteNonQuery($tmpSql, $dbServer, $dbName, $dbUser, $dbPass);
				$cnt++;
				
			}
			
		}//for each application link
		

		/////////////////////////////////////////////////////////////
		//alter bt_applications for existing installs < 2.1.8...
		$newCols = array();
		$newCols[] = "currentMode";
		$newCols[] = "currentPublishDate";
		$newCols[] = "currentPublishVersion";
		$newCols[] = "dataDir";
		$newCols[] = "registerForPushURL";
		$newCols[] = "startAPN";
		$newCols[] = "applePushCertDevPassword";
		$newCols[] = "applePushCertProdPassword";
		$newCols[] = "googleProjectId";
		$newCols[] = "googleProjectApiKey";
		$newCols[] = "scringoAppId";
		$newCols[] = "deviceCount";
		
		$alterCols = array();
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD currentMode varchar(50) DEFAULT NULL AFTER version";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD currentPublishDate dateTime DEFAULT NULL AFTER currentMode";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD currentPublishVersion varchar(50) DEFAULT NULL AFTER currentPublishDate";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD dataDir varchar(500) DEFAULT NULL AFTER currentPublishVersion";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD registerForPushURL varchar(500) DEFAULT NULL AFTER cloudURL";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD startAPN int(1) DEFAULT NULL AFTER startGPS";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD applePushCertDevPassword varchar(100) DEFAULT NULL AFTER iconName";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD applePushCertProdPassword varchar(100) DEFAULT NULL AFTER applePushCertDevPassword";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD googleProjectId varchar(100) DEFAULT NULL AFTER applePushCertProdPassword";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD googleProjectApiKey varchar(250) DEFAULT NULL AFTER googleProjectId";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD scringoAppId varchar(250) DEFAULT NULL AFTER googleProjectApiKey";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "applications ADD deviceCount int DEFAULT NULL AFTER viewCount";

		//get existing columns so we can test if these exist or not...
		$strSql = "SHOW COLUMNS FROM " . $dbTablePrefix . "applications";
		$res = fnDbGetResult($strSql, $dbServer, $dbName, $dbUser, $dbPass); 
		$cnt = 0;
		$existingCols = array();
		if($res){
			$numRows = mysql_num_rows($res);
			$fields_num = mysql_num_fields($res);
			if($numRows > 0){
				while ($row = mysql_fetch_array($res)){
					$existingCols[] = $row["Field"];
				}
			}
		}
		
		//alter if not exists...
		$cnt = 0;
		foreach ($newCols as $key => $value){
			if(!in_array($value, $existingCols)){
				
				//execute alter statement...
				fnExecuteNonQuery($alterCols[$cnt], $dbServer, $dbName, $dbUser, $dbPass);
					
			}
			
			//increment...
			$cnt++;
		}//for


		/////////////////////////////////////////////////////////////
		//alter bt_users for existing installs < 2.1.8...
		$newCols = array();
		$newCols[] = "contextVars";

		$alterCols = array();
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "users ADD contextVars varchar(250) DEFAULT NULL AFTER timeZone";

		//get existing columns so we can test if these exist or not...
		$strSql = "SHOW COLUMNS FROM " . $dbTablePrefix . "users";
		$res = fnDbGetResult($strSql, $dbServer, $dbName, $dbUser, $dbPass); 
		$cnt = 0;
		$existingCols = array();
		if($res){
			$numRows = mysql_num_rows($res);
			$fields_num = mysql_num_fields($res);
			if($numRows > 0){
				while ($row = mysql_fetch_array($res)){
					$existingCols[] = $row["Field"];
				}
			}
		}
		
		//alter if not exists...
		$cnt = 0;
		foreach ($newCols as $key => $value){
			if(!in_array($value, $existingCols)){
				
				//execute alter statement...
				fnExecuteNonQuery($alterCols[$cnt], $dbServer, $dbName, $dbUser, $dbPass);
					
			}
			
			//increment...
			$cnt++;
		}//for

		/////////////////////////////////////////////////////////////
		//alter bt_plugins for existing installs < 2.1.8...
		$newCols = array();
		$newCols[] = "supportedDevices";
		$newCols[] = "landingPage";

		$alterCols = array();
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "plugins ADD supportedDevices varchar(50) DEFAULT NULL AFTER modifiedByGuid";
		$alterCols[] = "ALTER TABLE " . $dbTablePrefix . "plugins ADD landingPage varchar(50) DEFAULT NULL AFTER webDirectoryName";

		//get existing columns so we can test if these exist or not...
		$strSql = "SHOW COLUMNS FROM " . $dbTablePrefix . "plugins";
		$res = fnDbGetResult($strSql, $dbServer, $dbName, $dbUser, $dbPass); 
		$cnt = 0;
		$existingCols = array();
		if($res){
			$numRows = mysql_num_rows($res);
			$fields_num = mysql_num_fields($res);
			if($numRows > 0){
				while ($row = mysql_fetch_array($res)){
					$existingCols[] = $row["Field"];
				}
			}
		}
		
		//alter if not exists...
		$cnt = 0;
		foreach ($newCols as $key => $value){
			if(!in_array($value, $existingCols)){
				
				//execute alter statement...
				fnExecuteNonQuery($alterCols[$cnt], $dbServer, $dbName, $dbUser, $dbPass);
					
			}
			
			//increment...
			$cnt++;
		}//for

		///////////////////////////////////////////////////
		//update table applications....
		$updates = array();
		$updates[] = "UPDATE " . $dbTablePrefix . "applications SET dataDir = CONCAT('/files/applications/', guid) WHERE dataDir = ''";
		$updates[] = "UPDATE " . $dbTablePrefix . "applications SET startAPN = '0' WHERE startAPN = ''";
		$updates[] = "UPDATE " . $dbTablePrefix . "applications SET deviceCount = '0' WHERE deviceCount = ''";

		//updates...
		$cnt = 0;
		foreach ($updates as $key => $value){
				
			//execute update statement...
			fnExecuteNonQuery($updates[$cnt], $dbServer, $dbName, $dbUser, $dbPass);
					
			//increment...
			$cnt++;
		}//for


		//All done! return...
		return true;
		
	}
	





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
			die("An error ocurred in the fnDbGetResult() method in db.php (3)" . fnSqlError());
		}
	}else{ // fetch array
		
		$result = mysql_query($theSql, $conn);
		if($result){
			return $result;
		}else{
			if(APP_ERROR_REPORTING > 0){
				die("<hr>" . $theSql . "<hr>" . fnSqlError());
			}else{
				die("An error ocurred in the fnDbGetResult() method in db.php (4)" . fnSqlError());
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
			die("An error ocurred in the fnExecuteNonQuery() method in db.php (5)" . fnSqlError());
		}
	}else{ // fetch array
		
		$result = mysql_query($theSql, $conn);
		if($result){
			return 1;
		}else{
			if(APP_ERROR_REPORTING > 0){
				die("fnExecuteNonQuery :: fnDbGetResult (get result)<br>" . fnSqlError());
			}else{
				die("An error ocurred in running the fnExecuteNonQuery() method in db.php (6) " . fnSqlError());
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
			die("An error ocurred in running the fnInsertReturnId() method in db.php (7)" . fnSqlError());
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
				die("An error ocurred in running the fnInsertReturnId() method in db.php (8) " . fnSqlError());
			}
		}
	} // end if connected
}
/* end db work */


/* end MYSQL functions */
//###################################################################################


?>