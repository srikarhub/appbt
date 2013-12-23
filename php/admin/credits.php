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
	$thisPage->pageTitle = "Admin Control Panel | Credits";

	//dates
	$dtSqlToday = strtotime(fnMySqlNow());
	$dtToday = fnFromUTC(fnMySqlNow(), $thisUser->infoArray["timeZone"], "m/d/y h:i A");
	$dtNow = fnMySqlNow();

	//vars..
	$strMessage = "";
	$bolDone = false;
	$bolPassed = true;
	
	//this page...
	$scriptName = "credits.php";

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
                   Credits
                </div>
                
                <div style="padding:10px;">
                	David Book at
                    <a href="http://www.buzztouch.com" target="_blank">buzztouch.com</a> is the original and primary developer of 
                    this software. It is distributed with a 
					<a href="http://en.wikipedia.org/wiki/GNU_General_Public_License" target="_blank">GNU General Public License.</a>
                    The source code files listed below are included in this program but were created by others. The 
                    <a href="http://en.wikipedia.org/wiki/GNU_General_Public_License" target="_blank">GNU General Public License</a> 
                    requires that all the notices below remain intact - do not remove them. 
                </div>
                    
                
                <div style='padding:10px;padding-top:0px;'>
                    
                    <div class="rowAlt" style="padding:5px;"><b>class.Json.php</b></div>
                    <div class="rowNormal" style="padding:5px;padding-top:5px;">
                        Copyright (C) 2007 by Cesar D. Rodas<br/>
                        The class.Json.php file is used to facilitate various JSON data parsing routines when
                        the host machine's PHP installation does not have the native JSON parser module installed. 
                    </div>
    
                    <div class="rowAlt" style="padding:5px;"><b>class.Phpmailer.php</b></div>
                    <div class="rowNormal" style="padding:5px;padding-top:5px;">
                         Copyright (C) 2001 - 2003 Brent R. Matzelle<br/>
                         The class.Phpmailer.php file is used to send outbound emails. 
                    </div>

                    <div class="rowAlt" style="padding:5px;"><b>class.Smtp.php</b></div>
                    <div class="rowNormal" style="padding:5px;padding-top:5px;">
                         Copyright (C) 2001 - 2003 Chris Ryan<br/>
                         The class.Smtp.php file is used in conjuction with the class.Phpmailer.php file (see above) in the event the 
                         host machine is configured to send email using the Simple Mail Protocol method. 
                    </div>

                    <div class="rowAlt" style="padding:5px;"><b>zip.php</b></div>
                    <div class="rowNormal" style="padding:5px;padding-top:5px;">
                         Copyright (C) August 2009 - Vincent Blavet<br/>
                         The zip.php file is used to archive and un-archive files into, or out of a "zipped" directory.
                    </div>

                    <div class="rowAlt" style="padding:5px;"><b>shadowbox-build-3.0b</b></div>
                    <div class="rowNormal" style="padding:5px;padding-top:5px;">
                         Copyright (C) 2007-2009 - Michael J. I. Jackson<br/>
                         The shadowbox-build-3.0b directory contains multiple files used to open new browser windows using a 
                         "shadowbox" effect.
                    </div>

                    <div class="rowAlt" style="padding:5px;"><b>CKEditor</b></div>
                    <div class="rowNormal" style="padding:5px;padding-top:5px;">
                         Copyright (C) 2003-2011 - CKSource - Frederico Knabben<br/>
                         The CKEditor package is used to transform common HTML &lt;textarea&gt; elements into editable HTML regions.
                    </div>




                </div>
                
            <!--content box-->
            </div>
            
        <!--box right-->
    	</div>          
        
                    
    </fieldset>
<?php echo $thisPage->fnGetBottomNavBar();?>
</div>
<?php echo $thisPage->fnGetBodyEnd(); ?>






