<?php

require_once("module.inc.php");
require_once("rooms.inc.php");


function calendar_apiGetEvents()
{
	module_serverCheckForNewContent("calendar_getEventsContentFunction");
}


// contentFunction() takes
//	a string representing the last entry output to the user (interpretted however the function likes)
//	the time the server last sent new output (as received from the server previously)
// if there is new content, it returns an array:
//	"lastId" => the new last entry,
//	"html" => the rendered output
// if there is no new content, it returns false
function calendar_getEventsContentFunction($lastId, $lastTime)
{
	// have we sent the calendar before?
	if ($lastTime != "")
	{
		$lastUpdateDatetime = time_makeDateFromUtcString($lastTime);

		// first check if any new occupants of rooms (as that should be fairly quick)
//hack
		if (true)
		{
			// don't bother refreshing the actual events if we did if fairly recently

			time_addSeconds($lastUpdateDatetime, CONFIG["module_refresh_calendar_events"]);
			if ($lastUpdateDatetime > time_nowInUtc())
			{
				return false;
			}
		}
	}


	// ok, we can check if the events have changed...


	// determine user's timezone

	$timezone = "";
	$tzoffset = 0;

	if (isset($_GET["timezone"]))
	{
		$timezone = $_GET["timezone"];
	}

	if (isset($_GET["tzoffset"]))
	{
		$tzoffset = intval($_GET["tzoffset"]);
	}

	$userTimezone = time_makeTimezone($timezone, $tzoffset);


	// what room are we in (0 for all rooms)

	$roomId = 0;

	if (isset($_GET["room_id"]))
	{
		$roomId = intval($_GET["room_id"]);
	}


	// how far ahead to look?

	if (isset($_GET["days"]))
	{
		$daysAhead = intval($_GET["days"]);
	}
	else
	{
		if ($roomId == 0)
		{
			$daysAhead = CONFIG["calendar_days_ahead_default_main"];
		}
		else
		{
			$daysAhead = CONFIG["calendar_days_ahead_default_room"];
		}
	}

	// what date is that we're going up to?

	$untilUser = time_extractDate(time_nowInLocalTimezone($userTimezone));
	time_addDays($untilUser, $daysAhead);
	$untilUtc = time_convertToUtc($untilUser);

	// get events, split into sub-events, and sort

	$events = calendar_getEvents($roomId, $untilUtc);
	$events = calendar_convertToUserTimeAndSplitIntoDays($events, $untilUser, $userTimezone);
	$events = calendar_sortEvents($events);


	// are the events the same as last time?

	$checksum = md5(json_encode($events));

	if ($checksum == $lastId)
	{
		return false;
	}


	// got new events, so we can render them to html

	ob_start();

	if (count($events) == 0)
	{
		echo "<div class='calendar-no-events'>";

		if ($daysAhead == 1)
		{
			echo LANG["calendar_no_events_today"];
		}
		else if ($daysAhead == 2)
		{
			echo LANG["calendar_no_events_tomorrow"];
		}
		else
		{
			echo LANG["calendar_no_events_other"];
		}
		echo "</div>";
	}

	$lastDate = "";

	foreach ($events as $event)
	{
		$eventId = $event["eventId"];
		$eventRoomId = $event["roomId"];
		$startUtc = $event["startUtc"];
		$endUtc = $event["endUtc"];
		$eventTimezone = $event["timezone"];
		$title = $event["title"];
		$description = $event["description"];
		$modifiedBy = $event["modifiedBy"];


		$dateFormatted = time_formatDateShort($event["subEventDate"]);

		if ($event["today"])
		{
			$dateFormatted .= " (" . LANG["calendar_today"] . ")";
			$todayClass = "calendar-date-today";
		}
		else if ($event["tomorrow"])
		{
			$dateFormatted .= " (" . LANG["calendar_tomorrow"] . ")";
			$todayClass = "calendar-date-tomorrow";
		}
		else
		{
			$todayClass = "";
		}

		$formattedTitle = htmlspecialchars($title);

		$formattedDescription = htmlspecialchars($description);
		$formattedDescription = template_replaceEmoticons($formattedDescription);
		$formattedDescription = template_replaceUrls($formattedDescription);
		$formattedDescription = template_replaceControlCodes($formattedDescription);

		$formattedTime = time_formatTimeDuration($event["subEventStart"], $event["subEventEnd"]);

		$startUser = time_convertToLocal($startUtc, $userTimezone);
		$endUser = time_convertToLocal($endUtc, $userTimezone);

		$userTimes = time_formatDateTimeDuration($startUser, $endUser);
		$userTimes .= " " . LANG["calendar_your_timezone"] . " (" . time_formatTimezone(timezone_name_get($userTimezone)) . ")";

		$startEvent = time_convertToLocal($startUtc, $eventTimezone);
		$endEvent = time_convertToLocal($endUtc, $eventTimezone);

		$eventTimes = time_formatDateTimeDuration($startEvent, $endEvent);
		$eventTimes .= " " . LANG["calendar_event_timezone"] . " (" . time_formatTimezone(timezone_name_get($eventTimezone)) . ")";

		$utcTimes = time_formatDateTimeDuration($startUtc, $endUtc);
		$utcTimes .= " UTC";

		if ($event["now"])
		{
			$formattedTime .= " (" . LANG["calendar_now"] . ")";
			$nowClass = "calendar-entry-now";
		}
		else
		{
			$nowClass = "";
		}

		if ($event["minutesUntil"] > 0)
		{
			if ($event["minutesUntil"] == 1)
			{
				$soonString = LANG["calendar_minutes_until_1"];
			}
			else
			{
				$soonString = sprintf(LANG["calendar_minutes_until"], $event["minutesUntil"]);
			}

			$soonClass = "calendar-entry-soon";
		}
		else
		{
			$soonString = "";
			$soonClass = "";
		}


		$roomLink = "";
		$roomClass = "calendar-in-room";
		$roomUrl = rooms_getUrlFromRoomId($eventRoomId);

		if ($roomId != $eventRoomId)
		{
			$roomName = rooms_getTitleFromRoomId($eventRoomId);
			$roomLink = "<div class='calendar-room'><a href='$roomUrl'>$roomName</a></div>";
			$roomClass = "calendar-not-in-room";
		}

		$editUrl = $roomUrl . PUBLIC_URL["editevent"] . "/" . $eventId;

		if ($dateFormatted != $lastDate)
		{
			?>
				<div class="calendar-date <?php echo $todayClass; ?>">
					<span>
						<?php echo $dateFormatted; ?>
					</span>
				</div>
			<?php
		}

		?>

			<div class='calendar-entry <?php echo $nowClass . " " . $soonClass . " " . $roomClass; ?>' id='calendar-entry-id-<?php echo $eventId ;?>'>
				<div class="calendar-soon">
					<?php echo $soonString; ?>
				</div>
				<div class="calendar-time">
					<?php echo $formattedTime; ?>
					<span class="calendar-more-button-mobile" onclick="calendar_moreButtonClicked(event)">
						<span class="button-icon">
							<?php echo icon_info(); ?>
						</span>
					</span>
				</div>
				<div class="calendar-details">
					<div class="calendar-header">
						<div class="calendar-header-left">
							<div class="calendar-title">
								<?php echo $formattedTitle; ?>
							</div>
							<?php echo $roomLink; ?>
						</div>
						<div class="calendar-header-right">
							<div class="calendar-more-button" onclick="calendar_moreButtonClicked(event)">
								<span class="button-icon">
									<?php echo icon_info(); ?>
								</span>
								<?php echo LANG["calendar_more_info"]; ?>
							</div>
						</div>
					</div>
					<div class="calendar-header-end">
					</div>
					<div class="calendar-more-info">
						<div class="calendar-description">
							<?php echo $formattedDescription; ?>
						</div>
						<div class="calendar-timezones">
							<?php echo $userTimes; ?>
							<br>
							<?php echo $eventTimes; ?>
							<br>
							<?php echo $utcTimes; ?>
						</div>
						<?php
							if (login_isLoggedIn() && $modifiedBy !== null)
							{
								?>
									<div class='calendar-modified-by'>
										<?php echo sprintf(LANG["calendar_modified_by"], $modifiedBy); ?>
									</div>
									<div class='calendar-edit'>
										<a href='<?php echo $editUrl; ?>'>
											<span class="button-icon">
												<?php echo icon_edit(); ?>
											</span>
											<?php echo LANG["calendar_edit"]; ?>
										</a>
									</div>
								<?php
							}
						?>
					</div>
				</div>
				<div class="calendar-entry-end">
				</div>
			</div>
		<?php

		$lastDate = $dateFormatted;
	}

	$html = ob_get_clean();

	return [ "lastId" => $checksum, "html" => $html ];
}


