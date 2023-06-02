<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("calendar.inc.php");
require_once("chat.inc.php");
require_once("rooms.inc.php");

template_drawHeader("", null, "no-box");

if (!login_isLoggedIn())
{
	template_drawAboutBox(LANG["welcome_preview"]);
}

template_bannerSuggestingLogin();

module_drawModuleHeader("module-main-calendar", "calendar_title_all_events", false, true, false, PUBLIC_URL["calendar"]);
calendar_drawCalendarWindow(0);
module_drawModuleFooter();

module_drawModuleHeader("module-main-rooms", "rooms_title", true, true, false, PUBLIC_URL["rooms"]);
rooms_drawRoomList();
module_drawModuleFooter();

module_drawModuleHeader("module-main-news", "news_title", true, true, false, PUBLIC_URL["news"]);
echo LANG["news_description"] . " ";
// this works as news.php is in the same directory
$modifiedDate = date("D j M", filemtime("news.php"));
echo "<a href='" . PUBLIC_URL["news"] . "'>" . sprintf(LANG["news_last_update"], $modifiedDate) . "</a>";
module_drawModuleFooter();

template_drawFooter();
