<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("chat.inc.php");
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

	rooms_drawHeaderForRoom($roomId, LANG["page_title_chat"], "no-box", false);
	module_drawModuleHeader("module-chat", "chat_title", false, true, true, rooms_getUrlFromRoomId($roomId));
}
else
{
	template_drawHeader(LANG["chat_default_text_chat_room_title"], null, "no-box");
	$roomId = rooms_getMainTextChatRoomId();
	template_bannerSuggestingLogin($roomId);
	module_drawModuleHeader("module-chat", "chat_title", false, false, true, "");
}

chat_drawChatWindow($roomId);
module_drawModuleFooter();
template_drawFooter();
