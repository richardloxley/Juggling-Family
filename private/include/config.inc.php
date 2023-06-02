<?php

define("CONFIG", array_merge
(
	parse_ini_file(CONFIG_PATH . "defaults.ini", false, INI_SCANNER_TYPED),
	parse_ini_file(CONFIG_PATH . "local.ini", false, INI_SCANNER_TYPED)
));
