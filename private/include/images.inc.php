<?php


function images_uploadFile($uploadFilename, $savedFilename, $directory, $newAspectRatio, $newHeight = false)
{
	// is it an image?
	if (($size = getimagesize($uploadFilename)) !== false)
	{
		list($originalWidth, $originalHeight, $type, $attr) = $size;

		$originalAspectRatio = $originalWidth / $originalHeight;


		if ($originalAspectRatio > $newAspectRatio)
		{
			// crop sides
			$cropHeight = $originalHeight;
			$cropWidth = $originalHeight / $newAspectRatio;
			$cropX = ($originalWidth - $cropWidth) / 2;
			$cropY = 0;

			if ($newHeight === false)
			{
				$newHeight = $originalHeight;
			}
		}
		else
		{
			// crop top/bottom
			$cropWidth = $originalWidth;
			$cropHeight = $originalWidth / $newAspectRatio;
			$cropY = ($originalHeight - $cropHeight) / 2;
			$cropX = 0;

			if ($newHeight === false)
			{
				$newHeight = $cropHeight;
			}
		}

		$newWidth = $newAspectRatio * $newHeight;

		$original = imagecreatefromstring(file_get_contents($uploadFilename));
		$resized = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($resized, $original, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $cropWidth, $cropHeight);
		imagedestroy($original);
		imagejpeg($resized, "$directory$savedFilename.jpg");
		imagedestroy($resized);
	}
}


function images_deleteFile($filename, $directory)
{
	$path = "$directory$filename.jpg";
	if (file_exists($path))
	{
		unlink($path);
	}
}


// RGB values: 0-255, 0-255, 0-255
// HSV values: 0-360, 0-1, 0-1
// takes array {r, g, b}
// returns array {h, s, v}
//
// by https://stackoverflow.com/users/629493/unsigned
// https://stackoverflow.com/questions/1773698/rgb-to-hsv-in-php
//
function image_rgbToHsv($colourRgb)
{
	list($r, $g, $b) = $colourRgb;

	// Convert the RGB byte-values to percentages
	$R = $r / 255;
	$G = $g / 255;
	$B = $b / 255;

	// Calculate a few basic values, the maximum value of R,G,B, the
	// minimum value, and the difference of the two (chroma).
	$maxRGB = max($R, $G, $B);
	$minRGB = min($R, $G, $B);
	$chroma = $maxRGB - $minRGB;

	// Value (also called Brightness) is the easiest component to calculate,
	// and is simply the highest value among the R,G,B components.
	$computedV = $maxRGB;

	// Special case if hueless (equal parts RGB make black, white, or grays)
	// Note that Hue is technically undefined when chroma is zero, as
	// attempting to calculate it would cause division by zero (see
	// below), so most applications simply substitute a Hue of zero.
	// Saturation will always be zero in this case, see below for details.
	if ($chroma == 0)
	{
		return array(0, 0, $computedV);
	}

	// Saturation is also simple to compute, and is simply the chroma
	// over the Value (or Brightness)
	$computedS = $chroma / $maxRGB;

	// Calculate Hue component
	// Hue is calculated on the "chromacity plane", which is represented
	// as a 2D hexagon, divided into six 60-degree sectors. We calculate
	// the bisecting angle as a value 0 <= x < 6, that represents which
	// portion of which sector the line falls on.
	if ($R == $minRGB)
	{
		$h = 3 - (($G - $B) / $chroma);
	}
	else if ($B == $minRGB)
	{
		$h = 1 - (($R - $G) / $chroma);
	}
	else
	{
		// $G == $minRGB
		$h = 5 - (($B - $R) / $chroma);
	}

	// After we have the sector position, we multiply it by the size of
	// each sector's arc (60 degrees) to obtain the angle in degrees.
	$computedH = 60 * $h;

	return array($computedH, $computedS, $computedV);
}



// HSV values: 0-360, 0-1, 0-1
// RGB values: 0-255, 0-255, 0-255
// takes array {h, s, v}
// returns array {r, g, b}
//
// https://www.cs.rit.edu/~ncs/color/t_convert.html
// https://en.wikipedia.org/wiki/HSL_and_HSV#Conversion_from_RGB_to_HSL_or_HSV
//
function image_hsvToRgb($colourHsv)
{
	list($h, $s, $v) = $colourHsv;

	$h /= 60;	// sector 0 to 5
	$i = floor($h);
	$f = $h - $i;	// factorial part of h
	$p = $v * (1 - $s);
	$q = $v * (1 - $s * $f);
	$t = $v * (1 - $s * (1 - $f));

	switch ($i)
	{
		case 0:
			$r = $v;
			$g = $t;
			$b = $p;
			break;
		case 1:
			$r = $q;
			$g = $v;
			$b = $p;
			break;
		case 2:
			$r = $p;
			$g = $v;
			$b = $t;
			break;
		case 3:
			$r = $p;
			$g = $q;
			$b = $v;
			break;
		case 4:
			$r = $t;
			$g = $p;
			$b = $v;
			break;
		default: // case 5:
			$r = $v;
			$g = $p;
			$b = $q;
			break;
	}

	return array($r * 255, $g * 255, $b * 255);
}


