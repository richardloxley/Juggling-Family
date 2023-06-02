<?php

require_once("settings.inc.php");


function time_addSeconds($datetime, $seconds)
{
	if ($seconds < 0)
	{
		date_sub($datetime, new DateInterval("PT" . abs($seconds) . "S"));
	}
	else
	{
		date_add($datetime, new DateInterval("PT" . $seconds . "S"));
	}
}


function time_addDays($datetime, $days)
{
	if ($days < 0)
	{
		date_sub($datetime, new DateInterval("P" . abs($days) . "D"));
	}
	else
	{
		date_add($datetime, new DateInterval("P" . $days . "D"));
	}
}


function time_addWeeks($datetime, $weeks)
{
	if ($weeks < 0)
	{
		date_sub($datetime, new DateInterval("P" . abs($weeks) . "W"));
	}
	else
	{
		date_add($datetime, new DateInterval("P" . $weeks . "W"));
	}
}


// if the same date doesn't exist in the target month, skip months until we find one
function time_getSameDateAfterAtLeastXMonths($datetime, $months)
{
	// remember which day of the month it was
	$oldDay = $datetime->format("d");

	for ($x = $months; $x < $months + 12; $x++)
	{
		$newDt = clone $datetime;

		// move date to appropriate month
		date_add($newDt, new DateInterval("P" . $x . "M"));

		// check day is the same (it may overflow if it was 29th, 30th or 31st!)
		$newDay = $newDt->format("d");

		// stop if the day matches (continue to the next month if it doesn't)
		if ($oldDay == $newDay)
		{
			break;
		}
	}

	return $newDt;
}


function time_minutesUntil($datetime)
{
	$now = time_nowInUtc();
	$then = time_convertToUtc($datetime);
	$seconds = $then->getTimestamp() - $now->getTimestamp();
	return ceil($seconds / 60);
}


function time_makeTimezone($timezoneString, $offsetMinutes)
{
	$timezone = null;

	// try to interprept timezone description
	if ($timezoneString != "")
	{
		try
		{
			$tzAttempt = timezone_open($timezoneString);
			if ($tzAttempt !== false)
			{
				$timezone = $tzAttempt;
			}
		}
		catch (Exception $e)
		{
		}
	}

	// that didn't work, let's try the offset given (not as good as it doesn't handle daylight saving time
	// changes in the future, but better than nothing
	if ($timezone === null)
	{
		$offsetMinutes = -$offsetMinutes;
		$hours = intdiv($offsetMinutes, 60);
		$minutes = abs($offsetMinutes % 60);
		$timezoneString = sprintf("%+03d%02d", $hours, $minutes);

		try
		{
			$tzAttempt = timezone_open($timezoneString);
			if ($tzAttempt !== false)
			{
				$timezone = $tzAttempt;
			}
		}
		catch (Exception $e)
		{
		}
	}

	// fallback to UTC if all else fails
	if ($timezone === null)
	{
		$timezone = timezone_open("UTC");
	}

	return $timezone;
}


function time_convertToLocal($datetime, $localTimezone)
{
	$localDatetime = clone $datetime;
	date_timezone_set($localDatetime, $localTimezone);
	return $localDatetime;
}


function time_convertToUtc($datetime)
{
	$utcDatetime = clone $datetime;
	date_timezone_set($utcDatetime, timezone_open("UTC"));
	return $utcDatetime;
}


function time_convertToMysqlString($datetime)
{
	return date_format($datetime, "Y-m-d H:i:s");
}


// just returns midnight at the start of the date
function time_extractDate($datetime)
{
	return date_create(date_format($datetime, "Y-m-d"), date_timezone_get($datetime));
}


function time_makeDateFromUtcString($datestring)
{
	return date_create($datestring, timezone_open("UTC"));
}


function time_makeDateFromLocalString($datestring, $timezone)
{
	return date_create($datestring, timezone_open($timezone));
}


function time_nowInUtc()
{
	return date_create("now", timezone_open("UTC"));
}


function time_nowInUtcString()
{
	return date_format(time_nowInUtc(), "c");
}


function time_nowInLocalTimezone($localTimezone)
{
	return date_create("now", $localTimezone);
}


// e.g.
//	2.15am
function time_formatTime($datetime)
{
	return date_format($datetime, 'g:ia');
}


