<?php   require_once("../../config.php");
		require_once(APP_PHYSICAL_PATH . "/includes/utilityFunctions.php");
 
////////////////////////////////////////////////////
//image class to resize, scale, stream images
 
class SimpleImage {
   
   var $image;
   var $image_type;
 
   function load($filename) {
      $image_info = getimagesize($filename);
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {
         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {
         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {
         $this->image = imagecreatefrompng($filename);
      }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image,$filename);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image,$filename);
      }   
      if( $permissions != null) {
         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {
      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {
         imagegif($this->image);         
      } elseif( $image_type == IMAGETYPE_PNG ) {
         imagepng($this->image);
      }   
   }
   function getWidth() {
      return imagesx($this->image);
   }
   function getHeight() {
      return imagesy($this->image);
   }
   function resizeToHeight($height) {
      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }
   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }
   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100; 
      $this->resize($width,$height);
   }
   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;   
   }      
}

//END CLASS
////////////////////////////////////////////////////

	/* sample uses 

	//resize to spacific size then save
	$image = new SimpleImage();
   	$image->load('picture.jpg');
   	$image->resize(250,400);
   	$image->save('picture2.jpg');
	
	//resize to specific width with original ration
	$image = new SimpleImage();
   	$image->load('picture.jpg');
   	$image->resizeToWidth(250);
   	$image->save('picture2.jpg');
	
	//scale by percent then save
	$image = new SimpleImage();
   	$image->load('picture.jpg');
   	$image->scale(50);
   	$image->save('picture2.jpg');
	
	//multiple operations at once
	$image = new SimpleImage();
	$image->load('picture.jpg');
	$image->resizeToHeight(500);
	$image->save('picture2.jpg');
	$image->resizeToHeight(200);
	$image->save('picture3.jpg')	

	//output to browser
	header('Content-Type: image/jpeg');
	$image = new SimpleImage();
   	$image->load('picture.jpg');
   	$image->resizeToWidth(150);
   	$image->output();
	
*/

	//vars
	$imageUrl = fnGetReqVal("imageUrl","", $myRequestVars); //current pin
	$maxWidth = fnGetReqVal("maxWidth","", $myRequestVars); //current pin
	if(!is_numeric($maxWidth)) $maxWidth = 250;
 	
	if($imageUrl != ""){
		header('Content-Type: image/jpeg');
		$image = new SimpleImage();
		$image->load($imageUrl);
		$image->resizeToWidth(intval($maxWidth));
		$image->output();
	}


?>