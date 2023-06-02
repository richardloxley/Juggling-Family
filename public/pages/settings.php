<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("time.inc.php");
require_once("video.inc.php");


template_drawHeader(LANG["page_title_settings"], null, "no-box");

?>
	<div class='settings-page-title'>
		<?php echo LANG["settings_title"]; ?>
	</div>
<?php

settings_javascript();

if (video_showSettings())
{
	settings_drawHeader("video", "settings_title_video");
	video_drawSettings();
	settings_drawFooter();
}

if (login_showSettings())
{
	settings_drawHeader("password", "settings_title_passwords");
	login_drawSettings();
	settings_drawFooter();
}

settings_drawHeader("language", "settings_title_language");
language_drawSettings();
settings_drawFooter();

echo "<p><br>";

template_drawFooter();
	
/*
	echo LANG["settings_current_timezone"] . ": ";
	echo time_currentTimezoneHuman();
?>
	<br>
	<a class='link-looking-like-a-button' href='<?php echo PUBLIC_URL["settings_timezone"]; ?>'>
		<?php echo LANG["settings_change_timezone"]; ?>
	</a>

<?php
*/

