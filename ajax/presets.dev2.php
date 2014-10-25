<?

	$results = 50;

	require_once "../db.php";
	$query = preg_replace("@[-_]+@", "", strtolower($_REQUEST["q"]));
	$type = preg_replace("@[^\w]+@", "", $_REQUEST["type"]);
	$page = preg_replace("@[^\d]+@", "", $_REQUEST["p"]);
	$page = $page ? $results*$page : 0;
	
	
	$t1 = microtime(true);
	
	if (preg_match_all("@\w[\w\-_]*@", $query, $matches)){
		//	Extract words
		$words = array_unique($matches[0]);
		function sort_strlen($a, $b){
			return strlen($b) - strlen($a);
		}
		usort($words, "sort_strlen");
		$indexes = array_keys($words);
		//	Create temporary table for the first word
		$word = array_shift($words);
		mysql_query("
			CREATE TEMPORARY TABLE
				__result
			AS SELECT
				p.id
			,	p.path
			,	p.title
			,	kd.score AS score
			FROM
				preset AS p
			INNER JOIN
				keyword_document AS kd ON kd.document_type = 'preset-$type' AND kd.document_id = p.id
			INNER JOIN
				keyword AS k ON k.id = kd.keyword_id
			WHERE
				p.type = '$type'
			AND
				k.word LIKE '$word%'
		");
		//	Remove non-matching elements for the next words
		foreach ($words as $i=>$word){
			// $sql .= "
				// AND
					// k$i.word LIKE '$word%'
			// ";
		}
		//	Relevance of results
		$sql = "
			SELECT
				id
			,	path
			,	title
			FROM
				__result
			ORDER BY
				score DESC
		";
	} else{
		$sql = "
			SELECT
				id
			,	path
			,	title
			FROM
				preset
			WHERE
				type = '$type'
			ORDER BY
				title
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