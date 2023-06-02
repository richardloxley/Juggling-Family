<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("calendar.inc.php");
require_once("rooms.inc.php");


if (isset($_GET["room"]))
{
	$roomId = rooms_getRoomIdFromUrl($_GET["room"]);

	if ($roomId === false)
	{
		http_response_code(404);
		exit(0);
	}

	$calendarName = CONFIG["site_title"] . ": " . rooms_getTitleFromRoomId($roomId);
}
else
{
	$roomId = 0;
	$calendarName = CONFIG["site_title"];
}



function escapeText($text)
{
	$search = array
	(
		"/\\\/",
		"/\n/",
		"/,/",
		"/;/"
	);

	$replace = array
	(
		"\\\\\\",
		"\\n",
		"\\,",
		"\\;"
	); 

	// escape characters that need escaping
	$text = preg_replace($search, $replace, $text);

	// remove any remaining control characters
	$text = preg_replace('/[[:cntrl:]]/', '', $text);

	return $text;
}


function outputTimezone($datetime, $fromOffsetMinutes, $toOffsetMinutes, $isDaylight, $abbreviation)
{
	if ($isDaylight)
	{
		$type = "DAYLIGHT";
	}
	else
	{
		$type = "STANDARD";
	}

	echo "BEGIN:" . $type . "\r\n";

	echo "DTSTART:" . time_formatIcsDateTimeUtc($datetime) . "\r\n";
	echo "TZNAME:" . $abbreviation . "\r\n";

	$fromHours = intdiv($fromOffsetMinutes, 60);
	$fromMinutes = abs($fromOffsetMinutes % 60);
	echo "TZOFFSETFROM:" . sprintf("%+03d%02d", $fromHours, $fromMinutes) . "\r\n";

	$toHours = intdiv($toOffsetMinutes, 60);
	$toMinutes = abs($toOffsetMinutes % 60);
	echo "TZOFFSETTO:" . sprintf("%+03d%02d", $toHours, $toMinutes) . "\r\n";

	echo "END:" . $type . "\r\n";
}


function outputTimezonesForEvents($events)
{
	// first find all the zones used by the events
	$zones = array();
	foreach ($events as $event)
	{
		$zones[] = timezone_name_get($event["timezone"]);
	}
	$zones = array_unique($zones);

	// now output zone information for each one
	foreach ($zones as $zonename)
	{
		// look up the time zone transitions from the PHP database
		// start from a year ago, and look two years ahead - that should be sufficient
		// without padding out the file too much
		$now = time();
		$year = 60 * 60 * 24 * 365;
		$timezone = timezone_open($zonename);
		$transitions = timezone_transitions_get($timezone, $now - $year, $now + (2 * $year));

		$numTransitions = count($transitions);

		if ($numTransitions > 0)
		{
			echo "BEGIN:VTIMEZONE\r\n";
			echo "TZID:" . $zonename . "\r\n";

			// store the offset from the first entry
			$fromOffsetMinutes = intdiv($transitions[0]["offset"], 60);

			if ($numTransitions == 1)
			{
				// this timezone only has one entry so doesn't change with daylight saving
				// just output a single entry with that offset
				$datetime = date_create($transitions[0]["time"]);
				$abbreviation = $transitions[0]["abbr"];
				$isDaylight = $transitions[0]["isdst"];
				outputTimezone($datetime, $fromOffsetMinutes, $fromOffsetMinutes, $isDaylight, $abbreviation);
			}
			else
			{
				for ($x = 1; $x < $numTransitions; $x++)
				{
					$datetime = date_create($transitions[$x]["time"]);
					$abbreviation = $transitions[$x]["abbr"];
					$isDaylight = $transitions[$x]["isdst"];
					$toOffsetMinutes = intdiv($transitions[$x]["offset"], 60);
					outputTimezone($datetime, $fromOffsetMinutes, $toOffsetMinutes, $isDaylight, $abbreviation);

					$fromOffsetMinutes = $toOffsetMinutes;
				}
			}

			echo "END:VTIMEZONE\r\n";
		}
	}
}



$events = calendar_getDbEvents($roomId);

$dtstamp = time_formatIcsDateTimeUtc(time_nowInUtc());

