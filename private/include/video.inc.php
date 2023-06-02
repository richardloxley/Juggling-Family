<?php

require_once("module.inc.php");
require_once("rooms.inc.php");
require_once("jitsi.inc.php");
require_once("mobile.inc.php");


function video_drawVideoWindow($roomId)
{
	?>
		<div id="video-list" class="module-background-update">
		</div>
		<div class='show-if-no-js'>
			<?php echo LANG['video_no_javascript']; ?>
		</div>
	<?php

	module_clientStartBackgroundRefresh(API_URL["video-get-list"] . "?roomId=$roomId", "", "", "video-list", "module_refresh_video_list", false);
}



function video_apiGetVideoList()
{
	module_serverCheckForNewContent("video_getVideoListContentFunction");
}


// contentFunction() takes
//	a string representing the last entry output to the user (interpretted however the function likes)
//	the time the server last sent new output (as received from the server previously)
// if there is new content, it returns an array:
//	"lastId" => the new last entry,
//	"html" => the rendered output
// if there is no new content, it returns false
function video_getVideoListContentFunction($lastId, $lastTime)
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

	video_cleanOutInactiveUsers();

	$newUpdateTime = video_updateTimeSince($roomId, $lastId);

	// any rooms included by this room?
	if (isset(CONFIG["room_included_by_room"]))
	{
		foreach (CONFIG["room_included_by_room"] as $child => $parent)
		{
			if ($roomId == $parent)
			{
				if ($newUpdateTime == false)
				{
					$newUpdateTime = video_updateTimeSince($child, $lastId);
				}
			}
		}
	}

	if ($newUpdateTime !== false)
	{
		ob_start();
		video_drawVideoChats($roomId);
		$html = ob_get_clean();

		return [ "lastId" => $newUpdateTime, "html" => $html ];
	}
	else
	{
		return false;
	}
}


function video_drawStartVideoButton($videoId, $jitsiRoomId, $jitsiTitle)
{
	if (isset($_COOKIE["jitsi-use-app"]))
	{
		$name = login_getDisplayName();
		$dummyUserId = md5($name);
		$joinedApiUrl =	API_URL["video-participant-changed"] . '?video=' . $videoId . '&myid=' . $dummyUserId . '&joined=' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
		?>
			<a class="link-looking-like-a-button video-mobile-app-button" href="<?php echo jitsi_deeplink($jitsiRoomId, $jitsiTitle); ?>" onclick="fetch('<?php echo $joinedApiUrl; ?>');">
				<span class="video-chat-icon">
					<?php echo icon_mobilePhone();?>
				</span>
				<?php echo LANG["video_start_video_app_button"]; ?>
			</a>
			<p>
		<?php
	}
	else
	{
		if (CONFIG["jitsi_embed_in_iframe"])
		{
			$encodedRoomTitle = htmlspecialchars(json_encode($jitsiTitle), ENT_QUOTES, 'UTF-8');
			?>
				<button class='start-video-button' onclick='startVideoButtonClicked(<?php echo $videoId; ?>, "<?php echo $jitsiRoomId; ?>", <?php echo $encodedRoomTitle; ?>);'>
					<span class="video-chat-icon">
						<?php echo icon_videoCamera();?>
					</span>
					<?php echo LANG["video_start_video_button"]; ?>
				</button>
			<?php
		}
		else
		{
			$name = login_getDisplayName();
			$dummyUserId = md5($name);
			$joinedApiUrl =	API_URL["video-participant-changed"] . '?video=' . $videoId . '&myid=' . $dummyUserId . '&joined=' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

			?>
				<a class="link-looking-like-a-button start-video-button" href="<?php echo jitsi_webLink($jitsiRoomId, $jitsiTitle); ?>" onclick="fetch('<?php echo $joinedApiUrl; ?>');" target="_blank">
					<span class="video-chat-icon">
						<?php echo icon_videoCamera();?>
					</span>
					<?php echo LANG["video_start_video_button"]; ?>
				</a>
				<p>
			<?php
		}
	}
}


