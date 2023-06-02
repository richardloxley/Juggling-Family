<?php

require_once("module.inc.php");
require_once("symbol.inc.php");
require_once("time.inc.php");
require_once("rooms.inc.php");


function chat_drawChatWindow($roomId)
{
	chat_drawChatJavascript($roomId);

	module_clientStartBackgroundRefresh(API_URL["chat-get-messages"] . "?roomId=$roomId", "", "time_adjustDatesToLocalTimezone", "chat-messages", "module_refresh_chat", true);

        ?>
		<div id="chat-box">
			<div id="chat-messages" class="module-background-update <?php if (!login_hasFullAccessToRoom($roomId)) echo 'chat-not-logged-in'; ?>">
				<div class='chat-message-retention'>
	<?php
					if ($roomId == rooms_getMainTextChatRoomId())
					{
						$retentionHours = CONFIG["chat_retention_hours_main_page"];
					}
					else
					{
						$retentionHours = CONFIG["chat_retention_hours_rooms"];
					}

					$alternativeLink = "<a href='" . CONFIG["chat_permanent_alternative_url"] . "' target='_blank'>" .
									 CONFIG["chat_permanent_alternative_description"] . "</a>";

					if ($retentionHours < 48)
					{
						echo sprintf(LANG["chat_message_retention_hours"], $retentionHours, $alternativeLink);
					}
					else
					{
						echo sprintf(LANG["chat_message_retention_days"], $retentionHours / 24, $alternativeLink);
					}
	?>
				</div>
			</div>

	<?php
			if (login_hasFullAccessToRoom($roomId))
			{
        ?>
				<div class='show-if-has-js'>
					<form name="message" action="">
						<textarea name="message-text" id="message-text" rows=1 maxlength=2000 placeholder="<?php echo LANG['chat_message_input_placeholder']; ?>"></textarea>
						<input name="message-send" type="submit" id="message-send" value="<?php echo icon_sendMessage();?>"/>
						<input name="message-thumbs-up" type="submit" id="message-thumbs-up" value="<?php echo icon_thumbsUp();?>"/>
					</form>
				</div>
				<div class='show-if-no-js'>
					<div id='message-no-chat'>
						<?php echo LANG['chat_message_no_javascript']; ?>
					</div>
				</div>
	<?php
			}
			else
			{
        ?>
				<div id='message-no-chat'>
					<?php echo LANG['chat_message_not_logged_in']; ?>
				</div>
	<?php
			}
        ?>
		</div>
	<?php
}


