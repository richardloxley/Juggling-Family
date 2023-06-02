<?php


function module_drawModuleHeader($moduleId, $titleKey, $simpleUi, $showFullScreenControl, $isFullScreen, $fullScreenLink)
{
	if ($fullScreenLink == "")
	{
		$fullScreenLink = PUBLIC_URL["index"];
	}

	if ($simpleUi)
	{
		$moduleClass = "module-simple";
	}
	else
	{
		$moduleClass = "module-full";
	}

	if ($isFullScreen)
	{
		$moduleClass .= " module-full-screen";
	}

	?>
		<div id='<?php echo $moduleId; ?>' class='module-id <?php echo $moduleClass; ?>'>
			<div class='module-header'>
				<?php
					if (!$simpleUi)
					{
						if (!$isFullScreen)
						{
							?>
								<div class='show-if-has-js'>
									<div class='module-show-button'>
										<span class='module-header-icon'>
											<?php echo icon_show(); ?>
										</span>
										<span class='module-header-button-label'>
											<?php echo LANG["module_button_show"]; ?>
										</span>
									</div>
									<div class='module-hide-button'>
										<span class='module-header-icon'>
											<?php echo icon_hide(); ?>
										</span>
										<span class='module-header-button-label'>
											<?php echo LANG["module_button_hide"]; ?>
										</span>
									</div>
								</div>
								<?php
									/*
									<div class='show-if-no-js'>
										<div class='module-refresh-button'>
											<a href='<?php echo $_SERVER['REQUEST_URI'] ?>'>
												<span class='module-header-icon'>
													<?php echo icon_refresh(); ?>
												</span>
												<span class='module-header-button-label'>
													<?php echo LANG["module_button_refresh"]; ?>
												</span>
											</a>
										</div>
									</div>
									*/
								?>

								<?php
									if ($showFullScreenControl)
									{
								?>
										<div class='module-full-screen-button'>
											<a href='<?php echo $fullScreenLink; ?>'>
												<span class='module-header-button-label'>
													<?php echo LANG["module_button_full_screen"]; ?>
												</span>
												<span class='module-header-icon'>
													<?php echo icon_fullScreen(); ?>
												</span>
											</a>
										</div>
								<?php
									}
								?>
							<?php
						}
						else if ($showFullScreenControl)
						{
							?>
								<div class='module-full-screen-button'>
									<a href='<?php echo $fullScreenLink; ?>'>
										<span class='module-header-button-label'>
											<?php echo LANG["module_button_exit_full_screen"]; ?>
										</span>
										<span class='module-header-icon'>
											<?php echo icon_minimise(); ?>
										</span>
									</a>
								</div>
							<?php
						}
					}
				?>

				<div class='module-title'>
					<?php echo LANG[$titleKey] ?>
				</div>
			</div>
			<div class='module-body'>
	<?php
}


function module_drawModuleFooter()
{
	?>
			</div>
			<div class='module-footer'>
			</div>
		</div>
	<?php
}


function module_javascriptModule()
{
	?>
		<script type="text/javascript">

			function changeModuleVisibility(elementInModule, visible, rememberThis)
			{
				// find the outer div (which also gives the ID of the module)
				var module = elementInModule.closest(".module-id");
				var cookie = "hide-" + module.attr("id");

				// find components
				var moduleBody = module.find(".module-body");
				var showButton = module.find(".module-show-button");
				var hideButton = module.find(".module-hide-button");
				var backgroundUpdates = module.find(".module-background-update");
				
				// toggle state
				if (visible)
				{
					// toggle body visibility
					moduleBody.show();
					// toggle show/hide buttons
					hideButton.show();
					showButton.hide();
					// tell the module its state has changed
					module.triggerHandler("show");
					// modify background task processing
					backgroundUpdates.triggerHandler("startBackgroundTasks");
					// save state in a cookie
					if (rememberThis)
					{
						deleteCookie(cookie);
					}
				}
				else
				{
					// toggle body visibility
					moduleBody.hide();
					// toggle show/hide buttons
					hideButton.hide();
					showButton.show();
					// tell the module its state has changed
					module.triggerHandler("hide");
					// modify background task processing
					backgroundUpdates.triggerHandler("stopBackgroundTasks");
					// save state in a cookie
					if (rememberThis)
					{
						setCookie(cookie, "1");
					}
				}
			}

			function hideAllModules()
			{
				$(".module-id").each(function()
				{
					changeModuleVisibility($(this), false, false);
				});
			}

			$(document).ready(function()
			{
				$(".module-id").each(function()
				{
					// hide module if cookie says so
					var cookie = "hide-" + $(this).attr("id");
					if (getCookie(cookie) != "")
					{
						changeModuleVisibility($(this), false, false);
					}
				});

				$('.module-show-button').click(function()
				{
					changeModuleVisibility($(this), true, true);
				});

				$('.module-hide-button').click(function()
				{
					changeModuleVisibility($(this), false, true);
				});
			});
		</script>
	<?php
}



