<?php

require_once("icon.inc.php");
require_once("symbol.inc.php");
require_once("time.inc.php");
require_once("module.inc.php");
require_once("fundraiser.inc.php");
require_once("captcha.inc.php");


function template_drawHeader($titleMessage, $roomIdOrNull, $bodyClass, $openGraph = null, $drawTopbar = true)
{
	if ($titleMessage == "")
	{
		if ($roomIdOrNull === null)
		{
			$title = CONFIG["site_title"];
		}
		else
		{
			$title = htmlspecialchars(rooms_getTitleFromRoomId($roomIdOrNull)) . " - " . CONFIG["site_title"];
		}
	}
	else
	{
		$title = $titleMessage . " - " . CONFIG["site_title"];
	}

	$ogDescription = CONFIG["open_graph_description"];
	$ogImage = CONFIG["site_root_url"] . IMAGES_URL . CONFIG["site_open_graph_banner"] . "?r=1";

	if ($openGraph !== null)
	{
		if (isset($openGraph["description"]))
		{
			$ogDescription = $openGraph["description"];
		}

		if (isset($openGraph["image"]))
		{
			$ogImage = $openGraph["image"] . "?r=1";
		}
	}

	//<link href="https://fonts.googleapis.com/css2?family=Lexend+Deca&display=swap" rel="stylesheet"> 
	//<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js"></script>

	// onclick on body is for iOS hover to work (tabindex=0 also works but breaks rome date picker!)

	?>
		<!doctype html public "-//w3c//dtd html 4.0 transitional//en">
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<link rel="manifest" href="<?php echo RESOURCES_URL;?>manifest.json.php">
			<meta name="mobile-web-app-capable" content="yes">
			<meta name="apple-mobile-web-app-capable" content="yes">
			<meta name="application-name" content="<?php echo $title;?>">
			<meta name="apple-mobile-web-app-title" content="<?php echo $title;?>">
			<meta property="og:type" content="website" />
			<meta property="og:title" content="<?php echo $title;?>" />
			<meta property="og:description" content="<?php echo $ogDescription;?>" />
			<meta property="og:image" content="<?php echo $ogImage;?>" />
			<title><?php echo $title;?></title>
			<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet"> 
			<link rel="stylesheet" href="/public/thirdparty/rome/dist/rome.min.css" type="text/css">
			<link rel="stylesheet" href="<?php echo RESOURCES_URL;?>style.css?r=38" type="text/css">
			<link rel="apple-touch-icon" href="<?php echo IMAGES_URL;?>mobile-icon.png"/>
			<link rel="icon" href="<?php echo IMAGES_URL;?>mobile-icon.png"/>
			<script type="text/javascript" src="/public/thirdparty/jquery/jquery-1.12.4.min.js"></script>
			<script type="text/javascript" src="/public/thirdparty/jquery-visibility/jquery-visibility.min.js"></script>
			<script type="text/javascript" src="/public/thirdparty/rome/dist/rome.js"></script>
			<?php
				icon_drawHeader();
				template_javascriptTouchscreenDetection();
				template_javascriptDetection();
				captcha_javascript();
			?>
		</head>
		<body onlick class='no-js <?php echo $bodyClass;?>'>
		<div id='all-content'>
	<?php

	template_javascriptDebug();
	template_javascriptCookies();
	template_javascriptTextareas();
	template_javascriptShowHide();

	module_javascriptModule();

	time_javascriptAdjustDatesToLocalTimezone();

	if ($drawTopbar)
	{
		template_drawTopBar($titleMessage, $roomIdOrNull);
	}

	echo "<div id='main-content'>";

	if (strpos($bodyClass, "no-box") !== false)
	{
		fundraiser_drawFundraiserBanner();
	}
}


function template_drawLogo()
{
	?>
		<a href='<?php echo PUBLIC_URL["index"] ?>'>
			<span class="logo1"><?php echo CONFIG["site_logo_1"]; ?></span><span class="logo2"><?php echo CONFIG["site_logo_2"]; ?></span><span class="logo3"><?php echo CONFIG["site_logo_3"]; ?></span>
		</a>
	<?php
}


