<?php

function database_getConnection()
{
	static $db_connection = null;

	if (is_null($db_connection))
	{
		// throw exceptions for all MySQL problems - makes our error handling much simpler
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		$db_config = parse_ini_file(CONFIG_PATH . "db.ini");
		$db_connection = mysqli_connect($db_config["db_servername"], $db_config["db_username"], $db_config["db_password"], $db_config["db_database"]);
	}

	return $db_connection;
}


function database_genericErrorMessage()
{
	$adminEmailTo = "<a href='mailto:" . CONFIG["site_admin_email"] . "'>" . CONFIG["site_admin_email"] . "</a>";
	$error = sprintf(LANG["database_error"], $adminEmailTo);

	return $error;
}
