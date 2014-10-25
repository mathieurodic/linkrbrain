<?


	//	 rsvg-convert -w 3000 test.svg -o test.png


	class PageQuery extends Page {
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			
			//	Empty the container
			$this->contents->html("");
			
			//	Required ressources
			$this->addStyle("/frameset");
			$this->addStyle("/query");
			$this->addScript("/three");
			$this->addScript("/frameset");
			$this->addScript("/query");
				
			return parent::render();
		}
	
	};

?>