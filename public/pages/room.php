<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("rooms.inc.php");
require_once("calendar.inc.php");
require_once("video.inc.php");
require_once("chat.inc.php");

$roomId = false;

if (isset($_GET["room"]))
{
	$roomId = rooms_getRoomIdFromUrl($_GET["room"]);
}

function noAccess()
{
	template_drawHeader(LANG["page_title_unknown_room"], null, "");
	echo LANG["room_no_room_found"];

	if (!login_isLoggedIn())
	{
		echo "<p>";
		echo LANG["room_try_logging_in"];
	}

	template_drawFooter();
	exit(0);
}

if ($roomId === false)
{
	noAccess();
}

if (!login_hasPreviewAccessToRoom($roomId))
{
	// private room and they don't (yet) have access

	// do they have an invitation?	
	if (isset($_GET["selector"]) && isset($_GET["token"]))
	{
		$selector = $_GET["selector"];
		$token = $_GET["token"];

		// is it valid?
		if (login_isInvitationValid($roomId, $selector, $token))
		{
			// are they logged in (i.e. can we make them a member)?
			if (login_isLoggedIn())
			{
				// make them a member and continue
				$error = rooms_addMemberToRoom($roomId);
				if ($error != "")
				{
					template_drawHeader(LANG["page_title_unknown_room"], null, "");
					echo $error;
					template_drawFooter();
					exit(0);
				}
			}
			else
			{
				// save invitation
				login_invitationPending($selector);

				// ask them to log in or create an account
				$bodyClass = "";
				$includeBlurb = false;
				$includeTopBar = true;
				$doingInvitation = true;
				rooms_drawHeaderForRoom($roomId, "", $bodyClass, $includeBlurb, $includeTopBar, $doingInvitation);
				invitationPrivateWelcome($roomId);
				template_drawFooter();
				exit(0);
			}
		}
		else
		{
			noAccess();
		}
	}
	else
	{
		noAccess();
	}
}

// handle invitations

