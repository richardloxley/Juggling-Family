<?php

require_once("database.inc.php");
require_once("rooms.inc.php");

// uses many of the principles suggested here:
// https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence


$login_userID = null;
$login_displayName = null;
$login_isAdmin = false;
$login_guestRoomId = null;


function login_showSettings()
{
	if (login_isLoggedIn())
	{
		return true;
	}

	return false;
}


function login_drawSettings()
{
	echo "<a class='link-looking-like-a-button' href='" . PUBLIC_URL["reset"] . "'>";
	echo LANG["settings_change_password"];
	echo "</a>";
	echo "<p>";
}


function login_preHeaderChecks()
{
	session_start();
	login_tryLoginUsingCookies();
}


function login_isLoggedIn()
{
	global $login_userID;
	return ($login_userID !== null);
}


function login_isAnAdmin()
{
	global $login_isAdmin;
	return ($login_isAdmin);
}


function login_isGuest()
{
	global $login_guestRoomId;
	return ($login_guestRoomId !== null);
}


function login_hasFullAccessToRoom($roomIdOrNull)
{
	// if there's a room specified, make sure they have basic access first
	if ($roomIdOrNull !== null && !login_hasPreviewAccessToRoom($roomIdOrNull))
	{
		return false;
	}

	// logged in users get full access to rooms they can see
	if (login_isLoggedIn())
	{
		return true;
	}

	// not logged in, so see if they have guest access to the room
	global $login_guestRoomId;
	if ($roomIdOrNull !== null && $roomIdOrNull === $login_guestRoomId)
	{
		return true;
	}

	// must be not logged in and no guest access, so they don't have full access
	return false;
}


function login_hasPreviewAccessToRoom($roomId)
{
	// admins can access anything
	if (login_isAnAdmin())
	{
		return true;
	}

	// anyone can preview a public room
	if (rooms_isRoomPublic($roomId))
	{
		return true;
	}

	// private room - so at a minimum need to be logged in to see it (no guest access to private rooms)
	if (!login_isLoggedIn())
	{
		return false;
	}

	// private room, so must also be a member
	return rooms_isMemberOfRoom($roomId);
}


function login_guestRoomId()
{
	global $login_guestRoomId;
	return $login_guestRoomId;
}


function login_getUserId()
{
	global $login_userID;
	return $login_userID;
}


function login_getDisplayName()
{
	global $login_displayName;
	return $login_displayName;
}


function login_setGlobals($userId, $displayName, $isAdmin)
{
	global $login_userID;
	global $login_displayName;
	global $login_isAdmin;
	$login_userID = $userId;
	$login_displayName = $displayName;
	$login_isAdmin = $isAdmin;
}


function login_setGuestGlobals($guestNickname, $guestRoomId)
{
	global $login_guestRoomId;
	global $login_displayName;
	$login_guestRoomId = $guestRoomId;
	$login_displayName = $guestNickname . " " . CONFIG["site_guest_suffix"];
}


function login_deleteGlobals()
{
	global $login_userID;
	global $login_displayName;
	global $login_isAdmin;
	global $login_guestRoomId;
	$login_userID = null;
	$login_displayName = null;
	$login_guestRoomId = null;
	$login_isAdmin = false;
}


