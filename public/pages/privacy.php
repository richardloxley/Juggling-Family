<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("privacy.inc.php");


template_drawHeader(LANG["page_title_privacy"], null, "");

echo "<h2>";
echo LANG["privacy_title"];
echo "</h2>";

privacy_drawSiteRulesAndPrivacy();

template_drawFooter();
