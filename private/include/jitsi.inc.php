<?php


function jitsi_jitsiDomain()
{
	return CONFIG["jitsi_domain"];
}


function jitsi_deeplink($jitsiRoomId, $roomName)
{
	if (mobile_isApple())
	{
		return jitsi_deeplinkIos($jitsiRoomId, $roomName);
	}

	if (mobile_isAndroid())
	{
		return jitsi_deeplinkAndroid($jitsiRoomId, $roomName);
        }

	return "";
}


function jitsi_webLink($jitsiRoomId, $roomName)
{
	$userNickname = login_getDisplayName();
	return "https://" . jitsi_jitsiDomain() . jitsi_optionsAsUrl($jitsiRoomId, $roomName, $userNickname);
}


function jitsi_deeplinkIos($jitsiRoomId, $roomName)
{
	$userNickname = login_getDisplayName();
	return "org.jitsi.meet://" . jitsi_jitsiDomain() . jitsi_optionsAsUrl($jitsiRoomId, $roomName, $userNickname);
}


function jitsi_deeplinkAndroid($jitsiRoomId, $roomName)
{
	$userNickname = login_getDisplayName();
	return "intent://" . jitsi_jitsiDomain() . jitsi_optionsAsUrl($jitsiRoomId, $roomName, $userNickname) . "#Intent;scheme=org.jitsi.meet;package=org.jitsi.meet;end";
}


function jitsi_configOptions()
{
	// for defaults see https://github.com/jitsi/jitsi-meet/blob/master/config.js

	$config = array
	(
		// open in mobile browser rather than asking to install app
		"disableDeepLinking" => true,

		// don't allow them to record it
		"localRecording" => array("enabled" => false),

		// everyone should have a display name, it's common courtesy :-)
		"requireDisplayName" => true,

		// if using the app, don't put the room name in the recently used list
		// as we use hidden room names, and we also don't want people joining
		// rooms without having gone through our site
		"doNotStoreRoom" => true,

		// don't allow them to invite anyone else to the room
		"disableInviteFunctions" => true,

		// don't show the "pre-join" screen
		"prejoinPageEnabled" => false,

		// when call is bigger than 25 people, new people joining must turn on camera and microphone manually
		// (increased from default of 9)
		"startVideoMuted" => 25,
		"startAudioMuted" => 25,

		// they can't change their name
		"readOnlyName" => true,

		// hide controls after 5 seconds
		"toolbarConfig" => array
		(
			"initialTimeout" => 5000,
			"timeout" => 5000,
			"alwaysVisible" => false
		),

		// commented out options are items that are available but we've
		// chosen to remove either because they are undesirable for our
		// application or clutter up the UI unnecessarily for new users
		// without any real benefit
		"toolbarButtons" => array
		(
			'camera',
			'chat',
			//'closedcaptions',
			'desktop',
			//'dock-iframe',
			//'download',
			//'embedmeeting',
			//'etherpad',
			//'feedback',
			'filmstrip',
			'fullscreen',
			'hangup',
			//'help',
			//'highlight',
			//'invite',
			//'linktosalesforce',
			//'livestreaming',
			'microphone',
			//'mute-everyone',
			//'mute-video-everyone',
			'noisesuppression',
			'participants-pane',
			//'profile',
			//'raisehand',
			//'recording',
			//'security',
			'select-background',
			'settings',
			//'shareaudio',
			//'sharedvideo',
			'shortcuts',
			'stats',
			'tileview',
			'toggle-camera',
			//'undock-iframe',
			'videoquality',
			'__end'
		),

		/////// performance related as Jitsi consumes very high CPU on older machines

		// don't show the blue dots for audio levels as the JavaScript rendering takes
		// up hige amounts of CPU just as we're trying to play the audio, making the
		// audio choppy
		// (now allow user to control that via video quality setting)
		//"disableAudioLevels" => true,

		// whilst H264 supports hardware acceleration, it doesn't support simulcast
		// meaning everyone would get 720p video even if they have a slow connection
		"disableH264" => true,

		// limit framerates - this can give quite a big boost to performance without
		// impacting "talking heads" video too much
		"constraints" => array
		(
			"video" => array
			(
				"frameRate" => array
				(
					"ideal" => CONFIG["jitsi_framerate_ideal"],
					"max" => CONFIG["jitsi_framerate_max"]
				)
			)
		)
	);

	return $config;
}


