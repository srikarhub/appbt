
<?php if(empty($userGuid)) $userGuid = "";?>
<?php if(empty($newEmail)) $newEmail = "";?>
<?php if(empty($newEmailConfirm)) $newEmailConfirm = "";?>
<?php if(empty($qVars)) $qVars = "";?>


<div style='padding-top:5px;'>
    <label>New Email Address</label>
    <input type="text" value="<?php echo fnFormOutput($newEmail)?>"  name="newEmail" id="newEmail" maxlength='100' style="width:200px;"/>

    <label>Re-Enter New Email Address</label>
    <input type="text" value="<?php echo fnFormOutput($newEmailConfirm)?>"  name="newEmailConfirm" id="newEmailConfirm" maxlength='100' style="width:200px;"/>
</div>


<div style='padding-top:5px;'>
    <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
    <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>';">
</div>

<div class='infoDiv' style='margin-top:30px;'>
    <b>Email Addresses</b> are used as Login Id's. This means if you change this persons email address they will need to use that
    address when they login on their next visit.
</div>