function chat_drawChatJavascript($roomId)
{
	// When the message input textarea isn't big enough for the message being typed, expand it
	// vertically to fit (up to a maximum of 80% of the height of the container, so they still
	// have 20% to see the previous messages). Note that we have to shrink it first with "height: auto"
	// to make sure it contracts when the text is removed. The textarea must have overflow:hidden
	// set, otherwise the appearing/disappearing scrollbar affects the height calculation.
	//
	// Then reduce the height of the message history area to compensate (leaving a 10px margin).
	//
	// Scroll the message history area so the message at the bottom is still in view.

	$refreshNowFunctionName = module_clientRefreshNowJsFunctionName("chat-messages");

        ?>
		<script type="text/javascript">

			function resizeChatMessages()
			{
				$('#message-text').css('height', 'auto');
				var newTextHeight = Math.min($('#message-text').prop("scrollHeight"), $('#chat-box').innerHeight() * 0.8);
				$('#message-text').innerHeight(newTextHeight);

				// message-text is missing if user isn't logged in, so use message-no-chat instead
				var protectedHeight = Math.max($('#message-text').outerHeight(), $('#message-no-chat').outerHeight());

				var oldMessagesHeight = $('#chat-messages').outerHeight();
				var oldMessagesScroll = $('#chat-messages').scrollTop();
				var newMessagesHeight = $('#chat-box').innerHeight() - protectedHeight - 10;
				var newMessagesScroll = oldMessagesScroll + (oldMessagesHeight - newMessagesHeight);
				$('#chat-messages').outerHeight(newMessagesHeight);
				$('#chat-messages').scrollTop(newMessagesScroll);
			}

			function updateSendButtons()
			{
				if ($('#message-text').val().length == 0)
				{
					$('#message-send').hide();
					$('#message-thumbs-up').show();
				}
				else
				{
					$('#message-send').show();
					$('#message-thumbs-up').hide();
				}
			}

			$(document).ready(function()
			{
				// initial sizing of elements
				resizeChatMessages();

				$('#message-text').keydown(function(e)
				{
					// enter submits form (but allow shift-enter to still insert a new line)
					if (e.which == 13 && !e.shiftKey)
					{
						$("#message-send").click();
						e.preventDefault();
					}
				});

				$('#message-text').keyup(function(e)
				{
					// change between send and thumbs up
					updateSendButtons();

					// resize elements according to size of entered message
					resizeChatMessages();
				});

				$("#message-send").click(function()
				{
					var message = $("#message-text").val();
					if (message.length > 0)
					{
						// post the message
						$.post("<?php echo API_URL["chat-send-message"]; ?>", {roomId: <?php echo $roomId; ?>, text: message});
						// clear entry box
						$("#message-text").val("");
						// resize box as it's now empty
						resizeChatMessages();
						// update history
						<?php echo $refreshNowFunctionName; ?>(false);
						// change between send and thumbs up
						updateSendButtons();
					}
					// don't submit form
					return false;
				});

				$("#message-thumbs-up").click(function()
				{
					// post the thumbs up
					$.post("<?php echo API_URL["chat-send-message"]; ?>", {roomId: <?php echo $roomId; ?>, text: ":thumb:"});
					// deselect thumbs up button
					$(this).blur();
					// update history
					<?php echo $refreshNowFunctionName; ?>(false);
					// don't submit form
					return false;
				});

				// if window resizes, resize chat messages
				$(window).resize(resizeChatMessages);

				// if module becomes visible, resize chat messages
				$('#message-text').closest(".module-id").bind("show", resizeChatMessages);
			});

		</script>
	<?php
}


