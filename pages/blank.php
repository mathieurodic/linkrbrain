<?

	class PageBlank extends Page {
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			global $file;
			$this->head->html("");
			$this->body->html("");
			return parent::render();
		}
	
	};

?>