function login_tryLoginUsingCookies()
{
	try
	{
		if (isset($_SESSION["session_selector"]) && isset($_SESSION["session_token"]))
		{
			// we have a session cookie telling us we're logged in

			$selector = $_SESSION["session_selector"];
			$token = $_SESSION["session_token"];

			if (login_setGlobalsFromSessionTable($selector, $token))
			{
				login_updateSessionLastSeenTime($selector);

				if (isset($_COOKIE["remember_me_selector"]))
				{
					// "remember me" cookie exists, so presumably they still want to be remembered
					// update cookie to update expiry date (and make sure contents match current session)
					login_setRememberMeCookies($selector, $token);
				}
			}
			else
			{
				// that session token wasn't valid, so delete it
				login_deleteSessionCookies();
			}
		}
		else if (isset($_COOKIE["remember_me_selector"]) && isset($_COOKIE["remember_me_token"]))
		{
			// we have a "remember me" cookie telling us we're logged in

			$selector = $_COOKIE["remember_me_selector"];
			$token = $_COOKIE["remember_me_token"];

			if (login_setGlobalsFromSessionTable($selector, $token))
			{
				// replace session in DB with new one (to limit impact of cookie theft)
				login_deleteSession($selector);
				login_createNewSession(login_getUserId(), true);
			}
			else
			{
				// that cookie token wasn't valid, so delete it
				login_deleteRememberMeCookies();
			}
		}
		else if (isset($_SESSION["guest_nickname"]) && isset($_SESSION["guest_room_id"]))
		{
			login_setGuestGlobals($_SESSION["guest_nickname"], $_SESSION["guest_room_id"]);
		}
	}
	catch (mysqli_sql_exception $e)
	{
		// ignore MySQL errors - they will be logged, but failing
		// to log in from the cookie isn't fatal
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


// throws an exception on DB error
function login_setGlobalsFromSessionTable($selector, $token)
{
	$db = database_getConnection();

	// remove any old entries in session table
	$days = CONFIG["remember_me_max_time_between_visits_in_days"];
	$st = mysqli_prepare($db, "delete from sessions where last_seen < date_sub(UTC_TIMESTAMP(), interval ? day)");
	mysqli_stmt_bind_param($st, "i", $days);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);

	// look for a match
	$st = mysqli_prepare($db, "select hashed_token, user_id, display_name, is_admin from sessions inner join users using (user_id) where selector = ?");
	mysqli_stmt_bind_param($st, "s", $selector);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $hashedToken, $userId, $displayName, $isAdmin);
	$found = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);

	if ($found)
	{
		if (hash_equals($hashedToken, login_hashData($token)))
		{
			// set global variables to save user information for the duration of the execution of this page
			login_setGlobals($userId, $displayName, $isAdmin);

			return true;
		}
	}

	return false;
}


// throws exception on database error
function login_updateSessionLastSeenTime($selector)
{
	$db = database_getConnection();
	$st = mysqli_prepare($db, "update sessions set last_seen = UTC_TIMESTAMP() where selector = ?");
	mysqli_stmt_bind_param($st, "s", $selector);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);

	// also set it in the PHP session - supposedly the session garbage collection is based on last
	// write time not last read time, so this will keep the session active as long as they are active
	// on the site
	$_SESSION['last_seen'] = time(); 
}


// throws exception on database error
function login_deleteSession($selector)
{
	$db = database_getConnection();
	$st = mysqli_prepare($db, "delete from sessions where selector = ?");
	mysqli_stmt_bind_param($st, "s", $selector);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);
}


// throws exception on database error
function login_createNewSession($userId, $rememberMe)
{
	$selector = login_makeSessionSelector();
	$token = login_makeSessionToken();
	$hashedToken = login_hashData($token);
	$db = database_getConnection();
	$st = mysqli_prepare($db, "insert into sessions (selector, hashed_token, user_id, last_seen) " .
				  "values (?, ?, ?, UTC_TIMESTAMP())");
	mysqli_stmt_bind_param($st, "ssi", $selector, $hashedToken, $userId);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);

	// save in session cookie to automatically log in for each page view in this session
	login_setSessionCookies($selector, $token);

	if ($rememberMe)
	{
		// save in persistent cookie
		login_setRememberMeCookies($selector, $token);
	}
}


function login_setSessionCookies($selector, $token)
{
	$_SESSION["session_selector"] = $selector;
	$_SESSION["session_token"] = $token;
}


function login_setGuestSessionCookies($guestNickname, $roomId)
{
	$_SESSION["guest_nickname"] = $guestNickname;
	$_SESSION["guest_room_id"] = $roomId;
}