if (!login_isLoggedIn() && isset($_GET["selector"]) && isset($_GET["token"]))
{
	$selector = $_GET["selector"];
	$token = $_GET["token"];

	$bodyClass = "";
	$includeBlurb = false;
	$includeTopBar = true;
	$doingInvitation = true;


	try
	{
		if (!login_isInvitationValid($roomId, $selector, $token))
		{
			rooms_drawHeaderForRoom($roomId, "", $bodyClass, $includeBlurb, $includeTopBar, $doingInvitation);
			invitationInvalid();
		}
		else
		{
			// valid invitation

			$nickname = "";
			$email = "";
			$nicknameError = "";
			$emailError = "";

			if (isset($_POST["nickname"]))
			{
				$nickname = trim($_POST["nickname"]);
				$isGuest = true;
				$nicknameError = login_checkNickname($nickname, $isGuest);
			}

			if (isset($_POST["email"]))
			{
				$email = trim($_POST["email"]);
				if ($email == "")
				{
					$emailError = LANG["invitation_member_email_error_invalid"];
				}
				else
				{
					$emailError = invitationSendEmail($email);
				}
			}

			if ($email != "" && $emailError == "")
			{
				// email sent
				rooms_drawHeaderForRoom($roomId, "", $bodyClass, $includeBlurb, $includeTopBar, $doingInvitation);
				invitationCheckSpamFolder($roomId);
			}
			else if ($nickname != "" && $nicknameError == "")
			{
				// got a valid nickname
				login_loginAsGuest($nickname, $roomId);

				// ask for email address
				rooms_drawHeaderForRoom($roomId, "", $bodyClass, $includeBlurb, $includeTopBar, $doingInvitation);
				invitationAskForEmail($roomId, $nickname, $email, $emailError);
			}
			else if (isset($_POST["continue-as-guest"]) || $nicknameError != "")
			{
				// haven't set a nickname yet, or it's not valid, so ask for one
				rooms_drawHeaderForRoom($roomId, "", $bodyClass, $includeBlurb, $includeTopBar, $doingInvitation);
				invitationAskForNickname($nickname, $nicknameError);
			}
			else
			{
				rooms_drawHeaderForRoom($roomId, "", $bodyClass, $includeBlurb, $includeTopBar, $doingInvitation);
				invitationWelcome($roomId);
			}
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		echo database_genericErrorMessage();
	}

	template_drawFooter();
	exit(0);
}


function invitationWelcome($roomId)
{
	echo "<p>";
	echo sprintf(LANG["invitation_welcome"], "<span class='invitation-room-name'>" . rooms_getTitleFromRoomId($roomId) . "</span>");
	echo "<p>";
	echo LANG["invitation_continue_as_guest_explain"];
	echo "<p>";
	echo "<form method='post' action='" . $_SERVER['REQUEST_URI'] . "'>";
	echo "<input type='submit' name='continue-as-guest' value='" . LANG["invitation_continue_as_guest"] . "'>";
	echo "</form>";
	echo "<p>";
	$loginLink = "<a href='" . PUBLIC_URL["login"] . "'>" . LANG["invitation_link_login"] . "</a>";
	$joinLink = "<a href='" . PUBLIC_URL["join"] . "'>" . LANG["invitation_link_join"] . "</a>";
	echo sprintf(LANG["invitation_alternatively"], $joinLink, $loginLink);
}


function invitationPrivateWelcome($roomId)
{
	echo "<p>";
	echo sprintf(LANG["invitation_private_welcome_1"], "<span class='invitation-room-name'>" . rooms_getTitleFromRoomId($roomId) . "</span>");
	echo "<p>";
	echo LANG["invitation_private_welcome_2"];
	echo "<p>";
	$loginLink = "<a href='" . PUBLIC_URL["login"] . "'>" . LANG["invitation_link_login"] . "</a>";
	$joinLink = "<a href='" . PUBLIC_URL["join"] . "'>" . LANG["invitation_link_join"] . "</a>";
	echo sprintf(LANG["invitation_private_welcome_3"], $joinLink, $loginLink);
}


function invitationInvalid()
{
	echo "<p>";
	echo LANG["invitation_expired_1"];
	echo "<p>";
	$loginLink = "<a href='" . PUBLIC_URL["login"] . "'>" . LANG["invitation_link_login"] . "</a>";
	$joinLink = "<a href='" . PUBLIC_URL["join"] . "'>" . LANG["invitation_link_join"] . "</a>";
	echo sprintf(LANG["invitation_expired_2"], $joinLink, $loginLink);
}


function invitationAskForNickname($nickname, $error)
{
	echo "<div id='nickname-form'>";

	if ($error != "")
	{
		echo "<div class='form-error'>";
		echo $error;
		echo "</div>";
	}

	?>
		<form method="post" autocorrect="off" autocapitalize="off" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
			<label>
				<?php echo LANG["invitation_nickname_prompt"] ?>
				<input type="text" name="nickname" value="<?php echo htmlspecialchars($nickname);?>" size=50 maxlength=255>
			</label>
			<input type="submit" name="next" value="<?php echo LANG["invitation_nickname_label_submit"] ?>">
		</form>

		</div>
	<?php
}


function invitationAskForEmail($roomId, $nickname, $email, $error)
{
	echo "<p>";
	echo LANG["invitation_guest_benefit"];
	echo "<ul class='drawbacks'>";
	foreach (LANG["invitation_guest_benefit_explain"] as $explanation)
	{
		echo "<li>";
		echo $explanation;
		echo "</li>";
	}
	echo "</ul>";

	echo "<p>";
	echo LANG["invitation_member_benefit"];

	echo "<ul class='benefits'>";
	foreach (LANG["invitation_member_benefit_explain"] as $explanation)
	{
		echo "<li>";
		echo $explanation;
		echo "</li>";
	}
	echo "</ul>";
	echo "<p>";

	?>
		<div id='join-form'>
			<form method="post" autocorrect="off" autocapitalize="off" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<input type="hidden" name="nickname" value="<?php echo htmlspecialchars($nickname);?>">
				<label>
					<?php echo LANG["invitation_member_email_prompt"] ?>
					<input type="email" name="email" placeholder='<?php echo LANG["invitation_member_email_placeholder"];?>' value="<?php echo htmlspecialchars($email);?>" size=50 maxlength=255>
				</label>
				<p>

				<?php
					if ($error != "")
					{
						echo "<div class='form-error'>";
						echo $error;
						echo "</div>";
					}
				?>

				<input type="submit" name="yes-join" value="<?php echo LANG["invitation_member_email_button_yes"] ?>">
			</form>
			<a href='<?php echo rooms_getUrlFromRoomId($roomId);?>'>
				<?php echo LANG["invitation_member_email_button_no"] ?>
			</a>
		</div>
	<?php
}


function invitationSendEmail($email)
{
	$error = "";

	try
	{
		if (!login_sendValidationEmail($email, true))
		{
			$error = LANG["login_sign_up_form_error"];
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		$error = database_genericErrorMessage();
	}

	return $error;
}


function invitationCheckSpamFolder($roomId)
{
	echo "<p>";
	echo LANG["invitation_member_email_thank_you"];
	echo "<p>";
	echo "<a class='link-looking-like-a-button' href='" . rooms_getUrlFromRoomId($roomId) . "'>";
	echo LANG["invitation_member_email_continue"];
	echo "</a>";
}


rooms_drawHeaderForRoom($roomId, "", "", true);

video_drawBannerAndFrame($roomId);

if (!rooms_isRoomPublic($roomId))
{
	module_drawModuleHeader("module-room-members", "rooms_members_title", false, false, false, "");
	rooms_drawMembersWindow($roomId);
	module_drawModuleFooter();
}

module_drawModuleHeader("module-room-calendar", "calendar_title_room_events", false, true, false, $_SERVER['REQUEST_URI'] . PUBLIC_URL["calendar"]);
calendar_drawCalendarWindow($roomId);
module_drawModuleFooter();

module_drawModuleHeader("module-room-video", "video_title", false, false, false, "");
video_drawVideoWindow($roomId);
module_drawModuleFooter();

module_drawModuleHeader("module-room-chat", "chat_title", false, true, false, $_SERVER['REQUEST_URI'] . PUBLIC_URL["chat"]);
chat_drawChatWindow($roomId);
module_drawModuleFooter();

template_drawFooter();
