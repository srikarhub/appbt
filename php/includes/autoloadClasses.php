<?php	
    function __autoload($class_name){
	
		/* 
			FILE and CLASS naming convention 
			file names: class.User.php (first letter caps, all others lower case, spaces are an underscore)
			class name:	class User (first letter caps, all others lower case, spaces are an underscore)
		*/
		$fixedClassName = ucfirst($class_name);
		require_once("class." . $fixedClassName . ".php");
	
	}		
?>