function login_deleteSessionCookies()
{
	unset($_SESSION["session_selector"]);
	unset($_SESSION["session_token"]);
	unset($_SESSION["guest_nickname"]);
	unset($_SESSION["guest_room_id"]);
}


function login_setRememberMeCookies($selector, $token)
{
	$expiryTime = time() + CONFIG["remember_me_max_time_between_visits_in_days"] * 24 * 60 * 60;
	setcookie("remember_me_selector", $selector, $expiryTime, "/");
	setcookie("remember_me_token", $token, $expiryTime, "/");
}


function login_deleteRememberMeCookies()
{
	$expiryTime = time() - 3600;
	setcookie("remember_me_selector", "", $expiryTime, "/");
	setcookie("remember_me_token", "", $expiryTime, "/");
	unset($_COOKIE["remember_me_selector"]);
	unset($_COOKIE["remember_me_token"]);
}


// throws an exception on DB error
function login_getHashedEmail($email)
{
	// we used to store hashed email as case-sensitive, which caused confusion
	// now we change them to lower case whenever someone logs in

	$hashedEmailSensitive = login_hashData($email);
	$hashedEmailLower = login_hashData(strtolower($email));

	$db = database_getConnection();
	$st = mysqli_prepare($db, "select hashed_email, email_is_lower_case from users where hashed_email = ? or hashed_email = ?");
	mysqli_stmt_bind_param($st, "ss", $hashedEmailSensitive, $hashedEmailLower);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $hashedEmail, $emailIsLowerCase);
	$found = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);

	if ($found && !$emailIsLowerCase)
	{
		// convert hashed email to lower case in DB

		$st = mysqli_prepare($db, "update users set hashed_email = ?, email_is_lower_case = 1 where hashed_email = ?");
		mysqli_stmt_bind_param($st, "ss", $hashedEmailLower, $hashedEmail);
		$result = mysqli_stmt_execute($st);
		mysqli_stmt_close($st);

		$st = mysqli_prepare($db, "update invitations_pending set hashed_email = ? where hashed_email = ?");
		mysqli_stmt_bind_param($st, "ss", $hashedEmailLower, $hashedEmail);
		$result = mysqli_stmt_execute($st);
		mysqli_stmt_close($st);

		$st = mysqli_prepare($db, "update email_validation set hashed_email = ? where hashed_email = ?");
		mysqli_stmt_bind_param($st, "ss", $hashedEmailLower, $hashedEmail);
		$result = mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}

	return $hashedEmailLower;
}



// returns true/false depending on email/password match
// throws an exception on DB error
// sets cookies so needs to be called before any page output
function login_tryLoginUsingPassword($email, $password, $rememberMe)
{
	$hashedEmail = login_getHashedEmail($email);

	$db = database_getConnection();
	$st = mysqli_prepare($db, "select hashed_password, user_id, display_name, is_admin from users where hashed_email = ?");
	mysqli_stmt_bind_param($st, "s", $hashedEmail);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $hashedPassword, $userId, $displayName, $isAdmin);
	$found = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);

	if ($found)
	{
		if (login_checkPassword($password, $hashedPassword))
		{
			// create the session
			login_createNewSession($userId, $rememberMe);

			// set global variables to save user information for the duration of the execution of this page
			login_setGlobals($userId, $displayName, $isAdmin);

			// any pending private room invitations?
			login_checkForPendingInvitation($hashedEmail);

			return true;
		}
	}

	return false;
}