function template_drawTopBar($titleMessage, $roomIdOrNull)
{
	?>
		<div id='top-bar'>
			<div id='top-bar-logo-and-buttons'>
				<h1>
					<?php template_drawLogo(); ?>
				</h1>

				<?php template_drawTopBarButtons(); ?>
			</div>

			<div id='top-bar-status-line'>
	<?php
				if (login_isLoggedIn() || login_isGuest())
				{
					?>
						<span id='top-bar-welcome-message'>
							<?php echo LANG["top_bar_welcome"]; ?>
							<?php echo htmlspecialchars(login_getDisplayName()); ?>
						</span>
					<?php
				}

				template_drawBreadcrumb($titleMessage, $roomIdOrNull);
	?>
			</div>

			<div id='top-bar-status-line-end'>
			</div>
		</div>
	<?php
}


function template_drawTopBarButtons()
{
	echo "<div id='top-bar-buttons'>";

//	echo "<div id='top-bar-buttons-section-1'>";

	?>
		<div class='top-bar-button'>
			<a href='<?php echo PUBLIC_URL["index"] ?>'>
				<div class='button-icon'>
					<?php echo icon_home(); ?>
				</div>

				<div class='top-bar-button-label'>
					<?php echo LANG["top_bar_button_home"] ?>
				</div>
			</a>
		</div>
	<?php

	if (!login_isLoggedIn())
	{
		?>
			<div class='top-bar-button'>
				<a href="<?php echo PUBLIC_URL["join"];?>">
					<div class='button-icon'>
						<?php echo icon_signUp(); ?>
					</div>

					<div class='top-bar-button-label'>
						<?php echo LANG["top_bar_button_join"] ?>
					</div>
				</a>
			</div>
		<?php
	}

//	echo "</div>";

//	echo "<div id='top-bar-buttons-section-2'>";

	if (login_isLoggedIn() || login_isGuest())
	{
		$settingsTitle = LANG["top_bar_button_settings"];
		$settingsIcon = icon_cog();
		$settingsUrl = PUBLIC_URL["settings"];
	}
	else
	{
		$settingsTitle = LANG["top_bar_button_language"];
		$settingsIcon = icon_language();
		$settingsUrl = PUBLIC_URL["settings"] . "?open=language";
	}

	?>
		<div class='top-bar-button'>
			<a href="<?php echo $settingsUrl; ?>">
				<div class='button-icon'>
					<?php echo $settingsIcon; ?>
				</div>

				<div class='top-bar-button-label'>
					<?php echo $settingsTitle; ?>
				</div>
			</a>
		</div>
	<?php

	if (login_isLoggedIn() || login_isGuest())
	{
		$logoutButton = LANG["top_bar_button_logout"];

		if (login_isGuest())
		{
			$logoutButton .= "<br>" . CONFIG["site_guest_suffix"];
		}

		?>

			<div class='top-bar-button'>
				<a href="<?php echo PUBLIC_URL["logout"];?>">
					<div class='button-icon'>
						<?php echo icon_logout(); ?>
					</div>

					<div class='top-bar-button-label'>
						<?php echo $logoutButton; ?>
					</div>
				</a>
			</div>
		<?php
	}
	else
	{
		?>
			<div class='top-bar-button'>
				<a href="<?php echo PUBLIC_URL["login"];?>">
					<div class='button-icon'>
						<?php echo icon_login(); ?>
					</div>

					<div class='top-bar-button-label'>
						<?php echo LANG["top_bar_button_login"] ?>
					</div>
				</a>
			</div>
		<?php
	}

	//echo "</div>";
	echo "</div>";
	echo "<div id='top-bar-buttons-end'>";
	echo "</div>";
}


function template_denyIfNotLoggedIn()
{
	if (!login_isLoggedIn())
	{
		echo "<div id='banner-join-us-denied'>";
			echo LANG["login_join_us_prompt_page"];
		echo "</div>";
		template_drawFooter();
		exit(0);
	}
}


function template_denyIfNotLoggedInOrGuest()
{
	if (!login_isLoggedIn() && !login_isGuest())
	{
		echo "<div id='banner-join-us-denied'>";
			echo LANG["login_join_us_prompt_page"];
		echo "</div>";
		template_drawFooter();
		exit(0);
	}
}


