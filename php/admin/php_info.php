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
	$thisPage->pageTitle = "Admin Control Panel | PHP Info";

	//vars...
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	$dtNow = fnMySqlNow();

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
                   PHP Info
                </div>
            
                <style type="text/css">
                    table{width:97%;}
                    .e{white-space:nowrap;border-bottom:1px solid black;}
                    .v{border-bottom:1px solid black;}
                    th{text-align:left;padding-top:10px;}
                    h1{color:#FFFFFF;background-color:#999999;padding:5px;width:97%;font-size:16pt;font-weight:bold;}
                    h2{color:#FFFFFF;background-color:#999999;padding:5px;width:97%;font-size:14pt;font-weight:bold;}
                </style>                    
                
                <div style='padding:10px;padding-top:0px;'>
                    <?php 
                        ob_start();
                        phpinfo();
                        $pinfo = ob_get_contents();
                        ob_end_clean();
                         
                        $pinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$pinfo);
                        $pinfo = str_replace("<hr />", "", $pinfo);
                        $pinfo = str_replace("width=\"600\"", "", $pinfo);
                        $pinfo = str_replace(",form=fakeentry", ", form=fakeentry", $pinfo);
                        $pinfo = str_replace("q=0.9,*/*;q=0.8", ", q=0.9,*/*;q=0.8", $pinfo);
                        

                        echo $pinfo;
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
