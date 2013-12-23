<?PHP	

	//where is image
	$imageUrl = "";
	if(isset($_GET['imageUrl'])){
		$imageUrl = $_GET['imageUrl'];
	}
	$scale = "original";
	if(isset($_GET['scale'])){
		$scale = $_GET['scale'];
	}
		
	if($imageUrl == "" || $scale == ""){
		echo "no image path supplied";
		exit();
	}
	
	// get original size
	if(!file_exists($imageUrl)){
		echo $imageUrl . " does not exists?";
		exit();
	}
	
	//get attributes
	$size = @getimagesize($imageUrl); 
    $width = $size[0];
    $height = $size[1];
				
	//if we are original, don't bother re-creating new image
	switch (strtoupper($scale)){
		case "SMALL":
			$max_width = ceil($width * .30);
			$max_height = ceil($height * .30);
			break;
		case "MEDIUM":
			$max_width = ceil($width * .50);
			$max_height = ceil($height * .50);
			break;
		case "LARGE":
			$max_width = ceil($width * .75);
			$max_height = ceil($height * .75);
			break;
		case "ORIGINAL":
			$max_width = $width;
			$max_height = $height;
			break;
			
	}//end scale
		
	// proportionally resize if not showing the original...
	if(strtoupper($scale) != "ORIGINAL"){
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
	}
	
	
     //img resource
	$file_ext = strtolower(substr($imageUrl, strrpos($imageUrl, '.') + 1));
	if($file_ext == "jpg" || $file_ext == "jpeg"){ 
    	$src = @imagecreatefromjpeg($imageUrl);
		header('Content-type: image/jpeg');
	}
	if($file_ext == "png"){
		$src = @imagecreatefrompng($imageUrl); 
		header('Content-type: image/png');
	}
	
	
   	//if not original, make copy....
	if(strtoupper($scale) != "ORIGINAL"){
		$dst = @imagecreatetruecolor($tn_width, $tn_height);
    	@imagecopyresized($dst, $src, 0, 0, 0, 0, $tn_width, $tn_height, $width, $height);
		
		if($file_ext == "jpg" || $file_ext == "jpeg"){ 
			@imagejpeg($dst);
		}
		if($file_ext == "png"){ 
			@imagepng($dst);
		}
		
	}else{
	
		if($file_ext == "jpg" || $file_ext == "jpeg"){ 
			@imagejpeg($src);
		}
		if($file_ext == "png"){ 
			@imagepng($src);
		}

	}
	//destroy
	@imagedestroy($src);
	
?>