function template_bannerSuggestingLogin($roomIdOrNull = null)
{
	if (login_hasFullAccessToRoom($roomIdOrNull))
	{
		return;
	}

	$createAnAccountLink = "<a href='" . PUBLIC_URL["join"] . "'>" . LANG["login_join_us_prompt_link_text"] . "</a>";

	if (login_isGuest())
	{
		$roomName = rooms_getTitleFromRoomId(login_guestRoomId());

		if ($roomIdOrNull === null)
		{
			echo "<div id='banner-join-us-suggested'>";
			echo sprintf(LANG["login_join_us_prompt_banner_guest"], $roomName, $createAnAccountLink);
			echo "</div>";
		}
		else
		{
			echo "<div id='banner-join-us-denied'>";
			echo sprintf(LANG["login_join_us_prompt_banner_guest_wrong_room"], $roomName, $createAnAccountLink);
			echo "</div>";
		}
	}
	else
	{
		echo "<div id='banner-join-us-suggested'>";
			echo sprintf(LANG["login_join_us_prompt_banner"], $createAnAccountLink);
		echo "</div>";
	}
}


function template_drawFooter($includeLinks = true)
{
	echo "</div>";

	if ($includeLinks)
	{
		?>
			<div id="footer">
				<span class='footer-button'>
					<a href="<?php echo PUBLIC_URL["about"];?>">
						<span class='button-icon'>
							<?php echo icon_info(); ?>
						</span>
						<?php echo LANG["footer_button_about"] ?>
					</a>
				</span>
				<span class='footer-button'>
					<a href="<?php echo PUBLIC_URL["faq"];?>">
						<span class='button-icon'>
							<?php echo icon_question(); ?>
						</span>
						<?php echo LANG["footer_button_faq"] ?>
					</a>
				</span>
				<span class='footer-button'>
					<a href="<?php echo PUBLIC_URL["contact"];?>">
						<span class='button-icon'>
							<?php echo icon_email(); ?>
						</span>
						<?php echo LANG["footer_button_contact"] ?>
					</a>
				</span>
				<span class='footer-button'>
					<a href="<?php echo PUBLIC_URL["news"];?>">
						<span class='button-icon'>
							<?php echo icon_news(); ?>
						</span>
						<?php echo LANG["footer_button_news"] ?>
					</a>
				</span>
				<span class='footer-button'>
					<a href="<?php echo PUBLIC_URL["privacy"];?>">
						<span class='button-icon'>
							<?php echo icon_privacy(); ?>
						</span>
						<?php echo LANG["footer_button_privacy"] ?>
					</a>
				</span>
				<span class='footer-button'>
					<a href="<?php echo PUBLIC_URL["credits"];?>">
						<span class='button-icon'>
							<?php echo icon_credits(); ?>
						</span>
						<?php echo LANG["footer_button_credits"] ?>
					</a>
				</span>
			</div>
		<?php
	}

	?>
		</div>
		</body>
		</html>
	<?php
}


function template_javascriptTouchscreenDetection()
{
	?>
		<script type="text/javascript">

		$(document).ready(function()
		{
			if ('ontouchstart' in document.documentElement)
			{
				$('body').addClass("touchscreen");
			}
		});

		</script>
	<?php
}


function template_javascriptDetection()
{
	?>
		<script type="text/javascript">

		$(document).ready(function()
		{
			$("body").removeClass("no-js");
			$("body").addClass("has-js");
		});

		</script>
	<?php
}


function template_javascriptShowHide()
{
	?>
		<script type="text/javascript">
			$(document).ready(function()
			{
				$(".show-hide-box").hide();
				$(".linked-show-hide-box").hide();
				$(".show-hide-box-start-open").show();

				$('a.show-hide').click(function()
				{
					$(this).next(".show-hide-box").toggle();
					return false;
				});

				$('a.linked-show-hide').click(function()
				{
					var box = $(this).next(".linked-show-hide-box");

					if (box.is(":visible"))
					{
						box.hide();
					}
					else
					{
						$(this).siblings(".linked-show-hide-box").hide();
						box.show();
					}

					return false;
				});
			});
		</script>
	<?php
}