// e.g.
//	2.00pm-3.30pm
//	all day
//	until 5pm
//	2.15am onwards
function time_formatTimeDuration($start, $end)
{
	$startsAtMidnight = (date_format($start, "Hi") == "0000");
	$endsAtMidnight = (date_format($end, "Hi") == "0000");


	if ($startsAtMidnight && $endsAtMidnight)
	{
		return LANG["calendar_all_day"];
	}
	else if ($startsAtMidnight)
	{
		return sprintf(LANG["calendar_until"], time_formatTime($end));
	}
	else if ($endsAtMidnight)
	{
		return sprintf(LANG["calendar_onwards"], time_formatTime($start));
	}
	else
	{
		return sprintf(LANG["calendar_duration"], time_formatTime($start), time_formatTime($end));
	}
}

// e.g.
//	Mon 5 Dec 14:00 - 15:30
//	Mon 5 Dec 23:00 - Tue 6 Dec 01:00
//	Mon 5 Dec 00:00 - Tue 6 Dec 00:00
function time_formatDateTimeDuration($start, $end)
{
	$startDate = time_formatDateShort($start);
	$endDate = time_formatDateShort($end);

	$formatted = $startDate . date_format($start, " H:i - ");

	if ($startDate != $endDate)
	{
		$formatted .= $endDate . " ";
	}

	$formatted .= date_format($end, "H:i");

	return $formatted;
}


// e.g. Mon 5 Dec
function time_formatDateShort($datetime)
{
	$dayName = LANG["time_day_short"][date_format($datetime, 'w')];
	$date = date_format($datetime, 'j');
	$monthName = LANG["time_month_short"][date_format($datetime, 'n')];

	return "$dayName $date $monthName";
}


// e.g. Mon 5 December
function time_formatDate($datetime)
{
	$dayName = LANG["time_day_short"][date_format($datetime, 'w')];
	$date = date_format($datetime, 'j');
	$monthName = LANG["time_month_long"][date_format($datetime, 'n')];

	return "$dayName $date $monthName";
}


function time_formatIsoDateTime($datetime)
{
	return date_format($datetime, "c");
}


function time_formatIsoDate($datetime)
{
	return date_format($datetime, "Y-m-d");
}


function time_formatIcsDateTime($datetimeUtc, $timezone)
{
	$localDt = time_convertToLocal($datetimeUtc, $timezone);
	$timezoneString = timezone_name_get($timezone);

	return "TZID=" . $timezoneString . ":" . date_format($localDt, "Ymd") . "T" . date_format($localDt, "His");
}


function time_formatIcsDateTimeUtc($datetimeUtc)
{
	return date_format($datetimeUtc, "Ymd") . "T" . date_format($datetimeUtc, "His") . "Z";
}


function time_getIcsDayOfWeek($datetime)
{
	$dayStrings = array("SU", "MO", "TU", "WE", "TH", "FR", "SA");
	$day = intval(date_format($datetime, "w"));
	return $dayStrings[$day];
}


function time_getDayOfMonth($datetime)
{
	return intval(date_format($datetime, "j"));
}


function time_getHours($datetime)
{
	return intval(date_format($datetime, "G"));
}


function time_getMinutes($datetime)
{
	return intval(date_format($datetime, "i"));
}


function time_humanDisplayDate($mysqlDatetimeUtc)
{
        static $mysqlZoneUtc = null;
        if (is_null($mysqlZoneUtc))
	{
		$mysqlZoneUtc = new DateTimeZone('UTC');
	}

	$datetime = date_create($mysqlDatetimeUtc, $mysqlZoneUtc);

	// e.g. 2020-08-02T18:16:21+00:00
	$utc = date_format($datetime, 'c');
	// e.g. Sun 17:16
	$utcFormatted = date_format($datetime, 'D H:i');

	return "<time datetime='$utc' class='day-time'>$utcFormatted UTC</time>";
}


function time_javascriptAdjustDatesToLocalTimezone()
{
	$jsDayNames = json_encode(LANG["time_day_short"]);

	?>
		<script type="text/javascript">

			const time_dayNames = <?php echo $jsDayNames; ?>;

			function time_adjustDatesToLocalTimezone(element)
			{
				element.find("time.day-time").each(function()
				{
					var utcDate = $(this).attr("datetime");
					var localDate = new Date(utcDate);
					var hours = localDate.getHours();
					var minutes = localDate.getMinutes();
					if (hours < 10)
					{
						hours = "0" + hours;
					}
					if (minutes < 10)
					{
						minutes = "0" + minutes;
					}
					var dayTime = time_dayNames[localDate.getDay()] + " " + hours + ":" + minutes;
					$(this).html(dayTime);
				});
			}

			$(document).ready(function()
			{
				time_adjustDatesToLocalTimezone($(document));
			});

		</script>
	<?php
}


