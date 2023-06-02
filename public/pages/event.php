<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("calendar.inc.php");
require_once("rooms.inc.php");


function drawRoomLinks($categoryTitle, $categoryNumberOrFalseForDormantRooms, $public)
{
	if ($categoryNumberOrFalseForDormantRooms === false)
	{
		echo "<div class='add-event-category-dormant'>";
	}
	else
	{
		echo "<div class='add-event-category-$categoryNumberOrFalseForDormantRooms'>";
	}

	echo "<div class='add-event-category-header'>";
	echo $categoryTitle;
	echo "</div>";

	echo "<div class='add-event-category-body'>";

	$rooms = rooms_getRoomsInCategory($categoryNumberOrFalseForDormantRooms, $public);

	foreach ($rooms as $room)
	{
		$title = $room["title"];
		$url = PUBLIC_URL["room"] . $room["url"] . PUBLIC_URL["addevent"];
		echo "<a href='$url'>";
		echo "<div class='add-event-room'>";
		echo $title;
		echo "</div>";
		echo "</a>";
	}

	echo "</div>";

	echo "<div class='add-event-category-footer'>";
	echo "</div>";

	echo "</div>";
}


if (!isset($_GET["room"]))
{
	// first need to choose a room

	template_drawHeader(LANG["page_title_add_event"], null, "");
	template_denyIfNotLoggedIn();

	echo "<h2>";
	echo LANG["add_event_title"];
	echo "</h2>";
	echo "<p>";
	echo LANG["add_event_explanation"];
	echo " ";
	echo LANG["add_event_choose_room"];

	for ($x = 1; $x <= CONFIG["rooms_number_of_categories"]; $x++)
	{
		$categoryNumber = CONFIG["rooms_category_list_order"][$x];
		$categoryTitle = LANG["rooms_category_title"][$categoryNumber];
		drawRoomLinks($categoryTitle, $categoryNumber, true);
	}

	$categoryTitle = LANG["rooms_category_title_dormant"];
	drawRoomLinks($categoryTitle, false, true);

	$categoryTitle = LANG["rooms_category_title_private"];
	drawRoomLinks($categoryTitle, false, false);

	template_drawFooter();
	exit(0);
}


$roomId = rooms_getRoomIdFromUrl($_GET["room"]);

if ($roomId === false || !login_hasPreviewAccessToRoom($roomId))
{
	template_drawHeader(LANG["page_title_unknown_room"], null, "");
	echo LANG["room_no_room_found"];
	template_drawFooter();
	exit(0);
}

$event = null;

if (isset($_GET["event"]))
{
	$eventId = intval($_GET["event"]);
	$event = calendar_getEvent($eventId);

	if ($event === null)
	{
		rooms_drawHeaderForRoom($roomId, LANG["page_title_unknown_event"], "");
		echo LANG["edit_event_no_event_found"];
		template_drawFooter();
		exit(0);
	}
}

if ($event === null)
{
	rooms_drawHeaderForRoom($roomId, LANG["page_title_add_event"], "");
}
else
{
	rooms_drawHeaderForRoom($roomId, LANG["page_title_edit_event"], "");
}

template_denyIfNotLoggedIn();


// initialise everything to start state

$error = "";

$title = "";
$description = "";
$allDay = false;
$start = "";
$startHour = 0;
$startMinutes = 0;
$end = "";
$endHour = 0;
$endMinutes = 0;
$timezone = "";
$timezoneLabel = "";
$repeatType = "none";
$repeatUntil = false;
$repeatUntilDate = "";

$checkedRepeatTypeNone = "";
$checkedRepeatTypeDaily = "";
$checkedRepeatTypeWeekly = "";
$checkedRepeatTypeMonthlyDate = "";
$checkedRepeatTypeMonthlyWeek1 = "";
$checkedRepeatTypeMonthlyWeek2 = "";
$checkedRepeatTypeMonthlyWeek3 = "";
$checkedRepeatTypeMonthlyWeek4 = "";
$checkedRepeatTypeMonthlyWeekPenultimate = "";
$checkedRepeatTypeMonthlyWeekLast = "";


