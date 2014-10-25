<?

	class AjaxCategories extends Ajax{
	
		function __construct(){
			parent::__construct();
		}
		
		function render(){
			$type = preg_replace("@s$@", "", $this->data->type);
			if (!in_array($type, array("task","gene"))){
				return;
			}
			if (!empty($this->data->list)){
				$rs = mysql_query("
					SELECT
						id
					,	path
					,	title
					FROM
						preset
					WHERE
						type = '{$type}'
					ORDER BY
						title
				");
				$list = array();
				while ($r = mysql_fetch_assoc($rs)){
					$r["title"] = ucfirst($r["title"]);
					$list[] = $r;
				}
				$this->addCallback
				(	".window .contents.{$type}s"
				,	"data"
				,	array
					(	"list"	=>	$list
					)
				);
			}
			return parent::render();
		}
	
	};

?>
