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

	rooms_drawHeaderForRoom($roomId, LANG["page_title_subscribe"], "", false);
}
else
{
	$roomId = 0;
	template_drawHeader(LANG["page_title_subscribe"], null, "");
}

?>
	<h2>
		<?php echo LANG["page_title_subscribe"]; ?>
	</h2>

	<p>
<?php

echo "<p>";
echo LANG["calendar_subscribe_explanation"];
echo "<p>";



// draw a section in the option list for which room to use
function drawRoomList($currentRoomId, $categoryTitle, $categoryNumberOrFalseForDormantRooms)
{
	echo "<optgroup label='$categoryTitle'>";

	$rooms = rooms_getRoomsInCategory($categoryNumberOrFalseForDormantRooms, true);

	foreach ($rooms as $room)
	{
		$selected = "";

		if ($room["roomId"] == $currentRoomId)
		{
			$selected = "selected";
		}

		$url = $room["url"];
		echo "<option value='/$url' $selected>";
		echo $room["title"];
		echo "</option>";
	}

	echo "</optgroup>";
}


// choose the calendar

echo "<h3>";
echo LANG["calendar_subscribe_choose_calendar"];
echo "</h3>";

echo "<select id='subscribe-calendar'>";

if ($roomId == 0)
{
	echo "<option value='' selected>";
}
else
{
	echo "<option value=''>";
}
echo LANG["calendar_subscribe_choose_calendar_all_rooms"];
echo "</option>";

for ($x = 1; $x <= CONFIG["rooms_number_of_categories"]; $x++)
{
	$categoryNumber = CONFIG["rooms_category_list_order"][$x];
	$categoryTitle = LANG["rooms_category_title"][$categoryNumber];
	drawRoomList($roomId, $categoryTitle, $categoryNumber);
}

$categoryTitle = LANG["rooms_category_title_dormant"];
drawRoomList($roomId, $categoryTitle, false);

echo "</select>";



// choose any reminders

echo "<h3>";
echo LANG["calendar_subscribe_reminder"];
echo "</h3>";

echo "<div class='reminders'>";

foreach (CONFIG["calendar_subscribe_reminder_minutes"] as $reminderMinutes)
{
	echo "<label>";
	echo "<input type='checkbox' class='reminder-checkbox' value='$reminderMinutes'>";

	if ($reminderMinutes == 0)
	{
		echo LANG["calendar_subscribe_reminder_start"];
	}
	else if ($reminderMinutes < 60)
	{
		echo sprintf(LANG["calendar_subscribe_reminder_minutes"], $reminderMinutes);
	}
	else if ($reminderMinutes == 60)
	{
		echo LANG["calendar_subscribe_reminder_hour"];
	}
	else
	{
		echo sprintf(LANG["calendar_subscribe_reminder_hours"], $reminderMinutes / 60);
	}

	echo "</label>";
}

echo "</div>";

echo "<p>";
echo LANG["calendar_subscribe_reminder_explanation"];
echo "<p>";



// apps that can subscribe directly

echo "<h3>";
echo LANG["calendar_subscribe_supported_apps_description"];
echo "</h3>";
echo "<ul>";
foreach (LANG["calendar_subscribe_supported_apps"] as $description)
{
	echo "<li>";
	echo $description;
	echo "</li>";
}
echo "</ul>";


// subscribe button
echo "<a id='subscribe-button' class='link-looking-like-a-button' href=''>";
echo "<span class='button-icon'>";
echo icon_subscribe();
echo "</span>";
echo " ";
echo LANG["calendar_subscribe_button"];
echo "</a>";


// websites that can subscribe with a link

echo "<h3>";
echo LANG["calendar_subscribe_supported_websites_description"];
echo "</h3>";
echo "<ul>";
foreach (LANG["calendar_subscribe_supported_websites"] as $description)
{
	echo "<li>";
	echo $description;
	echo "</li>";
}
echo "</ul>";


// subscribe link

echo "<a id='subscribe-url' href=''></a>";
echo "<p>";


// copy link button

echo "<a id='subscribe-copy-button' class='link-looking-like-a-button' href='javascript:void(0);' onclick='copyToClipboard(\"#subscribe-url\")'>";
echo "<span class='button-icon'>";
echo icon_link();
echo "</span>";
echo " ";
echo LANG["calendar_subscribe_copy_link"];
echo "</a>";


// JavaScript to hang it all together

# replace http:// or https:// with webcal://
$urlHead = preg_replace("/[^\/]*\/\//", "webcal:\/\/", CONFIG["site_root_url"]);
$urlTail = PUBLIC_URL["calendar_feed"];

?>
	<script type="text/javascript">
		$(document).ready(function()
		{
			const urlHead = "<?php echo $urlHead; ?>";
			const urlTail = "<?php echo $urlTail; ?>";

			function updateSubscribeUrl()
			{
				var url = urlHead + $("#subscribe-calendar").val() + urlTail;

				var first = true;
				$(".reminder-checkbox:checked").each(function()
				{
					if (first)
					{
						url += "?r="
						first = false;
					}
					else
					{
						url += ","
					}

					url += $(this).val();
				});

				$("#subscribe-url").html(url);
				$("#subscribe-url").prop("href", url);
				$("#subscribe-button").prop("href", url);
			};

			$("#subscribe-calendar").change(function()
			{
				updateSubscribeUrl();
			});

			$(".reminder-checkbox").click(function()
			{
				updateSubscribeUrl();
			});

			updateSubscribeUrl();
		});
	</script>
<?php

template_javascriptClipboard();

template_drawFooter();