function calendar_deleteOldEvents()
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "delete from events where end < utc_timestamp() and (repeat_type = 'none' or (repeat_until is not null and repeat_until < utc_timestamp()))");
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function calendar_deleteEventsInRoom($roomId)
{
	// We don't actually delete it as anyone subscribed to the ICS feed won't see the deletion - instead we mark it as cancelled.
	//
	// To ensure it eventually gets deleted from the database we set the repeat until date to 7 days time.
	//
	// If it's not a repeating event it will get deleted after its end time.
	// If it is a repeating event it will get deleted in 7 days time - that's enough time for any ICS feeds to update.
	//
	// (If it's a non-repeating event, and the ICS feed doesn't update before the event happens, that doesn't really matter as it
	// will be in the past anyway by the time the feed updates.)

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "update events set cancelled = true, repeat_until = date_add(utc_timestamp(), interval 7 day), last_modified = utc_timestamp(), num_edits = num_edits + 1 where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function calendar_getDbEvents($roomId, $untilUtc = false)
{
	$events = calendar_getDbEventsSingleRoom($roomId, $untilUtc);

	// any rooms included by this room?
	if (isset(CONFIG["room_included_by_room"]))
	{
		foreach (CONFIG["room_included_by_room"] as $child => $parent)
		{
			if ($roomId == $parent)
			{
				$moreEvents = calendar_getDbEventsSingleRoom($child, $untilUtc);
				$events = array_merge($events, $moreEvents);
			}
		}
	}

	return $events;
}


function calendar_getDbEventsSingleRoom($roomId, $untilUtc = false)
{
	calendar_deleteOldEvents();

	$events = array();

	try
	{
		$db = database_getConnection();

		if ($untilUtc === false)
		{
			// all events

			if ($roomId == 0)
			{
				// all rooms
				if (login_isAnAdmin())
				{
					// admins can see everything
					$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, title, description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events left join users on (events.modified_by = users.user_id) where (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp())))");
				}
				else if (login_isLoggedIn())
				{
					// show events from public rooms or private rooms of which this user is a member
					$userId = login_getUserId();
					$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, events.title, events.description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events join rooms using (room_id) left join users on (events.modified_by = users.user_id) left join room_members using(room_id) where (public = 1 or room_members.user_id = ?) and (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp())))");
					mysqli_stmt_bind_param($st, "i", $userId);
				}
				else
				{
					// only show events from public rooms
					$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, events.title, events.description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events join rooms using (room_id) left join users on (events.modified_by = users.user_id) where public = 1 and (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp())))");
				}
			}
			else
			{
				// just one room - check user can view it
				if (!login_hasPreviewAccessToRoom($roomId))
				{
					return array();
				}

				$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, title, description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events left join users on (events.modified_by = users.user_id) where (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp()))) and room_id = ?");
				mysqli_stmt_bind_param($st, "i", $roomId);
			}
		}
		else
		{
			$untilUtcString = time_convertToMysqlString($untilUtc);

			if ($roomId == 0)
			{
				// all rooms
				if (login_isAnAdmin())
				{
					// admins can see everything
					$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, title, description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events left join users on (events.modified_by = users.user_id) where start < ? and (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp())))");
					mysqli_stmt_bind_param($st, "s", $untilUtcString);
				}
				else if (login_isLoggedIn())
				{
					// show events from public rooms or private rooms of which this user is a member
					$userId = login_getUserId();
					$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, events.title, events.description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events join rooms using (room_id) left join users on (events.modified_by = users.user_id) left join room_members using(room_id) where (public = 1 or room_members.user_id = ?) and start < ? and (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp())))");
					mysqli_stmt_bind_param($st, "is", $userId, $untilUtcString);
				}
				else
				{
					// only show events from public rooms
					$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, events.title, events.description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events join rooms using (room_id) left join users on (events.modified_by = users.user_id) left join room_members using(room_id) where public = 1 and start < ? and (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp())))");
					mysqli_stmt_bind_param($st, "s", $untilUtcString);
				}
			}
			else
			{
				// just one room - check user can view it
				if (!login_hasPreviewAccessToRoom($roomId))
				{
					return array();
				}

				$st = mysqli_prepare($db, "select event_id, room_id, cancelled, start, end, title, description, timezone, repeat_type, repeat_until, num_edits, last_modified, uid, display_name from events left join users on (events.modified_by = users.user_id) where start < ? and (end > utc_timestamp() or (repeat_type != 'none' and (isnull(repeat_until) or repeat_until > utc_timestamp()))) and room_id = ?");
				mysqli_stmt_bind_param($st, "si", $untilUtcString, $roomId);
			}
		}

		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $eventId, $eventRoomId, $cancelled, $startUtc, $endUtc, $title, $description, $timezone, $repeatType, $repeatUntilUtc, $numEdits, $lastModifiedUtc, $uid, $displayName);

		while (mysqli_stmt_fetch($st))
		{
			$events[] =
			[
				"eventId"		=> $eventId,
				"roomId"		=> $eventRoomId,
				"cancelled"		=> $cancelled,
				"startUtc"		=> time_makeDateFromUtcString($startUtc),
				"endUtc"		=> time_makeDateFromUtcString($endUtc),
				"title"			=> $title,
				"description"		=> $description,
				"timezone"		=> timezone_open($timezone),
				"repeatType"		=> $repeatType,
				"repeatUntilUtc"	=> ($repeatUntilUtc === null) ? null : time_makeDateFromUtcString($repeatUntilUtc),
				"numEdits"		=> $numEdits,
				"lastModifiedUtc"	=> time_makeDateFromUtcString($lastModifiedUtc),
				"modifiedBy"		=> $displayName,
				"uid"			=> $uid
			];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $events;
}


