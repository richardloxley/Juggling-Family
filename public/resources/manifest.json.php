<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

?>

{
	"name": "<?php echo CONFIG['site_app_title']; ?>",
	"short_name": "<?php echo CONFIG['site_app_title_short']; ?>",
	"lang": "en-GB",
	"start_url": "/",
	"scope": "/",
	"display": "standalone"
}
