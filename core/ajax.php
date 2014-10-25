<?	
	
	class Ajax{
		
		function __construct(){
			$this->data = isset($_POST["data"]) ? json_decode($_POST["data"]) : new StdClass();
			$this->output = NULL;
			$this->callbacks = array();
		}
		
		function addCallback($selector, $method, $argument = NULL){
			$this->callbacks[] = array
			(	"selector"	=>	$selector
			,	"method"	=>	$method
			,	"argument"	=>	$argument
			);
		}
		
		function render(){
			header("Content-Type: text/javascript; charset=utf-8");
			return json_encode(($this->output === NULL)  ? $this->callbacks : $this->output);
		}
	
	};
	
?>