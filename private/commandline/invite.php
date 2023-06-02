#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . "/../first.inc.php");

if ($argc != 2)
{
	echo "Usage: $argv[0] <room-id>\n";
	exit(1);
}

echo login_createInvitation($argv[1]);
echo "\n";
