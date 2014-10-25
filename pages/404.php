<?

	class Page404 extends Page{
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			global $file;
			$this->subtitle = "404 error";
			$this->description = "This page cannot be found.";
			$this->contents->right->html($file->html("404"));
			return parent::render();
		}
	
	};

?>