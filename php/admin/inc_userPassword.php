


<?php if(empty($userGuid)) $userGuid = "";?>
<?php if(empty($newPassword)) $newPassword = "";?>
<?php if(empty($newPasswordConfirm)) $newPasswordConfirm = "";?>
<?php if(empty($qVars)) $qVars = "";?>


<div style='padding-top:5px;'>
    <label>New Password</label>
    <input type="password" value="<?php echo fnFormOutput($newPassword)?>"  name="newPassword" id="newPassword" maxlength='100' style="width:200px;"/>

    <label>Re-Enter New Password</label>
    <input type="password" value="<?php echo fnFormOutput($newPasswordConfirm)?>"  name="newPasswordConfirm" id="newPasswordConfirm" maxlength='100' style="width:200px;"/>
</div>


<div style='padding-top:5px;'>
    <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
    <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>';">
</div>
