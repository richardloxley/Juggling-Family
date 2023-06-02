<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

$error = "";
$email = "";

if (isset($_POST["join"]) && isset($_POST["email"]) && $_POST["email"] != "")
{
	$email = $_POST["email"];

	try
	{
		if (login_sendValidationEmail($email, true))
		{
			template_drawHeader(LANG["page_title_join"], null, "");
			echo "<p>";
			echo LANG["login_sign_up_form_thank_you"];
			echo "<p>";
			echo "<a href='" . PUBLIC_URL["index"] . "' class='link-looking-like-a-button'>";
			echo LANG["link_back_to_home"];
			echo "</a>";
			template_drawFooter();
			exit(0);
		}
		else
		{
			$error = LANG["login_sign_up_form_error"];
		}
	}
	catch (mysqli_sql_exception $e)
	{
		error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
		$error = database_genericErrorMessage();
	}
}


template_drawHeader(LANG["page_title_join"], null, "");

?>
	<div id='join-form'>
		<h2>
			<?php echo LANG["login_sign_up_form_title"] ?>
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
				<?php echo LANG["login_sign_up_form_label_email"] ?>
				<input type="email" name="email" value="<?php echo htmlspecialchars($email);?>" size=50 maxlength=255>
			</label>
			<p>
			<?php echo LANG["login_sign_up_form_explanation"] ?>
			<input type="submit" name="join" value="<?php echo LANG["login_sign_up_form_label_submit"] ?>">
		</form>
	</div>
<?php

template_drawFooter();
