<?php

require_once("images.inc.php");
require_once("video.inc.php");
require_once("chat.inc.php");
require_once("calendar.inc.php");


define("ROOMS_CATEGORY_PRIVATE", "private");


function rooms_drawRoomList()
{
	?>
		<div id='rooms-header'>

	<?php
			if (!login_isLoggedIn())
			{
				// not logged in, always show blurb
	?>
				<div id='rooms-blurb'>
					<?php echo LANG["rooms_description_1"]; ?>
					<br>
					<?php echo LANG["rooms_description_2"]; ?>
					<br>
					<?php echo LANG["rooms_description_3"]; ?>
				</div>
	<?php
			}
			else if (!isset($_COOKIE["hide-rooms-blurb"]))
			{
				// logged in, only show blrub if they haven't hidden it
				// (and allow them to hide it)
	?>
				<div id='rooms-blurb'>
					<?php echo LANG["rooms_description_1"]; ?>
					<br>
					<?php echo LANG["rooms_description_2"]; ?>
					<br>
					<?php echo LANG["rooms_description_3"]; ?>
					<p>
					<a href="" onclick="hideRoomsBlurb(event)">
						<?php echo LANG["rooms_description_hide"]; ?>
					</a>
				</div>

				<script type="text/javascript">
					function hideRoomsBlurb(event)
					{
						setCookie("hide-rooms-blurb", true);
						$("#rooms-blurb").hide();
						event.preventDefault();
						return false;
					}
				</script>
	<?php
			}

	if (login_isLoggedIn())
	{
		?>
			<div class="new-room-wrapper">
				<a href="<?php echo PUBLIC_URL['createroom']; ?>" class="link-looking-like-a-button">
					<span class="button-icon">
						<?php echo icon_plusInCircle(); ?>
					</span>
					<?php echo LANG["rooms_create_room_button_label"]; ?>
				</a>
			</div>
		<?php
	}

	echo "</div>";

	?>
		<div id="room-list" class="module-background-update">
		</div>

		<div class='show-if-no-js'>
			<div id='rooms-needs-js'>
				<?php echo LANG['rooms_no_javascript']; ?>
			</div>
		</div>
	<?php

	module_clientStartBackgroundRefresh(API_URL["rooms-get-room-list"], "", "", "room-list", "module_refresh_room_list", false);
}


function rooms_apiGetRooms()
{
	module_serverCheckForNewContent("rooms_getRoomsContentFunction");
}


// contentFunction() takes
//	a string representing the last entry output to the user (interpretted however the function likes)
//	the time the server last sent new output (as received from the server previously)
// if there is new content, it returns an array:
//	"lastId" => the new last entry,
//	"html" => the rendered output
// if there is no new content, it returns false
function rooms_getRoomsContentFunction($lastId, $lastTime)
{
	$newUpdateTime = rooms_updateTimeSince($lastId);

	if ($newUpdateTime !== false)
	{
		//error_log("rooms updated at $newUpdateTime");

		ob_start();

			rooms_drawMainTextChat();

			for ($x = 1; $x <= CONFIG["rooms_number_of_categories"]; $x++)
			{
				$categoryNumber= CONFIG["rooms_category_list_order"][$x];
				$categoryTitle = LANG["rooms_category_title"][$categoryNumber];
				rooms_drawCategory($categoryTitle, $categoryNumber, true);
			}

			$categoryTitle = LANG["rooms_category_title_dormant"];
			rooms_drawCategory($categoryTitle, false, true);

			// private rooms currently have a category of 0
			$categoryTitle = LANG["rooms_category_title_private"];
			rooms_drawCategory($categoryTitle, 0, false);

		$html = ob_get_clean();

		return [ "lastId" => $newUpdateTime, "html" => $html ];
	}
	else
	{
		return false;
	}
}


