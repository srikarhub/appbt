


<?php if(empty($apiKeyGuid)) $apiKeyGuid = "";?>
<?php if(empty($newApiKey)) $newApiKey = "";?>
<?php if(empty($newOwnerName)) $newOwnerName = "";?>
<?php if(empty($newEmail)) $newEmail = "";?>
<?php if(empty($newAllowedIPAddress)) $newAllowedIPAddress = "";?>
<?php if(empty($newExpiresDate)) $newExpiresDate = "";?>


<?php if(empty($qVars)) $qVars = "";?>


<table style='width:100%;'>
    <tr>
        <td style='padding:10px;vertical-align:top;width:250px;'>                      
                
            <label>Control Panel Id</label>
            <input type="text" value="<?php echo fnFormOutput($newApiKey)?>"  name="newApiKey" id="newApiKey" maxlength='50' style="width:200px;"/>
            
            <label>Reset Password</label>
            <input type="password" value=""  name="newApiSecret" id="newApiSecret" maxlength='50' style="width:200px;"/>

            <label>Confirm Password</label>
            <input type="password" value=""  name="newConfirmApiSecret" id="newConfirmApiSecret" maxlength='50' style="width:200px;"/>
            
            <label>Expires Date <span style='font-weight:normal;'>01/01/2025</span></label>
            <input type="text" value="<?php echo fnFormOutput($newExpiresDate)?>"  name="newExpiresDate" id="newExpiresDate" maxlength='50' style="width:200px;"/>

        </td>
        <td style='padding:10px;vertical-align:top;'>                      

            <label>Owner Name</label>
            <input type="text" value="<?php echo fnFormOutput($newOwnerName)?>"  name="newOwnerName" id="newOwnerName" maxlength='50' style="width:200px;"/>

            <label>Owner Email Address</label>
            <input type="text" value="<?php echo fnFormOutput($newEmail)?>"  name="newEmail" id="newEmail" maxlength='100' style="width:200px;"/>

            <label>IP Address</label>
            <input type="text" value="<?php echo fnFormOutput($newAllowedIPAddress)?>"  name="newAllowedIPAddress" id="newAllowedIPAddress" maxlength='50' style="width:200px;"/>
            
            <div style='padding-top:10px;'>
                <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
                <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='key_details.php?unused=true<?php echo $qVars;?>&apiKeyGuid=<?php echo $apiKeyGuid;?>';">
            </div>
            
        </td>
    </tr>
    <tr>
        <td colspan='2'>
            <div style='padding-top:5px;'>
               <i>Control Panel Id's</i> are usually a version of the owners name, use something like: johnsmith. In cases where you're giving
                access to an application, use a version of the app's name (all app's require an Control Panel Id).
            </div>
            <div style='color:red;'>
                Passwords are encrypted in the database and cannot be reverse encrypted. This means you'll never be able to figure out what
                it is after you create it. WRITE DOWN the password you enter!
            </div>
            <div style='padding-top:5px;'>
                All requests by this Control Panel Id (app / person) will require both the Control Panel Id and password in the URL or POST variables.
            </div>
            <div style='padding-top:5px;'>
                <i>Expire Dates</i> are used to limit how long into the future this Control Panel Id should work. 
                Use a date long into the future to "never expire."
            </div>
            <div style='padding-top:5px;'>
                <i>IP Addresses</i> are used to allow access from that IP address only. Leave this empty in
                most cases.
            </div>
            
            
        </td>
    </tr>
</table>


