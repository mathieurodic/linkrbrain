<?
	require_once("html.php");


	class Page extends Html{
	
		function __construct(){
			global $file, $session;
			parent::__construct();
			$this->uri = explode("/", trim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/"));
			$this->title = "";
			$this->subtitle = "";
			$this->description = "";
			$this->contents = $this->body->append("div")->id("contents");
			// foreach (array("top","middle","bottom") as $id){
			foreach (array("middle","bottom") as $id){
				$this->contents->$id = $this->contents->append("div")->id($id);
			}
			foreach (array("left","right") as $id){
				$this->contents->$id = $this->contents->middle->append("div")->attr("id", $id)->text("");
			}
			$this->contents->left->html($file->html("left"));
			// $this->contents->bar = $this->contents->top->append("div")->attr("class", "bar");
			// $this->contents->session = $this->contents->bar->append("div")->class("session");
			// $session->addLoginboxTo( $this->contents->session );
			// $this->contents->top->append("div", $file->html("title"))->attr("class", "title");
			// $this->contents->menu = $this->contents->bar->append("ul")->attr("class", "menu");
			$this->contents->bottom->append("#html", $file->html("bottom"));
			$this->addStyle("/style");
			$this->addScript("/lib");
			$this->addScript("/script");
			$this->addScript("
				if (location.pathname.split('/')[1] != 'query'){
					window.onresize = function(){
						var width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
						document.body.style.fontSize = Math.round(8 + width / 160) + 'px';
					};
					window.onresize();
				}
			", false);
		}
		
		function redirect($url){
			header("Location: $url");
			die();
		}
		
		function render(){
			$title = $this->subtitle ? : $this->title;
			$this->head->append("title")->text($title);
			$this->head->append("meta", array("name"=>"description", "content"=>$this->description));
			// if ($this->subtitle){
				// $this->contents->right->prepend("h1")
					// ->append("a")->attr("href", "")->text($this->subtitle);
			// }
			return parent::render();
		}
	
	};

?>