// if form has been submitted, use submitted values
if (isset($_POST["submit-event"]))
{
	if (isset($_POST["title"]))
	{
		$title = trim($_POST["title"]);
	}

	if (isset($_POST["description"]))
	{
		$description = trim($_POST["description"]);
	}

	if (isset($_POST["all-day"]))
	{
		$allDay = true;
	}

	if (isset($_POST["start"]))
	{
		$start = time_validateDateInput($_POST["start"]);
	}

	if (isset($_POST["start-hour"]))
	{
		$startHour = intval($_POST["start-hour"]);
	}

	if (isset($_POST["start-minutes"]))
	{
		$startMinutes = intval($_POST["start-minutes"]);
	}

	if (isset($_POST["end"]))
	{
		$end = time_validateDateInput($_POST["end"]);
	}

	if (isset($_POST["end-hour"]))
	{
		$endHour = intval($_POST["end-hour"]);
	}

	if (isset($_POST["end-minutes"]))
	{
		$endMinutes = intval($_POST["end-minutes"]);
	}

	if (isset($_POST["event-timezone"]))
	{
		$timezone = time_validateTimezone($_POST["event-timezone"]);
	}

	if (isset($_POST["add-event-repeat-type"]))
	{
		$repeatType = $_POST["add-event-repeat-type"];
	}

	if (isset($_POST["repeat-until"]))
	{
		$repeatUntil = true;

		if (isset($_POST["repeatuntildate"]))
		{
			$repeatUntilDate = time_validateDateInput($_POST["repeatuntildate"]);
		}
	}
}
else if ($event !== null)
{
	// if we're editing, then populate everything with saved values

	$title = $event["title"];
	$description = $event["description"];

	$startDt = $event["startLocal"];
	$startHour = time_getHours($startDt);
	$startMinutes = time_getMinutes($startDt);

	$endDt = $event["endLocal"];
	$endHour = time_getHours($endDt);
	$endMinutes = time_getMinutes($endDt);

	if ($startHour == 0 && $startMinutes == 0 && $endHour == 0 && $endMinutes == 0)
	{
		$allDay = true;

		// all day is until midnight at the start of the following day,
		// so subtract a day to make it what the user expects
		time_addDays($endDt, -1);
	}

	$start = time_formatIsoDate($startDt);
	$end = time_formatIsoDate($endDt);

	$timezone = timezone_name_get($event["timezone"]);

	$repeatType = $event["repeatType"];

	if ($event["repeatUntilLocal"] !== null)
	{
		$repeatUntil = true;

		$repeatUntilDt = $event["repeatUntilLocal"];

		// repeat until is midnight at the start of the following day,
		// so subtract a day to make it what the user expects
		time_addDays($repeatUntilDt, -1);
		$repeatUntilDate = time_formatIsoDate($repeatUntilDt);
	}
}



// sort out the UI

if ($timezone == "")
{
	$timezoneLabel = LANG["event_form_no_timezone"];
}
else
{
	$timezoneLabel = time_formatTimezone($timezone);
}

if ($repeatType == "none")
{
	$checkedRepeatTypeNone = "checked='checked'";
}
else if ($repeatType == "day")
{
	$checkedRepeatTypeDaily = "checked='checked'";
}
else if ($repeatType == "week")
{
	$checkedRepeatTypeWeekly = "checked='checked'";
}
else if ($repeatType == "month_date")
{
	$checkedRepeatTypeMonthlyDate = "checked='checked'";
}
else if ($repeatType == "month_week_1")
{
	$checkedRepeatTypeMonthlyWeek1 = "checked='checked'";
}
else if ($repeatType == "month_week_2")
{
	$checkedRepeatTypeMonthlyWeek2 = "checked='checked'";
}
else if ($repeatType == "month_week_3")
{
	$checkedRepeatTypeMonthlyWeek3 = "checked='checked'";
}
else if ($repeatType == "month_week_4")
{
	$checkedRepeatTypeMonthlyWeek4 = "checked='checked'";
}
else if ($repeatType == "month_week_penultimate")
{
	$checkedRepeatTypeMonthlyWeekPenultimate = "checked='checked'";
}
else if ($repeatType == "month_week_last")
{
	$checkedRepeatTypeMonthlyWeekLast = "checked='checked'";
}
else
{
	// invalid input, force to none
	$repeatType = "none";
	$checkedRepeatTypeNone = "checked='checked'";
}



