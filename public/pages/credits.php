<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

template_drawHeader(LANG["page_title_credits"], null, "");

?>
	<h2>
		<?php echo LANG["credits_title"]; ?>
	</h2>

	<p>

	<div class="credit">
		<span class="credit-role">
			<?php echo LANG["credits_subheading_coding"]; ?>
		</span>
		<span class="credit-person">
			<a href="https://www.richardloxley.com/">
				Richard Loxley
			</a>
		</span>
	</div>
	<div class="credit">
		<span class="credit-role">
			<?php echo LANG["credits_subheading_design"]; ?>
		</span>
		<span class="credit-person">
			<a href="https://www.david.gibbs.co.uk/">
				David Gibbs
			</a>
		</span>
	</div>
	<div class="credit">
		<span class="credit-role">
			<?php echo LANG["credits_subheading_usability"]; ?>
		</span>
		<span class="credit-person">
			Rainbowmichelle
		</span>
	</div>
	<div class="credit">
		<span class="credit-role">
			<?php echo LANG["credits_subheading_photography"]; ?>
		</span>
		<span class="credit-person">
			<a href='<?php echo CONFIG["site_welcome_banner_copyright_url"]; ?>'>
				<?php echo CONFIG["site_welcome_banner_copyright"]; ?>
			</a>
		</span>
	</div>

	<p>
<?php

template_drawFooter();
