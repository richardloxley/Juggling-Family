<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

login_logout();
header("Location: " . PUBLIC_URL['index']);
