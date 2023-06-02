<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

template_drawHeader(LANG["page_title_contact"], null, "");

?>
	<h2>
		<?php echo LANG["contact_title"]; ?>
	</h2>

	<p>

	<span class='contact-icon'>
		<?php echo icon_email(); ?>
	</span>

	<?php echo LANG["contact_email"]; ?>
	<a href='mailto:<?php echo CONFIG["site_contact_email"]; ?>'>
		<?php echo CONFIG["site_contact_email"]; ?>
	</a>

<?php
	if (CONFIG["site_contact_twitter"] != "")
	{
?>
		<p>
		<span class='contact-icon-brand'>
			<?php echo icon_twitter(); ?>
		</span>
		<?php echo LANG["contact_twitter"]; ?>
		<a href='https://twitter.com/<?php echo CONFIG["site_contact_twitter"]; ?>'>
			@<?php echo CONFIG["site_contact_twitter"]; ?>
		</a>
<?php
	}

template_drawFooter();