// should be validated by JavaScript, but play safe

if ($error == "" && isset($_POST["submit-event"]))
{
	if ($title == "")
	{
		$error = LANG["event_error_no_title"];
	}

	if ($start == "")
	{
		$error = LANG["event_error_no_start"];
	}

	if ($end == "")
	{
		$error = LANG["event_error_no_end"];
	}

	if (!$allDay)
	{
		if ($startHour < 0 || $startHour > 23 || $startMinutes < 0 || $startMinutes > 59)
		{
			$error = LANG["event_error_no_start_time"];
		}
		if ($endHour < 0 || $endHour > 23 || $endMinutes < 0 || $endMinutes > 59)
		{
			$error = LANG["event_error_no_end_time"];
		}
	}

	if ($timezone == "")
	{
		$error = LANG["event_error_no_timezone"];
	}

	if ($repeatType != "none" && $repeatUntil && $repeatUntilDate == "")
	{
		$error = LANG["event_error_no_repeat_until"];
	}

	if ($error == "")
	{
		if ($allDay)
		{
			// midnight at the beginning of the day
			$startString = sprintf("%s 00:00:00", $start);
			$startDt = time_makeDateFromLocalString($startString, $timezone);

			// midnight at the beginning of the day
			$endString = sprintf("%s 00:00:00", $end);
			$endDt = time_makeDateFromLocalString($endString, $timezone);
			// add a day to make it midnight at the end of the day
			time_addDays($endDt, 1);
		}
		else
		{
			$startString = sprintf("%s %02d:%02d:00", $start, $startHour, $startMinutes);
			$startDt = time_makeDateFromLocalString($startString, $timezone);

			$endString = sprintf("%s %02d:%02d:00", $end, $endHour, $endMinutes);
			$endDt = time_makeDateFromLocalString($endString, $timezone);
		}

		if ($repeatType != "none" && $repeatUntil)
		{
			// midnight at the beginning of the day
			$untilString = sprintf("%s 00:00:00", $repeatUntilDate);
			$repeatUntilDt = time_makeDateFromLocalString($untilString, $timezone);
			// add a day to make it midnight at the end of the day
			time_addDays($repeatUntilDt, 1);
		}
		else
		{
			$repeatUntilDt = null;
		}

		if ($event === null)
		{
			$error = calendar_addEvent($roomId, $title, $description, $startDt, $endDt, $timezone, $repeatType, $repeatUntilDt);
			$successMessage = LANG["event_add_success"];
		}
		else
		{
			$error = calendar_editEvent($eventId, $title, $description, $startDt, $endDt, $timezone, $repeatType, $repeatUntilDt);
			$successMessage = LANG["event_edit_success"];
		}

		if ($error == "")
		{
			echo $successMessage;
			echo "<p>";
			echo "<a href='" . rooms_getUrlFromRoomId($roomId) . "'>";
			echo LANG["event_success_back_to_room"];
			echo "</a>";
			echo "<p>";
			echo "<a href='" . PUBLIC_URL["index"] . "'>";
			echo LANG["link_back_to_home"];
			echo "</a>";
			template_drawFooter();
			exit(0);
		}
	}
}


time_javascriptUserTimezones();
time_javascriptDateTimeSelectors();


if ($event === null)
{
	$heading = LANG["add_event_title"];
	$submitLabel = LANG["event_form_add_submit_label"];
}
else
{
	$heading = LANG["edit_event_title"];
	$submitLabel = LANG["event_form_edit_submit_label"];
}

?>

	<div id='add-event-form'>
		<h2>
			<?php echo $heading; ?>
		</h2>