function template_javascriptClipboard()
{
	?>
		<script type="text/javascript">
			function copyToClipboard(textSelector)
			{
				/* what are we copying? */
				var stringToCopy = $(textSelector).html();

				/* we can only select text in input boxes, so create one */
				var inputBox = $("<input type='text'>");
				$(textSelector).append(inputBox);

				/* put the text in it */
				inputBox.val(stringToCopy);

				/* select it (second version for old iOS Safari and others) */
				inputBox.select();
				inputBox[0].setSelectionRange(0, 999999);

				/* try to copy */
				if (document.execCommand("copy"))
				{
					/* worked, remove the input box */
					inputBox.remove();
				}
				else
				{
					/* older browser, didn't work, so leave text box visible so they can copy it themselves */
				}

			}
		</script>
	<?php
}

function template_javascriptDebug()
{
	if (CONFIG["debug_js"])
	{
		?>
			<script type="text/javascript">
				function debug(message)
				{
					console.warn(message);
				}
			</script>
		<?php
	}
	else
	{
		?>
			<script type="text/javascript">
				function debug(message)
				{
				}
			</script>
		<?php
	}
}


function template_javascriptCookies()
{
	?>
		<script type="text/javascript">

			function setCookieExpiresInHours(hours, key, value)
			{
				var d = new Date();
				d.setTime(d.getTime() + (hours * 3600000));
				var expires = "expires="+ d.toUTCString();
				document.cookie = key + "=" + value + ";" + expires + ";path=/";
			}

			function setCookie(key, value)
			{
				// expires in 10 years
				setCookieExpiresInHours(87600, key, value);
			}

			function getCookie(key)
			{
				var name = key + "=";
				var decodedCookie = decodeURIComponent(document.cookie);
				var cookies = decodedCookie.split(';');
				for (var x = 0; x < cookies.length; x++)
				{
					var cookie = cookies[x];
					while (cookie.charAt(0) == ' ')
					{
						cookie = cookie.substring(1);
					}
					if (cookie.indexOf(name) == 0)
					{
						return cookie.substring(name.length, cookie.length);
					}
				}
				return "";
			} 

			function deleteCookie(key)
			{
				document.cookie = key + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
			}
		</script>
	<?php
}


function template_javascriptTextareas()
{
return;
	?>
		<script type="text/javascript">
			$(document).ready(function()
			{
				$.each($('textarea'), function()
				{
					var offset = this.offsetHeight - this.clientHeight;
				 
					var resizeTextarea = function(el)
					{
						$(el).css('height', 'auto').css('height', el.scrollHeight + offset);
					};

					$(this).on('keyup input', function()
					{
						resizeTextarea(this);
					});
				});
			});
		</script>
	<?php
}


function template_drawBreadcrumb($titleMessage, $roomIdOrNull)
{
	echo "<span id='breadcrumbs'>";

	if ($roomIdOrNull === null && $titleMessage == "")
	{
		echo LANG["breadcrumb_home"];
	}
	else
	{
		echo "<a href='" . PUBLIC_URL["index"] . "'>";
		echo LANG["breadcrumb_home"];
		echo "</a>";

		echo " &gt; ";

		if ($roomIdOrNull === null)
		{
			echo $titleMessage;
		}
		else
		{
			if ($titleMessage == "")
			{
				echo rooms_getCategoryFromRoomId($roomIdOrNull) . ": ";
				echo htmlspecialchars(rooms_getTitleFromRoomId($roomIdOrNull));
			}
			else
			{
				echo "<a href='" . rooms_getUrlFromRoomId($roomIdOrNull) . "'>";
				echo rooms_getCategoryFromRoomId($roomIdOrNull) . ": ";
				echo htmlspecialchars(rooms_getTitleFromRoomId($roomIdOrNull));
				echo "</a>";
				echo " &gt; ";
				echo $titleMessage;
			}
		}
	}

	echo "</span>";
}



function template_replaceControlCodes($message)
{
	$message = preg_replace('/\n/', "<br/>", $message);
	return $message;
}