// sets cookies so needs to be called before any page output
function login_logout()
{
	// remove any matching sessions from the database
	try
	{
		$db = database_getConnection();

		if (isset($_SESSION["session_selector"]))
		{
			$st = mysqli_prepare($db, "delete from sessions where selector = ?");
			mysqli_stmt_bind_param($st, "s", $_SESSION["session_selector"]);
			mysqli_stmt_execute($st);
			mysqli_stmt_close($st);
		}

		if (isset($_COOKIE["remember_me_selector"]))
		{
			$st = mysqli_prepare($db, "delete from sessions where selector = ?");
			mysqli_stmt_bind_param($st, "s", $_COOKIE["remember_me_selector"]);
			mysqli_stmt_execute($st);
			mysqli_stmt_close($st);
		}
	}
	catch (mysqli_sql_exception $e)
	{
		// ignore MySQL errors - they will be logged, but failing
		// to delete the entry isn't fatal
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	// remove session cookies
	login_deleteSessionCookies();

	// remove any "remember me" cookies
	login_deleteRememberMeCookies();

	// delete global variables
	login_deleteGlobals();
}


function login_logoutEverywhere($userId)
{
	// log out elsewhere
	try
	{
		$db = database_getConnection();

		// remove all sessions for this user from the database
		$st = mysqli_prepare($db, "delete from sessions where user_id = ?");
		mysqli_stmt_bind_param($st, "i", $userId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		// ignore MySQL errors - they will be logged, but failing
		// to delete the entry isn't fatal
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


// return true/false on email sending, throws exception on database error
function login_sendValidationEmail($email, $newAccount)
{
	$hashedEmail = login_getHashedEmail($email);

	// see if this user already has an account, and choose appropriate email body

	$db = database_getConnection();
	$st = mysqli_prepare($db, "select user_id from users where hashed_email = ?");
	mysqli_stmt_bind_param($st, "s", $hashedEmail);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $userId);
	$userAlreadyExists = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);


	// store validation token

	$selector = login_generateToken(8);
	$token = login_generateToken(12);
	$hashedToken = login_hashData($token);
	$st = mysqli_prepare($db, "insert into email_validation (selector, hashed_token, hashed_email, created) " .
				  "values (?, ?, ?, UTC_TIMESTAMP())");
	mysqli_stmt_bind_param($st, "sss", $selector, $hashedToken, $hashedEmail);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);


	// save any pending invitation
	if (isset($_SESSION["pending_invitation_selector"]))
	{
		$pendingInvitationSelector = $_SESSION["pending_invitation_selector"];

		// look for a match
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select room_id, expiry from invitations where selector = ?");
		mysqli_stmt_bind_param($st, "s", $pendingInvitationSelector);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $roomId, $expiry);
		$found = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);

		if ($found)
		{
			$st = mysqli_prepare($db, "insert into invitations_pending (hashed_email, room_id, expiry) values (?, ?, ?)");
			mysqli_stmt_bind_param($st, "sis", $hashedEmail, $roomId, $expiry);
			mysqli_stmt_execute($st);
			mysqli_stmt_close($st);
		}
	}


	// send the email

	$siteName = CONFIG["site_title"];
	$siteUrl = CONFIG["site_root_url"];

	$url = $siteUrl . PUBLIC_URL["verify"] . "/" . $selector . "/" . $token;
	$hours = CONFIG["email_validation_expiry_time_in_hours"];

	if ($userAlreadyExists)
	{
		$emailSubject = sprintf(LANG["login_email_reset_subject"], $siteName);
	}
	else
	{
		$emailSubject = sprintf(LANG["login_email_join_subject"], $siteName);
	}

	$emailBody = sprintf(LANG["login_email_welcome"], $siteName);
	$emailBody .= "\n\n";

	if ($userAlreadyExists)
	{
		if ($newAccount)
		{
			$emailBody .= LANG["login_email_join_warning"] . "\n\n";
		}

		$emailBody .= LANG["login_email_reset"] . "\n";
	}
	else
	{
		if (!$newAccount)
		{
			$emailBody .= LANG["login_email_reset_warning"] . "\n\n";
		}

		$emailBody .= LANG["login_email_join"] . "\n";
	}

	$emailBody .= $url . "\n\n";
	$emailBody .= sprintf(LANG["login_email_link_valid"], $hours) . "\n\n";

	if ($userAlreadyExists)
	{
		$emailBody .= LANG["login_email_ignore_reset"] . "\n\n";
	}
	else
	{
		$emailBody .= LANG["login_email_ignore_join"] . "\n\n";
	}

	$emailBody .= LANG["login_email_privacy"] . "\n\n";
	$emailBody .= $siteName . ": " . $siteUrl;


	$emailFrom = CONFIG["site_bot_from"];
	$emailFromAddress = CONFIG["site_bot_email"];

	$headers = "From: $emailFrom <$emailFromAddress>\r\n";
	$options = "-f" . $emailFromAddress;

	return mail($email, $emailSubject, $emailBody, $headers, $options);
}