// RGB values: 0-255, 0-255, 0-255
// takes array {r, g, b}
// returns array {r, g, b}
// 
// finds a colour with a hue 180 degrees away, with full brightness,
// and full/low saturation depending on if the original colour is light/dark
//
function image_contrastingColour($colourRgb)
{
	list($h, $s, $v) = image_rgbToHsv($colourRgb);

	$contrastH = ($h + 180) % 360;

	$lightness = ((2 - $s) * $v) / 2;

	if ($lightness < 0.4)
	{
		$contrastS = 0.3;
		$contrastV = 1;
	}
	else if ($lightness < 0.9)
	{
		$contrastS = 1;
		$contrastV = 1;
	}
	else
	{
		$contrastS = 1;
		$contrastV = 0.5;
	}

	$hsv = array($contrastH, $contrastS, $contrastV);
	return image_hsvToRgb($hsv);
}


// RGB values: 0-255, 0-255, 0-255
// returns array {r, g, b}
function image_averageColour($image, $x, $y, $width, $height)
{
	// find average colour of image by shrinking it down to a single pixel
	$pixel = imagecreatetruecolor(1, 1);
	imagecopyresampled($pixel, $image, 0, 0, $x, $y, 1, 1, $width, $height);
	$pixelColour = imagecolorat($pixel, 0, 0);
	$rgb = imagecolorsforindex($image, $pixelColour);

	return array($rgb["red"], $rgb["green"], $rgb["blue"]);
}



function image_drawContrastTextFittingInCircle($image, $text, $fontFilename, $fillPercent, $outlinePercent)
{
	// size of the image, and the radius of the circle inside it
	$imageWidth = imagesx($image);
	$imageHeight = imagesy($image);
	$imageRadius = $imageWidth / 2;

	// try an initial text size and find the bounding box
	$TEXT_STARTING_SIZE = 100;
	$textBoundingBox = imagettfbbox($TEXT_STARTING_SIZE, 0, $fontFilename, $text);

	// resize the text so the diagonal of the bounding box fits inside the radius of the circle of the final image
	$textWidth = $textBoundingBox[2] - $textBoundingBox[6];
	$textHeight = $textBoundingBox[3] - $textBoundingBox[7];
	$textRadius = sqrt(($textWidth * $textWidth) + ($textHeight * $textHeight)) / 2;
	$textScale = ($imageRadius / $textRadius) * ($fillPercent / 100);
	$textSize = $TEXT_STARTING_SIZE * $textScale;

	// get the new bounding box of the resized text
	$textBoundingBox = imagettfbbox($textSize, 0, $fontFilename, $text);

	// get the text dimensions
	$textWidth = $textBoundingBox[2] - $textBoundingBox[6];
	$textHeight = $textBoundingBox[3] - $textBoundingBox[7];

	// get the offset of the text from the text origin (NB text origin is bottom left!)
	$textOffsetFromOriginX = $textBoundingBox[0];
	$textOffsetFromOriginY = $textBoundingBox[1];

	// calculate an origin for the text that will centre it in the image
	// (note that the origin is at the bottom left, so we need to add the text height to the Y origin
	$textOriginX = ($imageWidth - $textWidth) / 2 - $textOffsetFromOriginX;
	$textOriginY = ($imageHeight - $textHeight) / 2 + $textHeight - $textOffsetFromOriginY;

	// work out the average colour of the image in the rectangle behind the text, and find a contrasting colour
	$imageAverageColour = image_averageColour($image, $textOriginX + $textOffsetFromOriginX, $textOriginY + $textOffsetFromOriginY - $textHeight, $textWidth, $textHeight);
	$contrastColour = image_contrastingColour($imageAverageColour);
	$textColour = imagecolorallocate($image, $contrastColour[0], $contrastColour[1], $contrastColour[2]);

	// draw outline
	$outlineColour = imagecolorallocate($image, $imageAverageColour[0], $imageAverageColour[1], $imageAverageColour[2]);
	$outlineSize = $textHeight * $outlinePercent / 100;
	$left = $textOriginX - $outlineSize;
	$right = $textOriginX + $outlineSize;
	$up = $textOriginY - $outlineSize;
	$down = $textOriginY + $outlineSize;
	imagettftext($image, $textSize, 0, $right, $up, $outlineColour, $fontFilename, $text);
	imagettftext($image, $textSize, 0, $right, $down, $outlineColour, $fontFilename, $text);
	imagettftext($image, $textSize, 0, $left, $up, $outlineColour, $fontFilename, $text);
	imagettftext($image, $textSize, 0, $left, $down, $outlineColour, $fontFilename, $text);

	// draw the text
	imagettftext($image, $textSize, 0, $textOriginX, $textOriginY, $textColour, $fontFilename, $text);
}