function rooms_getRoomsInCategory($categoryNumberOrFalseForDormantRooms, $public)
{
	// first delete any expired rooms
	rooms_deleteExpiredRooms();

	$rooms = array();

	try
	{
		$db = database_getConnection();

		if ($public)
		{
			// show public rooms
			if ($categoryNumberOrFalseForDormantRooms === false)
			{
				$st = mysqli_prepare($db, "select room_id, title, description, url, timestampdiff(day, UTC_TIMESTAMP(), expiry) as daysleft from rooms where expiry is not null and public = 1 order by title asc");
			}
			else
			{
				$st = mysqli_prepare($db, "select room_id, title, description, url, null as days_left from rooms where expiry is null and category = ? and public = 1 order by title asc");
				mysqli_stmt_bind_param($st, "i", $categoryNumberOrFalseForDormantRooms);
			}
		}
		else if (login_isAnAdmin())
		{
			// private rooms requested, and we're an admin, so include all private rooms
			// ignore category and automatically include dormant rooms as well as they are all lumped into the private category
			$st = mysqli_prepare($db, "select room_id, title, description, url, timestampdiff(day, UTC_TIMESTAMP(), expiry) as daysleft from rooms where public = 0 order by title asc");
		}
		else
		{
			// private rooms requested, so only show rooms accessible to this user
			// ignore category and automatically include dormant rooms as well as they are all lumped into the private category
			$userId = login_getUserId();
			$st = mysqli_prepare($db, "select room_id, title, description, url, timestampdiff(day, UTC_TIMESTAMP(), expiry) as daysleft from rooms join room_members using (room_id) where public = 0 and user_id = ? order by title asc");
			mysqli_stmt_bind_param($st, "i", $userId);
		}

		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $roomId, $title, $description, $url, $daysLeft);

		while (mysqli_stmt_fetch($st))
		{
			$rooms[] =
			[
				"roomId" => $roomId,
				"title" => $title,
				"description" => $description,
				"url" => $url,
				"daysLeft" => $daysLeft
			];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $rooms;
}


function rooms_drawCategory($categoryTitle, $categoryNumberOrFalseForDormantRooms, $public)
{
	$rooms = rooms_getRoomsInCategory($categoryNumberOrFalseForDormantRooms, $public);

	if (count($rooms) > 0)
	{
		if ($categoryNumberOrFalseForDormantRooms === false)
		{
			rooms_drawCategoryHeader($categoryTitle, "dormant");
		}
		else if ($public)
		{
			rooms_drawCategoryHeader($categoryTitle, $categoryNumberOrFalseForDormantRooms);
		}
		else
		{
			rooms_drawCategoryHeader($categoryTitle, "private");
		}

		echo "<div class='rooms'>";

		$first = true;

		foreach ($rooms as $room)
		{
			rooms_drawRoomListing($room, $first);
			$first = false;
		}

		echo "</div>";

		rooms_drawCategoryFooter();
	}
}


function rooms_drawCategoryHeader($categoryTitle, $styleSuffix)
{
	?>
		<div class="rooms-category-<?php echo $styleSuffix; ?>">
			<div class="rooms-category-header">
				<div class="rooms-category-title">
					<?php echo $categoryTitle; ?>
				</div>
			</div>
			<div class="rooms-category-body">
	<?php
}


function rooms_drawCategoryFooter()
{
	?>
			</div>
			<div class="rooms-category-footer">
			</div>
		</div>
	<?php
}


function rooms_drawRoomListing($room, $first)
{
	$roomId = $room["roomId"];
	$title = $room["title"];
	$url = $room["url"];
	$daysLeft = $room["daysLeft"];
	$description = $room["description"];

	$formattedDescription = htmlspecialchars($description);
	$formattedDescription = template_replaceEmoticons($formattedDescription);
	$formattedDescription = template_replaceUrls($formattedDescription, true);
	$formattedDescription = template_replaceControlCodes($formattedDescription);
	
	$roomUrl = PUBLIC_URL["room"] . $url;
	$thumbUrl = IMAGES_URL . "room-thumb-default.png";
	$thumbBackgroundClass = "room-thumbnail-background-default";

	if (file_exists(ROOM_THUMBS_PATH . $url . ".jpg"))
	{
		$thumbUrl = ROOM_THUMBS_URL . $url . ".jpg";
		$thumbBackgroundClass = "room-thumbnail-background-image";
		
	}

	?>
		<div class='room-row <?php if ($first) echo "room-row-first"; else echo "room-row-rest"; ?>'>

			<a href='<?php echo $roomUrl; ?>'>

				<div class='room-thumbnail'>
					<div class='<?php echo $thumbBackgroundClass; ?>'>
						<img src='<?php echo $thumbUrl; ?>'>
					</div>
				</div>

				<div class='room-details'>
					<div class='room-title'>
						<?php
							echo htmlspecialchars($title);
							if ($daysLeft !== null)
							{
								echo "<span class='category-expiry-time'>";
								if ($daysLeft == 1)
								{
									echo LANG["rooms_category_dormant_expiry_1_day"];
								}
								else
								{
									echo sprintf(LANG["rooms_category_dormant_expiry_n_days"], $daysLeft);
								}
								echo "</span>";
							}
						?>
					</div>

					<div class='room-description'>
						<?php echo $formattedDescription; ?>
					</div>

					<?php
						$unreadLabel = "<span class='room-activity-icon'>" . icon_unread() . "</span>";
						rooms_drawUnreadMessages($roomId, $unreadLabel);

						$videoLabel = "<span class='room-activity-icon'>" . icon_videoCamera() . "</span>";
						video_drawUsersIn($roomId, null, $videoLabel);

						$textLabel = "<span class='room-activity-icon'>" . icon_speechBubble() . "</span>";
						chat_drawActiveUsers($roomId, $textLabel);
					?>
				</div>
			</a>
		</div>
	<?php

}


function rooms_drawMainTextChat()
{
	$thumbUrl = IMAGES_URL . "room-thumb-default.png";
	$thumbBackgroundClass = "room-thumbnail-background-default";
	$roomUrl = PUBLIC_URL["chat"];
	$roomId = rooms_getMainTextChatRoomId();

	$title = LANG["chat_default_text_chat_room_title"];
	$description = LANG["chat_default_text_chat_room_description"];

	?>
		<div class="rooms-category-default">
			<div class="rooms-category-header">
			</div>
			<div class="rooms-category-body">
				<div class='rooms'>
					<div class='room-row room-row-first'>
						<a href='<?php echo $roomUrl; ?>'>

							<div class='room-thumbnail'>
								<div class='<?php echo $thumbBackgroundClass; ?>'>
									<img src='<?php echo $thumbUrl; ?>'>
								</div>
							</div>

							<div class='room-details'>
								<div class='room-title'>
									<?php echo $title; ?>
								</div>

								<div class='room-description'>
									<?php echo $description; ?>
								</div>

								<?php
									$unreadLabel = "<span class='room-activity-icon'>" . icon_unread() . "</span>";
									rooms_drawUnreadMessages($roomId, $unreadLabel);

									$textLabel = "<span class='room-activity-icon'>" . icon_speechBubble() . "</span>";
									chat_drawActiveUsers($roomId, $textLabel);
								?>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div class="rooms-category-footer">
			</div>
		</div>
	<?php
}


// We have a dummy room entry in the database for the main text chat, so we can record when it changes
// and update the main page with any new chat messages in that chat.
// We have a dummy URL to identify it, and it's in category 0 with owner 0
function rooms_createMainTextChatRoomIfNecessary()
{
	try
	{
		$placeholderUrl = CONFIG["default_text_chat_room_placeholder_url"];
		$db = database_getConnection();
		$st = mysqli_prepare($db, "insert ignore into rooms (url, title, description, category, created_by, changed, last_used) " .
					  "values (?, ?, ?, 0, 0, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
                mysqli_stmt_bind_param($st, "sss", $placeholderUrl, $placeholderUrl, $placeholderUrl);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


// We have a dummy room entry in the database for the main text chat, so we can record when it changes
// and update the main page with any new chat messages in that chat.
// We have a dummy URL to identify it, and it's in category 0 with owner 0
function rooms_getMainTextChatRoomId()
{
	rooms_createMainTextChatRoomIfNecessary();

	$placeholderUrl = CONFIG["default_text_chat_room_placeholder_url"];
	return rooms_getRoomIdFromUrl($placeholderUrl);
}


// returns room ID or false
function rooms_getRoomIdFromUrl($url)
{
	// first delete any expired rooms in case we're accessing the URL of an expired room directly
	rooms_deleteExpiredRooms();

	$roomId = false;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select room_id from rooms where url = ?");
		mysqli_stmt_bind_param($st, "s", $url);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $foundId);
		if (mysqli_stmt_fetch($st))
		{
			$roomId = $foundId;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $roomId;
}


function rooms_getUrlFromRoomId($roomId)
{
	$url = PUBLIC_URL["index"];

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select url from rooms where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $foundUrl);
		if (mysqli_stmt_fetch($st))
		{
			$url = PUBLIC_URL["room"] . $foundUrl;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $url;
}


function rooms_getTitleFromRoomId($roomId)
{
	$result = "";

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select title from rooms where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $title);
		if (mysqli_stmt_fetch($st))
		{
			$result = $title;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $result;
}


function rooms_getCategoryFromRoomId($roomId)
{
	$result = "";

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select public, category from rooms where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $public, $categoryNumber);
		if (mysqli_stmt_fetch($st))
		{
			if ($public)
			{
				$result = LANG["rooms_category_title"][$categoryNumber];
			}
			else
			{
				$result = LANG["rooms_category_title_private"];
			}
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $result;
}


// "This is a 'gr8' room name!!!" -> "ThisIsAGr8RoomName"
function rooms_getRoomUrlFromTitle($title)
{
	$titleCase = ucwords($title);
	$camelCase = preg_replace('/[^a-zA-Z0-9]/', '', $titleCase);
	return $camelCase;
}


// returns error message if creation failed - otherwise redirects to new room and doesn't return
// so must be called before any header output
function rooms_createRoomAndRedirect($title, $category, $description, $imageFilename)
{
	$userId = login_getUserId();
	if ($userId === null)
	{
		// shouldn't happen, but hey
		return LANG["login_join_us_prompt_page"];
	}

	if ($category == ROOMS_CATEGORY_PRIVATE)
	{
		$roomUrl = login_generateToken(8);
	}
	else
	{
		$roomUrl = rooms_getRoomUrlFromTitle($title);
	}

	if (strlen($roomUrl) < CONFIG["room_url_min_length"])
	{
		return sprintf(LANG["create_room_no_title"], CONFIG["room_url_min_length"]);
	}

	if ($category == "")
	{
		return LANG["create_room_no_category"];
	}

	if ($category !== ROOMS_CATEGORY_PRIVATE)
	{
		$category = intval($category);
		if ($category < 1 || $category > CONFIG["rooms_number_of_categories"])
		{
			return LANG["create_room_no_category"];
		}
	}

	if ($description == "")
	{
		return LANG["create_room_no_description"];
	}

	// is it (similar to) a reserved URL?
	$testUrl = "/" . strtolower($roomUrl);
	if (array_search($testUrl, PUBLIC_URL) !== false)
	{
		return LANG["create_room_reserved_url"];
	}

	try
	{
		$db = database_getConnection();

		// is that URL already in use?
		$st = mysqli_prepare($db, "select room_id, title from rooms where url = ?");
		mysqli_stmt_bind_param($st, "s", $roomUrl);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $roomId, $existingTitle);
		$exists = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);
		if ($exists)
		{
			return sprintf(LANG["create_room_already_exists"], $existingTitle);
		}


		// add the room to the database
		if ($category == ROOMS_CATEGORY_PRIVATE)
		{
			// create a private room (all private rooms have category 0 for now)
			$st = mysqli_prepare($db, "insert into rooms (url, title, description, category, public, created_by, changed, last_used) " .
						"values (?, ?, ?, 0, 0, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
			mysqli_stmt_bind_param($st, "sssi", $roomUrl, $title, $description, $userId);
			$success = mysqli_stmt_execute($st);
			mysqli_stmt_close($st);
			if (!$success)
			{
				return database_genericErrorMessage();
			}

			// make this user the first member of the room
			$roomId = rooms_getRoomIdFromUrl($roomUrl);
			rooms_addMemberToRoom($roomId);
		}
		else
		{
			// create a public room
			$st = mysqli_prepare($db, "insert into rooms (url, title, description, category, public, created_by, changed, last_used) " .
						"values (?, ?, ?, ?, 1, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
			mysqli_stmt_bind_param($st, "sssii", $roomUrl, $title, $description, $category, $userId);
			$success = mysqli_stmt_execute($st);
			mysqli_stmt_close($st);
			if (!$success)
			{
				return database_genericErrorMessage();
			}
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		return database_genericErrorMessage();
	}

	// save any images
	if ($imageFilename != "")
	{
		$aspectRatio = CONFIG["rooms_image_aspect_ratio_x"] / CONFIG["rooms_image_aspect_ratio_y"];
		images_uploadFile($imageFilename, $roomUrl, ROOM_MAX_IMAGES_PATH, $aspectRatio);
		images_uploadFile($imageFilename, $roomUrl, ROOM_IMAGES_PATH, $aspectRatio, CONFIG["rooms_image_full_height"]);
		images_uploadFile($imageFilename, $roomUrl, ROOM_THUMBS_PATH, $aspectRatio, CONFIG["rooms_image_thumbnail_height"]);
	}

	// success, redirect to new page
	header("Location: " . PUBLIC_URL["room"] . $roomUrl);
	exit(0);
}


function rooms_drawHeaderForRoom($roomId, $subPageTitle, $bodyClass, $includeBlurb = false, $includeTopBar = true, $doingInvitation = false)
{
	rooms_markRoomAsUsed($roomId);

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select url, title, description, public, category, created_by, timestampdiff(day, UTC_TIMESTAMP(), expiry) as daysleft, display_name from rooms left join users on rooms.created_by = users.user_id where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $url, $title, $description, $public, $category, $createdBy, $daysLeft, $displayName);
		$exists = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);

		if (!$exists)
		{
			template_drawHeader(LANG["page_title_unknown_room"], null, "", null, $includeTopBar);
			return;
		}

		if (!$public)
		{
			// all private rooms are currently displayed in their own category
			$category = "private";
		}

		$bodyClass .= " rooms-category-$category";

		if ($includeBlurb)
		{
			$bodyClass .= " no-box";
		}

		$openGraph = array();

		$openGraphDescription = htmlspecialchars($description);
		$openGraphDescription = template_replaceEmoticons($openGraphDescription);
		$openGraphDescription = rooms_shortDescription($openGraphDescription);
		$openGraph["description"] = $openGraphDescription;

		if (file_exists(ROOM_MAX_IMAGES_PATH . $url . ".jpg"))
		{
			$openGraph["image"] = CONFIG["site_root_url"] . ROOM_MAX_IMAGES_URL . $url . ".jpg";
		}
		

		template_drawHeader($subPageTitle, $roomId, $bodyClass, $openGraph, $includeTopBar);

		if (!$doingInvitation)
		{
			template_bannerSuggestingLogin($roomId);
		}

		if ($includeBlurb)
		{
			if ($daysLeft !== null)
			{
				echo "<div id='banner-room-dormant'>";
				if ($daysLeft == 1)
				{
					echo LANG["rooms_header_dormant_expiry_1_day"];
				}
				else
				{
					echo sprintf(LANG["rooms_header_dormant_expiry_n_days"], $daysLeft);
				}
				echo " ";
				echo LANG["rooms_header_dormant_explanation"];
				echo "</div>";
			}

			$formattedDescription = htmlspecialchars($description);
			$formattedDescription = template_replaceEmoticons($formattedDescription);
			$formattedDescription = template_replaceUrls($formattedDescription);
			$formattedDescription = template_replaceControlCodes($formattedDescription);

			?>
				<div id='room-blurb-category-<?php echo $category; ?>'>

					<div id='room-blurb' class='module-id'>

						<div id='room-blurb-header'>
							<div class='show-if-has-js'>
								<div class='module-show-button'>
									<span class='module-header-icon'>
										<?php echo icon_show(); ?>
									</span>
									<span class='module-header-button-label'>
										<?php echo LANG["module_button_show"]; ?>
									</span>
								</div>
								<div class='module-hide-button'>
									<span class='module-header-icon'>
										<?php echo icon_hide(); ?>
									</span>
									<span class='module-header-button-label'>
										<?php echo LANG["module_button_hide"]; ?>
									</span>
								</div>
							</div>

							<div id='room-blurb-title'>
								<?php echo htmlspecialchars($title); ?>
							</div>
						</div>

						<div id='room-blurb-body' class='module-body'>
			<?php
							if (file_exists(ROOM_IMAGES_PATH . $url . ".jpg"))
							{
								$imageUrl = CONFIG["site_root_url"] . ROOM_IMAGES_URL . $url . ".jpg";
								?>
									<div id='room-blurb-image'>
										<img src='<?php echo $imageUrl; ?>'>
									</div>
								<?php
							}
			?>
							<div id='room-blurb-description'>
								<?php echo $formattedDescription; ?>
			<?php
								if (login_isLoggedIn())
								{
			?>
									<div class='invite-button'>
										<a href="<?php echo $_SERVER['REQUEST_URI'] . PUBLIC_URL['invite']; ?>" class="link-looking-like-a-button">
											<span class="button-icon">
												<?php echo icon_invite(); ?>
											</span>
											<?php echo LANG["room_invite_button_label"]; ?>
										</a>
									</div>
			<?php
								}
			?>

							</div>

							<div id='room-blurb-end'>
							</div>
						</div>
					</div>
				</div>
			<?php
		echo "<div class='rooms-category-$category'>";
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		template_drawHeader(LANG["page_title_unknown_room"], null, "");
		echo database_genericErrorMessage();
	}
}


function rooms_markRoomAsUsed($roomId)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "update rooms set last_used = UTC_TIMESTAMP() where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		// just log and ignore, user doesn't need to know if timestamp can't be updated
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function rooms_markRoomAsChanged($roomId)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "update rooms set changed = UTC_TIMESTAMP(), expiry = null where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		// just log and ignore, user doesn't need to know if timestamp can't be updated
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


// if rooms have been updated since $previousUpdateTime, return the new time of the latest update
// otherwise return false
function rooms_updateTimeSince($previousUpdateTime)
{
	rooms_createMainTextChatRoomIfNecessary();

	$result = false;

	try
	{
		$db = database_getConnection();

		$userId = login_getUserId();

		if ($userId === null)
		{
			// no user logged in, so just include public rooms
			if ($previousUpdateTime == "")
			{
				$st = mysqli_prepare($db, "select max(changed) as last_update, UTC_TIMESTAMP() as now from rooms where public = 1");
			}
			else
			{
				// look for changes since last update,
				// or changes that happened within the previous "timeout" window but have now dropped out of the timeout window
				$st = mysqli_prepare($db, "select max(changed) as last_update, UTC_TIMESTAMP() as now from rooms where public = 1 and changed > ? or (changed > date_sub(?, interval ? minute) and changed < date_sub(UTC_TIMESTAMP(), interval ? minute))");
				$timeout = CONFIG["chat_active_timeout_minutes"];
				mysqli_stmt_bind_param($st, "ssii", $previousUpdateTime, $previousUpdateTime, $timeout, $timeout);
			}
		}
		else if (login_isAnAdmin())
		{
			// include all rooms
			if ($previousUpdateTime == "")
			{
				$st = mysqli_prepare($db, "select max(changed) as last_update, UTC_TIMESTAMP() as now from rooms");
			}
			else
			{
				// look for changes since last update,
				// or changes that happened within the previous "timeout" window but have now dropped out of the timeout window
				$st = mysqli_prepare($db, "select max(changed) as last_update, UTC_TIMESTAMP() as now from rooms where changed > ? or (changed > date_sub(?, interval ? minute) and changed < date_sub(UTC_TIMESTAMP(), interval ? minute))");
				$timeout = CONFIG["chat_active_timeout_minutes"];
				mysqli_stmt_bind_param($st, "ssii", $previousUpdateTime, $previousUpdateTime, $timeout, $timeout);
			}
		}
		else
		{
			// include public rooms, or private rooms accessible to this user

			if ($previousUpdateTime == "")
			{
				$st = mysqli_prepare($db, "select max(changed) as last_update, UTC_TIMESTAMP() as now from rooms left join room_members using (room_id) where (public = 1 or user_id = ?)");
				mysqli_stmt_bind_param($st, "i", $userId);
			}
			else
			{
				// look for changes since last update,
				// or changes that happened within the previous "timeout" window but have now dropped out of the timeout window
				$st = mysqli_prepare($db, "select max(changed) as last_update, UTC_TIMESTAMP() as now from rooms left join room_members using (room_id) where (public = 1 or user_id = ?) and changed > ? or (changed > date_sub(?, interval ? minute) and changed < date_sub(UTC_TIMESTAMP(), interval ? minute))");
				$timeout = CONFIG["chat_active_timeout_minutes"];
				mysqli_stmt_bind_param($st, "issii", $userId, $previousUpdateTime, $previousUpdateTime, $timeout, $timeout);
			}
		}

		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $lastUpdateTime, $now);

		if (mysqli_stmt_fetch($st) && $lastUpdateTime !== null)
		{
			$result = $now;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $result;
}


function rooms_drawActiveUsers($roomId, $users, $label)
{
	if (count($users) > 0)
	{
		echo "<div class='room-activity'>";

		echo $label;

		if (login_hasFullAccessToRoom($roomId))
		{
			foreach ($users as $user)
			{
				if (isset($user["inactive"]) && $user["inactive"])
				{
					echo '<span class=room-occupant-inactive>';
				}
				else
				{
					echo '<span class=room-occupant-active>';
				}

				$safeName = htmlspecialchars($user["name"]);
				$nonBreakingName = preg_replace('/ /', '&nbsp;', $safeName);
				echo $nonBreakingName;

				/*
				if ($user["mobile"])
				{
					echo symbol_mobilePhone();
				}
				*/

				echo '</span>';
				echo '&nbsp';
				echo '<span class=room-occupant-time>';
				echo sprintf(LANG["rooms_users_elapsed_time"], $user["secondsSinceLastUpdate"] / 60);
				echo '</span>';
				echo ' ';
			}
		}
		else
		{
			echo '<span class=room-occupants-count>';

			if (count($users) == 1)
			{
				echo LANG["rooms_user_count_one"];
			}
			else
			{
				echo sprintf(LANG["rooms_user_count_multiple"], count($users));
			}

			echo '</span>';
		}

		echo "</div>";
	}
}


function rooms_drawUnreadMessages($roomId, $label)
{
	$numUnread = chat_unreadMessages($roomId);

	if ($numUnread > 0)
	{
		echo "<div class='room-activity'>";

		echo $label;

		echo '<span class=room-unread-messages-count>';

		if (login_isLoggedIn())
		{
			if ($numUnread == 1)
			{
				echo LANG["rooms_unread_messages_count_one"];
			}
			else
			{
				echo sprintf(LANG["rooms_unread_messages_count_multiple"], $numUnread);
			}
		}
		else
		{
			if ($numUnread == 1)
			{
				echo LANG["rooms_messages_count_one"];
			}
			else
			{
				echo sprintf(LANG["rooms_messages_count_multiple"], $numUnread);
			}
		}

		echo '</span>';

		echo "</div>";
	}
}


function rooms_shortDescription($description)
{
	$maxChars = CONFIG["room_short_description_max_characters"];

	if (strlen($description) > $maxChars)
	{
		// truncate
		$description = substr($description, 0, $maxChars);

		// look for previous word break
		$pos = strrpos($description, " ");

		if ($pos !== false)
		{
			$description = substr($description, 0, $pos + 1);
		}

		$description .= "...";
	}

	return $description;
}


function rooms_deleteExpiredRooms()
{
	try
	{
		$rooms = array();

		$db = database_getConnection();

		$st = mysqli_prepare($db, "select room_id, url from rooms where expiry < UTC_TIMESTAMP()");
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $roomId, $url);

		while (mysqli_stmt_fetch($st))
		{
			$rooms[] =
			[
				"roomId" => $roomId,
				"url" => $url
			];
		}

		mysqli_stmt_close($st);

		foreach ($rooms as $room)
		{
			// delete room
			$st = mysqli_prepare($db, "delete from rooms where room_id = ?");
			mysqli_stmt_bind_param($st, "i", $room["roomId"]);
			mysqli_stmt_execute($st);

			// delete room members
			$st = mysqli_prepare($db, "delete from room_members where room_id = ?");
			mysqli_stmt_bind_param($st, "i", $room["roomId"]);
			mysqli_stmt_execute($st);

			// delete videos
			video_deleteVideosInRoom($room["roomId"]);

			// delete chats
			chat_deleteChatsInRoom($room["roomId"]);

			// delete events
			calendar_deleteEventsInRoom($room["roomId"]);

			// delete invitations
			login_deleteInvitationsInRoom($room["roomId"]);

			// delete room images
			images_deleteFile($room["url"], ROOM_MAX_IMAGES_PATH);
			images_deleteFile($room["url"], ROOM_IMAGES_PATH);
			images_deleteFile($room["url"], ROOM_THUMBS_PATH);

		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function rooms_isRoomPublic($roomId)
{
	$result = false;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select public from rooms where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $public);
		if (mysqli_stmt_fetch($st))
		{
			$result = $public;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $result;
}


function rooms_isMemberOfRoom($roomId)
{
	$userId = login_getUserId();
	$isMember = false;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select * from room_members where room_id = ? and user_id = ?");
		mysqli_stmt_bind_param($st, "ii", $roomId, $userId);
		mysqli_stmt_execute($st);
		if (mysqli_stmt_fetch($st))
		{
			$isMember = true;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $isMember;
}


function rooms_getMembers($roomId)
{
	$members = array();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select display_name from room_members join users using (user_id) where room_id = ? order by display_name asc");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $name);
		while (mysqli_stmt_fetch($st))
		{
                        $members[] = [ "name" => $name ];
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $members;
}


function rooms_addMemberToRoom($roomId)
{
	$userId = login_getUserId();
	if ($userId === null)
	{
		// shouldn't happen, but hey
		return LANG["login_join_us_prompt_page"];
	}

	try
	{
		$db = database_getConnection();

		// add the room to the database
		$st = mysqli_prepare($db, "insert ignore into room_members (room_id, user_id) values (?, ?)");
		mysqli_stmt_bind_param($st, "ii", $roomId, $userId);
		$success = mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
		if (!$success)
		{
			return database_genericErrorMessage();
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		return database_genericErrorMessage();
	}

	return "";
}


function rooms_drawMembersWindow($roomId)
{
	?>
		<div id="rooms-members" class="module-background-update">
		</div>
		<div class='show-if-no-js'>
			<?php echo LANG['rooms_members_no_javascript']; ?>
		</div>
	<?php

	module_clientStartBackgroundRefresh(API_URL["rooms-get-members"] . "?roomId=$roomId", "", "", "rooms-members", "module_refresh_rooms_members", false);
}



function rooms_apiGetMembers()
{
	module_serverCheckForNewContent("rooms_getMembersContentFunction");
}


// contentFunction() takes
//	a string representing the last entry output to the user (interpretted however the function likes)
//	the time the server last sent new output (as received from the server previously)
// if there is new content, it returns an array:
//	"lastId" => the new last entry,
//	"html" => the rendered output
// if there is no new content, it returns false
function rooms_getMembersContentFunction($lastId, $lastTime)
{
	if (!isset($_GET["roomId"]))
	{
		return false;
	}

	$roomId = intval($_GET["roomId"]);

	if (!login_hasPreviewAccessToRoom($roomId))
	{
		return false;
	}


	// get the members

	$members = rooms_getMembers($roomId);
	$admins = login_getAdmins();


	// have they changed?

	$allMembers = [ $members, $admins ];
	$checksum = md5(json_encode($allMembers));

	if ($checksum == $lastId)
	{
		return false;
	}


	// got new members, so we can render them to html

	ob_start();

	rooms_drawActiveUsers($roomId, $members, LANG["rooms_members_label"]);
	rooms_drawActiveUsers($roomId, $admins, LANG["rooms_admins_label"]);

	$html = ob_get_clean();

	return [ "lastId" => $checksum, "html" => $html ];
}
