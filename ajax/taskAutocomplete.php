<?

	class AjaxTaskAutocomplete extends Ajax{
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			global $file;
			$tasks = $file->module("tasks");
			$query = trim(isset($_REQUEST["term"]) ? $_REQUEST["term"] : "");
			$query = preg_replace("@[^\w]+@", " ", $query);
			$query = strtolower($query);
			$words = preg_split("@\s+@", $query);
			return json_encode($tasks->search($words));
		}
	
	};

?>