function jitsi_interfaceOptions()
{
	// for defaults see https://github.com/jitsi/jitsi-meet/blob/master/interface_config.js

	$interface = array
	(
		"SETTINGS_SECTIONS" => array
		(
			'devices',
			'language',
			//'moderator',
			//'calendar',
			//'profile',
			'sounds'
		),

		// make the warnings about muted microphones, etc, disappear after 5 sec
		"ENFORCE_NOTIFICATION_AUTO_DISMISS_TIMEOUT" => 5000,

		// don't use "Fellow Jitser"
		"DEFAULT_REMOTE_DISPLAY_NAME" => LANG["video_anonymous_user"]
	);

	return $interface;
}


function jitsi_optionsAsJs($nickname)
{
	$options = array
	(
		"userInfo" => array
		(
			"displayName" => $nickname,
		),
		"width" => "100%",
		"height" => "100%",
		"configOverwrite" => jitsi_configOptions(),
		"interfaceConfigOverwrite" => jitsi_interfaceOptions()
	);

	// we override the actions on these buttons if running in JS
	$options["configOverwrite"]["buttonsWithNotifyClick"] = array
	(
		'fullscreen',
		'participants-pane'
	);


	return json_encode($options);
}


function jitsi_optionsAsUrl($jitsiRoomId, $roomName, $nickname)
{
	$url = "/$jitsiRoomId#jitsi_meet_external_api_id=0";

	$url .= "&userInfo.displayName=" . rawurlencode(json_encode($nickname));

	$url .= "&config.subject=" . rawurlencode(json_encode($roomName));

	foreach (jitsi_configOptions() as $key => $value)
	{
		$url .= "&config." . $key . "=" . rawurlencode(json_encode($value));
	}

	foreach (jitsi_interfaceOptions() as $key => $value)
	{
		$url .= "&interfaceConfig." . $key . "=" . rawurlencode(json_encode($value));
	}

	return $url;
}


