<?

	class PageTasks extends Page{
	
		function __construct(){
			parent::__construct();
			$this->words = array();
			$this->menu = array
			(	"/tasks/"				=>	"<h2>Browse cognitive tasks...</h2>"
			,	"/tasks/categories/"	=>	"...by category"
			,	"/tasks/alphabetical/"	=>	"...by name"
			,	"/tasks/search/"		=>	"<h2>Search by keyword...</h2>"
			);
		}
		
		function left(){
			$ul = $this->contents->left->append("ul")->class("menu");
			foreach ($this->menu as $href=>$html){
				$li = $ul->append("li");
				if (strpos($_SERVER["REQUEST_URI"], $href) === 0){
					$li->class("selected");
				}
				$li->append("a")->href($href)->html($html);
			}
			$li->append("form")->method("get")->action("/tasks/search/")
				->append("input")->type("text")->title("Keywords...")->name("q")->value(implode(" ", $this->words))
				->append("input")->type("submit")->class("submit")->value("Search!");
		}
		
		function right(){
			global $url, $file;
			switch ($url[1]){
				case "":
					$this->subtitle = "Cognitive tasks";
					$this->contents->right->html($file->html("tasks/"));
					break;
				case "categories":
					$this->rightCategories();
					break;
				case "alphabetical":
					$this->rightAlphabetical();
					break;
				case "search":
					$this->rightSearch();
					break;
				case "task":
					$this->rightTask();
					break;
			}
		}
		function rightAlphabetical(){
			$this->subtitle = "Browse tasks by alphabetical order";
			$ul = $this->contents->right->append("ul")->class("results");
			$rs = mysql_query("
				SELECT
					name
				,	definition
				FROM
					task
				ORDER BY
					name
			");
			$previousFirst = "";
			while ($task = mysql_fetch_assoc($rs)){
				$name = ucfirst($task["name"]);
				$path = str_replace(" ", "-", $task["name"]);
				$first = substr($name, 0, 1);
				$definition = $task["definition"] ? : "(aucune définition disponible)";
				//
				if ($first != $previousFirst){
					$ul->append("li")->append("h2")->class("center")
						->append("a")->name($first)->text($first);
				}
				//
				$li = $ul->append("li");
				$li->append("h2")
					->append("a")->text($name)->href("/tasks/task/$path");
				$li->append("p")->text($definition);
				$previousFirst = $first;
			}
		}
		function rightCategories(){
		}
		function rightSearch(){
			if (preg_match_all("@\w+@", $_GET["q"], $matches)){
				$this->words = array_map("strtolower", $matches[0]);
				if ($this->words){
					$this->subtitle = "Tasks matching '" . implode(" ", $this->words) . "' :";
					$wordsCount = count($this->words);
					$wordsString = implode("','", $this->words);
					$rs = mysql_query("
						SELECT
							t.path
						,	t.name
						,	t.definition
						,	SUM(kt.weight) AS score
						FROM
							keyword AS k
						INNER JOIN
							keyword_task AS kt ON kt.keyword_id = k.id
						INNER JOIN
							task AS t ON t.id = kt.task_id
						WHERE
							k.word IN ('$wordsString')
						GROUP BY
							t.id
						HAVING
							COUNT(k.id) = $wordsCount
						ORDER BY
							score DESC
						LIMIT
							100
					");
					echo mysql_error();
					if (mysql_num_rows($rs)){
						$ul = $this->contents->right->append("ul")->class("results");
						while ($task = mysql_fetch_assoc($rs)){
							$link = "/tasks/task/$task[path]/";
							$name = ucfirst($task["name"]);
							$definition = $task["definition"] ? : "(no definition available yet)";
							//
							$li = $ul->append("li");
							$li->append("h2")
								->append("a")->text($name)->href($link);
							$li->append("p")->text($definition);
						}
					}
				}
			}
		}
		function rightTask(){
			global $url;
			if (!$task = mysql_single_assoc("
				SELECT
					id
				,	name
				,	definition
				FROM
					task
				WHERE
					path = '$url[2]'
			")){
				$this->redirect("/tasks/");
			}
			$this->subtitle = ucfirst($task["name"]);
			$this->definition = $task["definition"];
			//	Definition
			$this->contents->right->append("h2")
				->class("resultsTitle")
				->text("Definition");
			$li = $this->contents->right->append("ul")
				->class("results")->append("li");
			$definitions = $task["definition"] ? : "No definition available yet.";
			$definitions = explode("\n\n\n\n", $definitions);
			foreach ($definitions as $definition){
				$li->append("p")->text($definition);
			}
			//	Views
			$this->contents->right->append("h2")
				->class("resultsTitle")
				->text("Views");
			$ul = $this->contents->right->append("ul")->class("results");
			$ul->append("li")->append("a")->href("#2D")->text("2D view");
			$ul->append("li")->append("a")->href("#3D")->text("3D view");
			//	Related tasks
			$this->contents->right->append("h2")
				->class("resultsTitle")
				->text("Tasks with similar activation map");
			$ul = $this->contents->right->append("ul")->class("results");
			$rs = mysql_query("
				SELECT
					t2.path
				,	t2.name
				FROM
					task AS t1
				INNER JOIN
					task_point AS tp1 ON tp1.task_id = t1.id
				INNER JOIN
					point AS p1 ON p1.id = tp1.point_id
				INNER JOIN
					point AS p2
						ON	p2.x BETWEEN p1.x - 10 AND p1.x + 10
						AND	p2.y BETWEEN p1.y - 10 AND p1.y + 10
						AND	p2.z BETWEEN p1.z - 10 AND p1.z + 10
				INNER JOIN
					task_point AS tp2 ON tp2.point_id = p2.id				
				INNER JOIN
					task AS t2 ON t2.id = tp2.task_id
				WHERE
					t1.id =  $task[id]
				GROUP BY
					t2.id
				ORDER BY
					SUM(SQRT(
						(p2.x - p1.x) * (p2.x - p1.x)
					+	(p2.y - p1.y) * (p2.y - p1.y)
					+	(p2.z - p1.z) * (p2.z - p1.z)
					) / SQRT(t1.points * t2.points))
				LIMIT
					10
			");
			echo mysql_error();
			while ($t = mysql_fetch_assoc($rs)){
				$ul->append("li")->append("a")
					->href("/tasks/task/$t[path]/")
					->text(ucfirst($t["name"]))
					->target("_BLANK");
			}
			//	Related tasks
			// $this->contents->right->append("h2")
				// ->text("Related tasks");
			// $ul = $this->contents->right->append("ul")->class("results");
			// $rs = mysql_query("
				// SELECT
					// t.path
				// ,	t.name
				// FROM
					// taskgraphnode AS n1
				// INNER JOIN
					// taskgraphedge AS e ON e.node1_id = n1.id
				// INNER JOIN
					// taskgraphnode AS n2 ON n2.id = e.node2_id
				// INNER JOIN
					// task AS t ON t.id = n2.task_id
				// ORDER BY
					// e.weight DESC
				// LIMIT
					// 100
			// ");
			// while ($t = mysql_fetch_assoc($rs)){
				// $ul->append("li")->append("a")
					// ->href("/tasks/task/$t[path]/")
					// ->text(ucfirst($t["name"]))
					// ->target("_BLANK");
			// }
			//	Barycenters
			$this->contents->right->append("h2")->class("resultsTitle")
				->text("Brain activation barycenters");
			$table = $this->contents->right->append("table")->class("results");
			$tr = $table->append("thead")->append("tr");
			foreach (array("x","y","z") as $column){
				$tr->append("th")->text($column);
			}
			$rs = mysql_query("
				SELECT
					x
				,	y
				,	z
				FROM
					task_point AS tp
				INNER JOIN
					point AS p ON p.id = tp.point_id
				WHERE
					tp.task_id = $task[id]
			");
			$tbody = $table->append("tbody")->title("Click on a row to view the corresponding barycenter");
			$dataBarycenters = array();
			while ($point = mysql_fetch_row($rs)){
				$tr = $tbody->append("tr");
				foreach ($point as $coordinate){
					$tr->append("td")->text($coordinate);
					$dataBarycenters[] = $coordinate;
				}
			}
			$this->contents->right->append("div")
				->class("windows")->append("div")
				->class("window view")->text("")
				->attr("data-barycenters", json_encode($dataBarycenters));
		}
		
		function render(){
			$this->right();
			$this->left();
			return parent::render();
		}
	
	};

?>