<?php 

	/*
		##############################################################################################################
		buzztouch Self Hosted v2.1.9
		This file does several things and it's included in every .php file in this web application. 
		------------------------------
		1) 	It establishes a list of .php CONSTANTS used throughout the application.
		2) 	It begins a .php SESSION using session_start() so session tracking is enabled throughout the application.
		3) 	It includes two .php files so they are available throughout the application.
			../includes/autoloadClasses.php
			../includes/utilityFunctions.php
	
		A database connection is required on every page and screen in the application. Set the values 
		below to suit your requirments. Change only the value of the constant, not the constant name.
		
		##############################################################################################################
	*/
	
	//################################################################################
	//COPY AND PASTE CONFIG VALUES CREATED DURING THE INSTALL PROCESS
	
	/* database server information. Enter your database login credentials here. */
	define("APP_DB_HOST", "localhost");
	define("APP_DB_NAME", "yourDbName");
	define("APP_DB_USER", "yourDbUserName");
	define("APP_DB_PASS", "yourDbPassword");
	
	/* buzztouch.com User Email Address, Password */
	define("APP_BT_ACCOUNT_USEREMAIL", "YOUR_BUZZTOUCH_ACCOUNT_USER_NAME");
	define("APP_BT_ACCOUNT_USERPASS", "YOUR_BUZZTOUCH_ACOUNT_PASSWORD");

	/* buzztouch.com API URL, API Key, API Secret. Log in at buzztouch.com then see Account > Self Hosted Servers */
	define("APP_BT_SERVER_API_URL", "YOUR_BUZZTOUCH_API_URL");
	define("APP_BT_SERVER_API_KEY", "YOUR_BUZZTOUCH_API_KEY");
	define("APP_BT_SERVER_API_KEY_SECRET", "YOUR_BUZZTOUCH_API_SECRET");

	/* application URL. Do not enter the trailing slash (/) after any URL. */
	define("APP_URL", "YOUR_APP_URL");

	/* application physical path on server, data directories. *
	/*These begin with a forward slash (/). Do not enter the trailing slash (/) */
	define("APP_PHYSICAL_PATH", "YOUR_APP_PHYSICAL_PATH");
	define("APP_DATA_DIRECTORY", "/files"); 
	define("APP_THEME_PATH", "/files/theme"); 
	
	/* values used in <head> section of the HTML for many pages in this web based application */
	define("APP_APPLICATION_NAME", "Buzztouch");
	define("APP_DEFAULT_PAGE_TITLE", "Buzztouch");
	define("APP_DEFAULT_PAGE_DESCRIPTION", "Open Source iOS and Android App Platform");
	define("APP_DEFAULT_KEYWORDS", "buzztouch");
	
	/* outbound email settings. Used by the "forgot password" routine on the login page */
	define("APP_ADMIN_EMAIL", "no-reply@domain.com");
	define("APP_MAIL_SERVER", "");
	define("APP_MAIL_SERVER_USER", "");
	define("APP_MAIL_SERVER_PASS", "");
	define("APP_MAIL_USE_SMTP", "0");
	
	/* encryption key for senstive data. Set this once, then DO NOT CHANGE IT. */
	/* letters and numbers only, NO SPACES OR SPECIAL CHARACTERS */
	define("APP_CRYPTO_KEY", "YOUR_UNIQUE_CRYPTO_STRING");
	
	/* google maps API key for application usage map */
	define("APP_GOOGLE_MAPS_API_KEY", "YOUR_GOOGLE_MAPS_API_KEY");
	
	/* miscellaneous settings */
	define("APP_MAX_UPLOAD_SIZE", "52428800");
	define("APP_MAX_EXECUTION_TIME", "360");
	define("APP_LOGGEDIN_EXPIRES_SECONDS", "180");
	define("APP_ERROR_REPORTING", "0");
	define("APP_CURRENT_VERSION", "2.1.9");
	
	/* Cookie names are unique to this installation and must be unique values such as a GUID */
	define("APP_LOGGEDIN_COOKIE_NAME", "YOUR_UNIQUE_LOGGEDIN_COOKIE_NAME");
	define("APP_REMEMBER_COOKIE_NAME", "YOUR_UNIQUE_REMEMBER_COOKIE_NAME");

	/* database table names. Do not change these unless you know what you're doing */
	define("TBL_USERS", "bt_users");
	define("TBL_APPLICATIONS", "bt_applications");
	define("TBL_APP_USERS", "bt_app_users");
	define("TBL_CP_LINKS", "bt_cp_links");
	define("TBL_BT_ITEMS", "bt_items");
	define("TBL_BT_FILES", "bt_files");
	define("TBL_BT_PLUGINS", "bt_plugins");
	define("TBL_API_REQUESTS", "bt_api_requests");
	define("TBL_API_KEYS", "bt_api_keys");	
	define("TBL_APN_DEVICES", "bt_apn_devices");
	define("TBL_APN_QUEUE", "bt_apn_queue");
	
	//END COPY AND PASTE VALUES CREATED DURING THE INSTALL PROCESS
	//################################################################################	
	
	
	/*
		##############################################################################################################
			DO NOT CHANGE ANYTHING BELOW THIS LINE. 
		##############################################################################################################
	*/

	//turn error warning on / off...	
	if(defined("APP_ERROR_REPORTING")){
		if(APP_ERROR_REPORTING == "1"){
			@error_reporting(E_ALL);
			@ini_set("display_errors", "1");
		}else{
			@error_reporting(0);
			@ini_set("display_errors", "0");
		}
	}else{
		@error_reporting(0);
		@ini_set("display_errors", "0");
	}
	
	//php error handler..
	function handleShutdown(){
        $error = error_get_last();
        if($error !== NULL){
			
			// handle error..email it, print it, whatever floats your boat...the "info" variable holds the details...
			$info = "File: " . $error['file'];
			$info .= "<br/>Line: " . $error['line'];
			$info .= "<br/>Message: " . $error['message'] . PHP_EOL;
        	
			//UNCOMMENT THIS TO SHOQ PHP ERROR AND WARNING DETAILS ON EACH SCREEN 
			/*
			echo "<div style='border:1px solid red;padding:10px;margin:10px;background-color:#FFFFFF;'>";
				echo "Oops, a PHP error was trapped.";
				echo "<hr>";
				echo $info;
			echo "<div>";
			exit();
			*/
			
			
		}
    }
	
	//register the php error handler...
	@register_shutdown_function('handleShutdown');

	
	//we do not begin a session if we are in the /api directory. /api calls do not require session management...
	if(isset($_SERVER["SCRIPT_FILENAME"])){
		 if(strpos($_SERVER["SCRIPT_FILENAME"], "/api") > 0){
		 	
			//this is an API call, do not use "sessionStart()"
			
		 }else{

				//set the session timeout value if we have one
				if(defined("APP_LOGGEDIN_EXPIRES_SECONDS")){
					
					//if -1 is set try to keep this person logged in for a month or so. This is a bad idea in cases
					//where the control panel is used by more than one user!
					if(APP_LOGGEDIN_EXPIRES_SECONDS == "-1"){
						@ini_set("session.gc_maxlifetime", 2592000);
					}else{
						if(is_numeric(APP_LOGGEDIN_EXPIRES_SECONDS)){
							@ini_set("session.gc_maxlifetime", APP_LOGGEDIN_EXPIRES_SECONDS);
						}else{
							@ini_set("session.gc_maxlifetime", 180);
						}
					}
					
				}
				
				//set the name of the session...
				if(defined("APP_LOGGEDIN_COOKIE_NAME")){
					@ini_set("session.name", APP_LOGGEDIN_COOKIE_NAME . "-session");
				}
				
				//begin the .php session...
				if(!@session_start()){
					echo "This server does not appear to support PHP Session variables. Session variables are required to use this software.";
					echo " Enable PHP sessions in your backend configuration. Ask your website administrator for help if this don't understand what this means.";
					exit();
				}
		 }
		 
	}
	
	//PHP sometimes shows a warning if no default time-zone is set...
	//do not change this timezone, even if you're not in LA :-)
	date_default_timezone_set('America/Los_Angeles');

	//include two required files used throughout the application...
	require_once("includes/autoloadClasses.php");	 
	require_once("includes/utilityFunctions.php");
	
	//IMPORTANT: WHITE SPACE (CARRIAGE RETURNS) CANNOT EXIST IN THIS DOCUMENT BEYOND THE PHP CLOSING TAG.
?>