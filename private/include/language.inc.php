<?php


$language = CONFIG["default_language"];


define("LANG", parse_ini_file(LANGUAGES_PATH . $language . ".ini"));


function language_drawSettings()
{
	echo "<p>";
	echo LANG["settings_title_language_explanation"];
}
