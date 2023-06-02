<?php

function debug($message)
{
	if (CONFIG["debug_php"])
	{
		error_log($message);
	}
}
