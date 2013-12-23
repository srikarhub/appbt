<?php
	
	/*
		this file is included in all plugin /index.php pages and manages the "breadcrumb" navigation...
		When screens are connected to menu's it is important that the "previous screen" show in the
		navigation so a user can go back to that menu.
		
		$objBT_item is declared in the /index.php page that includes this script. It holds the screen-data.
		$qVars is declared in the /index.php page that includes this script. It holds possible sort/filter data.
		$BT_previousScreenId is declared in the /index.php page that lead to this page...
		
	*/
	function fnBuildBreadcrumbsForScreen($objBT_item, $BT_previousScreenId, $qVars){
		
		//always include link back to screens and actions page...
		$r = "\n<a href=\"" . APP_URL . "/bt_v15/bt_app/bt_screens.php?appGuid=" . $objBT_item -> infoArray["appGuid"] . "&unused=true" . $qVars . "\" title=\"Screens / Actions\">Screens / Actions</a>";
		
		//if this screen has a parentScreenGuid we need it's nickname, type, directory, etc...
		if($BT_previousScreenId != ""){
			$tmpSql = " SELECT I.guid, I.appGuid, I.nickname, P.uniquePluginId, P.webDirectoryName ";
			$tmpSql .= "FROM " . TBL_BT_ITEMS . " AS I ";
			$tmpSql .= " INNER JOIN " . TBL_BT_PLUGINS . " AS P ON I.uniquePluginId = P.uniquePluginId ";
			$tmpSql .= " WHERE I.guid = '" . $BT_previousScreenId . "' ";
			$res = fnDbGetResult($tmpSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS);
			if($res){
				while($row = mysql_fetch_array($res)){

					//link to plugin management screen...
					$parentScreenURL = rtrim(APP_URL, "/") . "/" . ltrim(APP_DATA_DIRECTORY, "/") . "/plugins" . $row['webDirectoryName'] . "/?appGuid=" . $row["appGuid"] . "&BT_itemId=" . $row["guid"] . $qVars;
				
					//link to parent screen...
					$r .= "\n&nbsp;&nbsp;>&nbsp;&nbsp;";
    				$r .= "<a href=\"" . $parentScreenURL . "\" title=\"" . fnFormOutput($row["nickname"]) . "\">" . fnFormOutput($row["nickname"]) . "</a>";
				
				}//while...
			
			
            }//if
		}

		//add this screen nickname that's not a link, we are on this page...
		$r .= "\n&nbsp;&nbsp;>&nbsp;&nbsp;";
		$r .= fnFormOutput($objBT_item -> infoArray["nickname"]);
		return $r;
	
	}
	
	//comes from links in menus if we are more than one level deep....
	$BT_previousScreenId = fnGetReqVal("BT_previousScreenId", "", $myRequestVars);
	
?>


<!--previous screen if this screen has a parentScreenGuid -->
<input type="hidden" name="BT_previousScreenId" id="BT_previousScreenId" value="<?php echo $BT_previousScreenId;?>">

<?php echo fnBuildBreadcrumbsForScreen($objBT_item, $BT_previousScreenId, $qVars);?>





