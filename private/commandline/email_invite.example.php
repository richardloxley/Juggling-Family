#!/usr/bin/php
<?php

// insert room ID here
$ROOM_ID = 1;

require_once(dirname(__FILE__) . "/../first.inc.php");

// insert recipient group email alias here
$to = "group@example.com";

// insert subject here
$subject = "Chat tonight at 8pm";

// insert from address here
$headers = "From: Admin <admin@example.com>\r\n";
$headers .= "Content-Type: text/plain;charset=utf-8\r\n";

// insert from address here
$options = "-fadmin@example.com";

// insert body here
$body = "Your weekly reminder: video chat is tonight at 8pm.\n";
$body .= "\n";
$body .= login_createInvitation($ROOM_ID) . "\n";
$body .= "\n";
$body .= "Admin\n";

if (!mail($to, $subject, $body, $headers, $options))
{
	echo "Failed to send email...";
	echo "\n";
	echo $body;
}
