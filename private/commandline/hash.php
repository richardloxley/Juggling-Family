#!/usr/bin/php
<?php

require_once(dirname(__FILE__) . "/../first.inc.php");

if ($argc != 2)
{
	echo "Usage: $argv[0] <data-to-hash>\n";
	exit(1);
}

echo login_hashData($argv[1]);
echo "\n";
