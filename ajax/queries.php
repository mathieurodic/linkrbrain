<?

	require_once dirname(__FILE__) . "/../modules/query.php";


	class AjaxQueries extends Ajax{
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			
			$queries = new Queries();
			$this->output = $queries->list;
			return parent::render();
		}
	
	};

?>