


<?php if(empty($userGuid)) $userGuid = "";?>
<?php if(empty($userType)) $userType = "";?>
<?php if(empty($qVars)) $qVars = "";?>


<div style='padding-top:5px;'>
    <label>User Type</label>
    <select name="newUserType" id="newUserType" style="width:200px;">
        <option value="">--select--</option>
        <option value="normal" <?php echo fnGetSelectedString("normal", $newUserType);?>>Normal</option>
        <option value="admin" <?php echo fnGetSelectedString("admin", $newUserType);?>>Admin</option>
    </select>
    <div style='padding-top:0px;'>
        Normal users can only manage their own applications.
        <br/>
        Admin users can manage all applications.
        <br/>
        Admin users can add and modify users.
    </div>
</div>


<div style='padding-top:5px;'>
    <input type="button" class="buttonSubmit" value="submit" onclick="document.forms[0].submit();">
    <input type="button" class="buttonCancel" value="cancel" onclick="document.location.href='user_details.php?unused=true<?php echo $qVars;?>&userGuid=<?php echo $userGuid;?>';">
</div>






