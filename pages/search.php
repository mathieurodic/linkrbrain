<?

	class PageSearch extends Page{
	
		function __construct(){
			global $file;
			parent::__construct();
		}
		
		function query(){
			global $file;
			$this->subtitle = "Search points...";
			$this->contents->right->append("div")
				->html($file->html("search/"));
			//
			$ul = $this->contents->left->append("ul")
				->class("querypoints");
			$ul->append("li")->append("h1")
				->text("Search points");
			$li = $ul->append("li")
				->class("pointset");
			$li->append("h2")->append("input")
				->type("text")->value("Group 1")->title("Enter the name of the group here...");
			$form = $li->append("form");
			$div = $form->append("div");
			foreach ($this->config->pointset->type as $type){
				$attributes = $type->attributes();
				$name = (string)$attributes["name"];
				$title = (string)$attributes["title"];
				$div->append("a")
					->href("#$name")->text($title);
				$fieldset = $form->append("fieldset")
					->class($name);
				$fieldset->append("a", "")->name($name);
				//
				$field = NULL;
				switch ($name){
					case "raw":
						$field = $fieldset->append("textarea")->html("")
							->title("Enter the points coordinates here...");
						break;
					case "text":
					case "nifti":
						$field = $fieldset->append("iframe")->html("")
							->src("/upload/?type=pointset-$name");
						break;
					case "tasks":
						$field = $fieldset->append("input")
							->type("text")->title("Type a few words here...");
						$fieldset->append("ul", "");
						break;
				}
				$field->name("$name");
				$fieldset->append("button")->text("Validate these points");
			}
			$ul->append("li")->append("button")
				->class("blue")->text("Start query!");
		}
		
		function resultsLeft($id){
			$baseUrl = "/search/$id";
			$menu = $this->contents->left->append("ul")->class("menu");
			foreach ($this->config->node as $node){
				$attributes = $node->attributes();
				$href = "$baseUrl/$attributes[href]" . ($attributes["href"] ? "" : "/");
				$li = $menu->append("li");
				$li->append("a")->href($href)
					->append("h2")->text($attributes["caption"]);
				// foreach ($node->node as $node){					
					// $attributes = $node->attributes();
					// $li = $menu->append("li");
					// $li->append("a")->href("$href$attributes[href]/")
						// ->text($attributes["caption"]);
				// }
				// foreach ($this->query->pointsets as $pointset){
					// $li = $menu->append("li");
					// $li->append("a")
						// ->href("$baseUrl/data/pointset-$pointset->id/")
						// ->text($pointset->title());
				// }
			}
		}
		
		function resultsRightDatasets($query_id){
			$rs = mysql_query("
				SELECT
					title
				,	color
				,	description
				FROM
					pointset
				WHERE
					query_id = $query_id
			");
			$ul = $this->contents->right->append("ul")->class("fieldsetsEdition");
			while ($pointset = mysql_fetch_assoc($rs)){
				$li = $ul->append("li");
				$li->append("h4")->text($pointset["title"]);
				$li->append("p")->text($pointset["description"]);
			}
		}
		function resultsRightCorrelation($query_id){
		
			$this->subtitle = "Correlation with tasks";
		
			//	HTML controls
			$form = $this->contents->right->append("form", "Sort by ")->method("get")->action("")->class("query");
			$selectPointset = $form->append("select")->name("pointset");
			$selectPointset->append("option", "whole query")->value("0");
			$selectType = $form->append("select")->name("type");
			foreach (array("absolute", "relative") as $type){
				$selectType->append("option", $type)->value($type);
			}
			$form->appendText(" correlation score in ");
			$selectOrder = $form->append("select")->name("order");
			foreach (array("descending", "ascending") as $order){
				$selectOrder->append("option", $order)->value($order);
			}
			$form->appendText(" order:");
		
			//	HTML basis
			$htmlTable = $this->contents->right->append("table")->class("scores");
			$htmlThead = $htmlTable->append("thead");
			$htmlTbody = $htmlTable->append("tbody");
			
			//	Query preparation
			$columns = array("t.name", "qt.score AS score0");
			$tables = array("FROM task AS t", "INNER JOIN query_resulttask AS qt ON qt.task_id = t.id");
			$conditions = array("qt.query_id = $query_id");
			$rs = mysql_query("
				SELECT
					p.id
				,	p.title
				FROM
					pointset AS p
				WHERE
					query_id = $query_id
			");
			$htmlTr = $htmlThead->append("tr");
			$htmlTr->append("th", "");
			$htmlTr->append("th", "Task name");
			$htmlTh = $htmlTr->append("th", "Correlation with the <b>whole query</b>");
			while ($pointset = mysql_fetch_assoc($rs)){
				$htmlTh = $htmlTr->append("th", "Correlation with the group <b>$pointset[title]</b>");
				$selectPointset->append("option", $pointset["title"])->value($pointset["id"]);
				$columns[] = "pt$pointset[id].score AS score$pointset[id]";
				$tables[] = "LEFT JOIN pointset_resulttask AS pt$pointset[id] ON pt$pointset[id].task_id = t.id";
				$conditions[] = "pt$pointset[id].pointset_id = $pointset[id]";
			}
			
			
			//	Query parameters
			$parameters = array("pointset"=>"0", "type"=>"absolute", "order"=>"descending");
			foreach ($parameters as $key=>&$value){
				if (isset($_GET[$key])){
					$value = $_GET[$key];
				}
				foreach ($form->children as $select){
					if ($select->nodeName == "select"){
						if ($select->attr("name") == $key){
							foreach ($select->children as $option){
								if ($parameters[$key] === $option->attr("value")){
									$option->selected("selected");
								}
							}
						}
					}
				}
			}
			
			//	Query results
			$i = 0;
			$rs = mysql_query
			(	"SELECT "
			.	implode(",", $columns)
			.	"\n"
			.	implode("\n", $tables)
			.	"\nWHERE "
			.	implode("\nAND ", $conditions)
			.	"\nORDER BY (score" . (int)$parameters["pointset"]
			.	($parameters["type"] == "relative" ? " - score0" : "")
			.	") "
			.	($parameters["order"] == "ascending" ? "ASC" : "DESC")
			);
			while ($row = mysql_fetch_row($rs)){
				$htmlTr = $htmlTbody->append("tr");
				$htmlTr->append("td", (string)++$i);
				$htmlTr->append("td", array_shift($row));
				//
				$scoreQuery = array_shift($row) * 100;
				$htmlTr->append("td")->class("score")->append("b", floor($scoreQuery) . "%");
				foreach ($row as $score){
					$score = $score * 100;
					$dScore = floor($score - $scoreQuery);
					$dScore = $dScore>0 ? "+$dScore" : "$dScore";
					$htmlTd = $htmlTr->append("td")->class("score");
					$htmlTd->append("b", floor($score) . "%");
					$htmlTd->append("span", $dScore . "%");
				}
			}
		}
		function resultsRightGraph($id){
			global $file;
			$this->contents->right->append("div", $file->html("search/graph"));
			$id = $this->query->id;
			$this->contents->right->append("div")
				->attr("data-graph", mysql_single_value("SELECT graph FROM query WHERE id = $id"))
				->class("graph");
			//
			$this->subtitle = "Graph representation";
		}
		function resultsRightView($id){
			//	Pointset list
			$this->subtitle = "View activated zones";
			$n = count($this->query->pointsets);
			$ul = $this->contents->right->append("ul")
				->class("groups");
			//	Pointsets
			foreach ($this->query->pointsets as $i=>$pointset){
				$li = $ul->append("li")
					->attr("data-points", json_encode($pointset->points()))
					->text($pointset->title());
				$li->prepend("input")->class("color")->type("hidden");
				$li->prepend("button")->class("on")->text("On");
			}
			//	Viewbox
			$this->contents->right->append("div", "")
				->class("viewbox");
			//	3D library
			$this->addScript("/three");
		}
		
		
		function resultsRight($id){
			global $url;
			switch($url[2]){
				case "":
					$this->resultsRightDatasets($id);
					break;
				case "view":
					$this->resultsRightView($id);
					break;
				case "graph":
					$this->resultsRightGraph($id);
					break;
				case "correlation":
					$this->resultsRightCorrelation($id);
					break;
				default:
					$this->redirect("/search/$id/");
					break;
			}
		}
		
		function resultsRightData($id){
			global $url;
			$table = $this->contents->right
				->append("table")->class("scores");
			//
			$thead = $table->append("thead");
			foreach (array("", "Task name", "Similarity") as $text){
				$thead->append("th")->text($text);
			}
			//
			$tbody = $table->append("tbody");
			if (preg_match("@^pointset\-(\d+)$@", $url[3], $match)){
				$id = $match[1];
				$sql = "
					SELECT
						t.name
					,	t.path
					,	FLOOR(100 * LOG2(1 + pt.score)) AS score
					FROM
						pointset_resulttask AS pt
					INNER JOIN
						task AS t ON t.id = pt.task_id
					WHERE
						pt.pointset_id = $id
					HAVING
						score > 0
					ORDER BY
						score DESC
				";
			} else{
				$sql = "
					SELECT
						t.name
					,	t.path
					,	FLOOR(100 * LOG2(1 + qt.score)) AS score
					FROM
						query_resulttask AS qt
					INNER JOIN
						task AS t ON t.id = qt.task_id
					WHERE
						qt.query_id = $id
					HAVING
						score > 0
					ORDER BY
						score DESC
				";
			}
			$i = 0;
			$rs = mysql_query($sql);			
			while ($r = mysql_fetch_assoc($rs)){
				$tr = $tbody->append("tr");
				$td = $tr->append("td")->text(++$i);
				$td = $tr->append("td")->append("a")
					->target("_blank")
					->href("/tasks/task/$r[path]/")
					->text($r["name"]);
				$td = $tr->append("td")->text("$r[score]%")->class("score");
			}
		}
		function resultsRightDataPointset($id){
		}
		
		function render(){
			global $url, $file;
			if ($id = preg_replace("@[^\d]+@", "", $url[1])){
				$this->config = $file->xml("results");
				$this->query = $file->module("pointsetQuery");
				$this->query->fromId($id);
				$this->resultsLeft($id);
				$this->resultsRight($id);
			} else{
				$this->config = $file->xml("query");
				$this->query();
			}
			return parent::render();
		}
	
	};

?>