<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

$error = "";
$email = "";

if (isset($_POST["next"]) && isset($_POST["email"]) && $_POST["email"] != "")
{
	$email = $_POST["email"];

	if (captcha_validate())
	{
		try
		{
			if (login_sendValidationEmail($email, false))
			{
				template_drawHeader(LANG["page_title_reset"], null, "");
				echo LANG["login_reset_password_form_thank_you"];
				echo "<p>";
				echo "<a href='" . PUBLIC_URL["index"] . "'>";
				echo LANG["link_back_to_home"];
				echo "</a>";
				template_drawFooter();
				exit(0);
			}
			else
			{
				$error = LANG["login_reset_password_form_error"];
			}
		}
		catch (mysqli_sql_exception $e)
		{
			error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
			$error = database_genericErrorMessage();
		}
	}
	else
	{
		$error = LANG["login_captcha_error"];
	}
}


template_drawHeader(LANG["page_title_reset"], null, "");

?>
	<div id='reset-password-form'>
		<h2>
			<?php echo LANG["login_reset_password_form_title"] ?>
		</h2>
<?php
		if ($error != "")
		{
			echo "<div class='form-error'>";
			echo $error;
			echo "</div>";
		}
?>
		<form method="post" autocorrect="off" autocapitalize="off" class="<?php echo captcha_formClass(); ?>" <?php echo captcha_formData(); ?> action="<?php echo $_SERVER['REQUEST_URI'] ?>">
			<label>
				<?php echo LANG["login_reset_password_form_label_email"] ?>
				<input type="email" name="email" value="<?php echo htmlspecialchars($email);?>" size=50 maxlength=255>
			</label>
			<p>
			<?php echo LANG["login_reset_password_form_explanation"] ?>
			<input type="submit" name="next" value="<?php echo LANG["login_reset_password_form_label_submit"] ?>">
		</form>
	</div>
<?php

template_drawFooter();