header("Content-type:text/calendar");
header("Content-Disposition: attachment; filename=calendar.ics");

// get an array of unique numbers of minutes before the event to set reminders (alarms)
$reminders = array();
if (isset($_GET["r"]))
{
	$args = explode(',', $_GET["r"]);
	foreach ($args as $arg)
	{
		if (is_numeric($arg) && intval($arg) >= 0)
		{
			$reminders[] = intval($arg);
		}
	}
	$reminders = array_unique($reminders);
	sort($reminders);
}


echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:" . template_domainName() . "\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:" . $calendarName . "\r\n";

// suggest clients look for updates every hour - ignored by most calendars!
echo "X-PUBLISHED-TTL:PT1H\r\n";
echo "REFRESH-INTERVAL;VALUE=DURATION:PT1H\r\n";

outputTimezonesForEvents($events);

foreach ($events as $event)
{
	echo "BEGIN:VEVENT\r\n";

	echo "DTSTAMP:" . $dtstamp . "\r\n";

	echo "DTSTART;" . time_formatIcsDateTime($event["startUtc"], $event["timezone"]) . "\r\n";
	echo "DTEND;" . time_formatIcsDateTime($event["endUtc"], $event["timezone"]) . "\r\n";

	echo "SUMMARY:" . escapeText($event["title"]) . "\r\n";
	echo "DESCRIPTION:" . escapeText($event["description"]) . "\r\n";

	$roomUrl = CONFIG["site_root_url"] . rooms_getUrlFromRoomId($event["roomId"]);
	$roomTitle = rooms_getTitleFromRoomId($event["roomId"]);
	echo "LOCATION;ALTREP=\"" . $roomUrl . "\":" . $roomTitle . "\r\n";
	echo "URL:" . $roomUrl . "\r\n";

	echo "UID:" . $event["uid"] . "\r\n";

	echo "TRANSP:TRANSPARENT\r\n";

	if ($event["cancelled"])
	{
		echo "STATUS:CANCELLED\r\n";
	}
	else
	{
		echo "STATUS:CONFIRMED\r\n";
	}

	$repeatType = $event["repeatType"];
	if ($repeatType != "none")
	{
		$rrule = "RRULE:";

		if ($repeatType == "day")
		{
			$rrule .= "FREQ=DAILY";
		}
		else if ($repeatType == "week")
		{
			$rrule .= "FREQ=WEEKLY";
		}
		else if ($repeatType == "month_date")
		{
			$startInEventTimezone = time_convertToLocal($event["startUtc"], $event["timezone"]);
			$dayOfMonth = time_getDayOfMonth($startInEventTimezone);
			$rrule .= "FREQ=MONTHLY;BYMONTHDAY=" . $dayOfMonth;
		}
		else
		{
			// month_week_1, month_week_2, month_week_3, month_week_4, month_week_penultimate, month_week_last
			if ($repeatType == "month_week_last")
			{
				$ordinal = "-1";
			}
			else if ($repeatType == "month_week_penultimate")
			{
				$ordinal = "-2";
			}
			else
			{
				$ordinal = preg_replace("/month_week_/", "", $repeatType);
			}

			$startInEventTimezone = time_convertToLocal($event["startUtc"], $event["timezone"]);
			$dayOfWeek = time_getIcsDayOfWeek($startInEventTimezone);
			$rrule .= "FREQ=MONTHLY;BYDAY=" . $ordinal . $dayOfWeek;
		}

		if ($event["repeatUntilUtc"] !== null)
		{
			$rrule .= ";UNTIL=" . time_formatIcsDateTimeUtc($event["repeatUntilUtc"]);
		}

		echo $rrule . "\r\n";
	}

	echo "LAST-MODIFIED:" . time_formatIcsDateTimeUtc($event["lastModifiedUtc"]) . "\r\n";
	echo "SEQUENCE:" . $event["numEdits"] . "\r\n";

	foreach ($reminders as $minutes)
	{
		echo "BEGIN:VALARM\r\n";
		echo "TRIGGER:-PT". $minutes . "M\r\n";
		echo "ACTION:DISPLAY\r\n";
		echo "DESCRIPTION:" . escapeText($event["title"]) . "\r\n";
		echo "END:VALARM\r\n";
	}

	echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";