// throws exception on database error
function login_deleteExpiredValidationTokens()
{
	$db = database_getConnection();
	$hours = CONFIG["email_validation_expiry_time_in_hours"];
	$st = mysqli_prepare($db, "delete from email_validation where created < date_sub(UTC_TIMESTAMP(), interval ? hour)");
	mysqli_stmt_bind_param($st, "i", $hours);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);
}


// delete *all* tokens corresponding to a user's email when one of them has been used
function login_deleteUsedValidationTokens($hashedEmail)
{
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "delete from email_validation where hashed_email = ?");
		mysqli_stmt_bind_param($st, "s", $hashedEmail);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		// ignore database error as we've don't want to report an error to the user just for failing
		// to delete the token since the action they used the token for must have succeeded
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


// returns true (new user), false (invalid token), or nickname (existing user), throws exception on database error
function login_checkValidation($selector, $token)
{
	// first delete any expired tokens from the validation table
	login_deleteExpiredValidationTokens();

	// now check the table for the token
	$db = database_getConnection();
	$st = mysqli_prepare($db, "select hashed_token, display_name from email_validation left join users using (hashed_email) where selector = ?");
	mysqli_stmt_bind_param($st, "s", $selector);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $hashedToken, $nickname);
	$found = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);

	if ($found && hash_equals($hashedToken, login_hashData($token)))
	{
		if ($nickname == null)
		{
			return true;
		}
		else
		{
			return $nickname;
		}
	}
	else
	{
		return false;
	}
}


// returns error message if not valid
function login_checkNickname($nickname, $isGuest)
{
	$nicknameLowerCase = strtolower($nickname);


	// is it too short?

	if (strlen($nickname) < CONFIG["nickname_minimum_length"])
	{
		return sprintf(LANG["login_error_nickname_too_short"], CONFIG["nickname_minimum_length"]);
	}


	// is it a reserved name?

	foreach (CONFIG["nickname_reserved_name"] as $reserved)
	{
		if (strtolower($reserved) == $nicknameLowerCase)
		{
			return LANG["login_error_nickname_invalid"];
		}
	}

	// does it contain a quote (causes issues with JS)?

	if (strstr($nickname, "'") !== false || strstr($nickname, '"') !== false)
	{
		return LANG["login_error_nickname_invalid_character"];
	}


	// no further checks for guest nicknames

	if ($isGuest)
	{
		return "";
	}


	// is it already being used?

	try
	{
		if (!login_isNicknameAvailable($nickname))
		{
			return LANG["login_error_nickname_in_use"];
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		return database_genericErrorMessage();
	}


	// is it a single first name?

	$file = fopen(CONFIG_PATH . "invalid_names.txt", "r");
	if ($file)
	{
		while (!feof($file))
		{
			$name = trim(fgets($file));
			if ($nicknameLowerCase == $name)
			{
				fclose($file);
				return LANG["login_error_nickname_is_a_name"];
			}
		}
	}
	fclose($file);


	return "";
}


// return true/false, throws exception on database error
function login_isNicknameAvailable($nickname)
{
	$db = database_getConnection();
	// MySQL comparison is case insensitive
	$st = mysqli_prepare($db, "select user_id from users where display_name = ?");
	mysqli_stmt_bind_param($st, "s", $nickname);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $userId);
	$found = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);
	return ($found === null);
}


