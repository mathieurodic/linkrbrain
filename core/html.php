<?

	class HtmlElement{
		
		function __construct(){
			$data = func_get_args();
			$this->nodeName = "";
			$this->attributes = array();
			$this->children = array();
			if (count($data)){
				if (is_string($data[0])){
					$this->nodeName = array_shift($data);
					switch ($this->nodeName){
						case "#html":
							$this->data = $data[0];
							break;
						case "#text":
							$this->data = nl2br($this->entities($data[0]), true);
							break;
						default:
							foreach ($data as $info){
								if (is_string($info)){
									$this->html($info);
								} elseif (is_array($info)){
									$this->attributes = $info;
								}
							}
					}
				}
			}
		}

		function entities($text){
			$text = str_replace("<", "&lt;",	$text);
			$text = str_replace(">", "&gt;",	$text);
			$text = str_replace('"', "&quot;",	$text);
			return $text;
		}
		
		function &attr($key = NULL, $value = NULL, $append = false){
			if ($key === NULL){
				return $key;
			} elseif ($value === NULL){
				return $this->attributes[$key];
			} else{
				if ($value === true){
					$value = $key;
				}
				if ($append){
					$this->attributes[$key] .= $this->entities($value);
				} else{
					$this->attributes[$key] = $this->entities($value);
				}
				return $this;
			}
		}		
		function &__call($name, $arguments){
			$arguments[1] = isset($arguments[1]) ? $arguments[1] : false;
			return $this->attr($name, $arguments[0], $arguments[1]);
		}
		function &data($key = NULL, $value = NULL){
			if ((is_array($key) || is_object($key))  &&  $value == NULL){
				$array = $key;
				foreach ($array as $key=>$value){
					$this->data($key, $value);
				}
				return $this;
			} elseif ($key === NULL){
				$data = array();
				foreach ($this->attributes as $key=>$value){
					if (preg_match("@^data\-(.+)$@", $key, $match)){
						$data[$match[1]] = $value;
					}
				}
				return $data;
			} else{
				if ($value === NULL){
					return $this->attr("data-$key");
				} else{
					if (is_numeric($value) || is_string($value)){
						return $this->attr("data-$key", $value);
					} else{
						return $this->attr("data-$key", json_encode($value));
					}
				}
			}
		}
		
		function &clear(){
			$this->children = array();
			return $this;
		}
		
		function &prepend($a=NULL, $b=NULL, $c=NULL){
			if (is_object($a)){
				$child = $a;
			} else{
				$child = new HtmlElement($a, $b, $c);
			}
			array_unshift($this->children, $child);
			return $this->children[0];
		}
		function &prependText($text){
			$this->prepend("#text", $text);
			return $this;
		}
		function &prependHtml($html){
			return $this->prepend("#html", $html);
		}
		
		function &append($a=NULL, $b=NULL, $c=NULL){
			if (is_object($a)){
				$child = $a;
			} else{
				$child = new HtmlElement($a, $b, $c);
			}
			array_push($this->children, $child);
			return $this->children[count($this->children) - 1];
		}
		function &appendText($text){
			$this->append("#text", $text);
			return $this;
		}
		function &appendHtml($html){
			return $this->append("#html", $html);
		}
		
		function &fill($a=NULL, $b=NULL, $c=NULL){
			$this->clear()->append($a, $b, $c);
			return $this;
		}
		function &text($text){
			$this->fill("#text", $text);
			return $this;
		}
		function &html($html){
			$this->fill("#html", $html);
			return $this;
		}
		
		function render(){
			$html = '';
			if (strpos($this->nodeName, "#") === 0){
				$html .= $this->data;
			} elseif ($this->nodeName == ""){
				foreach ($this->children as $child){
					$html .= $child->render();
				}
			} else{
				$html .= '<' . $this->nodeName;
				foreach ($this->attributes as $key=>$value){
					$html .= ' ';
					$html .= $this->entities($key);
					$html .= '="';
					$html .= $this->entities($value);
					$html .= '"';
				}
				if (in_array($this->nodeName, array("area","base","basefont","bgsound","br","col","command","embed","frame","hr","img","input","isindex","keygen","link","meta","param","source","spacer","track","wbr"))){
					$html .= '/>';
				} else{
					$html .= '>';
					foreach ($this->children as $child){
						$html .= $child->render();
					}
					$html .= '</' . $this->nodeName . '>';
				}
			}
			return $html;
		}
		
	};
	
	

	class Html{
	
		function __construct(){
			$this->document = new HtmlElement("");
			$this->document->appendHtml("<!DOCTYPE html>\n");
			$this->html = $this->document->append("html", array("lang"=>"en"));
			//
			$this->head = $this->html->append("head");
			$this->head->append("meta", array("http-equiv"=>"Content-Type", "content"=>"text/html; charset=UTF-8"));
			//
			$this->body = $this->html->append("body");
		}
		
		function addStyle($data, $isFile = true){
			if ($isFile){
				$style = $this->head->append("link", array
				(	"rel"	=>	"stylesheet"
				,	"href"	=>	"$data.css"
				));
			} else{
				$style = $this->head->append("style", $data);
			}
			$style->type("text/css");
		}
		function addScript($data, $isFile = true){
			$script = $this->body->append("script")->type("text/javascript");
			if ($isFile){
				$script->src("$data.js")->text("");
			} else{
				$script->html($data);
			}
		}
		
		function render(){
			return $this->document->render();
		}
	
	};

?>