// contentFunction() takes
//	a string representing the last entry output to the user (interpretted however the function likes)
//	the time the server last sent new output (as received from the server previously)
// if there is new content, it returns an array:
//	"lastId" => the new last entry,
//	"html" => the rendered output
// if there is no new content, it returns false
function module_serverCheckForNewContent($contentFunction)
{
	$lastId = "";
	$lastTime = "";

	if (isset($_GET["lastId"]))
	{
		$lastId = $_GET["lastId"];
	}

	if (isset($_GET["lastTime"]))
	{
		$lastTime = $_GET["lastTime"];
	}

	$result = $contentFunction($lastId, $lastTime);

	if ($result === false)
	{
		// no new content
		header("HTTP/1.1 304 Not Modified");
	}
	else
	{
		// record time
		$result["lastTime"] = time_nowInUtcString();

		// new content, return it as JSON
		header('Content-Type: application/json');
		echo json_encode($result);
	}
}


function module_clientRefreshNowJsFunctionName($divId)
{
	// we have multiple modules so we need to prefix all the JavaScript names to avoid name clashes
	$jsName = preg_replace('/[^a-zA-Z0-9]/', '', $divId);
	$refreshNowFunctionName = "refresh_now_" . $jsName;
	return $refreshNowFunctionName;
}


