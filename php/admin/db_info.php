<?php   require_once("../config.php");

	//who's logged in
	$guid = "";
	if(isset($_SESSION[APP_LOGGEDIN_COOKIE_NAME])) $guid = fnFormInput($_SESSION[APP_LOGGEDIN_COOKIE_NAME]);
		
	//init user object
	$thisUser = new User($guid);
	$thisUser -> fnLoggedInReq($guid);
	$thisUser -> fnAdminRequired($guid);
	$thisUser -> fnUpdateLastRequest($guid, "1");

	//init page object
	$thisPage = new Page();
	$thisPage->pageTitle = "Admin Control Panel | Database Info";

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

	//page load timer variables
	$reqTime = microtime();
	$reqTime = explode(" ", $reqTime);
	$reqTime = $reqTime[1] + $reqTime[0];
	$reqStart = $reqTime;
			
	$thisFile = "db_info.php"; //this page
	$command = fnGetReqVal("command", "showTables", $myRequestVars); //for displaying misc. database info.
	$tbl = fnGetReqVal("tbl", "", $myRequestVars); //if table details clicked

	//converts seconds to days, months, years..
	function fnFriendlyUpdtime($s) {
	  $d = intval($s/86400);
	  $s -= $d*86400;
	
	  $h = intval($s/3600);
	  $s -= $h*3600;
	
	  $m = intval($s/60);
	  $s -= $m*60;
	
	  if ($d) $str = $d . 'd ';
	  if ($h) $str .= $h . 'h ';
	  if ($m) $str .= $m . 'm ';
	  if ($s) $str .= $s . 's';
	
	  return $str;
	}

	//formats some fields as data-size...
	function fnFormatFieldValue($field, $value){
		switch($field){
			
			//mysql status fields..
			case "Bytes_received": return fnFormatBytes($value); break;
			case "Bytes_sent": return fnFormatBytes($value); break;
			case "Innodb_data_read": return fnFormatBytes($value); break;
			case "Innodb_data_written": return fnFormatBytes($value); break;
			case "Innodb_os_log_written": return fnFormatBytes($value); break;
			case "Innodb_page_size": return fnFormatBytes($value); break;
			case "Innodb_row_lock_time": return $value . " milliseconds"; break;
			case "Innodb_row_lock_time_avg": return $value . " milliseconds"; break;
			case "Innodb_row_lock_time_max": return $value . " milliseconds"; break;
			case "Qcache_free_memory": return fnFormatBytes($value); break;

			//mysql variables feilds..
			case "bdb_cache_size": return fnFormatBytes($value); break;
			case "bdb_log_buffer_size": return fnFormatBytes($value); break;
			case "binlog_cache_size": return fnFormatBytes($value); break;
			case "bulk_insert_buffer_size": return fnFormatBytes($value); break;
			case "innodb_additional_mem_pool_size": return fnFormatBytes($value); break;
			case "innodb_buffer_pool_size": return fnFormatBytes($value); break;
			case "innodb_log_buffer_size": return fnFormatBytes($value); break;
			case "innodb_log_file_size": return fnFormatBytes($value); break;
			case "join_buffer_size": return fnFormatBytes($value); break;
			case "key_buffer_size": return fnFormatBytes($value); break;
			case "key_cache_block_size": return fnFormatBytes($value); break;
			case "max_allowed_packet": return fnFormatBytes($value); break;
			case "max_binlog_cache_size": return fnFormatBytes($value); break;
			case "max_binlog_size": return fnFormatBytes($value); break;
			case "max_heap_table_size": return fnFormatBytes($value); break;
			case "max_join_size": return fnFormatBytes($value); break;
			case "max_length_for_sort_data": return fnFormatBytes($value); break;
			case "myisam_max_sort_file_size": return fnFormatBytes($value); break;
			case "myisam_sort_buffer_size": return fnFormatBytes($value); break;
			case "net_buffer_length": return fnFormatBytes($value); break;
			case "preload_buffer_size": return fnFormatBytes($value); break;
			case "query_alloc_block_size": return fnFormatBytes($value); break;
			case "query_cache_limit": return fnFormatBytes($value); break;
			case "query_cache_min_res_unit": return fnFormatBytes($value); break;
			case "query_prealloc_size": return fnFormatBytes($value); break;
			case "range_alloc_block_size": return fnFormatBytes($value); break;
			case "read_buffer_size": return fnFormatBytes($value); break;
			case "read_rnd_buffer_size": return fnFormatBytes($value); break;
			case "sort_buffer_size": return fnFormatBytes($value); break;
			case "transaction_alloc_block_size": return fnFormatBytes($value); break;
			case "transaction_prealloc_size": return fnFormatBytes($value); break;

			case "Uptime": return fnFriendlyUpdtime($value); break;
			case "Uptime_since_flush_status": return fnFriendlyUpdtime($value); break;

		}
		return $value;
	}

	//print html...
	echo $thisPage->fnGetPageHeaders();
	echo $thisPage->fnGetBodyStart();
	echo $thisPage->fnGetTopNavBar($thisUser->infoArray['guid']);
	