function video_drawAltVideoButton($videoId, $altUrl)
{
	$name = login_getDisplayName();
	$dummyUserId = md5($name);
	$joinedApiUrl =	API_URL["video-participant-changed"] . '?video=' . $videoId . '&myid=' . $dummyUserId . '&joined=' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

	?>
		<a class="link-looking-like-a-button video-external-button" href="<?php echo $altUrl; ?>" onclick="fetch('<?php echo $joinedApiUrl; ?>');" target="_blank">
			<span class="video-chat-icon">
				<?php echo icon_videoCamera();?>
			</span>
			<?php echo LANG["video_start_video_button"]; ?>
		</a>
	<?php
}


function video_drawVideoChats($roomId)
{
	$canJoinVideo = true;

	if (mobile_isIos())
	{
		$version = mobile_iosMajorVersion();
		if ($version > 0 && $version <= 10)
		{
			// we need WebRTC which wasn't added to iOS Safari until iOS 11
			// also the Jitsi app only supports iOS 11+ as their WebRTC library is no longer
			// guaranteed to work on older versions of iOS
			// https://github.com/jitsi/jitsi-meet/issues/6512
			echo LANG["video_old_ios"];
			$canJoinVideo = false;
		}
	}

	if ($canJoinVideo && !login_hasFullAccessToRoom($roomId))
	{
		echo LANG["video_not_logged_in"];
		$canJoinVideo = false;
	}


	echo "<div id='video-controls'>";
	echo "<center>";


	// headphone text

	if ($canJoinVideo)
	{
		foreach (LANG["video_hint"] as $hint)
		{
			echo "<div class='video-hint'>";
			echo $hint;
			echo "</div>";
		}


		// mobile browser text

		if (mobile_isMobile())
		{
			echo "<p>";
			if (isset($_COOKIE["jitsi-use-app"]))
			{
				// "If video doesn't work well try switching to the mobile app in Settings"
				$link = "<a href='" . PUBLIC_URL["settings"] . "?open=video'>" . LANG["top_bar_button_settings"] . "</a>";
				echo sprintf(LANG["video_switch_to_browser"], $link);
			}
			else
			{
				// "Video will open in the Jitsi app - or you can switch to video in the browser in Settings."
				$link = "<a href='" . PUBLIC_URL["settings"] . "?open=video'>" . LANG["top_bar_button_settings"] . "</a>";
				echo sprintf(LANG["video_switch_to_app"], $link);
			}
		}
	}

	// get list of video chats
	$videos = video_getVideoChats($roomId);


	// any rooms included by this room?
	if (isset(CONFIG["room_included_by_room"]))
	{
		foreach (CONFIG["room_included_by_room"] as $child => $parent)
		{
			if ($roomId == $parent)
			{
				$extraVideos = video_getVideoChats($child);
				$extraVideos[0]["title"] = rooms_getTitleFromRoomId($child);
				$videos = array_merge($videos, $extraVideos);
			}
		}
	}


	// draw them all

	$cameraIcon = "<span class='video-chat-icon'>" . icon_videoCamera() . "</span>";
	$roomName = rooms_getTitleFromRoomId($roomId);

	if (count($videos) == 1)
	{
		// only one, just do a simple UI with a start button

		if ($videos[0]["jitsiId"])
		{
			if ($canJoinVideo)
			{
				video_drawStartVideoButton($videos[0]["videoId"], $videos[0]["jitsiId"], $roomName);
			}
		}
		else
		{
			video_drawAltVideoButton($videos[0]["videoId"], $videos[0]["altUrl"]);
		}

		video_drawUsersIn($roomId, $videos[0]["videoId"], $cameraIcon);
	}
	else
	{
		// more than one, separate them out with titles for each video chat

		echo "<div class='video-chat-multiple-wrapper'>";

		$x = 1;
		foreach ($videos as $video)
		{
			echo "<div class='video-chat-section'>";

			if ($video["title"] === null)
			{
				$title = sprintf(LANG["video_chat_title_multiple"], $x);
				$jitsiTitle = "$roomName ($x)";
			}
			else
			{
				$title = $video["title"];
				$jitsiTitle = "$roomName ($title)";
			}

			echo "<div class='video-chat-title'>";
			echo $title;
			echo "</div>";

			if ($video["jitsiId"])
			{
				if ($canJoinVideo)
				{
					video_drawStartVideoButton($video["videoId"], $video["jitsiId"], $jitsiTitle);
				}
			}
			else
			{
				video_drawAltVideoButton($video["videoId"], $video["altUrl"]);
			}

			video_drawUsersIn($roomId, $video["videoId"], $cameraIcon);

			$x++;

			echo "</div>";
		}

		echo "</div>";
	}

	echo "</center>";
	echo "</div>";


	if (CONFIG["jitsi_embed_in_iframe"])
	{
		// load the Jitsi stuff

		jitsi_drawJitsiJs($roomId, "video-jitsi-frame");


		// javascript for everything else

		?>
			<script type="text/javascript">

				function startVideoButtonClicked(videoId, jitsiRoomId, roomName)
				{
					$("body").addClass("video-body");
					hideAllModules();

					$("#video-jitsi-frame").show();

					startVideo(videoId, jitsiRoomId, roomName);
				};

			</script>
		<?php
	}
}


