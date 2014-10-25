<?

	class Http{
	
		function __construct(){
			//	Request type
			$this->ajax = $this->download = false;
			$headers = array_change_key_case(apache_request_headers());
			if (isset($headers["x-requested-with"])){
				if (in_array(strtolower($headers["x-requested-with"]), array("xmlhttprequest","ajax"))){
					$this->ajax = true;
				}
			}
			//	Content type
			if ($this->ajax){
				$this->contentType = "text/javascript";
			} elseif ($this->download){
				$this->contentType = "";				
			} else{
				$this->contentType = "text/html";
			}
		}
		
		function render(){
			global $url, $file;
			if ($this->ajax){
				$name = isset($_POST["form"]) ? $_POST["form"] : $url[0];
				return $file->ajax($name)->render();
			} elseif ($this->download){
				return;
			} else{
				$pageName = "Error";
				$pageTitle = "404 error";
				//	Find page
				foreach ($file->xml("menu") as $node){
					$attributes = $node->attributes();
					if ($attributes["url"] == $url[0]){
						$pageName = (string)$attributes["name"];
						$pageTitle = (string)$attributes["title"];
					}
				}
				$page = $file->page($pageName);
				//	Write on page
				$page->title = $pageTitle;
				// foreach ($file->xml("menu") as $node){
					// $attributes = $node->attributes();
					// if ($attributes["display"] == "yes"){
						// $li = $page->contents->menu->append("li");
						// if ($attributes["url"] == $url[0]){
							// $li->attr("class", "selected");
						// }
						// $li->append("a")
							// ->attr("href", "/$attributes[url]/")
							// ->text($attributes["title"]);
					// }
				// }
				return $page->render();
			}
		}
	
	};

?>