// returns true if user created
function login_createUser($selector, $token, $displayName, $password)
{
	try
	{
		// first delete any expired tokens from the validation table
		login_deleteExpiredValidationTokens();

		// now check the table for the token
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select hashed_token, hashed_email, user_id from email_validation left join users using (hashed_email) where selector = ?");
		mysqli_stmt_bind_param($st, "s", $selector);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $hashedToken, $hashedEmail, $userId);
		$found = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);

		if ($found && hash_equals($hashedToken, login_hashData($token)))
		{
			if ($userId == null)
			{
				// token exists, user doesn't already exist, ok to create a new user
				$hashedPassword = login_hashPassword($password);
				$st = mysqli_prepare($db, "insert into users (hashed_email, hashed_password, display_name, created) " .
							  "values (?, ?, ?, UTC_TIMESTAMP())");
				mysqli_stmt_bind_param($st, "sss", $hashedEmail, $hashedPassword, $displayName);
				$result = mysqli_stmt_execute($st);
				mysqli_stmt_close($st);

				if ($result)
				{
					// they successfully used a token, delete all their outstanding tokens
					login_deleteUsedValidationTokens($hashedEmail);
				}

				return $result;
			}
			else
			{
				// user already exists
				return false;
			}
		}
		else
		{
			// token doesn't exist
			return false;
		}
	}
	catch (mysqli_sql_exception $e)
	{
		// database error
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		return false;
	}
}


// returns true if password changed
function login_changePassword($selector, $token, $password)
{
	try
	{
		// first delete any expired tokens from the validation table
		login_deleteExpiredValidationTokens();

		// now check the table for the token
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select hashed_token, hashed_email, user_id from email_validation left join users using (hashed_email) where selector = ?");
		mysqli_stmt_bind_param($st, "s", $selector);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $hashedToken, $hashedEmail, $userId);
		$found = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);

		if ($found && hash_equals($hashedToken, login_hashData($token)))
		{
			if ($userId == null)
			{
				// user doesn't exist
				return false;
			}
			else
			{
				// token exists, user exists, ok to change password
				$hashedPassword = login_hashPassword($password);
				$st = mysqli_prepare($db, "update users set hashed_password = ? where hashed_email = ?");
				mysqli_stmt_bind_param($st, "ss", $hashedPassword, $hashedEmail);
				$result = mysqli_stmt_execute($st);
				mysqli_stmt_close($st);

				if ($result)
				{
					// they successfully used a token, delete all their outstanding tokens
					login_deleteUsedValidationTokens($hashedEmail);

					// and log them out everywhere in case they changed their
					// password because someone else knew it
					login_logoutEverywhere($userId);
				}

				return $result;
			}
		}
		else
		{
			// token doesn't exist
			return false;
		}
	}
	catch (mysqli_sql_exception $e)
	{
		// database error
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		return false;
	}
}


function login_createInvitation($roomId)
{
	$url = "";

	$expiryLengthDays = CONFIG["invitation_expiry_days"];

	try
	{
		login_deleteOldInvitations();

		$selector = login_generateToken(8);
		$token = login_generateToken(12);
		$hashedToken = login_hashData($token);

		$db = database_getConnection();
		$st = mysqli_prepare($db, "insert into invitations (selector, hashed_token, room_id, expiry) " .
					  "values (?, ?, ?, (date_add(UTC_TIMESTAMP(), interval ? day)))");
		mysqli_stmt_bind_param($st, "ssii", $selector, $hashedToken, $roomId, $expiryLengthDays);

		if (mysqli_stmt_execute($st))
		{
			$url = CONFIG["site_root_url"] . rooms_getUrlFromRoomId($roomId) . "/" . $selector . "/" . $token;
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		// database error
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $url;
}


// throws exception on DB error
function login_deleteOldInvitations()
{
	$db = database_getConnection();

	$st = mysqli_prepare($db, "delete from invitations where expiry < UTC_TIMESTAMP()");
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);

	$st = mysqli_prepare($db, "delete from invitations_pending where expiry < UTC_TIMESTAMP()");
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);
}


