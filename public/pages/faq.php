<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

template_drawHeader(LANG["page_title_faq"], null, "");

?>
	<h2>
		<?php echo LANG["faq_title"]; ?>
	</h2>

	<p>

	<div class="faq">
		<div class="faq-heading">
		How does video chat work?
		</div>
		<div class="faq-body">
		We use a service called "Jitsi" to embed video chat in the website.  Click the "Join Video Chat" button and it should
		just work!  You may need to tell your web browser or your computer to allow access to your microphone and camera.
		<p>
		On mobile devices, video should work in the browser, but you may find it's better if you download the Jitsi mobile app.
		For more details, see the Video section of the Settings page (once you're logged in).
		</div>
	</div>

	<div class="faq">
		<div class="faq-heading">
		How do I stop echoes in video chat?
		</div>
		<div class="faq-body">
		Echoes happen when someone in the chat has their microphone in front of their speakers.  If everyone uses headphones
		instead of speakers, the echo goes away.  If you don't have headphones you can press the "microphone" button in the
		video chat to mute your microphone when you're not speaking.
		</div>
	</div>

	<div class="faq">
		<div class="faq-heading">
		Why is this site different from Zoom / Skype / Google Hangouts / Discord / etc?
		</div>
		<div class="faq-body">
		There are many video chatting tools available.  I think juggling.family is different because:
		<ul>
			<li>
				It's free
			</li>
			<li>
				You don't need to give away any personal information to use it
			</li>
			<li>
				You don't need to install any software
			</li>
			<li>
				You don't need a "host" to start a video chat - the rooms are always there, and anyone can join
				them at any time
			</li>
			<li>
				You can see who else is in a video chat before you join it
			</li>
			<li>
				There is a directory of "rooms" so you can find (or create) a sub-area for your particular group or interest
			</li>
			<li>
				Rooms are linked to calendars, so you can schedule events and know when to come back and join in
			</li>
			<li>
				There is a text chat in each room that lasts for 7 days, so you can make arrangements for later
			</li>
			<li>
				It can cope with enough participants for social video chatting (generally up to about 25 people).
			</li>
			<li>
				It's run by a juggler (Richard Loxley) free of charge for the benefit of the community
			</li>
		</ul>
		Juggling.family isn't perfect, and there are some reasons you may want to use another platform instead:
		<ul>
			<li>
				Video quality is reasonable, but is not as good as those that charge money
			</li>
			<li>
				Video chatting sometimes doesn't work on very old computers or older web browsers
			</li>
			<li>
				Video chats cope with up to 20-25 participants, but above that things get a bit sluggish
			</li>
			<li>
				Nothing is private - all chats are accesssible to any user of the site
			</li>
		</div>
	</div>

	<div class="faq">
		<div class="faq-heading">
		Can I run a show in a video chat?
		</div>
		<div class="faq-body">
		You are welcome to try, but I'd suggest the experience may be disappointing unless your audience is small (up to 20-25 people).
		Above that the video software we use (Jitsi) struggles a bit.
		<p>
		Zoom or Twitch may be better platforms for larger events.
		<p>
		If you do want to try, I suggest asking viewers to turn off their cameras and microphones, to save bandwidth.
		</div>
	</div>

	<div class="faq">
		<div class="faq-heading">
		Technical details
		</div>
		<div class="faq-body">
		Juggling.family was written by me (Richard Loxley), an evolution of the software I wrote for the Virtual Bungay Balls Up convention.
		<p>
		The embedded video uses the open-source Jitsi software, which is hosted for us free of charge by Jitsi Meet.  In return they show
		a static advert when you hang up from the video chat.
		<p>
		Jitsi uses the WebRTC video protocol supported by most modern browsers.  Very old browsers or computers which don't have WebRTC
		can't use the video chat.
		<p>
		See the Privacy page for the information we store.  See the News page for what I'm working on.  See the Contact page if you'd like
		to get in touch with me.
		</div>
	</div>

	<p>
<?php

template_drawFooter();