// url = location of script that calls module_serverCheckForNewContent()
// divID = where to put the rendered HTML
// refreshRateConfigKey = array in the config file specifying refresh rates
// append = bool, whether to append HTML or replace it
function module_clientStartBackgroundRefresh($url, $jsExtraParamsFunctionName, $jsPostProcessContentFunctionName, $divId, $refreshRateConfigKey, $append)
{
	$javascriptRefreshRateArray = json_encode(CONFIG[$refreshRateConfigKey]);

	// we have multiple modules so we need to prefix all the JavaScript names to avoid name clashes
	$jsName = preg_replace('/[^a-zA-Z0-9]/', '', $divId);
	$refreshRateArrayName = "refresh_rate_" . $jsName;
	$lastIdReceivedVariableName = "last_id_received_" . $jsName;
	$lastServerTimeReceivedVariableName = "last_server_time_received_" . $jsName;
	$lastTimeReceivedVariableName = "last_time_received_" . $jsName;
	$refreshFunctionName = "refresh_" . $jsName;
	$refreshNowFunctionName = "refresh_now_" . $jsName;
	$startFunctionName = "start_" . $jsName;
	$stopFunctionName = "stop_" . $jsName;
	$timerName = "timer_" . $jsName;
	$timerStoppingName = "timer_stopping_" . $jsName;

	?>
		<script type="text/javascript">

			const <?php echo $refreshRateArrayName; ?> = <?php echo $javascriptRefreshRateArray; ?>;

			var <?php echo $lastIdReceivedVariableName; ?> = "";
			var <?php echo $lastServerTimeReceivedVariableName; ?> = "";
			var <?php echo $lastTimeReceivedVariableName; ?> = new Date();
			var <?php echo $timerName; ?>;
			var <?php echo $timerStoppingName; ?> = false;

			$(document).ready(function()
			{
				// start on page load (if div is visible)
				if ($("#<?php echo $divId; ?>").is(":visible"))
				{
					<?php echo $startFunctionName; ?>();
				}

				$("#<?php echo $divId; ?>").bind("startBackgroundTasks", function()
				{
					// start if div is shown
					<?php echo $startFunctionName; ?>();
				});

				$("#<?php echo $divId; ?>").bind("stopBackgroundTasks", function()
				{
					// stop if div is hidden
					<?php echo $stopFunctionName; ?>();
				});

				$(document).on('show', function()
				{
					// start if page is shown (and div is visible)
					if ($("#<?php echo $divId; ?>").is(":visible"))
					{
						<?php echo $startFunctionName; ?>();
					}
				});

				$(document).on('hide', function()
				{
					// stop if page is hidden
					<?php echo $stopFunctionName; ?>();
				});
			});

			function <?php echo $startFunctionName; ?>()
			{
				debug('<?php echo $divId; ?>' + ': start background tasks');
				<?php echo $timerStoppingName; ?> = false;
				// reset counter as the user is now active
				<?php echo $lastTimeReceivedVariableName; ?> = new Date();
				<?php echo $refreshFunctionName; ?>();
			}

			function <?php echo $stopFunctionName; ?>()
			{
				debug('<?php echo $divId; ?>' + ': stop background tasks');
				<?php echo $timerStoppingName; ?> = true;
				clearTimeout(<?php echo $timerName; ?>);
			}

			function <?php echo $refreshNowFunctionName; ?>(forceUpdate)
			{
				// if the UI has triggered a content change, don't wait for timer to fire

				// if requested, clear state so we force update of all content
				if (forceUpdate)
				{
					<?php echo $lastIdReceivedVariableName; ?> = "";
					<?php echo $lastServerTimeReceivedVariableName; ?> = "";
				}

				// cancel timer and get content now
				<?php echo $stopFunctionName; ?>();
				<?php echo $startFunctionName; ?>();
			}

			function <?php echo $refreshFunctionName; ?>()
			{
				debug('<?php echo $divId; ?>' + ': running tasks');

				var apiUrl = "<?php echo $url; ?>";

				<?php
					if ($jsExtraParamsFunctionName != "")
					{
				?>
						apiUrl = <?php echo $jsExtraParamsFunctionName; ?>(apiUrl);
				<?php
					}
				?>

				$.ajax(
				{
					url: apiUrl,
					data:
					{
						lastId: <?php echo $lastIdReceivedVariableName; ?>,
						lastTime: <?php echo $lastServerTimeReceivedVariableName; ?>,
					},
					dataType: "json",
					success: function(json, textStatus, jqXHR)
					{
						if (jqXHR.status == 200)
						{
							// check we actually have new content
							if (json.lastId != <?php echo $lastIdReceivedVariableName; ?>)
							{
								debug('<?php echo $divId; ?>' + ': got new content');

								// remember where we got to so we don't request it again
								<?php echo $lastIdReceivedVariableName; ?> = json.lastId;
								<?php echo $lastServerTimeReceivedVariableName; ?> = json.lastTime;

								// reset refresh timer to beginning
								<?php echo $lastTimeReceivedVariableName; ?> = new Date();

								<?php
									if ($append) 
									{
								?>
										// append content
										$("#<?php echo $divId; ?>").append(json.html);
										// scroll to bottom
										$('#<?php echo $divId; ?>').scrollTop($('#<?php echo $divId; ?>')[0].scrollHeight - $('#<?php echo $divId; ?>')[0].clientHeight);
								<?php
									}
									else
									{
								?>
										// replace content
										$("#<?php echo $divId; ?>").html(json.html);
								<?php
									}

									// do any post-processing of new content
									if ($jsPostProcessContentFunctionName != "")
									{
								?>
										<?php echo $jsPostProcessContentFunctionName; ?>($("#<?php echo $divId; ?>"));
								<?php
									}
								?>
							}
						}
					},
				}).always(function()
				{
					if (!<?php echo $timerStoppingName; ?>)
					{
						// calculate how long to wait before next refresh

						var elapsed = new Date() - <?php echo $lastTimeReceivedVariableName; ?>;
						var newDuration = 1000;

						for (var limit in <?php echo $refreshRateArrayName; ?>)
						{
							if (elapsed > limit * 1000)
							{
								newDuration = <?php echo $refreshRateArrayName; ?>[limit] * 1000;
							}
						}

						debug('<?php echo $divId; ?>' + ': setting new timeout to ' + newDuration);
						<?php echo $timerName; ?> = setTimeout(<?php echo $refreshFunctionName; ?>, newDuration);
					}
				});
			}
		</script>
	<?php
}
