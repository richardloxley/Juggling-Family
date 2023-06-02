<?php

require_once("module.inc.php");


function fundraiser_drawFundraiserWindow()
{
	echo "<p>";
	echo LANG["fundraiser_description_1"];

	echo "<p>";
	echo LANG["fundraiser_description_2"];

	echo "<p>";
	$donation_link = "<a href='" . CONFIG["fundraiser_link"] . "'>" . CONFIG["fundraiser_link_description"] . "</a>";
	$email_link = "<a href='mailto:" . CONFIG["fundraiser_email"] . "'>" . LANG["fundraiser_email_description"] . "</a>";
	echo sprintf(LANG["fundraiser_description_3"], $donation_link, $email_link);

	echo "<p>";
	$fundraiser_progress = "<span class='fundraiser_progress'>" . CONFIG["fundraiser_progress"] . "</span>";
	$fundraiser_total = "<span class='fundraiser_total'>" . CONFIG["fundraiser_total"] . "</span>";
	echo sprintf(LANG["fundraiser_progress"], $fundraiser_progress, $fundraiser_total);
}


function fundraiser_drawFundraiserBanner()
{
	if (login_isLoggedIn() && CONFIG["fundraiser_is_active"])
	{
		module_drawModuleHeader("module-main-fundraiser", "fundraiser_title", false, false, false, "");
		fundraiser_drawFundraiserWindow();
		module_drawModuleFooter();
	}
}
