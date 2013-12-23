<?php 

	//CREATES a gradient reflection image to be placed under an original image.
	//original image path is passed in.
	
	/* 
		Usage in HTML: MUST BE .JPG Image. Note the margin's on the original and the reflection
		<img src='../originalImage.jpg' style='margin:0px;border:0px;'>
		<br>
		<img src='image_reflection.php?original=IMAGE_PATH' style='margin-top:-2px;border:0px;'>
	*/

	// Replaces spacing with %20 in image name
	$imageUrl = "";
	if(isset($_GET['imageUrl'])){
		$imageUrl = $_GET['imageUrl'];
	}else{
		exit();
	}
	//need an image.
	if(!is_file($imageUrl)){
		echo "file doesn't exist";
		exit();
	}
	//size of original	
	$size = getimagesize($imageUrl);

	//Import the image into GD
	$imgImport = imagecreatefromjpeg($imageUrl);
	
	// Assign width and height of your image to variables
	$imgName_w = $size[0];
	$imgName_h = $size[1];
	
	// Gradient Height
	$gradientHeight = 50;
	
	// Create new blank image with sizes.
	$background = imagecreatetruecolor($imgName_w, $gradientHeight);

	$gradientColor = "FFFFFF"; //no # in hex code! this will break our code.
	$dividerHeight = 1;
	
	// Set the start coordinate of the gradient - ie below the divider, otherwise we'll
	// end up drawing over the top of it
	$gradient_y_startpoint = $dividerHeight;
	
	// Convert hex color to a color GD can use
	sscanf($gradientColor, "%2x%2x%2x", $red2, $green2, $blue2);
	$gdGradientColor=ImageColorAllocate($background,$red2,$green2,$blue2);

	$newImage = imagecreatetruecolor($imgName_w, $imgName_h);
	for ($x = 0; $x < $imgName_w; $x++){
		for ($y = 0; $y < $imgName_h; $y++){
			imagecopy($newImage, $imgImport, $x, $imgName_h - $y - 1, $x, $y, 1, 1);
		}
	}
	// Add it to the blank background image
	imagecopymerge ($background, $newImage, 0, 0, 0, 0, $imgName_w, $imgName_h, 100);

	// Create new image for our line which we will use over and over. We do this rather than
	// drawing a GD line because we cannot set its transparency if it is a GD line.
	$gradient_line = imagecreatetruecolor($imgName_w, 1);

	// Next we draw a GD line into our gradient_line
	imageline ($gradient_line, 0, 0, $imgName_w, 0, $gdGradientColor);

	// Now, lets draw that gradient
	$i = 0;
	$transparency = 40;
	
	while ($i < $gradientHeight){
		imagecopymerge ($background, $gradient_line, 0, $gradient_y_startpoint, 0, 0, $imgName_w, 1, $transparency);
		++$i;
		++$gradient_y_startpoint;
		if($transparency == 100){
			$transparency = 100;
		}else{
			$transparency = $transparency +2;
		}//end if tranparency == 100
	}//end while

	// Set the thickness of the line we're about to draw
	imagesetthickness ($background, $dividerHeight);
	
	// Draw the line
	imageline ($background, 0, 0, $imgName_w, 0, $gdGradientColor);
	
	//output reflection img to browser
	imagejpeg($background, "", 90); //outputs gradient to browser, place UNDER original image
	
	//destroy resource images.
	imagedestroy($background);
	imagedestroy($gradient_line);
	imagedestroy($newImage);

	header("Content-type: image/jpeg");


?>
	