?>

<div class='content'>
        
    <fieldset class='colorLightBg'>
           
        <!-- left side--> 
        <div class='boxLeft'>
            <div class='contentBox colorDarkBg minHeight'>
                <div class='contentBand colorBandBg'>
                    Admin Options
                </div>
                <div id="leftNavLinkBox" style='padding:10px;white-space:nowrap;'>
        			<?php echo $thisPage->fnGetControlPanelLinks("admin", "", "block", ""); ?>
				</div>
             </div>
        </div>
        
        <!-- right side--> 
        <div class='boxRight'>
            <div class='contentBox colorLightBg minHeight'>
                
                <div class='contentBand colorBandBg'>
                   mySQL Server: <i><?php echo APP_DB_HOST;?></i> : <i><?php echo APP_DB_NAME;?></i>
                </div>

				<style type="text/css">
                    .h{padding:3px;padding-left:10px;font-weight:bold;}
                    .d{padding:3px;padding-left:10px;}
                </style>                    
                
                <div style='padding:10px;'>
					
                    <div style='margin-bottom:10px;'>
                  		<a href="<?php echo $thisFile . "?command=showTables"?>" title='Show Tables' target='_self'><img src="<?php echo fnGetSecureURL(APP_URL);?>/images/arr_right.gif" alt='arrow'/>Show Tables</a>
						&nbsp;&nbsp;|&nbsp;&nbsp;
                  		<a href="<?php echo $thisFile . "?command=showStatus";?>" title='Show Status' target='_self'>Show mySQL Status</a>
						&nbsp;&nbsp;|&nbsp;&nbsp;
                  		<a href="<?php echo $thisFile . "?command=showVariables";?>" title='Show Variables' target='_self'>Show mySQL Variables</a>
                    </div>
                
                    <!-- show tables -->
                    <?php 
						if(strtoupper($command) == "SHOWTABLES"){ 
                      		echo "<table cellspacing='0' cellpadding='0' style='width:100%;'>";
								echo "<tr class='rowAlt'>";
                            		echo "<td class='h'>Table</td>";
                            		echo "<td class='h'>Rows</td>";
                            		echo "<td class='h'>Data Size</td>";
                            		echo "<td class='h'>Index Size</td>";
                            		echo "<td class='h'>Modified</td>";
                           		echo "</tr>";
                            
                                $tblCnt = 0;
                                $totalSize = 0;
                                $strSql = "SHOW TABLE STATUS"; 
                                $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
                                $numRows = 0;
                                $cnt = 0;
                                if($res){
                                    $numRows = mysql_num_rows($res);
                                    if($numRows > 0){
                                        while ($row=mysql_fetch_array($res)){
                                            $tblCnt++;
                                            
                                            //style
                                            $css = (($tblCnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
                                            
                                            $tblName = $row['Name'];
                                            $totalRows = $row['Rows'];
                                            $dataSize = $row['Data_length'];
                                            $displaySize = fnFormatBytes($dataSize);
                                            $indexSize = $row['Index_length'];
                                            $displayIndexSize = fnFormatBytes($indexSize);
                                            $createdTime = $row['Create_time'];
                                                //format mySql date
                                                $tmpDate = strtotime($createdTime); 
                                                $displayDate = 	date("m/d/Y", $tmpDate);
                                            //sub up size
                                            $totalSize = $totalSize + ($dataSize + $indexSize);
                                            
                                            echo "\n\n<tr class='" . $css . "'>";
                                                echo "\n<td class='d'><a href='" . $thisFile . "?command=showTables&tbl=" . $tblName . "' title='Show Details' target='_self'>" . $tblName . "</a></td>";
                                                echo "\n<td class='d'>" . $totalRows . "</td>";
                                                echo "\n<td class='d'>" . $displaySize . "</td>";
                                                echo "\n<td class='d'>" . $displayIndexSize . "</td>";
                                                echo "\n<td class='d'>" . $displayDate . "</td>";
                                            echo "\n</tr>";
                                        
										}//while
                                    }//numRows
                                
									//footer info..
                            		echo "<tr>";
                                		echo "<td colspan='6' style='padding:10px;'>";
                                    		echo $tblCnt . " Tables ";
                                    		echo fnFormatBytes($totalSize);
                                    	echo "</td>";
									echo "</tr>";
								}//res...
								echo "</table>";
               				}//showTables
						?> 
                                
                
                        <!-- table details -->
                        <?php if($tbl != ""){ 
                        	echo "<table cellspacing='0' cellpadding='0' style='width:100%;'>";
								echo "<tr class='rowAlt'>";
									echo "<td class='h'>Field info for " . $tbl . "</td>";
                                	echo "<td class='h'>Type</td>";
                                	echo "<td class='h'>Extra</td>";
                            	echo "</tr>";

                                $strSql = "SHOW COLUMNS FROM " . $tbl;
                                $res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
                                $numRows = 0;
                                $cnt = 0;
                                if($res){
                                    $numRows = mysql_num_rows($res);
                                    $fields_num = mysql_num_fields($res);
                                    if($numRows > 0){
                                        while ($row = mysql_fetch_array($res)){
                                            $cnt++;
                                            
                                            //style
                                            $css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
                                            
                                            echo "<tr class=" . $css . "'>";
                                            
                                            echo "\n\n<tr class='" . $css . "'>";
                                                echo "\n<td class='d'>" . $row["Field"] . "</td>";
                                                echo "\n<td class='d'>" . $row["Type"] . "</td>";
                                                echo "\n<td class='d'>" . $row["Extra"] . "</td>";
                                            echo "\n</tr>";
                                            
                                            echo "</tr>";
                                            
                                        }//while
                                    }//numRows
                                }//res
                            	echo "</table>";  
                           }//tbl
                        ?>    
                        
                    	<!-- mySQL Server Variables -->
                        <?php 
							if(strtoupper($command) == "SHOWVARIABLES"){
                        		echo "<table cellspacing='0' cellpadding='0' style='width:100%;'>";
							    	echo "<tr class='rowAlt'>";
                                		echo "<td class='h' style='width:275px;'>Variable</td>";
                                		echo "<td class='h'>Value</td>";
                                		echo "<td class='h'></td>";
                            		echo "</tr>";

									$strSql = "SHOW VARIABLES ";
									$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
									$numRows = 0;
									$cnt = 0;
									if($res){
										$numRows = mysql_num_rows($res);
										$fields_num = mysql_num_fields($res);
										if($numRows > 0){
											while($row = mysql_fetch_assoc($res)){
												
												$cnt++;
												
												//style
												$css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
												
												//fnFormatBytes($indexSize)
												$field = $row["Variable_name"];
												$value = $row["Value"];
												$formattedValue = fnFormatFieldValue($field, $value);
												
												echo "\n\n<tr class='" . $css . "'>";
													echo "\n<td class='d'>" . $field . "</td>";
													echo "\n<td class='d'>" . $formattedValue . "</td>";
													echo "\n<td class='d'></td>";
												echo "\n</tr>";
												
												
											}//while
										}//numRows
									}//res                 
								echo "</table>";	
							}//showVariables
						?>
                    	
                        <!-- mySQL Server Status -->
                        <?php 
							if(strtoupper($command) == "SHOWSTATUS"){
                        		echo "<table cellspacing='0' cellpadding='0' style='width:100%;'>";
							    	echo "<tr class='rowAlt'>";
                                		echo "<td class='h' style='width:275px;'>Status</td>";
                                		echo "<td class='h'>Value</td>";
                                		echo "<td class='h'></td>";
                            		echo "</tr>";

									$strSql = "SHOW GLOBAL STATUS ";
									$res = fnDbGetResult($strSql, APP_DB_HOST, APP_DB_NAME, APP_DB_USER, APP_DB_PASS); 
									$numRows = 0;
									$cnt = 0;
									if($res){
										$numRows = mysql_num_rows($res);
										$fields_num = mysql_num_fields($res);
										if($numRows > 0){
											while($row = mysql_fetch_assoc($res)){
												
												$cnt++;
												
												//style
												$css = (($cnt % 2) == 0) ? "rowAlt" : "rowNormal" ;
												
												//fnFormatBytes($indexSize)
												$field = $row["Variable_name"];
												$value = $row["Value"];
												$formattedValue = fnFormatFieldValue($field, $value);
												
												echo "\n\n<tr class='" . $css . "'>";
													echo "\n<td class='d'>" . $field . "</td>";
													echo "\n<td class='d'>" . $formattedValue . "</td>";
													echo "\n<td class='d'></td>";
												echo "\n</tr>";
												
											}//while
										}//numRows
									}//res
								echo "</table>";                      
							}//showStatus
						?>
                        
                        
                                    
                </div>
       
       
       
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>
