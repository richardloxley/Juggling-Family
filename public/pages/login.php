<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");


$error = "";
$email = "";
$password = "";
$rememberMe = true;
$redirectTo = "";

if (isset($_GET['redirect']) && $_GET['redirect'] != "")
{
	$redirectTo = $_GET['redirect'];
}
else
{
	$redirectTo = PUBLIC_URL["index"];
}

if (isset($_POST["log-in"]))
{
	if (isset($_POST["email"]))
	{
		$email = $_POST["email"];
	}

	if (isset($_POST["password"]))
	{
		$password = $_POST["password"];
	}

	if (!isset($_POST["remember-me"]))
	{
		$rememberMe = false;
	}

	if (isset($_POST["redirect-to"]))
	{
		$redirectTo = $_POST["redirect-to"];
	}

	if ($email == "" && $password == "")
	{
		$error = LANG["login_form_error_no_email_or_password"];
	}
	else if ($email == "")
	{
		$error = LANG["login_form_error_no_email"];
	}
	else if ($password == "")
	{
		$error = LANG["login_form_error_no_password"];
	}
	else
	{
		try
		{
			if (login_tryLoginUsingPassword($email, $password, $rememberMe))
			{
				// logged in, redirect to the same page to escape form submissions if they refresh the page
				header("Location: " . $redirectTo);
				exit(0);
			}
			else
			{
				$adminEmailTo = "<a href='mailto:" . CONFIG["site_admin_email"] . "'>" . CONFIG["site_admin_email"] . "</a>";
				$error = sprintf(LANG["login_form_error_no_account"], $adminEmailTo);
			}
		}
		catch (mysqli_sql_exception $e)
		{
			error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
			$error = database_genericErrorMessage();
		}
	}
}


template_drawHeader(LANG["page_title_login"], null, "");

?>
	<div id='login-form'>
		<h2>
			<?php echo LANG["login_form_title"] ?>
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
			<input type="hidden" name="redirect-to" value="<?php echo htmlspecialchars($redirectTo);?>">
			<label>
				<?php echo LANG["login_form_label_email"] ?>
				<input type="email" name="email" value="<?php echo htmlspecialchars($email);?>" maxlength=255>
			</label>
			<label>
				<?php echo LANG["login_form_label_password"] ?>
				<input type="password" name="password" value="<?php echo htmlspecialchars($password);?>" maxlength=255>
			</label>
			<label>
				<?php echo LANG["login_form_label_remember_me"] ?>
				<input type="checkbox" id="remember-me" name="remember-me" value="remember-me" <?php if ($rememberMe) echo "checked";?>>
			</label>
			<input type="submit" name="log-in" value="<?php echo LANG["login_form_label_submit"] ?>">
		</form>
	</div>

	<label>
		<?php echo LANG["alternatively"]; ?>
	</label>

	<a href="<?php echo PUBLIC_URL["reset"];?>" class="link-looking-like-a-button">
		<?php echo LANG["login_forgotten_button"] ?>
	</a>

<?php
template_drawFooter();
