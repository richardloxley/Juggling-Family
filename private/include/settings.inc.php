<?php


$settings_cachedSettings = array();


function settings_loadCachedSettings()
{
	$userId = login_getUserId();

	if ($userId === null)
	{
		// not logged in, use defaults
		return;
	}

	global $settings_cachedSettings;

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "select setting, value from settings where user_id = ?");
		mysqli_stmt_bind_param($st, "i", $userId);
		mysqli_stmt_execute($st);
		mysqli_stmt_bind_result($st, $key, $value);

		while (mysqli_stmt_fetch($st))
		{
			$settings_cachedSettings[$key] = $value;
		}

		mysqli_stmt_close($st);
        }
        catch (mysqli_sql_exception $e)
        {
                error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
        }
}


function settings_getSetting($key, $defaultValue)
{
	global $settings_cachedSettings;

	if (isset($settings_cachedSettings[$key]))
	{
		return $settings_cachedSettings[$key];
	}
	else
	{
		return $defaultValue;
	}
}


function settings_saveSetting($key, $value)
{
	$userId = login_getUserId();

	if ($userId === null)
	{
		// not logged in, shouldn't happen
		return;
	}

	try
	{
		$db = database_getConnection();
		$st = mysqli_prepare($db, "insert into settings (user_id, setting, value) values (?, ?, ?) on duplicate key update value = ?");
		mysqli_stmt_bind_param($st, "isss", $userId, $key, $value, $value);
		mysqli_stmt_execute($st);
		mysqli_stmt_close($st);

		global $settings_cachedSettings;
		$settings_cachedSettings[$key] = $value;
        }
        catch (mysqli_sql_exception $e)
        {
                error_log(__FILE__ . ":" . __LINE__ . " " . $e->getMessage());
        }
}


function settings_apiSettingChanged()
{
	if (!login_isLoggedIn())
	{
		return;
	}

	if (!isset($_POST["key"]) || !isset($_POST["value"]))
	{
		return;
	}

	$key = $_POST["key"];
	$value = $_POST["value"];

	if (isset(SETTINGS[$key]))
	{
		$type = SETTINGS[$key];

		if ($type == "int")
		{
			$validatedValue = strval(intval($value));
			settings_saveSetting($key, $validatedValue);
		}
		else if ($type == "bool")
		{
			if ($value === "1" || $value === "true")
			{
				settings_saveSetting($key, "1");
			}
			else
			{
				settings_saveSetting($key, "0");
			}
		}
		// ... add more types here
	}
}


function settings_drawHeader($sectionId, $titleKey)
{
	?>
		<div id='<?php echo $sectionId; ?>' class='settings-section'>
			<div class='settings-header'>
				<div class='settings-title'>
					<?php echo LANG[$titleKey] ?>
				</div>
			</div>
			<div class='settings-body'>
	<?php
}


function settings_drawFooter()
{
	?>
			</div>
			<div class='settings-footer'>
			</div>
		</div>
	<?php
}


function settings_javascript()
{
	if (isset($_GET["open"]))
	{
		$sectionToOpen = preg_replace('/[^a-z]/', '', $_GET["open"]);

		?>
			<script type="text/javascript">

				$(document).ready(function()
				{
					$('#<?php echo $sectionToOpen; ?>').find(".settings-body").show();
				});

			</script>
		<?php
	}

	?>
		<script type="text/javascript">

			$(document).ready(function()
			{
				$('.settings-header').click(function()
				{
					var section = $(this).closest(".settings-section");
					var body = section.find(".settings-body");

					if (body.is(":visible"))
					{
						body.hide();
					}
					else
					{
						// hide any other sections that might be open
						$(".settings-body").hide();
						body.show();
					}
				});
			});

		</script>
	<?php
}