<?php
		if ($error != "")
		{
			echo "<div class='form-error'>";
			echo $error;
			echo "</div>";
		}

		if ($repeatType != "none")
		{
			echo "<div class='form-error'>";
			echo LANG["edit_event_warning"];
			echo "</div>";
		}
?>
		<form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
			<label>
				<?php echo LANG["event_form_title_label"] ?>
				<input type="text" name="title" value="<?php echo htmlspecialchars($title);?>" maxlength=100>
			</label>

			<label>
				<?php echo LANG["event_form_description_label"] ?>
				<textarea name="description" rows=4 maxlength=1000 placeholder="<?php echo LANG["event_form_description_optional"] ?>"><?php echo htmlspecialchars($description);?></textarea>
			</label>

			<?php template_drawSwitch(LANG["event_form_all_day_label"], "all-day", $allDay); ?>

			<label class='label-same-line'>
				<?php echo LANG["event_form_start_date_label"] ?>
				<?php time_drawDateSelector("start", $start); ?>
			</label>

			<span class="add-event-times">
				<?php time_drawTimeOfDaySelector("start", LANG["event_form_start_time"], $startHour, $startMinutes); ?>
			</span>

			<div class="add-event-duration">
				<label class='radio-float'>
					<?php echo LANG["event_form_duration_label"] ?>
				</label>
				<?php
					foreach (CONFIG["add_event_durations_minutes"] as $minutes)
					{
						echo "<input type='radio' id='duration-$minutes' name='duration' value='$minutes' />";
						echo "<label for='duration-$minutes' class='radio-float'>";
						echo "<div class='radio-title'>";
						if ($minutes < 60)
						{
							echo sprintf(LANG["event_form_duration_minutes"], $minutes);
						}
						else if ($minutes == 60)
						{
							echo LANG["event_form_duration_hour"];
						}
						else
						{
							echo sprintf(LANG["event_form_duration_hours"], template_formatSimpleNumber($minutes / 60));
						}
						echo "</div>";
						echo "</label>";
					}
				?>
				<div class='radio-float-end'>
				</div>
			</div>

			<label class='label-same-line'>
				<span id='add-event-end-date-all-day'>
					<?php echo LANG["event_form_end_date_label"] ?>
				</span>
				<span id='add-event-end-date-not-all-day'>
					<?php echo LANG["event_form_or_end_date_label"] ?>
				</span>
				<?php time_drawDateSelector("end", $end); ?>
			</label>

			<span class="add-event-times">
				<?php time_drawTimeOfDaySelector("end", LANG["event_form_end_time"], $endHour, $endMinutes); ?>
			</span>

			<label class='add-event-timezone-label'>
				<?php echo LANG["event_form_timezone_label"] ?>
				<span id='add-event-timezone'>
					<?php echo $timezoneLabel; ?>
				</span>
				<input type="hidden" name="event-timezone" value="<?php echo $timezone;?>">
			</label>

			<div id='add-event-timezone-list'>
				<?php time_drawTimeZoneRadioBoxes($timezone); ?>
			</div>

			<div class="add-event-repeat-section">
				<label>
					<?php echo LANG["event_form_repeat_label"]; ?>
				</label>

				<input type='radio' id='add-event-repeat-none' name='add-event-repeat-type' value='none' <?php echo $checkedRepeatTypeNone; ?>/>
				<label for='add-event-repeat-none'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_none"]; ?>
					</div>
				</label>

				<input type='radio' id='add-event-repeat-daily' name='add-event-repeat-type' value='day' <?php echo $checkedRepeatTypeDaily; ?>/>
				<label for='add-event-repeat-daily'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_daily"]; ?>
					</div>
				</label>

				<input type='radio' id='add-event-repeat-weekly' name='add-event-repeat-type' value='week' <?php echo $checkedRepeatTypeWeekly; ?>/>
				<label for='add-event-repeat-weekly'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_weekly"]; ?>
					</div>
				</label>

				<input type='radio' id='add-event-repeat-monthly-date' name='add-event-repeat-type' value='month_date' <?php echo $checkedRepeatTypeMonthlyDate; ?>/>
				<label for='add-event-repeat-monthly-date'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_date"]; ?>
					</div>
				</label>

				<input type='radio' class='add-event-repeat-monthly-week' id='add-event-repeat-monthly-week-1' name='add-event-repeat-type' value='month_week_1' <?php echo $checkedRepeatTypeMonthlyWeek1; ?>/>
				<label for='add-event-repeat-monthly-week-1'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_week_1"]; ?>
					</div>
				</label>

				<input type='radio' class='add-event-repeat-monthly-week' id='add-event-repeat-monthly-week-2' name='add-event-repeat-type' value='month_week_2' <?php echo $checkedRepeatTypeMonthlyWeek2; ?>/>
				<label for='add-event-repeat-monthly-week-2'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_week_2"]; ?>
					</div>
				</label>

				<input type='radio' class='add-event-repeat-monthly-week' id='add-event-repeat-monthly-week-3' name='add-event-repeat-type' value='month_week_3' <?php echo $checkedRepeatTypeMonthlyWeek3; ?>/>
				<label for='add-event-repeat-monthly-week-3'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_week_3"]; ?>
					</div>
				</label>

				<input type='radio' class='add-event-repeat-monthly-week' id='add-event-repeat-monthly-week-4' name='add-event-repeat-type' value='month_week_4' <?php echo $checkedRepeatTypeMonthlyWeek4; ?>/>
				<label for='add-event-repeat-monthly-week-4'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_week_4"]; ?>
					</div>
				</label>

				<input type='radio' class='add-event-repeat-monthly-week' id='add-event-repeat-monthly-week-penultimate' name='add-event-repeat-type' value='month_week_penultimate' <?php echo $checkedRepeatTypeMonthlyWeekPenultimate; ?>/>
				<label for='add-event-repeat-monthly-week-penultimate'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_week_penultimate"]; ?>
					</div>
				</label>

				<input type='radio' class='add-event-repeat-monthly-week' id='add-event-repeat-monthly-week-last' name='add-event-repeat-type' value='month_week_last' <?php echo $checkedRepeatTypeMonthlyWeekLast; ?>/>
				<label for='add-event-repeat-monthly-week-last'>
					<div class='radio-title'>
						<?php echo LANG["event_form_repeat_monthly_week_last"]; ?>
					</div>
				</label>

				<div class="add-event-repeat-until-section">
					<?php template_drawSwitch(LANG["event_form_repeat_until_label"], "repeat-until", $repeatUntil); ?>

					<span class="add-event-repeat-until-date-section">
						<label class='label-same-line'>
							<?php echo LANG["event_form_repeat_until_date_label"] ?>
							<?php time_drawDateSelector("repeatuntildate", $repeatUntilDate); ?>
						</label>
					</span>
				</div>
			</div>

			<input type="submit" name="submit-event" value="<?php echo $submitLabel; ?>">
		</form>

		<script type="text/javascript">

			$(document).ready(function()
			{
				function enableSubmitButton()
				{
					$("[name='submit-event']").prop('disabled', false);
				}

				function disableSubmitButton()
				{
					$("[name='submit-event']").prop('disabled', true);
				}

				function removeAllErrorFields()
				{
					$(".field-error").removeClass("field-error");
				}

				function markError(selector)
				{
					$(selector).addClass("field-error");
				}

				function showHideSections()
				{
					if ($("#all-day").prop('checked'))
					{
						$(".add-event-times").hide();
						$(".add-event-duration").hide();
						$("#add-event-end-date-not-all-day").hide();
						$("#add-event-end-date-all-day").show();
					}
					else
					{
						$(".add-event-times").show();
						$(".add-event-duration").show();
						$("#add-event-end-date-not-all-day").show();
						$("#add-event-end-date-all-day").hide();
					}

					var startDate = $("#start").val();
					var endDate = $("#end").val();

					if (startDate != "" && endDate != "")
					{
						$(".add-event-repeat-section").show();
					}
					else
					{
						$(".add-event-repeat-section").hide();
					}

					var repeatType = $("[name='add-event-repeat-type']:checked").val();
					if (repeatType == "none")
					{
						$(".add-event-repeat-until-section").hide();
					}
					else
					{
						$(".add-event-repeat-until-section").show();
					}

					if ($("#repeat-until").prop('checked'))
					{
						$(".add-event-repeat-until-date-section").show();
					}
					else
					{
						$(".add-event-repeat-until-date-section").hide();
					}
				}

				function checkForErrors()
				{
					// enable submit button until proved otherwise
					enableSubmitButton();

					// remove all error boxes until proved otherwise
					removeAllErrorFields();

					// need a title
					var title = $("[name='title']").val();
					if (title == "")
					{
						markError("[name='title']");
						disableSubmitButton();
					}

					// need a start date
					var startDate = $("#start").val();
					if (startDate == "")
					{
						markError("#start");
						disableSubmitButton();
					}

					// need an end date
					var endDate = $("#end").val();
					if (endDate == "")
					{
						markError("#end");
						disableSubmitButton();
					}

					// end must be after start
					var allDay = ($("#all-day").prop('checked'));
					if (allDay)
					{
						if (startDate != "" && endDate != "" && endDate < startDate)
						{
							markError("#end");
							disableSubmitButton();
						}
					}
					else
					{
						var startDt = time_getDateFromDateTimeSelector("start");
						var endDt = time_getDateFromDateTimeSelector("end");
						if (startDt == null || endDt == null)
						{
							disableSubmitButton();
						}
						else if (startDt >= endDt)
						{
							if (startDate == endDate)
							{
								markError("#end-hour");
								markError("#end-minutes");
							}
							else
							{
								markError("#end");
							}

							disableSubmitButton();
						}
					}

					// need a timezone
					var tz = $("[name='event-timezone']").val();
					if (tz == "")
					{
						markError("#add-event-timezone");
						disableSubmitButton();
					}

					// if we have a repeat and have selected 'repeat until'
					var repeatType = $("[name='add-event-repeat-type']:checked").val();
					var repeatUntilSelected = ($("#repeat-until").prop('checked'));
					if (repeatType != "none" && repeatUntilSelected)
					{
						// we need a repeat until date and it must be on or after the start date
						var repeatUntilDate = $("#repeatuntildate").val();
						if (repeatUntilDate == "" || (startDate != "" && repeatUntilDate < startDate))
						{
							markError("#repeatuntildate");
							disableSubmitButton();
						}
					}
				}

				function updateUi()
				{
					showHideSections();
					checkForErrors();
				}

				function setTimezone(tz)
				{
					$("[name='event-timezone']").val(tz);
					$("#add-event-timezone").html(time_formatTimezone(tz));
				}

				function setUserTimezone()
				{
					// don't use the browser timezone if one has already been set by the user
					if ($("[name='event-timezone']").val() != "")
					{
						return;
					}

					var userTimezone = time_getUserTimezone();

					$("[name='timezone']").each(function()
					{
						// check the user timezone reported by the browser matches one of the ones
						// we know before blindly accepting it
						if ($(this).val() == userTimezone)
						{
							// remember the timezone
							setTimezone(userTimezone);
							// mark it as selected in the timezone list
							$(this).prop("checked", true);
							// open the section it's in
							$(this).closest(".linked-show-hide-box").show();
						}
					});
				}

				function updateEndDatetime()
				{
					var endDate = $("#end").val();
					var duration = $("[name='duration']:checked").val();
					var dt = time_getDateFromDateTimeSelector("start");

					if (dt != null && (endDate == "" || duration > 0))
					{
						if (duration > 0)
						{
							time_addMinutes(dt, duration);
						}
						time_updateDateTimeSelector("end", dt);
					}
				}

				function updateDuration()
				{
					$("[name='duration']").prop("checked", false);
					var startDate = $("#start").val();
					var endDate = $("#end").val();
					if (start != "" && end != "")
					{
						var startDt = time_getDateFromDateTimeSelector("start");
						var endDt = time_getDateFromDateTimeSelector("end");
						var duration = time_diffInMinutes(endDt, startDt);
						$("[name='duration']").each(function()
						{
							if ($(this).val() == duration)
							{
								$(this).prop("checked", true);
							}
						});
					}
				}

				function updateMonthlyRepeats()
				{
					var startDate = $("#start").val();
					if (startDate == "")
					{
						// no date yet, allow all weekly repeats
						$(".add-event-repeat-monthly-week").prop('disabled', false);
					}
					else
					{
						// disable all weekly repeats
						$(".add-event-repeat-monthly-week").prop('disabled', true);

						// re-enable any that are valid
						var startDt = time_getDateFromDateTimeSelector("start");

						var weekNumFromStart = time_weekNumberInMonthFromStart(startDt);
						if (weekNumFromStart <= 4)
						{
							$("#add-event-repeat-monthly-week-" + weekNumFromStart).prop('disabled', false);
						}

						var weekNumFromEnd = time_weekNumberInMonthFromEnd(startDt);
						if (weekNumFromEnd == 1)
						{
							$("#add-event-repeat-monthly-week-last").prop('disabled', false);
						}
						else if (weekNumFromEnd == 2)
						{
							$("#add-event-repeat-monthly-week-penultimate").prop('disabled', false);
						}
						
						// was one of them selected?
						$(".add-event-repeat-monthly-week:checked").each(function()
						{
							// is it now disabled?
							if ($(this).prop('disabled'))
							{
								// unselect it
								$(this).prop("checked", false);
								// find another one to select instead
								var foundOne = false;
								$(".add-event-repeat-monthly-week").each(function()
								{
									if (!foundOne && !$(this).prop('disabled'))
									{
										$(this).prop("checked", true);
										foundOne = true;
									}
								});
							}
						});

					}
				}

				$("[name='title']").on("input", function()
				{
					updateUi();
				});

				$("#start").on("input", function()
				{
					updateEndDatetime();
					updateMonthlyRepeats();
					updateUi();
				});

				time_onDateChanged("start", function()
				{
					updateEndDatetime();
					updateMonthlyRepeats();
					updateUi();
				});

				$("#start-hour").change(function()
				{
					updateEndDatetime();
					updateUi();
				});

				$("#start-minutes").change(function()
				{
					updateEndDatetime();
					updateUi();
				});

				$("[name='duration']").click(function()
                                {
					updateEndDatetime();
					updateUi();
                                });

				$("#end").on("input", function()
				{
					updateDuration();
					updateUi();
				});

				time_onDateChanged("end", function()
				{
					updateDuration();
					updateUi();
				});

				$("#end-hour").change(function()
				{
					updateDuration();
					updateUi();
				});

				$("#end-minutes").change(function()
				{
					updateDuration();
					updateUi();
				});

				$("#add-event-timezone").click(function()
				{
					$("#add-event-timezone-list").toggle();
				});

				$("[name='timezone']").click(function()
                                {
                                        $("#add-event-timezone-list").hide();
					setTimezone($(this).val());
					updateUi();
                                });

				$("#repeatuntildate").on("input", function()
				{
					updateUi();
				});

				time_onDateChanged("repeatuntildate", function()
				{
					updateUi();
				});


				$("#all-day").click(function()
				{
					var endDate = $("#end").val();

					if ($("#all-day").prop('checked'))
					{
						var endHours = time_getHours("end");
						var endMinutes = time_getMinutes("end");
						if (endDate != "" && endHours == 0 && endMinutes == 0)
						{
							time_addDaysToField("end", -1);
						}
					}
					else
					{
						time_setHours("start", 0);
						time_setMinutes("start", 0);
						time_setHours("end", 0);
						time_setMinutes("end", 0);
						if (endDate != "")
						{
							time_addDaysToField("end", 1);
						}
						updateDuration();
					}

					updateUi();
				});

				$("[name='add-event-repeat-type']").click(updateUi);
				$("#repeat-until").click(updateUi);

				// initialise UI
				setUserTimezone();
				updateDuration();
				updateMonthlyRepeats();
				updateUi();
			});

		</script>
	</div>
<?php

template_drawFooter();