// the invitation corresponding to $selector has been validated, but it's for a private room,
// so remember it in case they log in or create an account during this session
function login_invitationPending($selector)
{
	$_SESSION["pending_invitation_selector"] = $selector;
}


function login_checkForPendingInvitation($hashedEmail)
{
	login_deleteOldInvitations();


	// look for invitations in the current session

	if (isset($_SESSION["pending_invitation_selector"]))
	{
		$selector = $_SESSION["pending_invitation_selector"];

		// look for a match
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select room_id from invitations where selector = ?");
		mysqli_stmt_bind_param($st, "s", $selector);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $roomId);
		$found = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);

		if ($found)
		{
			rooms_addMemberToRoom($roomId);
		}
	}


	// look for invitations in the DB

	$rooms = array();

	$db = database_getConnection();
	$st = mysqli_prepare($db, "select room_id from invitations_pending where hashed_email = ?");
	mysqli_stmt_bind_param($st, "s", $hashedEmail);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $roomId);

	while (mysqli_stmt_fetch($st))
	{
		$rooms[] = $roomId;
	}

	mysqli_stmt_close($st);

	foreach ($rooms as $roomId)
	{
		rooms_addMemberToRoom($roomId);
	}

	// delete pending invitations now they've been used
	$st = mysqli_prepare($db, "delete from invitations_pending where hashed_email = ?");
	mysqli_stmt_bind_param($st, "s", $hashedEmail);
	mysqli_stmt_execute($st);
	mysqli_stmt_close($st);
}


function login_deleteInvitationsInRoom($roomId)
{
	try
	{
		$db = database_getConnection();

		$st = mysqli_prepare($db, "delete from invitations where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);

		$st = mysqli_prepare($db, "delete from invitations_pending where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		// database error
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


// throws exception on DB error
function login_isInvitationValid($roomId, $selector, $token)
{
	login_deleteOldInvitations();

	// look for a match
	$db = database_getConnection();
	$st = mysqli_prepare($db, "select hashed_token from invitations where selector = ? and room_id = ?");
	mysqli_stmt_bind_param($st, "si", $selector, $roomId);
	mysqli_stmt_execute($st);
	mysqli_stmt_bind_result($st, $hashedToken);
	$found = mysqli_stmt_fetch($st);
	mysqli_stmt_close($st);

	if ($found)
	{
		if (hash_equals($hashedToken, login_hashData($token)))
		{
			return true;
		}
	}

	return false;
}


function login_loginAsGuest($guestNickname, $roomId)
{
	login_setGuestSessionCookies($guestNickname, $roomId);
	login_setGuestGlobals($guestNickname, $roomId);
}


function login_makeSessionSelector()
{
	return login_generateToken(8);
}


function login_makeSessionToken()
{
	return login_generateToken(32);
}


function login_generateToken($numBytes)
{
	return bin2hex(random_bytes($numBytes));
}


function login_hashData($data)
{
	$salted = CONFIG["hash_salt"] . $data;
	// hash() param 3 = false so output a string with hex digits
	$hashed = hash('sha256', $salted, false);

	return $hashed;
}


function login_hashPassword($password)
{
	// the PHP default password hash of bcrypt has a limit of 72 characters so we hash with SHA256,
	// then base 64 encode (to eliminate null bytes) first
	// hash() param 3 = true so output binary data
	$hashedPassword =  password_hash(base64_encode(hash('sha256', $password, true)), PASSWORD_DEFAULT);

	return $hashedPassword;
}


function login_checkPassword($password, $hashedPassword)
{
	// the PHP default password hash of bcrypt has a limit of 72 characters so we hash with SHA256,
	// then base 64 encode (to eliminate null bytes) first
	return password_verify(base64_encode(hash('sha256', $password, true)), $hashedPassword);
}


function login_getAdmins()
{
	$admins = array();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select display_name from users where is_admin = 1 order by display_name asc");
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $name);
		while (mysqli_stmt_fetch($st))
		{
                        $admins[] = [ "name" => $name ];
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $admins;
}
