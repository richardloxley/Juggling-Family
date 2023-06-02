<?php

define("ROOT_DIR", dirname(__FILE__) . "/..");

// should be included once by all PHP files before anything else is done
// (1) sets file paths
// (2) includes files required by everything
// (3) handles sessions & cookies that must be done before any output

define("RESOURCES_URL",		"/public/resources/");
define("IMAGES_URL",		RESOURCES_URL . "images/");

define("ROOM_THUMBS_URL",	"/public/uploads/rooms/thumbs/");
define("ROOM_IMAGES_URL",	"/public/uploads/rooms/large/");
define("ROOM_MAX_IMAGES_URL",	"/public/uploads/rooms/max/");

define("ROOM_THUMBS_PATH",	ROOT_DIR . ROOM_THUMBS_URL);
define("ROOM_IMAGES_PATH",	ROOT_DIR . ROOM_IMAGES_URL);
define("ROOM_MAX_IMAGES_PATH",	ROOT_DIR . ROOM_MAX_IMAGES_URL);

define("INCLUDE_PATH",		ROOT_DIR . "/private/include/");
define("CONFIG_PATH",		ROOT_DIR . "/private/config/");
define("LANGUAGES_PATH",	ROOT_DIR . "/private/languages/");

set_include_path(INCLUDE_PATH);

// these are the top level URLs
// - they handled by redirects to sub-directories in .htaccess, so any changes to this list need to be reflected there too
// - this also defines a list of "reserved words" which cannot be used as room names, since room names are also top-level URLs
define("PUBLIC_URL", array
(
	"index"			=> "/",
	"room"			=> "/",
	"chat"			=> "/chat",
	"calendar"		=> "/calendar",
	"calendar_sub"		=> "/calendar/subscribe",
	"calendar_feed"		=> "/calendar.ics",
	"addevent"		=> "/calendar/add",
	"editevent"		=> "/calendar/edit",
	"settings"		=> "/settings",
	"rooms"			=> "/rooms",
	"createroom"		=> "/createroom",
	"user"			=> "/user",
	"login"			=> "/login",
	"logout"		=> "/logout",
	"join"			=> "/join",
	"reset"			=> "/reset",
	"verify"		=> "/verify",
	"about"			=> "/about",
	"faq"			=> "/faq",
	"contact"		=> "/contact",
	"news"			=> "/news",
	"privacy"		=> "/privacy",
	"credits"		=> "/credits",
	"invite"		=> "/invite",
	"private"		=> null,	// not used directly, it's the name of a sub-directory
	"public"		=> null		// not used directly, it's the name of a sub-directory
));

// URLs used for AJAX APIs - so visible to browser but not the user
define("API_URL", array
(
	"chat-send-message"		=> "/public/api/chat-send-message.php",
	"chat-get-messages"		=> "/public/api/chat-get-messages.php",
	"rooms-get-room-list"		=> "/public/api/rooms-get-room-list.php",
	"video-get-list"		=> "/public/api/video-get-list.php",
	"video-participant-changed"	=> "/public/api/video-participant-changed.php",
	"setting-changed"		=> "/public/api/setting-changed.php",
	"calendar-get-events"		=> "/public/api/calendar-get-events.php",
	"rooms-get-members"		=> "/public/api/rooms-get-members.php",
	"debug"				=> "/public/api/debug.php"
));

define("SETTINGS", array
(
	// keys are saved in DB so do not rename!
	// max key length in DB is 30 chars
	"video-text-popup-duration"	=> "int",
	"video-full-screen"		=> "bool"
));

require_once("config.inc.php");
require_once("language.inc.php");
require_once("template.inc.php");
require_once("login.inc.php");
require_once("settings.inc.php");
require_once("debug.inc.php");

login_preHeaderChecks();
settings_loadCachedSettings();