function time_javascriptUserTimezones()
{
	?>
		<script type="text/javascript">

			function time_getUserTimezone()
			{
				var timezone = "";

				try
				{
					// Internet Explorer and older browsers don't have this
					timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
				}
				catch (error)
				{
					timezone = "";
				}

				return timezone;
			}

			function time_getUserTimezoneOffset()
			{
				return new Date().getTimezoneOffset();
			}


			function time_addUserTimezonesToUrl(url)
			{
				return url + "&timezone=" + time_getUserTimezone() + "&tzoffset=" + time_getUserTimezoneOffset();
			}

			function time_formatTimezone(tz)
			{
				return tz.replace(/_/g, " ");
			}

		</script>
	<?php
}


/*
function time_humanDisplayDateOld($mysqlDatetimeUtc)
{
	// see https://stackoverflow.com/questions/1349280/storing-datetime-as-utc-in-php-mysql

        static $mysqlZoneUtc = null;
        if (is_null($mysqlZoneUtc))
	{
		$mysqlZoneUtc = new DateTimeZone('UTC');
	}

	$userZone = new DateTimeZone(time_currentTimezone());

	$dt = new DateTime($mysqlDatetimeUtc, $mysqlZoneUtc);
	$dt->setTimezone($userZone);
	return date_format($dt, 'D H:i');
}


function time_setUserTimezone($newZone)
{
	if (timezone_open($newZone) !== false)
	{
		settings_saveSetting("timezone", $newZone);
	}
}

function time_currentTimezone()
{
	return settings_getSetting("timezone", CONFIG["default_timezone"]);
}


function time_currentTimezoneHuman()
{
	$zone = time_currentTimezone();
	return str_replace("_", " ", $zone);
}

*/

function time_drawTimeZoneRadioBoxes($initialZone)
{
	$initialContinent = time_getContinent($initialZone);

	$zones = timezone_identifiers_list();

	$sectionHeading = "";
	$x = 0;

	foreach ($zones as $zone)
	{
		$continent = time_getContinent($zone);
		$area = time_getArea($zone);

		if ($sectionHeading != $continent)
		{
			if ($sectionHeading != "")
			{
				// close previous .show-hide-box
				echo "</div>";
				echo "<div class='radio-float-end'>";
				echo "</div>";
			}

			$sectionHeading = $continent;

			echo "<a class='linked-show-hide radio-section-heading' href=''>";
			echo $sectionHeading . " >";
			echo "</a>";

			$startOpen = "";

			if ($continent == $initialContinent)
			{
				$startOpen = "show-hide-box-start-open";
			}

			echo "<div class='linked-show-hide-box $startOpen'>";
		}

		$checked = "";

		if ($zone == $initialZone)
		{
			$checked = "checked='checked'";
		}

		echo "<input type='radio' id='time-zone-$x' name='timezone' value='$zone' $checked />";
		echo "<label for='time-zone-$x' class='radio-float'>";
		echo "<div class='radio-title'>";
		echo $area;
		echo "</div>";
		echo "</label>";

		$x++;
	}

	// close final .show-hide-box
	echo "</div>";
	echo "<div class='radio-float-end'>";
	echo "</div>";
}


function time_getContinent($zone)
{
	$p = strpos($zone, "/");

	if ($p === false)
	{
		return $zone;
	}
	else
	{
		return substr($zone, 0, $p);
	}
}


function time_getArea($zone)
{
	$p = strpos($zone, "/");

	if ($p === false)
	{
		return $zone;
	}
	else
	{
		$area = substr($zone, $p + 1);
		$area = time_formatTimezone($area);
		return $area;
	}
}


function time_formatTimezone($zone)
{
	return str_replace("_", " ", $zone);
}


function time_validateTimezone($zone)
{
	$zones = timezone_identifiers_list();
	if (array_search($zone, $zones) === false)
	{
		return "";
	}
	else
	{
		return $zone;
	}
}


