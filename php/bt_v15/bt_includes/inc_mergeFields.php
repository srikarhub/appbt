
	<div style='padding-top:5px;'>
    	<b>Use Merge Fields to append device information to the end of the Data URL.</b>
        This advanced approach allows a backend script to capture device information before outputting the screens
        data. If you use merge fields you will need a backend .php script to process the request.
	</div>
	<div style='padding-top:5px;'>
       <b>Available Merge Fields</b> 
         <ul>
            <li>[buzztouchAppId] This is the App Id in your buzztouch control panel</li>
            <li>[buzztouchAPIKey] This is the app API Key in your buzztouch control panel</li>
            <li>[screenId] The unique id of the current screen (useful for determing the app context)</li>
            <li>[userId] The Unique Id of a logged in user (if the app uses login screens)</li>
            <li>[userEmail] The email address of a logged in user</li>
            <li>[deviceId] A globally unique string value assigned to the device.</li>
            <li>[deviceModel] A string value controlled by the device manufacturer.</li>
            <li>[deviceLatitude] A latitude coordinate value (if the device is reporting it's location).</li>
            <li>[deviceLongitude] A longitude coordinate value (if the device is reporting it's location).</li>
        </ul>
        
        <b>If you used this URL...</b>
        <div style='padding-top:5px;'>
            <?php echo APP_URL;?>/localrestaurants.php?deviceLatitude=[deviceLatitude]&deviceLongitude=[deviceLongitude]&userId=[userId]
        </div>
        <div style='padding-top:5px;'>
			<b>The device would request....</b>
		</div>
        <div style='padding-top:5px;'>
            <?php echo APP_URL;?>/localrestaurants.php?latitude=38.4456&longitude=-102.3444&userId=00000
        </div>
        <hr>
	</div>
