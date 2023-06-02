<?php

require_once($_SERVER["DOCUMENT_ROOT"] . "/private/first.inc.php");

require_once("rooms.inc.php");


$error = "";

$title = "";
$category = "";
$description = "";
$imageFilename = "";

if (isset($_POST["title"]))
{
	$title = $_POST["title"];
}

if (isset($_POST["category"]))
{
	$category = $_POST["category"];
}

if (isset($_POST["description"]))
{
	$description = $_POST["description"];
}

if (isset($_FILES["thumbnail"]))
{
	if ($_FILES["thumbnail"]["error"] == UPLOAD_ERR_OK)
	{
		$imageFilename = $_FILES["thumbnail"]["tmp_name"];
	}
	else if ($_FILES["thumbnail"]["error"] !=  UPLOAD_ERR_NO_FILE)
	{
		$error = LANG["create_room_file_upload_failed"];
	}
}

if ($error == "" && isset($_POST["create-room"]))
{
	$error = rooms_createRoomAndRedirect($title, $category, $description, $imageFilename);
}


template_drawHeader(LANG["page_title_create_room"], null, "");
template_denyIfNotLoggedIn();

?>
	<div id='create-room-form'>
		<h2>
			<?php echo LANG["create_room_form_title"] ?>
		</h2>
<?php
		if ($error != "")
		{
			echo "<div class='form-error'>";
			echo $error;
			echo "</div>";
		}
?>
		<form method="post" enctype="multipart/form-data" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
			<label>
				<?php echo LANG["create_room_form_title_label"] ?>
				<input type="text" name="title" value="<?php echo htmlspecialchars($title);?>" maxlength=255>
			</label>
			<label>
				<?php echo LANG["create_room_form_category_label"]; ?>
				<?php
					// categories defined in config file
					for ($x = 1; $x <= CONFIG["rooms_number_of_categories"]; $x++)
					{
						$categoryNumber = CONFIG["rooms_category_create_order"][$x];
						$categoryTitle = LANG["rooms_category_title"][$categoryNumber];
						$categoryDescription = LANG["rooms_category_description"][$categoryNumber];
						$categoryLabel = $x;
						$checked = "";
						if ($category == $categoryLabel)
						{
							$checked = "checked='checked'";
						}

						echo "<input type='radio' id='room-category-$categoryLabel' name='category' value='$categoryLabel' $checked />";
						echo "<label for='room-category-$categoryLabel'>";
						echo "<div class='radio-title'>";
						echo $categoryTitle;
						echo "</div>";
						echo "<div class='radio-description'>";
						echo $categoryDescription;
						echo "</div>";
						echo "</label>";
					}

					// private room category
					$categoryLabel = ROOMS_CATEGORY_PRIVATE;
					$categoryTitle = LANG["rooms_category_title_private"];
					$categoryDescription1 = LANG["rooms_category_description_private_1"];
					$categoryDescription2 = LANG["rooms_category_description_private_2"];
					$checked = "";
					if ($category == $categoryLabel)
					{
						$checked = "checked='checked'";
					}

					echo "<input type='radio' id='room-category-$categoryLabel' name='category' value='$categoryLabel' $checked />";
					echo "<label for='room-category-$categoryLabel'>";
					echo "<div class='radio-title'>";
					echo $categoryTitle;
					echo "</div>";
					echo "<div class='radio-description'>";
					echo $categoryDescription1;
					echo "<br/>";
					echo $categoryDescription2;
					echo "</div>";
					echo "</label>";
				?>
			</label>
				<?php
/*
			<label>
				<?php echo LANG["create_room_form_type_label"] ?>
				<br>
				<input type="radio" name="type" value="listed" checked><?php echo LANG['create_room_form_type_listed'];?></input>
				<br>
				<?php echo LANG['create_room_form_type_listed_description']; ?>
				<br>
				<input type="radio" name="type" value="unlisted"><?php echo LANG['create_room_form_type_unlisted'];?></input>
				<br>
				<?php echo LANG['create_room_form_type_unlisted_description']; ?>
			</label>
			<br>
*/
				?>
			<label>
				<?php echo LANG["create_room_form_description_label"] ?>
				<textarea name="description" rows=4 maxlength=1000><?php echo htmlspecialchars($description);?></textarea>
			</label>
			<label>
				<?php echo sprintf(LANG["create_room_form_image_label"], CONFIG["rooms_image_aspect_ratio_x"], CONFIG["rooms_image_aspect_ratio_y"]); ?>
				<input type="file" name="thumbnail" accept="image/*">
			</label>
			<input type="submit" name="create-room" value="<?php echo LANG["create_room_form_submit_label"] ?>">
		</form>
	</div>
<?php

template_drawFooter();
