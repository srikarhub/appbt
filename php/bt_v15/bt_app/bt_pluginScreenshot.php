<?php   require_once("../../config.php");


	//path to image...
	$filePath = fnGetReqVal("filePath", "", $myRequestVars);
	
	//must have file path...
	if($filePath == ""){
		exit();	
	}	
	
  	//file Exists? 
  	if(file_exists($filePath) ){ 
    
		//parse info / get extension 
		$fsize = filesize($filePath); 
		$path_parts = pathinfo($filePath); 
		$ext = strtolower($path_parts["extension"]); 
		
		//determine content type from file extention
		switch ($ext) { 
		  case "png": $ctype = "image/png"; break; 
		  case "jpeg": $ctype = "image/jpeg"; break; 
		  case "pjpeg": $ctype = "image/jpeg"; break; 
		} 
	
		//get attributes
		$size = getimagesize($filePath); 
		$width = $size[0];
		$height = $size[1];
				
		$max_width = 60;
		$max_height = 100;
				
		//proportionally resize
		 $x_ratio = $max_width / $width;
		 $y_ratio = $max_height / $height;
		 if( ($width <= $max_width) && ($height <= $max_height) ){
				  $tn_width = $width;
				  $tn_height = $height;
			 }elseif (($x_ratio * $height) < $max_height){
				  $tn_height = ceil($x_ratio * $height);
				  $tn_width = $max_width;
			 }else{
				  $tn_width = ceil($y_ratio * $width);
				  $tn_height = $max_height;
			 }
	
	
		//img resource..
		if($ext == "jpg" || $ext == "jpeg"){
			$src = @imagecreatefromjpeg($filePath);
		}
		if($ext == "png"){
			$src = @imagecreatefrompng($filePath);
		}
		if($ext == "gif"){
			$src = @imagecreatefromgif($filePath);
		}
		
		//if not original, make copy....
		$dst = @imagecreatetruecolor($tn_width, $tn_height);
		
		//png's may be transparent so method depends on the type of image to output...
		if($ext == "png"){
			header('Content-Type: image/png');
			@imagealphablending($dst, false);
			@imagesavealpha($dst, true);
  			$transparent = @imagecolorallocatealpha($dst, 255, 255, 255, 127);
  			@imagefilledrectangle($dst, 0, 0, $tn_width, $tn_height, $transparent);
 			imagecopyresampled($dst, $src, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
			@imagepng($dst);
		}else{
			header('Content-Type: image/jpeg');
			@imagecopyresized($dst, $src, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
			@imagejpeg($dst, NULL, 100);

		}

		//destroy
		@imagedestroy($src);

		
  }else{
  	echo "file not found?";
	exit();
  }
	
	
	


?>