function video_makeFirstVideoChatIfNecessary($roomId)
{
	$needToMakeFirstVideo = true;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select count(room_id) as num_videos from video where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $numVideos);

		if (mysqli_stmt_fetch($st))
		{
			if ($numVideos > 0)
			{
				$needToMakeFirstVideo = false;
			}
		}

		mysqli_stmt_close($st);

		if ($needToMakeFirstVideo)
		{
			// create first video chat since we don't have one yet
			$jitsiId = login_generateToken(32);
			$st = mysqli_prepare($db, "insert into video (room_id, jitsi_id, created, changed) values (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())");
			mysqli_stmt_bind_param($st, "is", $roomId, $jitsiId);
			if (!mysqli_stmt_execute($st))
			{
				error_log(__FILE__ . ":" . __LINE__ . " failed to create video chat");
			}
			mysqli_stmt_close($st);
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function video_getVideoChats($roomId)
{
	video_makeFirstVideoChatIfNecessary($roomId);

	$videos = array();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select video_id, title, jitsi_id, alt_url from video where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $videoId, $title, $jitsiId, $altUrl);

		while (mysqli_stmt_fetch($st))
		{
			$videos[] =
			[
				"videoId" => $videoId,
				"title" => $title,
				"jitsiId" => $jitsiId,
				"altUrl" => $altUrl
			];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $videos;
}


function video_numberSecondsForInactivity()
{
	if (CONFIG["jitsi_embed_in_iframe"])
	{
		// if they've missed at least 2 updates, regard them as no longer active
		return 3 * CONFIG["jitsi_occupant_refresh_time_seconds"];
	}
	else
	{
		// other video rooms will have inactive users almost immediately as we can't read them
		return CONFIG["video_user_inactive_after_seconds"];
	}
}


// if videos have been updated since $previousUpdateTime, return the new time of the latest update
// otherwise return false
function video_updateTimeSince($roomId, $previousUpdateTime)
{
	video_makeFirstVideoChatIfNecessary($roomId);

	$result = false;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select max(changed) as last_update from video where room_id = ? and changed > ?");
		mysqli_stmt_bind_param($st, "is", $roomId, $previousUpdateTime);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $lastUpdateTime);
		if (mysqli_stmt_fetch($st) && $lastUpdateTime !== null)
		{
			$result = $lastUpdateTime;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $result;
}


function video_drawBannerAndFrame($roomId)
{
	// make the banner for when we've left the chat

	?>
		<div id="video-end-banner">
			<table>
				<tr>
					<td id='video-end-button'>
						<a class="link-looking-like-a-button" href=<?php echo rooms_getUrlFromRoomId($roomId); ?>>
							<?php echo LANG["video_back_button"]; ?>
						</a>
					</td>
					<td id='video-end-message'>
						<?php echo LANG["video_sponsor_message"]; ?>
					</td>
				</tr>
			</table>
		</div>

		<div id="video-chat-popup">
			<div id="video-chat-popup-outer">
				<div id="video-chat-popup-inner">
					<span id="video-chat-popup-name">
					</span>
					<span id="video-chat-popup-message">
					</span>
				</div>
			</div>
		</div>

		<div id="video-volume-control-popup">
			<div id="video-volume-control-popup-outer">
				<div id="video-volume-control-popup-inner">
					<div id="video-volume-control-popup-title">
						<?php echo LANG["video_volume_title"]; ?>
					</div>
					<div id="video-volume-control-popup-user-list">
					</div>
					<div id="video-volume-control-popup-warning">
						This is an experimental juggling.family feature
					</div>
					<div id="video-volume-control-popup-buttons">
						<button id="video-volume-control-popup-reset-button">
							<?php echo LANG["video_volume_reset"]; ?>
						</button>
						<button id="video-volume-control-popup-close-button">
							<?php echo LANG["video_volume_close"]; ?>
						</button>
					</div>
				</div>
			</div>
		</div>

		<div id="video-jitsi-frame">
		</div>
	<?php

	// load any "fun" stuff
	video_drawFun();
}


function video_drawUsersIn($roomId, $videoIdOrNull, $label)
{
	video_cleanOutInactiveUsers();

	if ($videoIdOrNull === null)
	{
		$users = video_getUsersInRoom($roomId);

		// any rooms included by this room?
		if (isset(CONFIG["room_included_by_room"]))
		{
			foreach (CONFIG["room_included_by_room"] as $child => $parent)
			{
				if ($roomId == $parent)
				{
					$extraUsers = video_getUsersInRoom($child);
					$users = array_merge($users, $extraUsers);
				}
			}
		}

		rooms_drawActiveUsers($roomId, $users, $label);
	}
	else
	{
		$videoId = $videoIdOrNull;

		$users = video_getUsersInVideo($videoId);
		rooms_drawActiveUsers($roomId, $users, $label);
	}
}


function video_deleteVideosInRoom($roomId)
{
	try
	{
		$videoIds = array();

		$db = database_getConnection();

		$st = mysqli_prepare($db, "select video_id from video where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $videoId);

		while (mysqli_stmt_fetch($st))
		{
			$videoIds[] = $videoId;
		}

		mysqli_stmt_close($st);

		foreach ($videoIds as $videoId)
		{
			// delete video
			$st = mysqli_prepare($db, "delete from video where video_id = ?");
			mysqli_stmt_bind_param($st, "i", $videoId);
			mysqli_stmt_execute($st);

			// delete any video users
			$st = mysqli_prepare($db, "delete from video_users where video_id = ?");
			mysqli_stmt_bind_param($st, "i", $videoId);
			mysqli_stmt_execute($st);
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function video_getUsersInVideo($videoId)
{
	$users = array();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select video_display_name, has_javascript, time_to_sec(timediff(UTC_TIMESTAMP(), last_seen)) as seconds_since_last_update from video_users where video_id = ? order by last_seen desc");
		mysqli_stmt_bind_param($st, "i", $videoId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $jitsiName, $hasJavascript, $secondsSinceLastUpdate);

		while (mysqli_stmt_fetch($st))
		{
			$inactive = ($secondsSinceLastUpdate > video_numberSecondsForInactivity());
			$users[] =
			[
				"name" => $jitsiName,
				"mobile" => !$hasJavascript,
				"inactive" => $inactive,
				"secondsSinceLastUpdate" => $secondsSinceLastUpdate
			];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $users;
}


function video_getUsersInRoom($roomId)
{
	$users = array();

	try
	{
		$db = database_getConnection();

		$st = mysqli_prepare($db, "select video_display_name, has_javascript, time_to_sec(timediff(UTC_TIMESTAMP(), last_seen)) as seconds_since_last_update from video_users inner join video using (video_id) where room_id = ? order by last_seen desc");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $jitsiName, $hasJavascript, $secondsSinceLastUpdate);

		while (mysqli_stmt_fetch($st))
		{
			$inactive = ($secondsSinceLastUpdate > video_numberSecondsForInactivity());
			$users[] =
			[
				"name" => $jitsiName,
				"mobile" => !$hasJavascript,
				"inactive" => $inactive,
				"secondsSinceLastUpdate" => $secondsSinceLastUpdate
			];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $users;
}



function video_cleanOutInactiveUsers()
{
	$videoIdsToCleanOut = array();
	$videoIdsToRefresh = array();

	try
	{
		$db = database_getConnection();

		$st = mysqli_prepare($db, "select video_id, jitsi_id, sum(has_javascript) as total_javascript, count(*) as total, min(time_to_sec(timediff(UTC_TIMESTAMP(), last_seen))) as seconds_since_last_update from video_users inner join video using (video_id) group by video_id");
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $videoId, $jitsiId, $totalJavascript, $total, $secondsSinceLastUpdate);

		while (mysqli_stmt_fetch($st))
		{
			// each of these video rooms must have had at least one occupant if we've got to here

			if ($jitsiId !== null)
			{
				// we have an iframe Jitsi room - which can send us updates *if* there is at least one user with JavaScript
				if (CONFIG["jitsi_embed_in_iframe"] && $totalJavascript > 0)
				{
					// we have at least one person who can send updates
					if ($secondsSinceLastUpdate > video_numberSecondsForInactivity())
					{
						// but they've all missed at least 2 updates, so they've probably
						// dropped off the call without updating us, and the data isn't reliable
						$videoIdsToCleanOut[] = $videoId;
					}
				}
				else
				{
					// we have only mobile users or non-iframe web users (who don't send us updates)
					if ($secondsSinceLastUpdate > CONFIG["video_ignore_no_js_users_after_minutes"] * 60)
					{
						// they haven't been updated in a "reasonable" time
						$videoIdsToCleanOut[] = $videoId;
					}

					// we display the time in the room for these type of chats, so always flag the list
					// of users as having changed, since the time needs updating
					$videoIdsToRefresh[] = $videoId;
				}
			}
			else
			{
				// we have an alternative video room that can't send us updates for active users
				if ($secondsSinceLastUpdate > CONFIG["video_ignore_alt_users_after_minutes"] * 60)
				{
					// they haven't been updated in a "reasonable" time
					$videoIdsToCleanOut[] = $videoId;
				}

				// we display the time in the room for these type of chats, so always flag the list
				// of users as having changed, since the time needs updating
				$videoIdsToRefresh[] = $videoId;
			}

		}

		mysqli_stmt_close($st);

		foreach ($videoIdsToRefresh as $videoId)
		{
			video_usersHaveChanged($videoId);
		}

		// clean out the rooms that have unreliable data
		foreach ($videoIdsToCleanOut as $videoId)
		{
			$st = mysqli_prepare($db, "delete from video_users where video_id = ?");
			mysqli_stmt_bind_param($st, "i", $videoId);
			mysqli_stmt_execute($st);
			mysqli_stmt_close($st);

			video_usersHaveChanged($videoId);
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function video_handleParticipantsChanged()
{
	if (!isset($_GET["video"]) || !isset($_GET["myid"]))
	{
		return;
	}

	$videoId = $_GET["video"];
	$myId = $_GET["myid"];

	$roomId = video_roomIdFromVideoId($videoId);

	if (!login_hasFullAccessToRoom($roomId))
	{
		return;
	}

	if (isset($_GET["left"]))
	{
		$left = $_GET["left"];
		video_userLeft($videoId, $left);
		debug("Video $videoId: $myId reports user $left left");
	}
	else if (isset($_GET["kicked"]))
	{
		$kicked = $_GET["kicked"];
		video_userLeft($videoId, $kicked);
		debug("Video $videoId: $myId reports user $kicked kicked out");
	}
	else if (isset($_GET["allusers"]))
	{
		$allusers = json_decode($_GET["allusers"], true);
		video_setAllUsers($videoId, $allusers);
		$numUsers = count($allusers);
		debug("Video $videoId: $myId reports total $numUsers users");
	}
	else if (isset($_GET["joined"]))
	{
		$joinedName = $_GET["joined"];
		video_userJoined($videoId, $myId, $joinedName);
		debug("Video $videoId: $myId reports user $joinedName joined");
	}

	video_userIsActive($videoId, $myId);
	video_roomIsActive($videoId);
}


function video_userJoined($videoId, $userId, $userName)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "insert into video_users (video_id, video_user_id, video_display_name, first_seen, last_seen, has_javascript) values (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), false) on duplicate key update video_display_name = ?, last_seen = UTC_TIMESTAMP()");
		mysqli_stmt_bind_param($st, "isss", $videoId, $userId, $userName, $userName);
		mysqli_stmt_execute($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	// user joined so mark the video chat as changed
	video_usersHaveChanged($videoId);
}


function video_userLeft($videoId, $left)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "delete from video_users where video_id = ? and video_user_id = ?");
		mysqli_stmt_bind_param($st, "is", $videoId, $left);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	// user left so mark the video chat as changed
	video_usersHaveChanged($videoId);
}


function video_setAllUsers($videoId, $allusers)
{
	$usersHaveChanged = false;

	try
	{
		$db = database_getConnection();

		// add/update each user present

		foreach ($allusers as $id => $name)
		{
			$st = mysqli_prepare($db, "insert into video_users (video_id, video_user_id, video_display_name, first_seen, last_seen, has_javascript) values (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), false) on duplicate key update video_display_name = ?, last_seen = UTC_TIMESTAMP()");
			mysqli_stmt_bind_param($st, "isss", $videoId, $id, $name, $name);
			mysqli_stmt_execute($st);

			// With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 1 if the row is inserted as a
			// new row, 2 if an existing row is updated, and 0 if an existing row is set to its current values
			if (mysqli_stmt_affected_rows($st) == 1)
			{
				// new row created, so we haven't seen this user before
				$usersHaveChanged = true;
			}

			mysqli_stmt_close($st);
		}

		// delete any that weren't in that list
		// https://phpdelusions.net/mysqli_examples/prepared_statement_with_in_clause

		$allIds = array_keys($allusers);
		$questionMarkList = str_repeat('?,', count($allIds) - 1) . '?';
		$typeList = str_repeat('s', count($allIds));
		$st = mysqli_prepare($db, "delete from video_users where video_id = ? and video_user_id not in ($questionMarkList)");
		mysqli_stmt_bind_param($st, "i" . $typeList, $videoId, ...$allIds);
		mysqli_stmt_execute($st);

		if (mysqli_stmt_affected_rows($st) > 0)
		{
			// at least one user has been deleted
			$usersHaveChanged = true;

		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	if ($usersHaveChanged)
	{
		video_usersHaveChanged($videoId);
	}
}


function video_usersHaveChanged($videoId)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "update video set changed = UTC_TIMESTAMP() where video_id = ?");
		mysqli_stmt_bind_param($st, "s", $videoId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	video_roomHasChanged($videoId);
}


function video_userIsActive($videoId, $myId)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "update video_users set has_javascript = true, last_seen = UTC_TIMESTAMP() where video_id = ? and video_user_id = ?");
		mysqli_stmt_bind_param($st, "is", $videoId, $myId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function video_roomIsActive($videoId)
{
	$roomId = video_roomIdFromVideoId($videoId);

	if ($roomId !== null)
	{
		rooms_markRoomAsUsed($roomId);
	}
}


function video_roomHasChanged($videoId)
{
	$roomId = video_roomIdFromVideoId($videoId);

	if ($roomId !== null)
	{
		rooms_markRoomAsChanged($roomId);
	}
}


function video_roomIdFromVideoId($videoId)
{
	$roomId = null;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select room_id from video where video_id = ?");
		mysqli_stmt_bind_param($st, "i", $videoId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $foundRoomId);

		if (mysqli_stmt_fetch($st))
		{
			$roomId = $foundRoomId;
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $roomId;
}


function video_showSettings()
{
	// no settings if not logged in
	if (!login_isLoggedIn() && !login_isGuest())
	{
		return false;
	}

	// settings only available for mobile users and iframe Jitsi users
	if (!mobile_mightBeMobile() && !CONFIG["jitsi_embed_in_iframe"])
	{
		return false;
	}

	return true;
}


function video_drawSettings()
{
	if (mobile_mightBeMobile())
	{
		video_drawSettingsApp();
	}

	if (CONFIG["jitsi_embed_in_iframe"])
	{
		// these settings only work in the iframe version of Jitsi
		video_drawSettingsBrowser();
	}
}


function video_drawSettingsApp()
{
	echo "<h3>";
	echo LANG["settings_video_mobile_title"];
	echo "</h3>";

	echo "<p>";
	echo LANG["settings_video_mobile_explanation_1"];
	echo "<p>";
	echo LANG["settings_video_mobile_explanation_2"];

	if (mobile_isApple())
	{
		$icon = icon_apple();
		$downloadButton1 = LANG["settings_video_download_app_button_ios_1"];
		$downloadButton2 = LANG["settings_video_download_app_button_ios_2"];
		$appUrl = CONFIG["jitsi_app_url_ios"];
	}
	else if (mobile_isAndroid())
	{
		$icon = icon_googlePlay();
		$downloadButton1 = LANG["settings_video_download_app_button_android_1"];
		$downloadButton2 = LANG["settings_video_download_app_button_android_2"];
		$appUrl = CONFIG["jitsi_app_url_android"];
	}

	?>
		<div class='app-store-button-wrapper'>
			<a href="<?php echo $appUrl;?>" rel="noopener" target="_blank">
				<div class='app-store-button'>
					<div class='app-store-row'>
						<div class='app-store-icon'>
							<?php echo $icon; ?>
						</div>
						<div class='app-store-label'>
							<div class='app-store-button-line1'>
								<?php echo $downloadButton1; ?>
							</div>
							<div class='app-store-button-line2'>
								<?php echo $downloadButton2; ?>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
	<?php

	echo "<p>";
	echo LANG["settings_video_select_prompt"];
	echo "<p>";

	$checkedBrowser = "";
	$checkedApp = "";

	if (isset($_COOKIE["jitsi-use-app"]))
	{
		$checkedApp = "checked='checked'";
	}
	else
	{
		$checkedBrowser = "checked='checked'";
	}

	echo "<form>";

	echo "<input type='radio' id='settings-video-select-app' name='settings-video-select' value='app' $checkedApp />";
	echo "<label for='settings-video-select-app'>";
	echo "<div class='radio-title'>";
	echo LANG["settings_video_select_app"];
	echo "</div>";
	echo "<div class='radio-description'>";
	echo LANG["settings_video_select_app_explanation"];
	echo "<br>";
	echo LANG["settings_video_select_app_warning"];
	echo "</div>";
	echo "</label>";

	echo "<input type='radio' id='settings-video-select-browser' name='settings-video-select' value='browser' $checkedBrowser />";
	echo "<label for='settings-video-select-browser'>";
	echo "<div class='radio-title'>";
	echo LANG["settings_video_select_browser"];
	echo "</div>";
	echo "<div class='radio-description'>";
	echo LANG["settings_video_select_browser_explanation"];
	echo "</div>";
	echo "</label>";

	echo "</form>";

	?>
		<script type="text/javascript">

			$(document).ready(function()
			{
				if (getCookie("jitsi-use-app") != "")
				{
					$("#settings-video-browser").hide();
				}
			});

			$("#settings-video-select-app").click(function()
			{
				setCookie("jitsi-use-app", true);
				$("#settings-video-browser").hide();
			});

			$("#settings-video-select-browser").click(function()
			{
				deleteCookie("jitsi-use-app");
				$("#settings-video-browser").show();
			});

		</script>
	<?php
}


function video_drawSettingsBrowser()
{
	echo "<div id='settings-video-browser'>";

	echo "<h3>";
	echo LANG["settings_video_browser_title"];
	echo "</h3>";

	echo "<form>";

	// start full screen

	echo "<p>";
	$fullScreen = settings_getSetting("video-full-screen", CONFIG["jitsi_full_screen"]);
	template_drawSwitch(LANG["settings_video_fullscreen_label"], "settings-video-full-screen", $fullScreen);


	// pop-up duration

	$duration = settings_getSetting("video-text-popup-duration", CONFIG["jitsi_text_chat_popup_duration_seconds"]);
	echo "<p>";
	echo LANG["settings_video_popup_explanation"];
	echo "<label>";
	echo LANG["settings_video_popup_label"];
	echo "<span id='settings-video-popup-duration-feedback' class='slider-feedback'>";
	echo "</span>";
	echo "</label>";
	echo "<input type='range' id='settings-video-popup-duration' name='settings-video-popup-duration' min='0' max='60' step='5' value='$duration'>";

	echo "</form>";

	?>
		<script type="text/javascript">

			function updatePopupDurationSlider(saveValue)
			{
				var duration = $("#settings-video-popup-duration").val();

				if (duration == 0)
				{
					var text = '<?php echo LANG["settings_video_popup_value_never"]; ?>';
				}
				else
				{
					var text = '<?php echo LANG["settings_video_popup_value"]; ?>';
					text = text.replace("%d", duration);
				}

				$("#settings-video-popup-duration-feedback").html(text);

				if (saveValue)
				{
					$.post("<?php echo API_URL["setting-changed"]; ?>", {key: "video-text-popup-duration", value: duration});
				}
			}

			$(document).ready(function()
			{
				updatePopupDurationSlider(false);
			});

			$("#settings-video-popup-duration").on('input change', function()
			{
				updatePopupDurationSlider(true);
			});

			$("#settings-video-full-screen").on('change', function()
			{
				var fullScreen = $("#settings-video-full-screen").prop('checked');
				$.post("<?php echo API_URL["setting-changed"]; ?>", {key: "video-full-screen", value: fullScreen});
			});

		</script>
	<?php

	echo "</div>";
}


function video_drawFun()
{
	echo '<div id="video-bottom-controls">';

	if (CONFIG["video_fun_button"] == "fool")
	{
		video_drawFunFool();
	}
	else if (CONFIG["video_fun_button"] == "egg")
	{
		video_drawFunEgg();
	}

	echo '</div>';
}


function video_drawFunFool()
{
	?>
		<div id='video-fun-fool-button-start'>
			Don't click this
		</div>

		<div id='video-fun-fool-button-stop'>
			Don't click this again
		</div>

		<script type="text/javascript">

			$("#video-fun-fool-button-start").click(function()
			{
				$("#video-fun-fool-button-start").hide();
				$("#video-fun-fool-button-stop").show();
				$("#video-jitsi-frame").addClass("video-fun-fool");
			});

			$("#video-fun-fool-button-stop").click(function()
			{
				$("#video-fun-fool-button-start").show();
				$("#video-fun-fool-button-stop").hide();
				$("#video-jitsi-frame").removeClass("video-fun-fool");
			});

		</script>
	<?php
}


function video_drawFunEgg()
{
	?>
		<div id='video-fun-egg-button-start'>
			&#xf7fb
		</div>

		<script type="text/javascript">

			$("#video-fun-egg-button-start").click(function()
			{
				$("#video-fun-egg-button-start").hide();
				$("#video-jitsi-frame iframe").css('pointer-events', 'none');
				$("#video-jitsi-frame").click(function()
				{
					trail_stop();
					$("#video-jitsi-frame iframe").css('pointer-events', 'auto');
					$("#video-fun-egg-button-start").show();
				});
				trail_start();
			});

		</script>
	<?php

	video_drawJsEggCursorTrails();
}


function video_drawJsEggCursorTrails()
{
	?>
		<script type="text/javascript">

			const trail_numItems = 20;
			const trail_spacing = 0.8;

			var trail_running = false;

			var trail_dots = [];

			var trail_mouse =
			{
				x: 0,
				y: 0
			};

			var trail_Dot = function(colour)
			{
				this.x = 0;
				this.y = 0;
				this.node = (function()
				{
					var n = document.createElement("div");
					n.className = "cursor-trail-egg";
					n.style.pointerEvents = "none";
					n.style.color = "hsl(" + (360 * colour / trail_numItems) + ", 80%, 50%)";
					document.body.appendChild(n);
					return n;
				}());
			};

			trail_Dot.prototype.draw = function()
			{
				this.node.style.left = this.x + "px";
				this.node.style.top = this.y + "px";
			};

			for (var i = 0; i < trail_numItems; i++)
			{
				var d = new trail_Dot(i);
				trail_dots.push(d);
			}

			function trail_draw()
			{
				var x = trail_mouse.x + 5;
				var y = trail_mouse.y + 5;

				trail_dots.forEach(function(dot, index, dots)
				{
					var nextDot = trail_dots[index + 1] || trail_dots[0];

					dot.x = x;
					dot.y = y;
					dot.draw();
					x += (nextDot.x - dot.x) * trail_spacing;
					y += (nextDot.y - dot.y) * trail_spacing;
				});
			}

			addEventListener("mousemove", function(event)
			{
				trail_mouse.x = event.pageX;
				trail_mouse.y = event.pageY;
			});

			function trail_animate()
			{
				if (trail_running)
				{
					$(".cursor-trail-egg").show();
					trail_draw();
					requestAnimationFrame(trail_animate);
				}
				else
				{
					$(".cursor-trail-egg").hide();
				}
			}

			function trail_start()
			{
				trail_running = true;
				trail_animate();
			}

			function trail_stop()
			{
				trail_running = false;
			}

		</script>
	<?php
}
