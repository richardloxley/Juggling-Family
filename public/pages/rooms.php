<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("rooms.inc.php");


template_drawHeader(LANG["page_title_rooms"], null, "");

module_drawModuleHeader("module-rooms", "rooms_title", false, true, true, "");
rooms_drawRoomList();
module_drawModuleFooter();

template_drawFooter();
