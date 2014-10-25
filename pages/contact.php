<?

	class PageContact extends Page {
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
		
			global $file;
			
			// nifti -> analyze, ticom ()
			
			
			//	Empty the container
			$this->contents->right->html($file->html("contact"));
			
			// $this->addStyle("/frameset");
			// $this->addStyle("/query");
			// $this->addScript("/three");
			// $this->addScript("/frameset");
			// $this->addScript("/query");
				
			return parent::render();
		}
	
	};

?>