


<?php if(empty($userGuid)) $userGuid = "";?>
<?php if(empty($newFirstName)) $newFirstName = "";?>
<?php if(empty($newLastName)) $newLastName = "";?>
<?php if(empty($qVars)) $qVars = "";?>


<div style='padding-top:5px;'>
    <label>First Name</label>
    <input type="text" value="<?php echo fnFormOutput($newFirstName)?>"  name="newFirstName" id="newFirstName" maxlength='100' style="width:200px;"/>

    <labe>Last Name</label>
    <input type="text" value="<?php echo fnFormOutput($newLastName)?>"  name="newLastName" id="newLastName" maxlength='100' style="width:200px;"/>
</div>


<div style='padding-top:5px;'>
    <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
    <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>';">
</div>
