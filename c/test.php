<?
	
	require_once "db.php";
	mysql_select_db("carma");
	

	//	Initial data
	$rs = mysql_query("
		SELECT
			id
		,	name
		FROM
			task
		WHERE
			id < 20
		ORDER BY
			id
	");
	$taskIndex = array();
	$tasks = array();
	$i = 0;
	while ($task = mysql_fetch_assoc($rs)){
		$taskIndex[$task["id"]] = $i++;
		$tasks[] = $task;
		$text .= "$task[id]\n";
	}
	$text = count($tasks). "\n$text";
	//
	$rs = mysql_query("
		SELECT
			task1_id
		,	task2_id
		,	score
		FROM
			task_task
		WHERE
			task1_id < task2_id
		AND
			task2_id < 20
	");
	$edges = array();
	while ($edge = mysql_fetch_row($rs)){
		$edges[] = $edge;
	}
	

	//	Write input file
	foreach ($edges as $edge){
		$text .= implode(" ", $edge);
		$text .= "\n";
	}
	file_put_contents("in.txt", $text);

	
	//	Execute
	$process = proc_open
	// (	"tcc -run graph.c in.txt"
	// (	"g++ -lm graph.cpp -o graph && ./graph in.txt"
	(	"./graph in.txt"
	,	array
		(	array("pipe", "r")
		,	array("pipe", "w")
		,	array("pipe", "w")
		)
	,	$pipes
	,	__DIR__
	,	array
		()
	);
	
	//	Parse points
	$xmin = $ymin = +1e9;
	$xmax = $ymax = -1e9;
	$points = array();
	if (preg_match_all("@(\-?[e\d\.]+)\s+(\-?[e\d\.]+)\s+@i", stream_get_contents($pipes[1]), $matches, PREG_SET_ORDER)){
		foreach ($matches as $match){
			$x = floatval($match[1]);
			$y = floatval($match[2]);
			$points[] = array("x"=>$x, "y"=>$y);
			$xmin = min($x, $xmin);
			$xmax = max($x, $xmax);
			$ymin = min($y, $ymin);
			$ymax = max($y, $ymax);
		}
	}
	// die(stream_get_contents($pipes[2]));
	
	//	Normalize points
	$dx = $xmax - $xmin;
	$dy = $ymax - $ymin;
	// echo "$xmin\t$xmax\n";
	// echo "$ymin\t$ymax\n";
	$d = max($dx, $dy);
	$dx = $dy = $d;
	$xmax = $xmin + $dx;
	$ymax = $ymin + $dy;
	foreach ($points as $i=>&$point){
		$point["x"] = ($point["x"] - $xmin) / $dx;
		$point["y"] = ($point["y"] - $ymin) / $dy;
		$point["id"] = $tasks[$i]["id"];
		$point["name"] = $tasks[$i]["name"];
	}
	
	//	Close pipes
    for ($i=0; $i<3; $i++){
		fclose($pipes[$i]);
	}
	
	//	Edges
	$edgesAssoc = array();
	foreach ($edges as $edge){
		$edgesAssoc[] = array
		(	"n1"	=>	$taskIndex[$edge[0]]
		,	"n2"	=>	$taskIndex[$edge[1]]
		,	"weight"=>	$edge[2]
		);
	}

?>
<html>
	<body>
		<div id="paper"></div>
		<script type="text/javascript" src="/lib.js"></script>
		<script type="text/javascript">var nodes=<?			
			echo json_encode($points);
		?>;var edges=<?			
			echo json_encode($edgesAssoc);
		?></script>
		<script type="text/javascript" src="test.js"></script>
	</body>
</html>