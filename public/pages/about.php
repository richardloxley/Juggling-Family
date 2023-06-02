<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

template_drawHeader(LANG["page_title_about"], null, "no-box");

template_drawAboutBox(LANG["welcome_full_site"]);

template_drawFooter();
