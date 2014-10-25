<?

	// exec("g++ -I/usr/include/mysql/ -L/usr/lib/mysql/ -lmysqlclient graphQuery.cpp -o graphQuery  &&  ./graphQuery 19 22", $lines);
	exec("./graphQuery 19 24", $lines);
	
	//	Number of nodes
	$n = (int)$lines[0];
	$l = count($lines);	
	//	Nodes
	$nodes = array();
	$nodeKeys = array("type", "id", "name", "r", "x", "y");
	for ($i=1; $i<=$n; $i++){
		$nodeValues = explode("\t", $lines[$i]);
		$nodeValues[1] = (int)$nodeValues[1];
		$nodeValues[3] = (float)$nodeValues[3];
		$nodeValues[4] = (float)$nodeValues[4];
		$nodeValues[5] = (float)$nodeValues[5];
		$nodes[] = array_combine($nodeKeys, $nodeValues);
	}	
	//	Edges
	$edges = array();
	$edgeKeys = array("i1", "i2", "weight");
	for ($i=$n+1; $i<$l; $i++){
		$edgeValues = explode("\t", $lines[$i]);
		$edgeValues[0] = (int)$edgeValues[0];
		$edgeValues[1] = (int)$edgeValues[1];
		$edgeValues[2] = (float)$edgeValues[2];
		$edges[] = array_combine($edgeKeys, $edgeValues);
	}
	function edgeSortCallback($edge1, $edge2){
		return ($edge1["weight"] > $edge2["weight"]) ? +1 : -1;
	}
	usort($edges, "edgeSortCallback");

?>
<html>
	<body>
		<div id="paper"></div>
		<script type="text/javascript" src="/lib.js"></script>
		<script type="text/javascript">var nodes=<?			
			echo json_encode($nodes);
		?>;var edges=<?			
			echo json_encode($edges);
		?></script>
		<script type="text/javascript" src="test.js"></script>
	</body>
</html>