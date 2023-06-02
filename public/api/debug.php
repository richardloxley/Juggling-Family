<?php


require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

if (!login_isLoggedIn())
{
	exit(0);
}

function outputDebug($message)
{
	error_log($message . "\n", 3, "/tmp/php_debug");
}

outputDebug(date(DATE_RFC2822));

foreach ($_POST as $key => $value)
{
	outputDebug($key . " = " . trim($value));
}

outputDebug("");