function jitsi_drawJitsiJs($roomId, $frameId)
{
	$userNickname = login_getDisplayName();
	$popupDuration = settings_getSetting("video-text-popup-duration", CONFIG["jitsi_text_chat_popup_duration_seconds"]);
	if (settings_getSetting("video-full-screen", CONFIG["jitsi_full_screen"]))
	{
		$fullScreen = "true";
	}
	else
	{
		$fullScreen = "false";
	}

	?>
		<script src='https://<?php echo CONFIG["jitsi_domain"]; ?>/external_api.js'></script>

		<script type="text/javascript">

			//# sourceURL=jitsi.inc.php.js

			const nameUpdateURL = "<?php echo API_URL["video-participant-changed"] ;?>";

			const OCCUPANT_REFRESH_TIME = <?php echo CONFIG["jitsi_occupant_refresh_time_seconds"] * 1000; ?>;
			const TEXT_POPUP_ANIMATION_TIME = <?php echo CONFIG["jitsi_text_chat_popup_animation_milliseconds"]; ?>;
			const TEXT_POPUP_DISPLAY_TIME = <?php echo $popupDuration * 1000; ?>;

			const VOLUME_SLIDER_RANGE = 100;
			const VOLUME_MAX_MULTIPLIER = 10;
			// we use a log scale for relative volumes
			const VOLUME_LOG_STEP = Math.log10(VOLUME_MAX_MULTIPLIER) / VOLUME_SLIDER_RANGE;

			var api;
			var occupantTimer = null;
			var messageTimer = null;
			var myId = "";
			var fullScreen = false;

			var volumePopupVisible = false;
			var userVolumes = [];

			var videoId;
			var jitsiRoomId;
			var roomTitle;

			const domain = "<?php echo jitsi_jitsiDomain();?>";
			const userNickname = "<?php echo $userNickname;?>";
			const frameId = "<?php echo $frameId;?>";
			const testChatBroadcastPrefix = "<?php echo CONFIG['jitsi_text_chat_broadcast_prefix']; ?>";

			function startVideo(theVideoId, theJitsiRoomId, theRoomTitle)
			{
				// set globals
				videoId = theVideoId;
				jitsiRoomId = theJitsiRoomId;
				roomTitle = theRoomTitle;

				// get default site options
				var options = <?php echo jitsi_optionsAsJs($userNickname);?>;

				// set room ID
				options["roomName"] = jitsiRoomId;

				// put our title at the top instead of the Jitsi room ID
				options["configOverwrite"]["subject"] = roomTitle;

				// link to frame
				options["parentNode"] = document.querySelector("#" + frameId);

				// start up Jitsi
				api = new JitsiMeetExternalAPI(domain, options);

				api.addListener('participantJoined', participantJoined);
				api.addListener('participantKickedOut', participantKickedOut);
				api.addListener('participantLeft', participantLeft);
				api.addListener('displayNameChange', displayNameChange);
				api.addListener('videoConferenceJoined', videoConferenceJoined);
				api.addListener('videoConferenceLeft', videoConferenceLeft);
				api.addListener('incomingMessage', incomingMessage);
				api.addListener('outgoingMessage', outgoingMessage);
				api.addListener('toolbarButtonClicked', toolbarButtonClicked);

				window.onbeforeunload = videoConferenceLeft;
				window.onunload = videoConferenceLeft;
				window.onpagehide = videoConferenceLeft;

				$("#video-chat-popup-outer").click(function()
				{
					api.executeCommand('toggleChat');
				});

				// show top controls
				$("#video-bottom-controls").show();

				/* if start in fullscreen */
				if (<?php echo $fullScreen; ?>)
				{
					fullScreen = true;
					document.documentElement.requestFullscreen();
				}

				$("#video-volume-control-popup-reset-button").click(function()
				{
					resetAllVolumes();
				});

				$("#video-volume-control-popup-close-button").click(function()
				{
					volumePopupVisible = false;
					$("#video-volume-control-popup").hide();
				});
			};

			function toolbarButtonClicked(data)
			{
				debug("toolbarButtonClicked");
				if (data.key == "fullscreen")
				{
					if (fullScreen)
					{
						fullScreen = false;
						document.exitFullscreen();
					}
					else
					{
						fullScreen = true;
						document.documentElement.requestFullscreen();
					}
				}
				else if (data.key == "participants-pane")
				{
					if (volumePopupVisible)
					{
						volumePopupVisible = false;
						$("#video-volume-control-popup").hide();
					}
					else
					{
						volumePopupVisible = true;
						updateVolumeControls();
						$("#video-volume-control-popup").show();
					}
				}
			};

			function setInitialVolumeForUser(id)
			{
				debug("setInitialVolumeForUser");
				debug(id);

				// see if we've already got this ID
				var foundUser = userVolumes.find(function(user, index)
				{
					if (user.id == id)
					{
						return true;
					}
				});

				if (foundUser)
				{
					return;
				}

				// we're adding a new entry
				const name = api.getDisplayName(id);
				var newVolumeSlider = 0;

				// see if this user already has a volume set under a different ID
				// (e.g. because they left and rejoined)
				var foundUser = userVolumes.find(function(user, index)
				{
					if (user.name == name)
					{
						return true;
					}
				});

				if (foundUser)
				{
					newVolumeSlider = foundUser.volumeSlider;
				}

				// add this user ID
				userVolumes.push(
				{
					id: id,
					name: name,
					volumeSlider: newVolumeSlider
				});

				// sort by name
				userVolumes.sort((a, b) =>
				{
					var aLower = a.name.toLowerCase();
					var bLower = b.name.toLowerCase();
					if (aLower < bLower)
					{
						return -1;
					}
					if (aLower > bLower)
					{
						return 1;
					}
					return 0;
				});

				// update UI if necessary
				updateVolumeControls();

				// update new volumes
				updateUserVolumes();
			};

			function resetAllVolumes()
			{
				// start again from scratch so we delete users who have left
				userVolumes = [];

				var participants = api.getParticipantsInfo();
				participants.forEach((user) =>
				{
					var id = user.participantId;
					var name = user.displayName;

					if (id != myId)
					{
						userVolumes.push(
						{
							id: id,
							name: name,
							volumeSlider: 0
						});
					}
				});

				// update UI if necessary
				updateVolumeControls();

				// update new volumes
				updateUserVolumes();
			};

			function updateUserVolumes()
			{
				debug("updateUserVolumes");

				// find who is the loudest
				var highestSlider = -VOLUME_SLIDER_RANGE;
				userVolumes.forEach((user) =>
				{
					//debug("found slider @ " + user.volumeSlider);
					if (user.volumeSlider > highestSlider)
					{
						//debug("bigger than last");
						highestSlider = user.volumeSlider;
					}
				});

				highest = 10 ** (VOLUME_LOG_STEP * highestSlider);

				//debug("highest slider = " + highestSlider);
				//debug("highest = " + highest);

				// set each user's volume relative to the highest
				userVolumes.forEach((user) =>
				{
					var relativeVolume = 10 ** (VOLUME_LOG_STEP * user.volumeSlider);
					var absoluteVolume = relativeVolume / highest;

					//debug("relativeVolume = " + relativeVolume);
					//debug("absoluteVolume = " + absoluteVolume);

					if (absoluteVolume < 0)
					{
						absoluteVolume = 0;
					}

					if (absoluteVolume > 1)
					{
						absoluteVolume = 1;
					}

					debug("setting user " + user.id + " " + user.name + " to " + absoluteVolume);

					api.executeCommand('setParticipantVolume', user.id, absoluteVolume);
				});
			};

			function updateVolumeControls()
			{
				debug("updateVolumeControls");

				if (!volumePopupVisible)
				{
					return;
				}

				var participants = api.getParticipantsInfo();

				var html = "<table>";
				userVolumes.forEach((user) =>
				{
					var id = user.id;
					var name = user.name;
					var volumeSlider = user.volumeSlider;

					debug(id);

					// only display volume control if that user is still on the call
					// we don't delete their volume from the array if they leave for two reasons:
					//	1. they may come back
					//	2. we don't want to adjust other people's volume just because someone has left
					var foundUser = participants.find(function(user, index)
					{
						if (user.participantId == id)
						{
							return true;
						}
					});

					if (foundUser)
					{
						debug("found");
						html += "<tr>";
						html += "<td>" + name + "</td>";
						html += "<td>";
						html += "<div class='video-volume-slider-outer'>";
						html += "<input type='range' class='video-volume-slider' id='video-volume-slider-" + id + "' name='video-volume-slider-" + id + "' ";
						html += "min='-" + VOLUME_SLIDER_RANGE + "' max='" + VOLUME_SLIDER_RANGE + "' value='" + volumeSlider + "'>";
						html += "</div>";
						html += "</td>";
						html += "</tr>";
					}
				});
				html += "</tr></table>";

				// draw it
				$("#video-volume-control-popup-user-list").html(html);

				// monitor for changes
				$(".video-volume-slider").on('input change', function()
				{
					volumeSliderChanged($(this));
				});
			};

			function volumeSliderChanged(slider)
			{
				//debug("volumeSliderChanged");

				var id = slider.attr("name").replace("video-volume-slider-", "");
				var newVolumeSlider = parseFloat(slider.val());

				//debug(id);
				//debug(newVolumeSlider);

				var foundUser = userVolumes.find(function(user, index)
				{
					if (user.id == id)
					{
						return true;
					}
				});

				if (foundUser)
				{
					foundUser.volumeSlider = newVolumeSlider;
					updateUserVolumes();
				}
				else
				{
					debug("user not found");
				}
			};

			function incomingMessage(data)
			{
				debug("incomingMessage");

				if (TEXT_POPUP_DISPLAY_TIME > 0)
				{
					var message = data.message;
					if (data.privateMessage)
					{
						message = "(Private message) " + message;
					}

					$("#video-chat-popup-name").html(data.nick + ": ");
					$("#video-chat-popup-message").html(message);
					var height = $("#video-chat-popup").outerHeight();
					$("#video-chat-popup").css({top: -height});
					$("#video-chat-popup").show();
					$("#video-chat-popup").animate({top: 0}, {duration: TEXT_POPUP_ANIMATION_TIME, queue: false});
					restartMessageTimer();
				}
			}

			function restartMessageTimer()
			{
				debug("restartMessageTimer");

				if (messageTimer !== null)
				{
					clearTimeout(messageTimer);
				}

				messageTimer = setTimeout(hideMessagePopup, TEXT_POPUP_DISPLAY_TIME + TEXT_POPUP_ANIMATION_TIME);
			}

			function hideMessagePopup()
			{
				debug("hideMessagePopup");

				var height = $("#video-chat-popup").outerHeight();
				$("#video-chat-popup").animate({top: -height}, {duration: TEXT_POPUP_ANIMATION_TIME, queue: false});
			}

			function stopOccupantTimer()
			{
				if (occupantTimer !== null)
				{
					clearTimeout(occupantTimer);
					occupantTimer = null;
				}
			}

			function outgoingMessage(data)
			{
				debug("outgoingMessage");

				var message = data.message;
				const isPrivate = data.privatemessage;

				if (!isPrivate && message.startsWith(testChatBroadcastPrefix))
				{
					message = message.substring(testChatBroadcastPrefix.length);
					message = message.trim();
					message = testChatBroadcastPrefix + " " + message;
					$.post("<?php echo API_URL["chat-send-message"]; ?>", {roomId: <?php echo $roomId; ?>, text: message});
				}
			}

			function sendAllOccupantsToServer()
			{
				debug("sendAllOccupantsToServer");

				// if my ID isn't set, it's because I'm not yet in a meeting, or I'm in the process of being kicked
				// out of a meeting, so we don't want to send anything to the server
				if (myId == "")
				{
					return;
				}

				var participants = api.getParticipantsInfo();
				var users = {};

				for (var x = 0; x < participants.length; x++)
				{
					var id = participants[x].participantId;
					var name = participants[x].displayName;
					users[id] = name;
				}

				// We've just joined, and before we got the "videoConferenceJoined" notification we got "participantJoined"
				// notifications for all the other users in the room.  So we can now send a definitive list of users to the server.
				// This helps "reset" the server's view of who's in the room, as we can have phantom users left if the last people
				// to leave the room don't have JavaScript enabled (e.g. mobile app users).

				debug(JSON.stringify(users));

				fetch(nameUpdateURL + '?video=' + videoId + '&myid=' + encodeURIComponent(myId) + '&allusers=' + encodeURIComponent(JSON.stringify(users)));
				restartOccupantTimer();
			}

			function restartOccupantTimer()
			{
				debug("restartOccupantTimer");

				stopOccupantTimer();
				occupantTimer = setTimeout(sendAllOccupantsToServer, OCCUPANT_REFRESH_TIME);
			}

			function stopOccupantTimer()
			{
				if (occupantTimer !== null)
				{
					clearTimeout(occupantTimer);
					occupantTimer = null;
				}
			}


			///////////////////////// callbacks

			function tileChanged(newState)
			{
				// remove listener as we only want to change mode the first time - 
				// we're not going to stop the user manually changing back
				api.removeListener('tileViewChanged', tileChanged);

				if (!newState.enabled)
				{
					// if we haven't started up in tile view, enable it
					api.executeCommand('toggleTileView');
				}
			};

			function participantJoined(data)
			{
				debug("participantJoined");

				const id = data.id;
				const name = api.getDisplayName(id);

				debug(id);
				debug(name);

				setInitialVolumeForUser(id);
				sendAllOccupantsToServer();
			};

			function participantKickedOut(data)
			{
				debug("participantKickedOut");

				const id = data.kicked.id;

				debug(id);

				// update UI if necessary
				updateVolumeControls();

				// tell the server that someone's left
				fetch(nameUpdateURL + '?video=' + videoId + '&myid=' + encodeURIComponent(myId) + '&kicked=' + encodeURIComponent(id));

				// Was it me that was kicked out?  If so, reset my user ID as I'm about to get loads of notifications that
				// everyone else has apparently left, and I don't want to send those to the server!
				if (id == myId)
				{
					myId = "";
				}
			};

			function participantLeft(data)
			{
				debug("participantLeft");

				// update UI if necessary
				updateVolumeControls();

				// if my ID isn't set, it's because I'm not yet in a meeting, or I'm in the process of being kicked
				// out of a meeting, so we don't want to send anything to the server
				if (myId == "")
				{
					return;
				}

				const id = data.id;

				debug(id);

				// we don't send updates about people having left, in case they only *appear* to be leaving because
				// we've shut down the conference ourselves - instead let the timer handle it in due course
				// (if they aren't using the mobile app, they'll probably tell the server themselves anyway).
				restartOccupantTimer();
			};

			function displayNameChange(data)
			{
				debug("displayNameChange");

				const id = data.id;
				const name = api.getDisplayName(id);

				debug(id);
				debug(name);

				if (id == myId && name != userNickname && !name.endsWith("[" + userNickname + "]"))
				{
					// we've tried to change our own name, so add our "official" name to the end in brackets
					api.executeCommand('displayName', name + " [" + userNickname + "]");
				}

				// update UI if necessary
				updateVolumeControls();

				sendAllOccupantsToServer();
			};

			function videoConferenceJoined(data)
			{
				debug("videoConferenceJoined");

				myId = data.id;
				const name = api.getDisplayName(myId);

				debug(myId);
				debug(name);

				// We've just joined, and before we got the "videoConferenceJoined" notification we got "participantJoined"
				// notifications for all the other users in the room.  So we can now send a definitive list of users to the server.
				// This helps "reset" the server's view of who's in the room, as we can have phantom users left if the last people
				// to leave the room don't have JavaScript enabled (e.g. mobile app users).

				sendAllOccupantsToServer();
			};

			function videoConferenceLeft(data)
			{
				debug("videoConferenceLeft");
				debug(data.roomName);

				// hide controls
				$("#video-volume-control-popup").hide();
				$("#video-bottom-controls").hide();
				$("#video-chat-popup").hide();

				// show banner to return to main site
                                $("#video-end-banner").show();

				// if my ID isn't set, it's because I'm not yet in a meeting, or we've already told the server
				// we've left, so we don't want to send anything to the server
				if (myId == "")
				{
					return;
				}

				stopOccupantTimer();

				// notify that we've left
				fetch(nameUpdateURL + '?video=' + videoId + '&myid=' + encodeURIComponent(myId) + '&left=' + encodeURIComponent(myId));

				// reset my ID as we're no longer in the conference
				myId = "";
			};

		</script>
	<?php
}