function template_replaceEmoticons($message)
{
	$message = preg_replace('/:thumb:/', symbol_thumbsUp(), $message);
	$message = preg_replace('/:facepalm:/', symbol_facePalm(), $message);
	$message = preg_replace('/\B:-\)\B/', symbol_smile(), $message);
	$message = preg_replace('/\B:\)\B/', symbol_smile(), $message);
	$message = preg_replace('/:-D/', symbol_grin(), $message);
	$message = preg_replace('/:D/', symbol_grin(), $message);
	$message = preg_replace('/\B:-\(\B/', symbol_frown(), $message);
	$message = preg_replace('/\B:\(\B/', symbol_frown(), $message);
	$message = preg_replace('/\B;-\)\B/', symbol_wink(), $message);
	$message = preg_replace('/\B;\)\B/', symbol_wink(), $message);

	return $message;
}


function template_replaceUrls($message, $hideUrl = false)
{
	// just looks for white-space delimited string starting http:// or https://
	if ($hideUrl)
	{
		$message = preg_replace('/\b(https?:\/\/\S*)/i', LANG["url_hidden"], $message);
	}
	else
	{
		$message = preg_replace('/\b(https?:\/\/\S*)/i', "<a href='$1' target='_blank' rel='noreferrer'>$1</a>", $message);
	}

	return $message;
}


// e.g. 0.5, 1, 1.5 
function template_formatSimpleNumber($number)
{
	$s = sprintf("%f", $number);

	// remove trailing zeros
	while (substr($s, -1) == "0")
	{
		$s = substr($s, 0, -1);
	}

	// remove trailing decimal
	if (substr($s, -1) == ".")
	{
		$s = substr($s, 0, -1);
	}

	return $s;
}


function template_drawSwitch($label, $id, $on)
{
	$checked = "";
	if ($on)
	{
		$checked = "checked='checked'";
	}

	?>
		<div class="switch-wrapper">
			<label>
				<?php echo $label; ?>
				<span class='switch'>
					<input type='checkbox' id='<?php echo $id; ?>' name='<?php echo $id; ?>' <?php echo $checked; ?> >
					<span class='switch-slider'>
					</span>
				</span>
			</label>
		</div>
	<?php
}


function template_domainName()
{
        return preg_replace("/https?:\/\//i", "", CONFIG["site_root_url"]);
}


function template_generateUid()
{
	$domain = template_domainName();
	$time = time_nowInUtcString();
	$random = login_generateToken(5);

	return "$time-$random@$domain";
}



function template_drawAboutBox($joinMessage)
{
	?>
		<div id='welcome-banner'>
			<img src='<?php echo IMAGES_URL . CONFIG["site_welcome_banner"]; ?>'>

			<div id='welcome-message-box'>
				<div id='welcome-message'>
					<h2>
						<?php echo LANG["welcome_title"]; ?>
						<?php template_drawLogo(); ?>
						<span class='icon'>
							<?php echo icon_smile(); ?>
						</span>
					</h2>
					<p>
					<?php echo LANG["welcome_benefit_1"]; ?>
					<p>
					<?php echo LANG["welcome_benefit_2"]; ?>
					<p>
					<?php echo LANG["welcome_benefit_3"]; ?>
					<?php
						if (!login_isLoggedIn())
						{
					?>
							<p>
							<a href='<?php echo PUBLIC_URL["join"]; ?>'>
								<?php echo LANG["welcome_join"]; ?>
							</a>
							<?php echo $joinMessage; ?>
					<?php
						}
					?>
				</div>
			</div>

			<div id="welcome-banner-faq">
				<a href='<?php echo PUBLIC_URL["faq"]; ?>'>
					<?php echo LANG["faq_title"]; ?>
				</a>
			</div>

			<div id="welcome-banner-copyright">
				<a href='<?php echo CONFIG["site_welcome_banner_copyright_url"]; ?>'>
					<?php echo LANG["welcome_photo_copyright"]; ?>
					<?php echo CONFIG["site_welcome_banner_copyright"]; ?>
				</a>
			</div>
		</div>
	<?php
}
