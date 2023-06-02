<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("calendar.inc.php");
require_once("rooms.inc.php");


if (isset($_GET["room"]))
{
	$roomId = rooms_getRoomIdFromUrl($_GET["room"]);

	if ($roomId === false)
	{
		template_drawHeader(LANG["page_title_unknown_room"], null, "");
		echo LANG["room_no_room_found"];
		template_drawFooter();
		exit(0);
	}

	$titleKey = "calendar_title_room_events";
	rooms_drawHeaderForRoom($roomId, LANG["page_title_calendar"], "no-box", false);
	$moduleId = "module-calendar";
}
else
{
	$roomId = 0;
	$titleKey = "calendar_title_all_events";
	template_drawHeader(LANG["page_title_calendar"], null, "no-box");
	$moduleId = "module-main-calendar";
}

module_drawModuleHeader($moduleId, $titleKey, false, true, true, rooms_getUrlFromRoomId($roomId));
calendar_drawCalendarWindow($roomId, true);
module_drawModuleFooter();

template_drawFooter();
