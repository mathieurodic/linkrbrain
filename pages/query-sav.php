<?


	//	 rsvg-convert -w 3000 test.svg -o test.png


	class PageQuery extends Page {
	
		function __construct(){
			parent::__construct();
		}
		
		function renderGroups($frame, $query){
			//	Title bar
			$title = $frame->append("div")->class("title");
			$title->append("button")->text("-")->title("Remove a group");
			$title->append("input")->type("text")->value($query->groups())->title("Change the number of groups");
			$title->append("button")->text("+")->title("Add a group");
			$title->append("span")->text("&nbsp;data groups");
			//	Groups
			$htmlGroups = $frame->append("div")->class("container");
		}
		function renderCorrelations($frame){
			//	Title
			$title = $frame->append("div")->class("title");
			$title->append("span")->text("Correlation with&nbsp;");
			$title->append("button")->text("tasks");
			$title->append("button")->text("genes");
			// $title->append("button")->text("groups");
			//	Settings
			$container = $frame->append("div", "&nbsp;")->class("container");
		}
		function renderView($frame){
			//	Title
			$title = $frame->append("div")->class("title");
			$title->append("button")->text("2D");
			$title->append("button")->text("3D")->class("selected");
			$title->append("span")->text("&nbsp;view");
			//	View
			$container = $frame->append("div")->class("container");
		}
		function renderGraph($frame){
			//	Title
			$title = $frame->append("div")->class("title");
			$title->append("span")->text("Graph");
			//	Graph
			$container = $frame->append("div", "&nbsp;")->class("container");
		}
		
		function renderMenu($bar){
			$menu = array
			(	array
				(	"title"			=>	"New"
				,	"description"	=>	"Create an empty query"
				,	"icon"			=>	0
				)
			,	array
				(	"title"			=>	"Duplicate"
				,	"description"	=>	"Copy this query into a new one"
				,	"icon"			=>	1
				)
			,	array
				(	"title"			=>	"Open"
				,	"description"	=>	"Load an existing query from your collection"
				,	"icon"			=>	2
				)
			,	array
				(	"title"			=>	"PDF"
				,	"description"	=>	"Save this query on your computer in the PDF format"
				,	"icon"			=>	4
				)
			,	array
				(	"title"			=>	"Help!"
				,	"description"	=>	"How do use this application?"
				,	"icon"			=>	5
				)
			);
			foreach ($menu as $item){
				$item["icon"] *= 3;
				$li = $bar->append("li")->title($item["description"]);
				$li->append("div")->append("img")->src("/icons.png")
					->style("top:-$item[icon]em");
				$li->append("p")->text($item["title"]);
			}
		}
		
		function render(){
			global $file;
			$this->addScript("/three");
			if (empty($this->uri[1])){
				$this->contents->right->append("a", "Start a new query")->target("_blank")->href("/query/new/")->style("display:inline-block;background:#EEE;border:solid 2px #888;padding:8px;cursor:pointer;color:#444;font-weight:bold");
				$ul = $this->contents->left->append("ul");
				$rs = mysql_query("SELECT id, title, creation_time FROM query");
				while ($r = mysql_fetch_assoc($rs)){
					$ul->append("li")->append("a")
						->target("_blank")
						->href("/query/$r[id]/")
						->text($r["title"] . " (" . date("Y-m-d H:i:s", strtotime($r["creation_time"])) . ")");
				}
			} elseif ($this->uri[1] == "new"){
				$file->php("modules/query");
				$this->query = new Query();
				$this->redirect("/query/{$this->query->id}/");
			} elseif (preg_match("@^\d+$@", $this->uri[1])){
				$file->php("modules/query");
				$this->query = new Query($this->uri[1]);
				//	Query object
				$this->addScript("var query = " . $this->query->dataJSON(), false);
				//	Required ressources
				$this->addStyle("/frameset");
				$this->addStyle("/hue");
				$this->addStyle("/query");
				$this->addScript("/jquery-tablesorter");
				$this->addScript("/frameset");
				$this->addScript("/hue");
				$this->addScript("/query");
				
				//	Query environment
				$query = $this->contents->clear()->append("div")->class("query");
				
				//	Title
				$query->append("h1")->append("span")->text($this->query->title())->title("Click to edit this title");
				//	Frameset
				$frameset = $query->append("div")->class("frameset");
				//	First row
				$framesetRow = $frameset->append("div")->class("row")
				 ->data(array("frameset-ratio" => 0.3));
				$this->renderGroups(
					$framesetRow->append("div")->class("frame groups")
					 ->data(array("frameset-ratio" => 0.5))
				,	$this->query
				);
				$this->renderCorrelations(
					$framesetRow->append("div")->class("frame correlations")
					 ->data(array("frameset-ratio" => 0.5))
				);
				//	Second row
				$framesetRow = $frameset->append("div")->class("row")
				 ->data(array("frameset-ratio" => 0.7));
				$this->renderView(
					$framesetRow->append("div")->class("frame view")
					 ->data(array("frameset-ratio" => 0.5))
				);
				$this->renderGraph(
					$framesetRow->append("div")->class("frame graph")
					 ->data(array("frameset-ratio" => 0.5))
				);
			}
			return parent::render();
		}
	
	};

?>