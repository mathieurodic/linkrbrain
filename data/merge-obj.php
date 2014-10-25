<?

	class Obj3D {

		function __construct($path=NULL){
			$this->empty();
			$this->load($path);
		}
		
		function empty(){
			$this->data = array
			(	"n"		=>	array()
			,	"vn"	=>	array()
			,	"f"		=>	array()
			);
		}
		function load($path){
			switch (gettype($path)){
			
				case "array":
				case "object":
					foreach ($path as $value){
						$this->load($value);
					}
					break;
				
				case "string":
					$path = fopen($path);
				case "resource":
					if (get_resource_type($path) == "stream"){
						while (($line = fgets($path)) !== FALSE){
							if (preg_match("@^([a-z]{1,2})\s@"), $line, $match){
								$prefix = strtolower($match[1])
								if (isset($this->data[$prefix])){
									$this->data[$prefix] = $line;
								}
							}
						}
					}
			}
		}
		function save($path){
			
		}
		
		function intersect($obj3D){
			$new = array
			(	""
			);
		}
		
	};

	class Obj3Dset {
		
		function __construct(){
			$this->empty();
			
		}
		
		function $load(){
		
			switch (gettype($path)){
			
				case "array":
				case "object":
					foreach ($path as $value){
						$this->load($value);
					}
					break;
				
				case "string":
					$path = fopen($path);
				case "resource":
					if (get_resource_type($path) == "stream"){
						while (($line = fgets($path)) !== FALSE){
							if (preg_match("@^([a-z]{1,2})\s@"), $line, $match){
								$prefix = strtolower($match[1])
								if (isset($this->data[$prefix])){
									$this->data[$prefix] = $line;
								}
							}
						}
					}
		}
	
		}
		
		function intersect(){
			
			
			
		}
		
	};
	
	$name = "left-cerebellum";
	$obj = new Obj3D("$name.obj");

?>