function calendar_getEvent($eventId)
{
	$event = null;

	try
	{
		$db = database_getConnection();

		if (login_isAnAdmin())
		{
			// admins can see everything
			$st = mysqli_prepare($db, "select event_id, room_id, start, end, title, description, timezone, repeat_type, repeat_until from events where event_id = ?");
			mysqli_stmt_bind_param($st, "i", $eventId);
		}
		else if (login_isLoggedIn())
		{
			// find event from public rooms or private rooms of which this user is a member
			$userId = login_getUserId();
			$st = mysqli_prepare($db, "select event_id, room_id, start, end, events.title, events.description, timezone, repeat_type, repeat_until from events join rooms using (room_id) left join room_members using(room_id) where (public = 1 or room_members.user_id = ?) and event_id = ?");

			mysqli_stmt_bind_param($st, "ii", $userId, $eventId);
		}
		else
		{
			// only show events from public rooms
			$st = mysqli_prepare($db, "select event_id, room_id, start, end, events.title, events.description, timezone, repeat_type, repeat_until from events join rooms using (room_id) left join room_members using(room_id) where public = 1 and event_id = ?");
			mysqli_stmt_bind_param($st, "i", $eventId);
		}

		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $eventId, $eventRoomId, $startUtcString, $endUtcString, $title, $description, $timezoneString, $repeatType, $repeatUntilUtcString);

		if (mysqli_stmt_fetch($st))
		{
			$startUtc = time_makeDateFromUtcString($startUtcString);
			$endUtc = time_makeDateFromUtcString($endUtcString);
			$timezone = timezone_open($timezoneString);
			$repeatUntilUtc = ($repeatUntilUtcString === null) ? null : time_makeDateFromUtcString($repeatUntilUtcString);
			$repeatUntilLocal = ($repeatUntilUtc === null) ? null : time_convertToLocal($repeatUntilUtc, $timezone);

			$event =
			[
				"eventId"		=> $eventId,
				"roomId"		=> $eventRoomId,
				"startUtc"		=> $startUtc,
				"endUtc"		=> $endUtc,
				"startLocal"		=> time_convertToLocal($startUtc, $timezone),
				"endLocal"		=> time_convertToLocal($endUtc, $timezone),
				"title"			=> $title,
				"description"		=> $description,
				"timezone"		=> $timezone,
				"repeatType"		=> $repeatType,
				"repeatUntilUtc"	=> $repeatUntilUtc,
				"repeatUntilLocal"	=> $repeatUntilLocal
			];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $event;
}


function calendar_addEvent($roomId, $title, $description, $startDt, $endDt, $timezone, $repeatType, $repeatUntilDt)
{
	// only logged in users can add events
	if (!login_isLoggedIn())
	{
		return "";
	}

	if (!login_hasFullAccessToRoom($roomId))
	{
		return "";
	}

	$start = time_convertToMysqlString(time_convertToUtc($startDt));
	$end = time_convertToMysqlString(time_convertToUtc($endDt));

	$repeatUntil = null;

	if ($repeatUntilDt !== null)
	{
		$repeatUntil = time_convertToMysqlString(time_convertToUtc($repeatUntilDt));
	}

	$modifiedBy = login_getUserId();

	$uid = template_generateUid();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "insert into events (room_id, start, end, title, description, timezone, repeat_type, repeat_until, last_modified, modified_by, uid) values (?, ?, ?, ?, ?, ?, ?, ?, utc_timestamp(), ?, ?)");
		mysqli_stmt_bind_param($st, "isssssssis", $roomId, $start, $end, $title, $description, $timezone, $repeatType, $repeatUntil, $modifiedBy, $uid);
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


function calendar_editEvent($eventId, $title, $description, $startDt, $endDt, $timezone, $repeatType, $repeatUntilDt)
{
	// only logged in users can edit events
	if (!login_isLoggedIn())
	{
		return "";
	}

	// look up event to check this user does actually have access to it
	if (calendar_getEvent($eventId) === null)
	{
		return "";
	}

	$start = time_convertToMysqlString(time_convertToUtc($startDt));
	$end = time_convertToMysqlString(time_convertToUtc($endDt));

	$repeatUntil = null;

	if ($repeatUntilDt !== null)
	{
		$repeatUntil = time_convertToMysqlString(time_convertToUtc($repeatUntilDt));
	}

	$modifiedBy = login_getUserId();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "update events set start = ?, end = ?, title = ?, description = ?, timezone = ?, repeat_type = ?, repeat_until = ?, last_modified = utc_timestamp(), modified_by = ?, num_edits = num_edits + 1 where event_id = ?");
		mysqli_stmt_bind_param($st, "sssssssii", $start, $end, $title, $description, $timezone, $repeatType, $repeatUntil, $modifiedBy, $eventId);
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


function calendar_findNextMonthlyRepeatByWeekday($localStart, $repeatType, $afterLocalDateTime)
{
	$localTimezone = date_timezone_get($localStart);
	$dayOfWeek = date_format($localStart, "D");
	$localStartTime = date_format($localStart, "H:i:s");

	$modifier = "";

	if ($repeatType == "month_week_1")
	{
		$ordinal = "first";
	}
	else if ($repeatType == "month_week_2")
	{
		$ordinal = "second";
	}
	else if ($repeatType == "month_week_3")
	{
		$ordinal = "third";
	}
	else if ($repeatType == "month_week_4")
	{
		$ordinal = "fourth";
	}
	else if ($repeatType == "month_week_last")
	{
		$ordinal = "last";
	}
	else if ($repeatType == "month_week_penultimate")
	{
		$ordinal = "last";
		$modifier = "-1 week";
	}

	$newLocalStartDate = date_create(date_format($afterLocalDateTime, "c") . " $ordinal $dayOfWeek of this month $modifier", $localTimezone);
	$newLocalStartDateTime = date_create(date_format($newLocalStartDate, "Y-m-d ") . $localStartTime, $localTimezone);

	if ($newLocalStartDateTime <= $afterLocalDateTime)
	{
		$newLocalStartDate = date_create(date_format($afterLocalDateTime, "c") . " $ordinal $dayOfWeek of next month $modifier", $localTimezone);
		$newLocalStartDateTime = date_create(date_format($newLocalStartDate, "Y-m-d ") . $localStartTime, $localTimezone);
	}

	return $newLocalStartDateTime;
}


function calendar_getEvents($roomId, $untilUtc)
{
	$events = calendar_getDbEvents($roomId, $untilUtc);

	$returnedEvents = array();

	foreach ($events as $event)
	{
		if (!$event["cancelled"])
		{
			$repeatType = $event["repeatType"];

			if ($repeatType == "none")
			{
				$returnedEvents[] = $event;
			}
			else
			{
				// repeating event

				// find when the first repeat is

				$localStart = time_convertToLocal($event["startUtc"], $event["timezone"]);
				$localEnd = time_convertToLocal($event["endUtc"], $event["timezone"]);
				$localNow = time_nowInLocalTimezone($event["timezone"]);

				$timeSinceEnd = date_diff($localEnd, time_nowInUtc());

				if ($timeSinceEnd->invert == 0)
				{
					// move end date into the current time window

					if ($repeatType == "day")
					{
						time_addDays($localStart, $timeSinceEnd->days + 1);
						time_addDays($localEnd, $timeSinceEnd->days + 1);
					}
					else if ($repeatType == "week")
					{
						$weeksSinceEnd = intdiv($timeSinceEnd->days, 7);
						time_addWeeks($localStart, $weeksSinceEnd + 1);
						time_addWeeks($localEnd, $weeksSinceEnd + 1);
					}
					else if ($repeatType == "month_date")
					{
						$monthsSinceEnd = $timeSinceEnd->y * 12 + $timeSinceEnd->m;
						$duration = date_diff($localStart, $localEnd);
						$localStart = time_getSameDateAfterAtLeastXMonths($localStart, $monthsSinceEnd + 1);
						$localEnd = clone $localStart;
						date_add($localEnd, $duration);
					}
					else
					{
						// month_week_1, month_week_2, month_week_3, month_week_4, month_week_penultimate, month_week_last
						$duration = date_diff($localStart, $localEnd);
						$localThen = date_sub($localNow, $duration);
						$localStart = calendar_findNextMonthlyRepeatByWeekday($localStart, $repeatType, $localThen);
						$localEnd = clone $localStart;
						date_add($localEnd, $duration);
					}

					$event["startUtc"] = time_convertToUtc($localStart);
					$event["endUtc"] = time_convertToUtc($localEnd);
				}

				// if it's still within our time window (and before the "repeat until" date), add it to the list
				if ($event["startUtc"] < $untilUtc && ($event["repeatUntilUtc"] === null || $event["startUtc"] < $event["repeatUntilUtc"]))
				{
					$returnedEvents[] = $event;

					// now find any repeats that are still within our time window
					// max 400 repeats to stop infinite loops if I get the code wrong!
					// should be sufficient as events can't repeat more than once a day, and we don't look more than a year ahead
					for ($x = 0; $x < 400; $x++)
					{
						// find next repeat

						$localStart = time_convertToLocal($event["startUtc"], $event["timezone"]);
						$localEnd = time_convertToLocal($event["endUtc"], $event["timezone"]);

						if ($repeatType == "day")
						{
							time_addDays($localStart, 1);
							time_addDays($localEnd, 1);
						}
						else if ($repeatType == "week")
						{
							time_addWeeks($localStart, 1);
							time_addWeeks($localEnd, 1);
						}
						else if ($repeatType == "month_date")
						{
							$duration = date_diff($localStart, $localEnd);
							$localStart = time_getSameDateAfterAtLeastXMonths($localStart, 1);
							$localEnd = clone $localStart;
							date_add($localEnd, $duration);
						}
						else
						{
							// month_week_1, month_week_2, month_week_3, month_week_4,  month_week_penultimate, month_week_last
							$duration = date_diff($localStart, $localEnd);
							$localStart = calendar_findNextMonthlyRepeatByWeekday($localStart, $repeatType, $localStart);
							$localEnd = clone $localStart;
							date_add($localEnd, $duration);
						}

						$event["startUtc"] = time_convertToUtc($localStart);
						$event["endUtc"] = time_convertToUtc($localEnd);

						// is it beyond our time window (or after the "repeat until" date)?
						if ($event["startUtc"] > $untilUtc || ($event["repeatUntilUtc"] !== null && $event["startUtc"] >= $event["repeatUntilUtc"]))
						{
							break;
						}

						// add it to the list
						$returnedEvents[] = $event;
					}
				}
			}
		}
	}

	return $returnedEvents;
}


function calendar_sortEvents($events)
{
	// sort into date order
	usort($events, function($a, $b)
	{
		// sort by start date/time, unless they start at the same time, in which case choose based on end time
		if ($a["subEventStart"] == $b["subEventStart"])
		{
			return $a["subEventEnd"] > $b["subEventEnd"];
		}
		else
		{
			return $a["subEventStart"] > $b["subEventStart"];
		}
	});

	return $events;
}


function calendar_convertToUserTimeAndSplitIntoDays($events, $untilUser, $userTimezone)
{
	$nowUser = time_nowInLocalTimezone($userTimezone);
	$today = time_extractDate($nowUser);
	$tomorrow = clone $today;
	time_addDays($tomorrow, 1);

	$returnedEvents = array();

	foreach ($events as $event)
	{
		$startUtc = $event["startUtc"];
		$endUtc = $event["endUtc"];

		$startUser = time_convertToLocal($startUtc, $userTimezone);
		$endUser = time_convertToLocal($endUtc, $userTimezone);

		$startDate = time_extractDate($startUser);
		$endDate = time_extractDate($endUser);

		$thisDate = clone $startDate;

		// second clause checks that it doesn't end at midnight at the start of that day
		while ($thisDate <= $endDate && $endUser != $thisDate)
		{
			$event["subEventDate"] = clone $thisDate;

			// format start time or empty for "from midnight"
			// second clause checks that it doesn't start at midnight at the start of that day
			if ($thisDate == $startDate && $startUser != $thisDate)
			{
				$event["subEventStart"] = $startUser;
			}
			else
			{
				$midnightYesterday = clone $thisDate;
				$event["subEventStart"] = $midnightYesterday;
			}

			// format end time or empty for "rest of day"
			if ($thisDate == $endDate)
			{
				$event["subEventEnd"] = $endUser;
			}
			else
			{
				$midnightTonight = clone $thisDate;
				time_addDays($midnightTonight, 1);
				$event["subEventEnd"] = $midnightTonight;
			}

			// set today / tomorrow flags
			if ($thisDate == $today)
			{
				$event["today"] = true;
				$event["tomorrow"] = false;
			}
			else if ($thisDate == $tomorrow)
			{
				$event["today"] = false;
				$event["tomorrow"] = true;
			}
			else
			{
				$event["today"] = false;
				$event["tomorrow"] = false;
			}

			// is it happening right now?
			if ($nowUser >= $event["subEventStart"] && $nowUser <= $event["subEventEnd"])
			{
				$event["now"] = true;
			}
			else
			{
				$event["now"] = false;
			}

			// is it happening soon
			$minutesUntil = time_minutesUntil($event["subEventStart"]);
			if ($minutesUntil > 0 && $minutesUntil <= CONFIG["calendar_alert_minutes"])
			{
				$event["minutesUntil"] = $minutesUntil;
			}
			else
			{
				$event["minutesUntil"] = 0;
			}


			time_addDays($thisDate, 1);

			// only add this sub-event if it's in the future and before our cut-off point
			if ($event["subEventEnd"] > $nowUser && $event["subEventStart"] < $untilUser)
			{
				$returnedEvents[] = $event;
			}
		}
	}

	return $returnedEvents;
}


function calendar_drawCalendarWindow($roomId, $isFullScreen = false)
{
        $refreshNowFunctionName = module_clientRefreshNowJsFunctionName("calendar");

	if ($roomId == 0)
	{
		$cookieName = "calendar_days_ahead_main";
		$defaultDaysAhead = CONFIG["calendar_days_ahead_default_main"];
	}
	else
	{
		$cookieName = "calendar_days_ahead_room";
		$defaultDaysAhead = CONFIG["calendar_days_ahead_default_room"];
	}

	if ($isFullScreen)
	{
		$cookieName .= "_fullscreen";
		$defaultDaysAhead = CONFIG["calendar_days_ahead_default_fullscreen"];
	}

	if (isset($_COOKIE[$cookieName]))
	{
		$daysSelected = $_COOKIE[$cookieName];
	}
	else
	{
		$daysSelected = $defaultDaysAhead;
	}

	?>
		<div class="calendar-days-ahead">
	<?php
			echo LANG["calendar_show_days"] . ": ";

			foreach (CONFIG["calendar_days_ahead"] as $daysAhead)
			{
				if ($daysAhead == $daysSelected)
				{
					$selected = "calendar-days-ahead-button-selected";
				}
				else
				{
					$selected = "";
				}

				echo "<span class='calendar-days-ahead-button $selected' onclick='calendar_daysAheadButtonClicked(event)'>";
				echo $daysAhead;
				echo "</span>";
			}
	?>
		</div>

		<div id="calendar" class="module-background-update">
		</div>

		<div class="calendar-buttons-wrapper">
	<?php
			if ($roomId == 0)
			{
				$subscribeUrl = PUBLIC_URL['calendar_sub'];
			}
			else
			{
				$subscribeUrl = rooms_getUrlFromRoomId($roomId) . PUBLIC_URL["calendar_sub"];
			}

			if ($roomId == 0 || rooms_isRoomPublic($roomId))
			{
	?>
				<div class="calendar-subscribe-wrapper">
					<a href="<?php echo $subscribeUrl; ?>" class="link-looking-like-a-button">
						<span class="button-icon">
							<?php echo icon_subscribe(); ?>
						</span>
						<?php echo LANG["calendar_subscribe_button"]; ?>
					</a>
				</div>
	<?php
			}

			if ($roomId != 0 && !rooms_isRoomPublic($roomId))
			{
	?>
				<div class="calendar-no-subscribe-wrapper">
					<?php echo LANG["calendar_no_subscribe_private"]; ?>
				</div>
	<?php
			}


			if (login_isLoggedIn())
			{
				if ($roomId == 0)
				{
					$buttonLabel = LANG["calendar_add_event_button"];
					$url = PUBLIC_URL['addevent'];
				}
				else
				{
					$buttonLabel = LANG["calendar_add_event_room_button"];
					$url = rooms_getUrlFromRoomId($roomId) . PUBLIC_URL["addevent"];
				}
	?>
				<div class="add-event-wrapper">
					<a href="<?php echo $url; ?>" class="link-looking-like-a-button">
						<span class="button-icon">
							<?php echo icon_plusInCircle(); ?>
						</span>
						<?php echo $buttonLabel; ?>
					</a>
				</div>
	<?php
			}
	?>
		</div>

		<div class='show-if-no-js'>
			<div id='calendar-needs-js'>
				<?php echo LANG['calendar_no_javascript']; ?>
			</div>
		</div>

		<script type="text/javascript">

			const cookieName = "<?php echo $cookieName; ?>";
			var calendar_days = <?php echo $daysSelected; ?>;
			var calendar_openDiv = "";

			function calendar_daysAheadButtonClicked(event)
			{
				var button = $(event.target);
				$(".calendar-days-ahead-button-selected").removeClass("calendar-days-ahead-button-selected");
				button.addClass("calendar-days-ahead-button-selected");
				calendar_days = button.html();
				setCookie(cookieName, calendar_days);
				calendar_openDiv = "";
				<?php echo $refreshNowFunctionName; ?>(true);
			}

			function calendar_moreButtonClicked(event)
			{
				var button = $(event.target);
				var entry = button.closest(".calendar-entry");

				if (entry.hasClass("calendar-entry-more-info-open"))
				{
					entry.removeClass("calendar-entry-more-info-open");
					calendar_openDiv = "";
				}
				else
				{
					// hide any other sections that might be open
					$(".calendar-entry").removeClass("calendar-entry-more-info-open");
					entry.addClass("calendar-entry-more-info-open");
					calendar_openDiv = entry.attr("id");
				}
			}

			function calendar_addParamsToRefreshUrl(url)
			{
				url = time_addUserTimezonesToUrl(url);
				url += "&days=" + calendar_days;
				return url;
			}

			function calendar_postProcessContent(div)
			{
				if (calendar_openDiv != "")
				{
					$("#" + calendar_openDiv).addClass("calendar-entry-more-info-open");
				}
			}

		</script>
	<?php

	time_javascriptUserTimezones();

	module_clientStartBackgroundRefresh(API_URL["calendar-get-events"] . "?room_id=$roomId", "calendar_addParamsToRefreshUrl", "calendar_postProcessContent", "calendar", "module_refresh_calendar", false);
}
