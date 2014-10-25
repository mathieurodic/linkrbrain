<?

	$results = 50;

	require_once "../db.php";
	$query = preg_replace("@[-_]+@", "", strtolower($_REQUEST["q"]));
	$type = preg_replace("@[^\w]+@", "", $_REQUEST["type"]);
	$page = preg_replace("@[^\d]+@", "", $_REQUEST["p"]);
	$page = $page ? $results*$page : 0;
	
	
	$sql = "
		SELECT
			p.id
		,	p.path
		,	p.title
			
		FROM
			preset AS p
	";
	if (preg_match_all("@\w[\w\-_]*@", $query, $matches)){
		//	Extract words
		$words = array_unique($matches[0]);
		function sort_strlen($a, $b){
			return strlen($b) - strlen($a);
		}
		usort($words, "sort_strlen");
		$indexes = array_keys($words);
		//	Tables
		foreach ($words as $i=>$word){
			$id = $i ? "kd".($i-1).".document_id" : "p.id";
			$sql .= "
				INNER JOIN
					keyword_document AS kd$i ON kd$i.document_type = 'preset-$type' AND kd$i.document_id = $id
				INNER JOIN
					keyword AS k$i ON k$i.id = kd$i.keyword_id
			";
		}
		//	Conditions
		$sql .= "
			WHERE
				p.type = '$type'
		";
		foreach ($words as $i=>$word){
			$sql .= "
				AND
					k$i.word LIKE '$word%'
			";
		}
		//	Relevance of results
		$sql .= "
			ORDER BY
				kd" . implode(".score * kd", $indexes) . ".score DESC
			,	p.title
		";
	} else{
		$sql .= "
			WHERE
				p.type = '$type'
			ORDER BY
				p.title
		";
	}
	
	
	//	
	//	
	//	À tenter :
	//	
	//	1 - faire un SELECT INTO vers une temporary table
	//	    avec tous les document_id correspondant
	//	    au premier mot
	//	
	//	2 - faire des DELETE pour éliminer successivement
	//	    les document_id ne correspondant pas aux
	//	    mots suivants
	//	
	
	
	//	Limit number of rows
	$sql .= "	
		LIMIT
			$page, $results
	";
	
	$t1 = microtime(true);
	$rs = mysql_query($sql);
	$t2 = microtime(true);
	echo mysql_error();
	$return = array
	(	"t"			=>	$_REQUEST["t"]
	,	"dt"		=>	($t2 - $t1)
	,	"results"	=>	array()
	);
	while ($r = mysql_fetch_assoc($rs)){
		$return["results"][] = $r;
	}
	echo json_encode($return, JSON_PRETTY_PRINT);

?>