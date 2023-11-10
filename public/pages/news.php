<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

template_drawHeader(LANG["page_title_news"], null, "");

/*
			All caught up (for now!) Report any issues using the "Contact us" button at the bottom of the page
*/

?>
	<h2>
		<?php echo LANG["news_title"]; ?>
	</h2>

	<p>
	Here's what I'm currently working on, and what I've achieved so far &mdash; Richard.

	<h3>
		Known bugs
	</h3>

	<ul>
		<li>
			Report any other issues using the "Contact us" button at the bottom of the page
		</li>
	</ul>

	<h3>
		Features planned
	</h3>

	<ul>
		<li>
			Editing and deleting events in the calendar (quite hard to do due to repeating events!)
		</li>
		<li>
			Show video chat occupants in the calendar for events that are currently happening.
		</li>
		<li>
			Filter the calendar to show only one-off events and not repeating events so you can see the new stuff. (Or would it be better to indicate events you've not seen before in some way?)
		</li>
		<li>
			Allow room description and image to be edited. (Maybe need a temporary edit history in case of destructive editing?)
		</li>
		<li>
			Allow people to leave "by invitation" rooms of which they are members
		<li>
			Make rooms "dormant" after a period of inactivity, and then delete after a period of being marked as dormant.
			(Also manually mark rooms as dormant when they're no longer required.
		</li>
		<li>
			Possibly allow the user who created a room to delete it? Or allow a room created in the last few days to be deleted by
			anyone?  (Basically an easy way to get rid of "test rooms", or rooms created in error.)
		</li>
		<li>
			Allow images for avatars on Jitsi video chat instead of initials (when video is turned off)
		</li>
		<li>
			When text chat is shown in their own window, fill the screen ("viewport")
		</li>
		<li>
			Other settings to configure Jitsi video chat to user preference
		</li>
		<li>
			Ability to delete text chat messages you've previously sent
		</li>
		<li>
			Privacy - ability to view all data stored on the site associated with your account
		</li>
		<li>
			Security - show the number of devices that are logged in on your account, and log them out remotely
		</li>
	</ul>

	<h3>
		Change Log
	</h3>

	<h4>
		Fri 10 Nov 2023
	</h4>

	<ul>
		<li>
			Bots have been trying to sign up for accounts, so I've implemented a "Captcha" on the forms
			for creating accounts or resetting your password.
		</li>
	</ul>

	<h4>
		Fri 2 Jun 2023
	</h4>

	<ul>
		<li>
			Jitsi have removed their free embedded API, so now I have to open Jitsi chats in a new window (on the Jitsi website).
			This means we can't do clever stuff with the user interface any longer, and can no longer see who is currently in a video chat.
			Instead I show when people last joined a video chat (if it's within the last 2 hours).
			Also the title of the window is gibberish (as it's the private ID of the chat room to stop anyone stumbling across our chats).
			Sadly there isn't anything much I can do about that.
		</li>
		<li>
			I've finally got around to publishing the code for Juggling Family as open source (as I originally promised I'd do).
			Sorry it's taken so long.  It's at <a href="https://github.com/richardloxley/Juggling-Family">https://github.com/richardloxley/Juggling-Family</a>
		</li>
	</ul>

	<h4>
		Mon 24 Apr 2023
	</h4>

	<ul>
		<li>
			External video chats (e.g. Skype) now have a list of people who have recently joined the chat.
			This list doesn't update when people leave (as the video chat is on another website) so the list only
			shows people who have joined the video chat in the last hour.  But at least it should give an idea
			of when an external video chat room is in use.
		</li>
	</ul>
	<h4>
		Fri 10 Feb 2023
	</h4>

	<ul>
		<li>
			I can now (manually) set rooms to have a link to an external video chat (e.g. Skype).  This is useful for rooms
			frequented by users who have problems with Jitsi.  Ask me if you want this configured on your room.
		</li>
	</ul>
	<h4>
		Wed 8 Dec 2021
	</h4>

	<ul>
		<li>
			There's a new (experimental) feature in video chat: click the "Participants" (two people) icon at the bottom.
			This will bring up a window where you can adjust the relative audio volumes of each participant.
			<p>
			It also allows you to "raise" the volume of someone above the default, something that the native Jitsi UI can't do.
			(Although it actually does this by lowering the volume of everyone else.  This means the person set to the highest relative volume will
			always be at the maximum volume your computer is set to.)
		</li>
	</ul>
	<h4>
		Mon 21 Jun 2021
	</h4>

	<ul>
		<li>
			Fixed a bug where phantom users appeared in video rooms if they didn't exit the video chat "cleanly"
			so an update wasn't sent to the server to say they'd left.
		</li>
	</ul>

	<h4>
		Fri 14 May 2021
	</h4>

	<ul>
		<li>
			New feature: "By invitation" rooms.  When you create a new room you have the option to make it "by invitation".  These rooms are hidden unless you've been invited by a member of the room.
			<p>
			Existing members of the room can create an invitation link to send to potential members.  If you open that link you will automatically be added to the room, and it will then appear in your list of rooms on the front page.
			<p>
			Temporary guest users aren't supported in these rooms. So if you're not logged into Juggling Family when you open the link, you'll be prompted to log in or create an account, and then you'll join the room once you log in.
			<p>
			Events in "by invitation" rooms will only appear in the site calendar for members of that room.  Such events won't appear in external calendar feeds.
		</li>
		<li>
			Tried to fix this problem: "When signing-in the email address is case-sensitive: you must use the same combination of capitals and lower case letters that you used when you registered".  Unfortunately it's hard to change the records as I encrypt your email address so have no access to it.  But when you next type in your email address to log in (matching the case when you first registered) your records will be updated so you don't have to match the case in the future.
			<p>
		</li>
		<li>
			Bug fix: monthly repeating events disappeared from the calendar as soon as the start time had passed, rather than waiting until the end time.
		</li>
	</ul>

	<h4>
		Wed 7 Apr 2021
	</h4>

	<ul>
		<li>
			Fixed a bug where events created in timezones other than UTC/GMT displayed incorrectly.
		</li>
	</ul>


	<h4>
		Sat 3 Apr 2021
	</h4>

	<ul>
		<li>
			The great egg hunt is on!  Get them while they're chocolatey.  For entertainment only, no redemption value ;-)
		</li>
	</ul>


	<h4>
		Thu 1 Apr 2021
	</h4>

	<ul>
		<li>
			Added a much requested button to the video chat.  Limited edition.  Just don't click it.  (Edit: too late, you missed it!)
		</li>
	</ul>


	<h4>
		Mon 8 Feb 2021
	</h4>

	<ul>
		<li>
			Rooms can now contain more than one video chat (individually named). This is useful for events.  Currently I have to manually
			create them in the database, but I hope to add user control before the next Bungay convention so we can use it for that.
		</li>
		<li>
			Along a similar line, other rooms can be referenced as "guest rooms" within another room, so their video chats appear in the
			list of video chats in that room.  So regular events in their own rooms can be "rolled into" one-off special events.  For example
			the Cryptic Crossword Club can appear within a weekend event that includes it as a "guest workshop".
		</li>
		<li>
			"Groups" now appear before "Interests", as they are more likely to be juggling-themed, and of more interest to casual visitors.
		</li>
		<li>
			Lists of video chats, and the number of people chatting, now appear in a room even if you can't join the video chat (e.g. because
			your device doesn't support it, or you aren't logged in.  That allows you to see if you want to bother to find a device that
			will support the video :-)
		</li>
		<li>
			"Dormant" rooms now get deleted when their expiry dates are reached.  I still need to manually mark them as dormant - I need to
			make a way for users to manually mark rooms as dormant, and possibly to automatically detect dormant rooms.
		</li>
	</ul>

	<h4>
		Wed 20 Jan 2021
	</h4>
	<ul>
		<li>
			You can now subscribe to calendars using your favourite external app (provided it supports the open standard Webcal/iCalendar/ICS). That includes Apple's MacOS and iOS Calendars, Outlook, and Google Calendar on Android and web.  Most other calendar apps should also support it.  Click the shiny new "Subscribe" button in the calendar for more details.
		</li>
	</ul>

	<h4>
		Thu 17 Dec 2020
	</h4>
	<ul>
		<li>
			Added a Frequently Asked Questions page (linked from the landing page and About page)
		</li>
		<li>
			Added a caption to the Open Graph preview image
		</li>
	</ul>

	<h4>
		Wed 16 Dec 2020
	</h4>
	<ul>
		<li>
			Added an About page, and a "landing" page if you're not logged in to explain what the site is about.
		</li>
		<li>
			Supply "Open Graph" metadata on all pages, so you get a correct image preview and description if you share the page on social media.
		</li>
	</ul>

	<h4>
		Mon 14 Dec 2020
	</h4>
	<ul>
		<li>
			Basic editing of events is now possible.  Although editing a repeating event currently affects all instances of the event.  This will be improved in the future!  No deletion of events yet, but a work-around for now is to edit it to be in the past and then it gets deleted automatically!
		</li>
	</ul>

	<h4>
		Fri 11 Dec 2020
	</h4>
	<ul>
		<li>
			Re-arranged the front page and make some sections "collapsible" to allow choices over what's on view.  A few bug fixes.
		</li>
	</ul>

	<h4>
		Thu 10 Dec 2020
	</h4>
	<ul>
		<li>
			The user editable calendar is now live!  (Actually done over the last few weeks as this was a HUGE piece of work! Very hard due to time zones, daylight saving time, repeating events, etc!).  Still needs editing/deleting of events, etc.
		</li>
	</ul>

	<h4>
		Wed 9 Dec 2020
	</h4>
	<ul>
		<li>
			Fixed: <i>Error messages when submitting forms aren't always formatted so they are obvious.</i>
		</li>
		<li>
			Can't solve this (seems to be a problem at Jitsi's end, maybe with iOS 11?)  Workaround is to use mobile app version. <i>Video chat isn't working reliably on an iPad running iOS 11 (error message from Jitsi "something has gone wrong").</i>
		</li>
	</ul>

	<h4>
		Mon 15 Nov 2020
	</h4>
	<ul>
		<li>
			On mobile devices you can now save the site to your Home screen as a web app!  It will then have the JF icon,
			and open as a full screen app.  Much easier for getting to the chat quickly :-)
		</li>
		<li>
			Since Jitsi isn't working reliably in the browser on iOS, I've changed the message under the "Join video chat"
			button to suggest using the mobile app if you have problems.  I've also changed the button to pink if you've
			selected the mobile app, so it's clearer which option you've selected.
		</li>
		<li>
			Fixed: <i>slider in settings doesn't fit on small mobile screens</i>
		</li>
	</ul>

	<h4>
		Sat 14 Nov 2020
	</h4>
	<ul>
		<li>
			Revamped settings screen and divided into sections
		</li>
		<li>
			Setting to change time for pop-up notifications of text messages in the video chat (or disable entirely)
		</li>
		<li>
			Setting to allow video chat to start full screen
		</li>
		<li>
			Fixed: <i>Pop-up notifications of chat messages in video chat should have bigger text (and maybe wider box?)</i>
		</li>
		<li>
			When sending video chat messages to the room text chat using the &gt;&gt;&gt; prefix, I'm now showing the &gt;&gt;&gt; prefix
			in the room chat to make clear it came from the video chat (and by displaying it people who don't know about the feature
			will see it and ask!)
		</li>
		<li>
			Fixed: <i>Footer buttons formatting poor on mobile screens (add a non-breaking space between icon and label)</i>
		</li>
		<li>
			Added icons to app store buttons
		</li>
		<li>
			Can't reproduce this: <i>In Firefox the pop-up notifications of chat messages in video chat are hidden behind an icon showing the webcam is in use</i>
		</li>
	</ul>

	<h4>
		Fri 13 Nov 2020
	</h4>
	<ul>
		<li>
			More research on calendars: I've created a provisional database structure that might be sufficient.
		</li>
	</ul>

	<h4>
		Thu 12 Nov 2020
	</h4>
	<ul>
		<li>
			Added a manual calendar section as a stop-gap till the real calendar is ready.
		</li>
		<li>
			Added this "Site News" page.
		</li>
		<li>
			Fixed: <i>Long words (including links) don't wrap in text chat in narrow windows</i>.
		</li>
		<li>
			Fixed (I think): <i>"Hover" timestamps in text chat don't work reliably on iOS</i>.  iOS uses a non-standard
			way of deciding if a hoverable element should display when you touch it.  I've added a suggested hack to
			the pages to try to force it to always work.  We'll see!
		</li>
		<li>
			Fixed: <i>Styling of "remember me" tick box wrong on iOS 9</i>
		</li>
		<li>
			Tried to fix: <i>Setting/changing password doesn't trigger browser to suggest a secure password</i>.
			<br>
			Sadly I can't easily fix this. The reason is the browser can only suggest and save the password if it knows
			the email address you will use to log into the site. But I can't access this, as I save this in an encrypted
			(hashed) form, so it isn't accessible at the point you choose the password.
			<br>
			When you subsequently log in,
			you are typing both your email address and password, so the browser will offer to remember it at this point.
			<br>
			I could fix this, but it would involve work and one of the following compromises:
			<ul>
				<li>
					storing user email addresses in plain text, which I don't really want to do
					from a privacy and security point of view
				</li>
				<li>
					by getting you to type your email in again, but that adds extra
					friction to the sign-up process
				</li>
				<li>
					by including your email in plain text in the verification link I email you and pre-populating
					an email input box in the password change form - which would probably work, but I haven't tested it,
					and it would make the links somewhat long, and risk the link getting broken into multiple lines in
					your email client making it unclickable (or broken)
				</li>
			</ul>
			There's no one perfect solution sadly, so I propose not doing anything unless you'd like to convince me it's more
			important than other items on the to-do list!
		</li>
	</ul>


	<h4>
		Wed 11 Nov 2020
	</h4>
	<ul>
		<li>
			Just research on calendars.  Nothing to show yet.  It's complicated!
		</li>
	</ul>

	<h4>
		Tue 10 Nov 2020
	</h4>
	<ul>
		<li>
			In video chat, you can now put "&gt;&gt;&gt;" at the start of a text message and it will be copied to the room's text chat.
			Useful for information you want to be available after the video chat ends!
		</li>
		<li>
			In video chat, text messages now appear as a pop-up notification (as well as in the text message sidebar).
		</li>

		<li>
			Video chats now have a "full screen" button in the top left.
		</li>
	</ul>

	<h4>
		Mon 9 Nov 2020
	</h4>
	<ul>
		<li>
			Fixed video issues (by matching the settings on the older Virtual BBU site
		</li>
		<li>
			Better colour palette
		</li>
		<li>
			Fixed overflowing text on small screens
		</li>
		<li>
			Promote the Juggling Edge for permanent chat
		</li>
		<li>
			"Breadcrumb" trail now lists room category as part of the room name as a separate entry was confusing
		</li>
		<li>
			Moved "hover" timestamps above text chat messages so they are visible on long messages that fill the width of the screen
		</li>
	</ul>

	<h4>
		Sun 8 Nov 2020
	</h4>
	<ul>
		<li>
			Site live for beta testers!
		</li>
	</ul>



<?php

template_drawFooter();
