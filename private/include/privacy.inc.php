<?php

function privacy_drawSiteRulesAndPrivacy()
{
	?>
		<h3>
			<?php echo LANG["privacy_site_rules_title"] ?>
		</h3>

		<dl>
			<dt>
				<?php echo LANG["privacy_site_rules_1"] ?>
			</dt>
				<dd>
					<?php echo LANG["privacy_site_rules_1_detail"] ?>
				</dd>

			<dt>
				<?php echo LANG["privacy_site_rules_2"] ?>
			</dt>
				<dd>
					<?php echo LANG["privacy_site_rules_2_detail"] ?>
				</dd>
		</dl>

		<h3>
			<?php echo LANG["privacy_site_privacy_title"] ?>
		</h3>

		<p>
		<?php echo LANG["privacy_site_privacy_public_subtitle"] ?>
		<ul>
			<li>
				<?php echo LANG["privacy_site_privacy_public_1"] ?>
			</li>
			<li>
				<?php echo LANG["privacy_site_privacy_public_2"] ?>
			</li>
			<li>
				<?php echo LANG["privacy_site_privacy_public_3"] ?>
			</li>
		</ul>
		<p>
		<?php echo LANG["privacy_site_privacy_private_subtitle"] ?>
		<ul>
			<li>
				<?php echo LANG["privacy_site_privacy_private_1"] ?>
			</li>
			<li>
				<?php echo LANG["privacy_site_privacy_private_2"] ?>
				<br>
				<?php echo LANG["privacy_site_privacy_private_2a"] ?>
			</li>
		</ul>
	<?php
}
