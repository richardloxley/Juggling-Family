<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("rooms.inc.php");


$roomId = false;

if (isset($_GET["room"]))
{
	$roomId = rooms_getRoomIdFromUrl($_GET["room"]);
}

if ($roomId === false || !login_hasPreviewAccessToRoom($roomId))
{
	template_drawHeader(LANG["page_title_unknown_room"], null, "");
	echo LANG["room_no_room_found"];
	template_drawFooter();
	exit(0);
}

$title = LANG["invite_title"];

rooms_drawHeaderForRoom($roomId, $title, "", false);

template_denyIfNotLoggedIn();

?>
	<h2>
		<?php echo $title; ?>
	</h2>

	<p>
<?php

template_javascriptClipboard();

$invitationUrl = login_createInvitation($roomId);
$roomName = rooms_getTitleFromRoomId($roomId);
$siteName = CONFIG["site_title"];

echo "<p>";

if (rooms_isRoomPublic($roomId))
{
	echo LANG["invite_explanation"];
}
else
{
	echo LANG["invite_private_explanation_1"];
	echo "<p>";
	echo LANG["invite_private_explanation_2"];
}

echo "<p>";
echo sprintf(LANG["invite_expiry"], CONFIG["invitation_expiry_days"]);
echo "<p>";

echo "<a id='invitation-url' href='$invitationUrl'>$invitationUrl</a>";
echo "<br>";

echo "<a class='link-looking-like-a-button' href='javascript:void(0);' onclick='copyToClipboard(\"#invitation-url\")'>";
echo "<span class='button-icon'>";
echo icon_link();
echo "</span>";
echo " ";
echo LANG["invite_copy_link"];
echo "</a>";

echo "<div id='invitation-message' class='invite-message'>";
if (rooms_isRoomPublic($roomId))
{
	echo sprintf(LANG["invite_link_blurb"], $roomName, $siteName, $invitationUrl); 
}
else
{
	echo sprintf(LANG["invite_private_link_blurb"], $roomName, $siteName, $invitationUrl); 
}
echo "</div>";

echo "<a class='link-looking-like-a-button' href='javascript:void(0);' onclick='copyToClipboard(\"#invitation-message\")'>";
echo "<span class='button-icon'>";
echo icon_copy();
echo "</span>";
echo " ";
echo LANG["invite_copy_blurb"];
echo "</a>";

template_drawFooter();