function chat_apiSendMessage()
{
	if (!isset($_POST["roomId"]) || !isset($_POST["text"]))
	{
		return;
	}

	$roomId = intval($_POST["roomId"]);

	if (!login_hasFullAccessToRoom($roomId))
	{
		return;
	}

	$message = $_POST["text"];

	// check room exists
	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select room_id from rooms where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $foundId);
		$exists = mysqli_stmt_fetch($st);
		mysqli_stmt_close($st);

		if (!$exists)
		{
			error_log(__FILE__ . ":" . __LINE__ . " bad room ID for chat message");
			return;
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	// post message
	try
	{
		$db = database_getConnection();

		if (login_isGuest())
		{
			$guestName = login_getDisplayName();
			$st = mysqli_prepare($db, "insert into chat (room_id, post_time, guest_display_name, message) values (?, UTC_TIMESTAMP(), ?, ?)");
			mysqli_stmt_bind_param($st, "iss", $roomId, $guestName, $message);
		}
		else
		{
			$userId = login_getUserId();
			$st = mysqli_prepare($db, "insert into chat (room_id, post_time, user_id, message) values (?, UTC_TIMESTAMP(), ?, ?)");
			mysqli_stmt_bind_param($st, "iis", $roomId, $userId, $message);
		}

		if (!mysqli_stmt_execute($st))
		{
			error_log(__FILE__ . ":" . __LINE__ . " failed to insert chat message ");
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	rooms_markRoomAsUsed($roomId);
	rooms_markRoomAsChanged($roomId);
}


function chat_apiGetMessages()
{
	module_serverCheckForNewContent("chat_getMessagesContentFunction");
}


// contentFunction() takes
//	a string representing the last entry output to the user (interpretted however the function likes)
//	the time the server last sent new output (as received from the server previously)
// if there is new content, it returns an array:
//	"lastId" => the new last entry,
//	"html" => the rendered output
// if there is no new content, it returns false
function chat_getMessagesContentFunction($lastId, $lastTime)
{
	chat_removeOldMessages();

	if (!isset($_GET["roomId"]))
	{
		return false;
	}

	$roomId = intval($_GET["roomId"]);

	if (!login_hasPreviewAccessToRoom($roomId))
	{
		return false;
	}

	$obscureChat = true;
	if (login_hasFullAccessToRoom($roomId))
	{
		$obscureChat = false;
	}

	$guestSuffix = " " . CONFIG["site_guest_suffix"];

	$newLastId = -1;
	$html = "";

	try
	{
		// could be null if not logged in
		$userId = login_getUserId();

		$guestName = "";

		if (login_isGuest())
		{
			$guestName = login_getDisplayName();
		}

		$db = database_getConnection();
		$st = mysqli_prepare($db, "select message_id, post_time, message, ifnull(display_name, guest_display_name) as name, (user_id = ? or binary guest_display_name = ?) as mine from chat left join users using (user_id) where room_id = ? and message_id > ? order by message_id asc");
		mysqli_stmt_bind_param($st, "isii", $userId, $guestName, $roomId, $lastId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $messageId, $postTime, $message, $displayName, $mine);

		while (mysqli_stmt_fetch($st))
		{
			$newLastId = $messageId;

			$htmlDisplayName = htmlspecialchars($displayName);

			$htmlMessage = htmlspecialchars($message);
			$htmlMessage = template_replaceEmoticons($htmlMessage);

			if ($obscureChat)
			{
				// obscure names and messages for non-members
				$htmlDisplayName = chat_obscureText($htmlDisplayName);
				$htmlMessage = chat_obscureText($htmlMessage);
			}
			else
			{
				// make links clickable
				$htmlMessage = template_replaceUrls($htmlMessage);
			}

			$htmlMessage = template_replaceControlCodes($htmlMessage);

			$displayTime = time_humanDisplayDate($postTime);

			if ($mine)
			{
				// don't display name since it's me
				$html .= "<div class='my-text-message'>";
			}
			else
			{
				$html .= "<div class='text-message'>";
				$html .= "<div class='post-name'>";
				$html .= $htmlDisplayName;
				$html .= "</div>";
			}

			$html .= "<div class='post-message'>";
			$html .= $htmlMessage;
			$html .= "<div class='post-time-wrapper'>";
			$html .= "<div class='post-time'>";
			$html .= $displayTime;
			$html .= "</div>";
			$html .= "</div>";
			$html .= "</div>";

			$html .= "</div>";

			$html .= "<div class='end-of-message'>";
			$html .= "</div>";
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	if ($newLastId < 0)
	{
		return false;
	}

	chat_markAsRead($roomId, $newLastId);

	return [ "lastId" => $newLastId, "html" => $html ];
}


function chat_removeOldMessages()
{
	$mainRoomId = rooms_getMainTextChatRoomId();
	$mainPageRetentionHours = CONFIG["chat_retention_hours_main_page"];
	$roomRetentionHours = CONFIG["chat_retention_hours_rooms"];

	try
	{
		$db = database_getConnection();

		// main page
		$st = mysqli_prepare($db, "delete from chat where room_id = ? and post_time < date_sub(UTC_TIMESTAMP(), interval ? hour)");
		mysqli_stmt_bind_param($st, "ii", $mainRoomId, $mainPageRetentionHours);
		if (!mysqli_stmt_execute($st))
		{
			error_log(__FILE__ . ":" . __LINE__ . " failed to delete chat messages");
		}
		mysqli_stmt_close($st);

		// rooms
		$st = mysqli_prepare($db, "delete from chat where room_id != ? and post_time < date_sub(UTC_TIMESTAMP(), interval ? hour)");
		mysqli_stmt_bind_param($st, "ii", $mainRoomId, $roomRetentionHours);
		if (!mysqli_stmt_execute($st))
		{
			error_log(__FILE__ . ":" . __LINE__ . " failed to delete chat messages");
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function chat_deleteChatsInRoom($roomId)
{
	try
	{
		// delete chats
		$db = database_getConnection();
		$st = mysqli_prepare($db, "delete from chat where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);

		// delete chat read entries as the room is being deleted
		$st = mysqli_prepare($db, "delete from chat_read where room_id = ?");
		mysqli_stmt_bind_param($st, "i", $roomId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function chat_markAsRead($roomId, $newLastId)
{
	if (!login_isLoggedIn())
	{
		return;
	}

	$userId = login_getUserId();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "insert into chat_read (user_id, room_id, last_message_id) values (?, ?, ?) on duplicate key update last_message_id = ?");
		mysqli_stmt_bind_param($st, "iiii", $userId, $roomId, $newLastId, $newLastId);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}
}


function chat_unreadMessages($roomId)
{
	$numUnread = 0;

	try
	{
		$db = database_getConnection();

		$lastRead = 0;

		if (login_isLoggedIn())
		{
			$userId = login_getUserId();

			$st = mysqli_prepare($db, "select last_message_id from chat_read where user_id = ? and room_id = ?");
			mysqli_stmt_bind_param($st, "ii", $userId, $roomId);
			mysqli_stmt_execute($st);
			mysqli_stmt_bind_result($st, $lastId);
			if (mysqli_stmt_fetch($st))
			{
				$lastRead = $lastId;
			}
			mysqli_stmt_close($st);
		}

		$st = mysqli_prepare($db, "select count(*) as num_messages from chat where room_id = ? and message_id > ?");
		mysqli_stmt_bind_param($st, "ii", $roomId, $lastRead);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $numMessages);
		if (mysqli_stmt_fetch($st))
		{
			$numUnread = $numMessages;
		}
		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $numUnread;
}


function chat_drawActiveUsers($roomId, $label)
{
	$users = chat_getActiveUsersIn($roomId);
	rooms_drawActiveUsers($roomId, $users, $label);
}


function chat_getActiveUsersIn($roomId)
{
	$users = array();

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select ifnull(display_name, guest_display_name) as name from chat left join users using (user_id) where room_id = ? and (post_time > date_sub(UTC_TIMESTAMP(), interval ? minute)) group by name order by post_time desc");
		$timeout = CONFIG["chat_active_timeout_minutes"];
		mysqli_stmt_bind_param($st, "ii", $roomId, $timeout);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $name);

		while (mysqli_stmt_fetch($st))
		{
			$users[] = [ "name" => $name ];
		}

		mysqli_stmt_close($st);
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
	}

	return $users;
}


function chat_obscureText($text)
{
	$newText = "";

	$upperCase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$lowerCase = "abcdefghijklmnopqrstuvwxyz";
	$numbers = "0123456789";

	$len = strlen($text);
	for ($x = 0; $x < $len; $x++)
	{
		if ($text[$x] == "&")
		{
			// doing an HTML entity, so leave it unmodified
			while ($x < $len && strpos("&#;xX0123456890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", $text[$x]) !== false)
			{
				$newText .= $text[$x];
				$x++;
			}
		}

		if ($x < $len)
		{
			$c = $text[$x];
			$c = chat_changeCharacterMatching($c, $lowerCase);
			$c = chat_changeCharacterMatching($c, $upperCase);
			$c = chat_changeCharacterMatching($c, $numbers);
			$newText .= $c;
		}
	}

	return $newText;
}


// if the character matches one in the string, replace it with a random character from that string
function chat_changeCharacterMatching($c, $string)
{
	if (strpos($string, $c) === false)
	{
		return $c;
	}
	else
	{
		$r = rand(0, strlen($string) - 1);
		return $string[$r];
	}
}
