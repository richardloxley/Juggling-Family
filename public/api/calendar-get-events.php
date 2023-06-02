<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("calendar.inc.php");

calendar_apiGetEvents();
