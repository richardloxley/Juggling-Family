<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("privacy.inc.php");


$error = "";
$newUser = true;
$nickname = "";

// check we've got a valid token

if (isset($_GET["selector"]) && isset($_GET["token"]))
{
	$selector = $_GET["selector"];
	$token = $_GET["token"];

	try
	{
		$result = login_checkValidation($selector, $token);

		if ($result === true)
		{
			$newUser = true;
		}
		else if ($result === false)
		{
			$error = LANG["verify_bad_token"];
		}
		else
		{
			$newUser = false;
			$nickname = $result;
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		$error = database_genericErrorMessage();
	}
}

if ($error != "")
{
	template_drawHeader(LANG["page_title_verify"], null, "");
	echo $error;
	echo "<p>";
	echo "<a href='" . PUBLIC_URL["index"] . "'>";
	echo LANG["link_back_to_home"];
	echo "</a>";
	template_drawFooter();
	exit(0);
}


if ($newUser)
{
	template_drawHeader(LANG["page_title_join"], null, "");
}
else
{
	template_drawHeader(LANG["page_title_password_reset"], null, "");
}




$error = "";

if (isset($_POST["nickname"]))
{
	$nickname = trim($_POST["nickname"]);
	$isGuest = false;
	$error = login_checkNickname($nickname, $isGuest);
}

if ($newUser && ($nickname == "" || $error != ""))
{
	$title = sprintf(LANG["verify_title_new_account"], 1, 3);
	doNicknameForm($title, $nickname, $error);
}
else
{
	$error = "";
	$password1 = "";
	$password2 = "";

	if (isset($_POST["password1"]) && isset($_POST["password2"]))
	{
		$password1 = $_POST["password1"];
		$password2 = $_POST["password2"];
		$error = checkPasswords($password1, $password2);
	}

	if ($password1 == "" || $password2 == "" || $error != "")
	{
		if ($newUser)
		{
			$title = sprintf(LANG["verify_title_new_account"], 2, 3);
		}
		else
		{
			$title = LANG["verify_title_password_reset"];
		}

		doPasswordForm($title, $nickname, $newUser, $password1, $password2, $error);
	}
	else
	{
		if ($newUser)
		{
			if (isset($_POST["create-account"]))
			{
				if (login_createUser($selector, $token, $nickname, $password1))
				{
					echo LANG["verify_account_creation_success"];
					echo "<p>";
					echo "<a href='" . PUBLIC_URL["login"] . "'>";
					echo LANG["verify_link_log_in"];
					echo "</a>";
				}
				else
				{
					echo LANG["verify_account_creation_failure"];
					echo "<p>";
					echo "<a href='" . PUBLIC_URL["index"] . "'>";
					echo LANG["link_back_to_home"];
					echo "</a>";
				}
			}
			else
			{
				$title = sprintf(LANG["verify_title_new_account"], 3, 3);
				doSiteRulesForm($title, $nickname, $password1);
			}
		}
		else
		{
			if (login_changePassword($selector, $token, $password1))
			{
				echo LANG["verify_password_change_success"];
				echo "<p>";
				echo "<a href='" . PUBLIC_URL["login"] . "'>";
				echo LANG["verify_link_log_in"];
				echo "</a>";
			}
			else
			{
				echo LANG["verify_password_change_failure"];
				echo "<p>";
				echo "<a href='" . PUBLIC_URL["index"] . "'>";
				echo LANG["link_back_to_home"];
				echo "</a>";
			}
		}	
	}
}

template_drawFooter();

function doNicknameForm($title, $nickname, $error)
{
	?>
		<div id='nickname-form'>
			<h2>
				<?php echo $title; ?>
			</h2>
	<?php
			if ($error != "")
			{
				echo "<div class='form-error'>";
				echo $error;
				echo "</div>";
			}
	?>
			<form method="post" autocorrect="off" autocapitalize="off" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<label>
					<?php echo LANG["verify_form_label_nickname"] ?>
					<input type="text" name="nickname" value="<?php echo htmlspecialchars($nickname);?>" size=50 maxlength=255>
				</label>
				<br>
				<?php echo LANG["verify_form_explanation_nickname"] ?>
				<input type="submit" name="next" value="<?php echo LANG["verify_form_nickname_label_submit"] ?>">
			</form>
		</div>
	<?php
}


function doPasswordForm($title, $nickname, $newUser, $password1, $password2, $error)
{
	// words that would be easily guessed if in a password
	$badWords = array();
	$badWords = array_merge($badWords, preg_split("/[^a-zA-Z]+/", CONFIG["site_title"]));
	$badWords = array_merge($badWords, preg_split("/[^a-zA-Z]+/", CONFIG["site_root_url"]));
	$badWords = array_merge($badWords, preg_split("/[^a-zA-Z]+/", $nickname));
	$badWordsJSArray = "['" . implode("','", $badWords) . "']";

	if ($newUser)
	{
		$submitLabel = LANG["verify_form_password_new_user_label_submit"];
	}
	else
	{
		$submitLabel = LANG["verify_form_password_reset_label_submit"];
	}

	?>
		<div id='password-form'>
			<h2>
				<?php echo $title; ?>
			</h2>
	<?php
			if ($error != "")
			{
				echo "<div class='form-error'>";
				echo $error;
				echo "</div>";
			}
	?>
			<form method="post" autocorrect="off" autocapitalize="off" action="<?php echo $_SERVER['REQUEST_URI'] ?>">

				<input type="hidden" name="nickname" value="<?php echo htmlspecialchars($nickname);?>">

				<label>
					<?php echo LANG["verify_form_label_password1"] ?>
					<input type="password" name="password1" value="<?php echo htmlspecialchars($password1);?>" size=50 maxlength=255>
				</label>

				<div id='password-strength-meter'>
					<div id="password-strength-0">
					</div>
					<div id="password-strength-1">
					</div>
					<div id="password-strength-2">
					</div>
					<div id="password-strength-3">
					</div>
					<div id="password-strength-4">
					</div>
				</div>
				<div id='password-strength-meter-labels'>
					<div id="password-strength-label-0">
						<?php echo LANG["verify_password_strength_0"] ?>
					</div>
					<div id="password-strength-label-1">
						<?php echo LANG["verify_password_strength_1"] ?>
					</div>
					<div id="password-strength-label-2">
						<?php echo LANG["verify_password_strength_2"] ?>
					</div>
					<div id="password-strength-label-3">
						<?php echo LANG["verify_password_strength_3"] ?>
					</div>
					<div id="password-strength-label-4">
						<?php echo LANG["verify_password_strength_4"] ?>
					</div>
				</div>
				<div id='password-strength-meter-end'>
				</div>

				<div id='password-strength-tips'>
					<div id='password-strength-warning'>
					</div>
					<div id='password-strength-suggestion'>
					</div>
				</div>

				<label>
					<?php echo LANG["verify_form_label_password2"] ?>
					<input type="password" name="password2" value="<?php echo htmlspecialchars($password2);?>" size=50 maxlength=255>
				</label>

				<div id='password-match-indicator'>
				</div>

				<br>

				<input type="submit" name="next" value="<?php echo $submitLabel ?>">
			</form>

			<script type='text/javascript' src='/public/thirdparty/zxcvbn/zxcvbn.js'></script>

			<script type='text/javascript'>

				function passwordChecks()
				{
					var okToSubmit = true;

					var password1 = $('[name="password1"]').val();
					var password2 = $('[name="password2"]').val();

					if (password1 == "" && password2 == "")
					{
						$('#password-match-indicator').html('');
						okToSubmit = false;
					}
					else if (password1 == password2)
					{
						$('#password-match-indicator').html('');
					}
					else
					{
						$('#password-match-indicator').html("<?php echo LANG['verify_form_error_passwords_dont_match'] ?>");
						okToSubmit = false;
					}

					var strength = zxcvbn(password1, <?php echo $badWordsJSArray ?>);

					var score = strength['score'];

					for (var x = 0; x <= 4; x++)
					{
						if (x <= score)
						{
							$('#password-strength-' + x).attr('class', 'password-score-' + score);
						}
						else
						{
							$('#password-strength-' + x).attr('class', '');
						}

						if (x == score)
						{
							$('#password-strength-label-' + x).attr('class', 'password-strength-selected');
						}
						else
						{
							$('#password-strength-label-' + x).attr('class', '');
						}
					}

					if (score < 3)
					{
						okToSubmit = false;
					}

					if (strength['feedback']['warning'].length > 0 || strength['feedback']['suggestions'].length > 0)
					{
						$('#password-strength-warning').text(strength['feedback']['warning']);

						var suggestions = strength['feedback']['suggestions'];
						var total = suggestions.length;
						var suggestionText = "";
						for (var x = 0; x < total; x++)
						{
							suggestionText += suggestions[x];
							suggestionText += "<br>";
						}
						$('#password-strength-suggestion').html(suggestionText);
						$('#password-strength-tips').show();
					}
					else
					{
						$('#password-strength-tips').hide();
					}

					if (okToSubmit)
					{
						$('[name="next"]').prop('disabled', false);
					}
					else
					{
						$('[name="next"]').prop('disabled', true);
					}
				}

				$(document).ready(passwordChecks);
				$('[name="password1"]').on("input", passwordChecks);
				$('[name="password2"]').on("input", passwordChecks);

			</script>

		</div>
	<?php
}


function doSiteRulesForm($title, $nickname, $password)
{
	?>
		<div id='site-rules-form'>
			<h2>
				<?php echo $title; ?>
			</h2>

			<?php privacy_drawSiteRulesAndPrivacy(); ?>

			<form method="post" autocorrect="off" autocapitalize="off" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<input type="hidden" name="nickname" value="<?php echo htmlspecialchars($nickname);?>">
				<input type="hidden" name="password1" value="<?php echo htmlspecialchars($password);?>">
				<input type="hidden" name="password2" value="<?php echo htmlspecialchars($password);?>">
				<input type="submit" name="create-account" value="<?php echo LANG["verify_form_create_account_label_submit"] ?>">
			</form>
		</div>
	<?php
}


// returns error message if not valid
function checkPasswords($password1, $password2)
{
	$error = "";

	if ($password1 != $password2)
	{
		$error = LANG["verify_form_error_passwords_dont_match"];
	}

	return $error;
}

