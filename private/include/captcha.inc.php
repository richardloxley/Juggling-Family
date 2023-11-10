<?php

// currently we're using Cloudflare Turnstile

// https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/
// https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
// https://clifford.io/blog/how-to-implement-cloudflare-turnstile-with-php-to-protect-your-webforms-a-super-simple-example/


function captcha_isActive()
{
	return isset(CONFIG["captcha_cloudflare_site_key"]) && isset(CONFIG["captcha_cloudflare_secret_key"]);
}


function captcha_javascript()
{
	if (captcha_isActive())
	{
		echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>';
	}
}


function captcha_formClass()
{
	if (captcha_isActive())
	{
		return "cf-turnstile";
	}
	else
	{
		return "";
	}
}


function captcha_formData()
{
	if (captcha_isActive())
	{
		return "data-sitekey='" . CONFIG["captcha_cloudflare_site_key"] . "'";
	}
	else
	{
		return "";
	}
}


function captcha_validate()
{
	if (!captcha_isActive())
	{
		return true;
	}


	$secret = CONFIG["captcha_cloudflare_secret_key"];
	$remote_addr = $_SERVER['REMOTE_ADDR'];
	$cf_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	$token = $_POST['cf-turnstile-response'];

	$data = array
	(
		"secret" => $secret,
		"response" => $token,
		"remoteip" => $remote_addr
	);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $cf_url);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($curl);

	$passed = false;

	if (curl_errno($curl))
	{
		$error_message = curl_error($curl);
		error_log("Cloudflare Turnstile error: $error_message");
	}
	else
	{
		$json = json_decode($response);

		if ($json !== null && isset($json->success))
		{
			if ($json->success === true)
			{
				$passed = true;
			}
			else
			{
				$error_message = "Cloudflare Turnstile errors ";
				if (isset($json->{"error-codes"}))
				{
					foreach ($json->{"error-codes"} as $code)
					{
						$error_message .= ": $code ";
					}
				}

				error_log($error_message);
			}
		}
		else
		{
			error_log("Invalid response from Cloudflare Turnstile");
		}
	}

	curl_close($curl);

	return $passed;
}