function time_javascriptDateTimeSelectors()
{
	?>
		<script type="text/javascript">

			function time_getHours(id)
			{
				return $("#" + id + "-hour").val();
			}

			function time_getMinutes(id)
			{
				return $("#" + id + "-minutes").val();
			}

			function time_setHours(id, hours)
			{
				$("#" + id + "-hour").val(hours);
			}

			function time_setMinutes(id, minutes)
			{
				$("#" + id + "-minutes").val(minutes);
			}

			function time_twoDigitNumber(n)
			{
				n = '' + n;
				if (n.length < 2)
				{
					n = '0' + n;
				}
				return n;
			}

			function time_isoDateString(d)
			{
				var month = time_twoDigitNumber(d.getMonth() + 1);
				var day = time_twoDigitNumber(d.getDate());
				var year = d.getFullYear();
				return [year, month, day].join('-');
			}

			function time_addDays(d, days)
			{
				d.setDate(d.getDate() + 1 * days);
			}

			function time_addMinutes(d, minutes)
			{
				d.setMinutes(d.getMinutes() + 1 * minutes);
			}

			function time_diffInMinutes(first, second)
			{
				var millis = first - second;
				return millis / 60000;
			}

			function time_weekNumberInMonthFromStart(dt)
			{
				return Math.ceil(dt.getDate() / 7);
			}

			function time_weekNumberInMonthFromEnd(dt)
			{
				var year = dt.getYear();
				var month = dt.getMonth();
				var lastDayOfMonth = new Date(year, month + 1, 0);
				var daysInMonth = lastDayOfMonth.getDate();
				var daysFromEndOfMonth = daysInMonth - dt.getDate() + 1;
				return Math.ceil(daysFromEndOfMonth / 7);
			}

			function time_updateDateTimeSelector(id, d)
			{
				var dateString = time_isoDateString(d);
				$("#" + id).val(dateString);
				var romeObject = eval("rome_" + id);
				romeObject.setValue(dateString);

				time_setHours(id, d.getHours());
				time_setMinutes(id, d.getMinutes());
			}

			function time_getDateFromDateTimeSelector(id)
			{
				var dateString = $("#" + id).val();
				if (dateString == "")
				{
					return null;
				}
				var timeString = time_twoDigitNumber(time_getHours(id)) + ":" + time_twoDigitNumber(time_getMinutes(id)) + ":00";
				return new Date(dateString + "T" + timeString);
			}

			function time_addDaysToField(id, days)
			{
				var dateString = $("#" + id).val();
				if (dateString != "")
				{
					var d = new Date(dateString);
					time_addDays(d, days);
					dateString = time_isoDateString(d);
					$("#" + id).val(dateString);
					var romeObject = eval("rome_" + id);
					romeObject.setValue(dateString);
				}
			}

			function time_onDateChanged(id, fn)
			{
				var romeObject = eval("rome_" + id);
				romeObject.on("data", fn);
			}

		</script>
	<?php
}


function time_validateDateInput($dateString)
{
	$date = date_create($dateString);

	if ($date === false)
	{
		return "";
	}

	return date_format($date, "Y-m-d");
}


function time_drawDateSelector($id, $initialDate)
{
	$jsDays = json_encode(LANG["time_day_very_short"]);

	?>
		<input id='<?php echo $id; ?>' name='<?php echo $id; ?>' class='input' value='<?php echo $initialDate; ?>'>

		<script type="text/javascript">

			var rome_<?php echo $id; ?>;

			$(document).ready(function()
			{
				rome_<?php echo $id; ?> = rome(<?php echo $id; ?>,
				{
					time: false,
					weekdayFormat: <?php echo $jsDays; ?>,
					weekStart: 1
				});
			});

		</script>
	<?php
}


function time_drawTimeOfDaySelector($id, $label, $initialHour, $initialMinutes)
{
	echo "<label>";
	echo $label;

	echo "<select name='$id-hour' id='$id-hour' class='time-of-day'>";
	for ($hour = 0; $hour < 24; $hour++)
	{
		$formattedHour = sprintf("%02d", $hour);

		if ($hour == $initialHour)
		{
			echo "<option value='$hour' selected>$formattedHour</option>";
		}
		else
		{
			echo "<option value='$hour'>$formattedHour</option>";
		}
	}
	echo "</select>";

	echo ":";

	echo "<select name='$id-minutes' id='$id-minutes' class='time-of-day'>";
	for ($minutes = 0; $minutes < 60; $minutes += 5)
	{
		$formattedMinutes = sprintf("%02d", $minutes);
		if ($minutes == $initialMinutes)
		{
			echo "<option value='$minutes' selected>$formattedMinutes</option>";
		}
		else
		{
			echo "<option value='$minutes'>$formattedMinutes</option>";
		}
	}
	echo "</select>";

	echo "</label>";
}
