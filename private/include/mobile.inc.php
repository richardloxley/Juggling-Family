<?php


function mobile_isMobile()
{
	return (mobile_isAndroid() || mobile_isIos());
}


function mobile_mightBeMobile()
{
	return (mobile_isAndroid() || mobile_isApple());
}


function mobile_isAndroid()
{
	$android = (stripos($_SERVER['HTTP_USER_AGENT'],"Android") !== false);

	return $android;
}


function mobile_isIos()
{
	$iPod    = (stripos($_SERVER['HTTP_USER_AGENT'],"iPod") !== false);
	$iPhone  = (stripos($_SERVER['HTTP_USER_AGENT'],"iPhone") !== false);
	$iPad    = (stripos($_SERVER['HTTP_USER_AGENT'],"iPad") !== false);

	// iPadOS isn't caught by the above, but Jitsi seems to work in iOS 13 Safari anyway.
	// If we want to promote app we could investigate using JS to see if the browser supports multi-touch to identify it
	// https://stackoverflow.com/questions/58019463/how-to-detect-device-name-in-safari-on-ios-13-while-it-doesnt-show-the-correct
	// $safari  = stripos($_SERVER['HTTP_USER_AGENT'],"Safari");
	// JS: let isIOS = /iPad|iPhone|iPod/.test(navigator.platform) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)

	return ($iPod || $iPhone || $iPad);
}


function mobile_isApple()
{
	// also includes iPadOS as that pretends to be a Mac!
	$macintosh = (stripos($_SERVER['HTTP_USER_AGENT'],"Macintosh") !== false);

	return ($macintosh || mobile_isIos());
}


function mobile_iosMajorVersion()
{
	if (!mobile_isIos())
	{
		return 0;
	}

	return (int)preg_replace("/.*OS ([1-9][0-9]*)_[0-9].*/", "$1", $_SERVER['HTTP_USER_AGENT']);
}


