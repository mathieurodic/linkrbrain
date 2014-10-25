<?

	//	group_points caching

	define("CARMA_MAX_GROUPS", 9);
	define("CARMA_GROUPS_COLOR_SHIFT", 4/9);
	// define("CARMA_DEBUG", true);
	define("CARMA_DEBUG", false);
	
	
	function sqlCaseCallback($match){
		return $match[1] . "_" . strtolower($match[2]);
	}
	function sqlCase($string){
		return preg_replace_callback("@([a-z])([A-Z])@", "sqlCaseCallback", $string);
	}

	function copyValues(&$arrayTo, $arrayFrom){
		foreach ($arrayFrom as $key=>$value){
			if (is_array($arrayTo)){
				if (isset($arrayTo[$key])){
					if (is_array($value) || is_object($value)){
						copyValues($arrayTo[$key], $value);
					} else{
						$arrayTo[$key] = $value;
					}
				}
			} elseif (is_object($arrayTo)){
				if (isset($arrayTo->$key)){
					if (is_array($value) || is_object($value)){
						copyValues($arrayTo->$key, $value);
					} else{
						$arrayTo->$key = $value;
					}
				}
			}
		}
	}
	
	

	class Point {
		function __construct($x, $y=false, $z=false){
			if ($y === false  &&  $z === false){
				$this->id = $x;
				foreach (mysql_single_assoc("SELECT x, y, z FROM point WHERE id = $x") as $c=>$v){
					$this->$c = $v;
				}
			} else{
				$this->x = 2 * round($x / 2);
				$this->y = 2 * round($y / 2);
				$this->z = 2 * round($z / 2);
				$this->id = mysql_single_value("
					SELECT
						id
					FROM
						point
					WHERE
						x = $this->x
					AND
						y = $this->y
					AND
						z = $this->z
				");
			}
		}
		function addToPointset($pointset, $value=1){
			$pointsetId = is_object($pointset)  ?  $pointset->id  :  $pointset;
			mysql_query("
				INSERT INTO
					pointset_point
					(	pointset_id
					,	point_id
					,	value
					)
				VALUES
					(	$pointsetId
					,	$this->id
					,	$value
					)
				ON DUPLICATE KEY UPDATE
					value = value + $value
			");
			mysql_query("
				INSERT INTO
					pointset_point_gui
					(	pointset_id
					,	point_id
					,	value
					)
				VALUES
					(	$pointsetId
					,	$this->id
					,	$value
					)
				ON DUPLICATE KEY UPDATE
					value = value + $value
			");
		}
	};
	

	class Pointset {
		function __construct($pointsetId=false, $id=false){
			if ($id === false){
				$this->id = (int)$pointsetId;
				$pointset = mysql_single_assoc("SELECT query_id, group_subid FROM pointset WHERE id = $pointsetId");
				$this->queryId = (int)$pointset["query_id"];
				$this->groupSubid = (int)$pointset["group_subid"];
			} else{
				$this->queryId = (int)$pointsetId;
				$this->groupSubid = (int)$id;
				mysql_query("INSERT INTO pointset (query_id, group_subid) VALUES ($this->queryId, $this->groupSubid)");
				$this->id = (int)mysql_insert_id();
			}
			$this->points = array();
			$rs = mysql_query("SELECT point_id, value FROM pointset_point WHERE pointset_id = $this->id");
			while ($r = mysql_fetch_assoc($rs)){
				$point = new Point($r["point_id"]);
				$point->value = (float)$r["value"];
				$this->points[] = $point;
			}
		}
		function __call($column, $arguments){
			if (in_array($column, array("type","title","description","data"))){
				$where = " WHERE id = $this->id";
				if ($arguments){
					$value = str_replace("'", "''", $arguments[0]);
					return mysql_query("UPDATE pointset SET $column = '$value' $where");
				} else{
					return mysql_single_value("SELECT $column FROM pointset $where");
				}
			} else{
				return false;
			}
		}
		
		function group(){
			return new Group($this->queryId, $this->groupSubid);
		}
		function query(){
			return new Query($this->queryId);
		}
		
		function addToGroup($queryId, $groupSubid=false){
			if ($groupSubid === false){
				$group = $queryId;
				$queryId = $group->queryId;
				$groupSubid = $group->subid;
			}
			mysql_query("
				UPDATE
					pointset
				SET
					query_id = $queryId
				,	group_subid = $groupSubid
				WHERE
					id = $this->id
			");
		}
		function addPoint($x, $y=false, $z=false, $value=false){
			if ($z === false){
				$point = is_object($x) ? $x : (new Point($x));
				$value = ($y===false) ? 1 : $y;
			} else{
				$point = new Point($x, $y, $z);
				$value = ($value===false) ? 1 : $value;
			}
			$point->addToPointset($this, $value);
			$point->value = $value;
			foreach ($this->points as $oldPoint){
				if ($point->id == $oldPoint->id){
					$oldPoint->value += $point->value;
					return;
				}
			}
			$this->points[] = $point;
			return $point;
		}
		function addPointsFromSource($type, $data){
			global $file;
			$title = ucfirst($type);
			$isFile = false;
			
			switch ($type){
				case "presets": {
					$data->type = preg_replace("@s$@", "", $data->type);
					if (is_array($data->ids)){
						$ids = array();
						foreach ($data->ids as $id){
							if (preg_match("@^\d+$@", $id)){
								$ids[] = $id;
							}
						}
						if ($ids){
							$ids = implode(",", $ids);
							$this->__call("data", array("{$type},{$data->type}:{$ids}"));
							mysql_query("
								INSERT INTO
									pointset_point
								SELECT
									{$this->id} AS pointset_id
								,	point_id
								,	SUM(value) AS value
								FROM
									preset_point
								WHERE
									preset_type = '{$data->type}'
								AND
									preset_id IN ({$ids})
								GROUP BY
									point_id
							");
							mysql_query("
								INSERT INTO
									pointset_point_gui
								SELECT
									{$this->id} AS pointset_id
								,	point_id
								,	SUM(value) AS value
								FROM
									preset_point_gui
								WHERE
									preset_type = '{$data->type}'
								AND
									preset_id IN ({$ids})
								GROUP BY
									point_id
							");
						}
						$names = array();
						$rs = mysql_query("
							SELECT
								title
							FROM
								preset
							WHERE
								type = '{$data->type}'
							AND
								id IN ({$ids})
							ORDER BY
								title
						");
						while ($name = mysql_fetch_value($rs)){
							$names[] = $name;
						}
						$title .= " (" . implode(", ", $names) . ")";
					}
					break;
				}
				case "nifti": {
					$filenames = $data;
					$data = array();
					foreach ($filenames as $filename){
						$input = base64_encode(file_get_contents("$file->rootdir/data/upload/$filename"));
						$output = $file->post("nifti", array("input" => $input, "filename" => $filename));
						// die($output);
						file_put_contents("$file->rootdir/data/upload/$filename.txt", $output);
						$data[] = "$filename.txt";
					}
				}
				case "text": {
					$filenames = $data;
					$data = "";
					foreach ($filenames as $filename){
						$data .= file_get_contents("$file->rootdir/data/upload/$filename");
						$data .= "\n";
					}
					$filenames = implode(",", $filenames);
					$this->__call("data", array("{$type}:{$filenames}"));
					$isFile = true;
				}
				case "input": {
					$num = "[-+]?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?";
					$del = "\s*[,;\s]\s*";
					$lines = explode("\n", $data);
					foreach ($lines as $line){
						$line = trim($line);
						if (preg_match("@($num)$del($num)$del($num)(?:$del($num))?@", $line, $match)){
							if (count($match) == 4){
								$match[] = 1;
							}
							$this->addPoint($match[1], $match[2], $match[3], $match[4]);
						}
					}
					if (!$isFile){
						$this->__call("data", array("{$type}:\n{$data}"));
					}
					break;
				}
				default: {
					return;
				}
			}
			$this->title($title);
		}
		function delete(){
			mysql_query("DELETE FROM pointset WHERE id = $this->id");
			mysql_query("DELETE FROM pointset_point WHERE pointset_id = $this->id");
			$this->id = 0;
		}
		function data(){
			return array
			(	"id"	=>	$this->id
			,	"title"	=>	$this->title()
			);
		}
	};
	

	class Group {
		function __construct($queryId, $groupSubid){
			$this->queryId = (int)$queryId;
			$this->subid = (int)$groupSubid;
			if (mysql_single_value("SELECT COUNT(*) FROM `group` WHERE query_id = $this->queryId AND subid = $this->subid") == 0){
				mysql_query("INSERT INTO `group` (query_id, subid) VALUES ($queryId, $groupSubid)");
				$this->title("Group " . ($groupSubid + 1));
			}
			$this->pointsets = array();
			$rs = mysql_query("SELECT id FROM pointset WHERE query_id = $this->queryId AND group_subid = $this->subid");
			while ($pointsetId = mysql_fetch_value($rs)){
				$this->pointsets[] = new Pointset($pointsetId);
			}
		}
		function __call($column, $arguments){
			if (in_array($column, array("title","description","hue"))){
				$column = sqlCase($column);
				$where = " WHERE query_id = $this->queryId AND subid = $this->subid";
				if (!empty($arguments)){
					$value = str_replace("'", "''", $arguments[0]);
					return mysql_query("UPDATE `group` SET $column = '$value' $where");
				} else{
					return mysql_single_value("SELECT $column FROM `group` $where");
				}
			} else{
				return false;
			}
		}
		function query(){
			return new Query($this->queryId);
		}
		
		function points(){
			$points = array();
			$rs = mysql_query("
				SELECT
					point.x
				,	point.y
				,	point.z
				,	SUM(pp.value) AS value
				FROM
					pointset AS p
				INNER JOIN
					pointset_point_gui AS pp ON pp.pointset_id = p.id
				INNER JOIN
					point ON point.id = pp.point_id
				WHERE
					p.query_id = $this->queryId
				AND
					p.group_subid = $this->subid
				GROUP BY
					point.id
			");
			while ($point = mysql_fetch_assoc($rs)){
				$points[] = array
				(	"x"		=>	(int)$point["x"]
				,	"y"		=>	(int)$point["y"]
				,	"z"		=>	(int)$point["z"]
				,	"value"	=>	(float)$point["value"]
				);
			}
			return $points;
		}
		function pointsets(){
			$pointsets = array();
			$rs = mysql_query("
				SELECT
					id
				FROM
					pointset
				WHERE
					query_id = {$this->queryId}
				AND
					group_subid = {$this->subid}
			");
			while ($id = mysql_fetch_value($rs)){
				$pointsets[] = new Pointset($id);
			}
			return $pointsets;
		}
		
		function addPointset(){
			$pointset = new Pointset($this->queryId, $this->subid);
			$this->pointsets[] = $pointset;
			$this->query()->lastUpdateTime(true);
			return $pointset;
		}
		function addPointsetFromSource($type, $data){
			$pointset = new Pointset($this->queryId, $this->subid);
			$pointset->addPointsFromSource($type, $data);
			$this->pointsets[] = $pointset;
			$this->lastUpdateTime(date("Y-m-d H:i:s"));
			$this->query()->lastUpdateTime(date("Y-m-d H:i:s"));
			return $pointset;
		}
		function data(){
			$pointsets = array();
			foreach ($this->pointsets as $pointset){
				$pointsets[] = $pointset->data();
			}
			return array
			(	"queryId"		=>	$this->queryId
			,	"subId"			=>	$this->subid
			,	"hue"			=>	(float)$this->hue()
			,	"title"			=>	$this->title()
			,	"description"	=>	$this->description()
			,	"pointsets"		=>	$pointsets
			,	"points"		=>	$this->points()
			);
		}
	};

	
	class Query {	
		function __construct($queryId=""){
			global $session;
			// echo "\n\n" . mysql_error() . "\n\n";
			// echo "\n\nID = " . $queryId . "\n\n";
			$queryId = preg_replace("@[^\d]+@", "", $queryId);
			if (is_object($session)){
				$userId = $session->userId();
				$sessionId = $session->id();
			} else{
				$userId = 0;
				$sessionId = 0;
			}
			if ($queryId == ""){
				mysql_query("
					INSERT INTO
						query
						(	session_id
						,	title
						)
					VALUES
						(	$sessionId
						,	'Untitled query'
						)
				");
				$this->id = mysql_insert_id();
				if (is_object($session)){
					$session->activity("query", "create", $this->id);
				}
			} else if (is_object($session)){
				$this->id = mysql_single_value("
					SELECT
						q.id
					FROM
						query AS q
					WHERE
						q.id = $queryId
					AND
						(
							q.session_id = 0
						OR
							q.session_id IN
							(
								SELECT
									s.id
								FROM
									session AS s
								WHERE
									s.user_id = $userId
							)
						)
				");
				$session->activity("query", "load", $queryId, $this->id);
			} else{
				$this->id = mysql_single_value("
					SELECT
						q.id
					FROM
						query AS q
					WHERE
						q.id = $queryId
					AND
						q.session_id = 0
				");
			}
			$this->groups = array();
			for ($groupSubid=0; $groupSubid<CARMA_MAX_GROUPS; $groupSubid++){
				$group = new Group($this->id, $groupSubid);
				if (!$queryId){
					if ($groupSubid){
						$hue += CARMA_GROUPS_COLOR_SHIFT;
						if ($hue > 1){
							$hue -= 1;
						}
					} else{
						$hue = lcg_value();
					}
					$group->hue($hue);
				}
				$this->groups[] = $group;
			}
		}
		function __call($column, $arguments){
			if (in_array($column, array("groups","title","correlationCache","graphCache","creationTime","lastGroupsTime","lastUpdateTime","lastCorrelationTime","lastGraphTime","correlateWith"))){
				$column = sqlCase($column);
				$where = " WHERE id = $this->id";
				if ($arguments){
					if ($column == "title"){
						$data = $this->data(array("title" => $arguments[0]));
					} elseif ($column == "groups"){
						$this->lastUpdateTime();
					}
					if (preg_match("@_time$@", $column)){
						$value = date("Y-m-d H:i:s");
					} else{
						$value = str_replace("'", "''", $arguments[0]);
					}
					mysql_query("UPDATE query SET $column = '$value' $where");
					$n = mysql_affected_rows();
					if ($column == "groups" && $n > 0){
						$this->lastUpdateTime(date("Y-m-d H:i:s"));
					}
					return $value;
				} else{
					return mysql_single_value("SELECT $column FROM query $where");
				}
			} else{
				return false;
			}
		}
		
		function delete(){
			global $session;
			if (is_object($session)){
				$session->activity("query", "delete", $this->id);
			}
			mysql_query("
				UPDATE
					`query`
				SET
					deleted = 1
				WHERE
					query_id = {$this->id}
			");
		}
		
		function correlate($limit=100){
			global $file;
			$settings = $this->settings();
			$correlations = array();
			$preset_type = $settings["correlation"]["type"];
			$maxGroupSubid = $this->groups();
			
			//
			//	Points by group
			//
			{
				mysql_query("
					DELETE FROM
						group_point
					WHERE
						query_id = {$this->id}
				");
				mysql_query("
					INSERT INTO
						group_point
					SELECT
						{$this->id} AS query_id
					,	p.group_subid
					,	pp.point_id
					,	SUM(pp.value)
					FROM
						pointset AS p
					INNER JOIN
						pointset_point AS pp ON pp.pointset_id = p.id
					WHERE
						p.query_id = {$this->id}
					AND
						p.group_subid < {$maxGroupSubid}
					GROUP BY
						p.group_subid
					,	pp.point_id
				");
				if (!mysql_affected_rows()){
					return null;
				}
			}
			
			//
			//	Points in query
			//
			{
				mysql_query("
					DELETE FROM
						query_point
					WHERE
						query_id = {$this->id}
				");
				mysql_query("
					DROP TABLE IF EXISTS
						groupcount
				");
				mysql_query("
					CREATE TEMPORARY TABLE
						groupvalue
					AS SELECT
						group_subid
					,	SUM(value) AS value
					FROM
						group_point
					WHERE
						query_id = {$this->id}
					GROUP BY
						group_subid
				");
				$sumvalues = mysql_single_value("
					SELECT
						SUM(value)
					FROM
						group_point
					WHERE
						query_id = {$this->id}
				");
				mysql_query("
					INSERT INTO
						query_point
					SELECT
						{$this->id} AS query_id
					,	gp.point_id
					,	SUM(gp.value / gv.value) * {$sumvalues} AS value
					FROM
						group_point AS gp
					INNER JOIN
						groupvalue AS gv ON gv.group_subid = gp.group_subid
					WHERE
						gp.query_id = {$this->id}
					GROUP BY
						gp.point_id
				");
			}
			
			//
			//	Autocorrelation score within query
			//
			{
				$rscore = sqrt(mysql_single_value("
					SELECT
						SUM(qp1.value * pp.score * qp2.value) AS score
					FROM
						query_point AS qp1
					INNER JOIN
						point_point AS pp ON pp.point1_id = qp1.point_id
					INNER JOIN
						query_point AS qp2 ON qp2.query_id = {$this->id} AND qp2.point_id = pp.point2_id
					WHERE
						qp1.query_id = {$this->id}
				"));
				mysql_query("
					UPDATE
						query
					SET
						rscore = {$rscore}
					WHERE
						id = {$this->id}
				");
			}
			
			//
			//	Correlation scores between groups
			//
			{
				mysql_query("
					DELETE FROM
						group_group
					WHERE
						query_id = {$this->id}
				");
				if ($maxGroupSubid == 1){
					mysql_query("
						INSERT INTO
							group_group
						SELECT
							{$this->id} AS query_id
						,	0 AS group1_subid
						,	0 AS group2_subid
						,	{$rscore} * {$rscore} AS score
						,	1.0 AS nscore
					");
				} else{
					mysql_query("
						INSERT INTO
							group_group
						SELECT
							{$this->id} AS query_id
						,	gp1.group_subid AS group1_subid
						,	gp2.group_subid AS group2_subid
						,	SUM(gp1.value * gp2.value * pp.score) AS score
						,	1.0 AS nscore
						FROM
							group_point AS gp1
						INNER JOIN
							point_point AS pp ON pp.point1_id = gp1.point_id
						INNER JOIN
							group_point AS gp2 ON gp2.point_id = pp.point2_id
						WHERE
							gp1.query_id = {$this->id}
						AND
							gp2.query_id = {$this->id}
						AND
							gp2.group_subid >= gp1.group_subid
						GROUP BY
							gp1.group_subid
						,	gp2.group_subid
					");
					mysql_query("
						UPDATE
							`group` AS g
						INNER JOIN
							group_group AS gg ON gg.query_id = {$this->id} AND gg.group1_subid = g.subid AND gg.group2_subid = g.subid
						SET
							g.rscore = SQRT(gg.score)
						WHERE
							g.query_id = {$this->id}
						AND
							g.subid < {$maxGroupSubid}
					");
					mysql_query("
						UPDATE
							group_group AS g0
						INNER JOIN
							`group` AS g1 ON g1.query_id = {$this->id} AND g1.subid = g0.group1_subid
						INNER JOIN
							`group` AS g2 ON g2.query_id = {$this->id} AND g2.subid = g0.group1_subid
						SET
							g0.nscore = g0.score / (g1.rscore * g2.rscore)
						WHERE
							g0.query_id = {$this->id}
					");
					echo mysql_error();
				}
			}
			
			//
			//	Process correlations with query
			//
			{
				mysql_query("
					DELETE FROM
						query_preset
					WHERE
						query_id = {$this->id}
				");
				mysql_query("
					INSERT INTO
						query_preset
					SELECT
						{$this->id} AS query_id
					,	'{$preset_type}' AS preset_type
					,	pp.preset_id
					,	SUM(pp.nscore) / {$rscore} AS score
					FROM
						query_point AS qp
					INNER JOIN
						point_preset AS pp ON pp.point_id = qp.point_id
					WHERE
						qp.query_id = {$this->id}
					AND
						pp.preset_type = '{$preset_type}'
					GROUP BY
						pp.preset_id
					ORDER BY
						score DESC
					LIMIT
						100
				");
			}
			
			//
			//	Process correlations with groups
			//
			{
				mysql_query("
					DELETE FROM
						group_preset
					WHERE
						query_id = {$this->id}
				");
				if ($maxGroupSubid == 1){
					mysql_query("
						INSERT INTO
							group_preset
						SELECT
							{$this->id} AS query_id
						,	0 AS group_subid
						,	'{$preset_type}' AS preset_type
						,	preset_id
						,	score
						FROM
							query_preset
						WHERE
							query_id = {$this->id}
					");
				} else{
					mysql_query("
						INSERT INTO
							group_preset
						SELECT
							{$this->id} AS query_id
						,	g_p.group_subid
						,	'{$preset_type}' AS preset_type
						,	p_pr.preset_id
						,	SUM(p_pr.nscore) / SQRT(g_g.score) AS score
						FROM
							group_group AS g_g
						INNER JOIN
							group_point AS g_p
								ON	g_p.query_id = {$this->id}
								AND	g_p.group_subid = g_g.group1_subid
						INNER JOIN
							point_preset AS p_pr
								ON	p_pr.point_id = g_p.point_id
								AND	p_pr.preset_type = '{$preset_type}'
						INNER JOIN
							query_preset AS q_pr
								ON	q_pr.query_id = {$this->id}
								AND	q_pr.preset_type = '{$preset_type}'
								AND	q_pr.preset_id = p_pr.preset_id
						WHERE
							g_g.query_id = {$this->id}
						AND
							g_p.group_subid < {$maxGroupSubid}
						AND
							g_g.group2_subid = g_g.group1_subid
						GROUP BY
							g_p.group_subid
						,	p_pr.preset_id
					");
				}
			}
			
			//
			//	Return result
			//
			$correlations = array();
			$rs = mysql_query("
				SELECT
					pr.id
				,	pr.path
				,	pr.title
				,	q_pr.score
				FROM
					query_preset AS q_pr
				INNER JOIN
					preset AS pr ON pr.id = q_pr.preset_id AND pr.type = '{$preset_type}'
				WHERE
					q_pr.query_id = {$this->id}
				AND
					q_pr.preset_type = '{$preset_type}'
				ORDER BY
					q_pr.score DESC
				LIMIT
					{$limit}
			");
			if ($maxGroupSubid == 1){
				while ($correlation = mysql_fetch_assoc($rs)){
					$scores = array_fill(0, 2, (float)array_pop($correlation));
					$correlation["scores"] = $scores;
					$correlations[] = $correlation;
				}				
			} else{
				$correlationById = array();
				while ($correlation = mysql_fetch_assoc($rs)){
					$scores = array_fill(0, $maxGroupSubid+1, 0);
					$scores[0] = (float)array_pop($correlation);
					$correlation["scores"] = $scores;
					$correlationById[$correlation["id"]] = count($correlations);
					$correlations[] = $correlation;
				}
				$rs = mysql_query("
					SELECT
						gp.group_subid
					,	gp.preset_id
					,	gp.score
					FROM
						group_preset AS gp
					INNER JOIN
						query_preset AS qp ON qp.query_id = {$this->id} AND qp.preset_id = gp.preset_id
					WHERE
						gp.query_id = {$this->id}
				");
				while ($correlation = mysql_fetch_assoc($rs)){
					if (isset($correlationById[$correlation["preset_id"]])){
						$i = $correlationById[$correlation["preset_id"]];
						$j = $correlation["group_subid"] + 1;
						$correlations[$i]["scores"][$j] = (float)$correlation["score"];
					}
				}
			}
			return $correlations;
		}
		function graph($correlations, $limit=25){
			
			global $file;
			$maxGroupSubid = $this->groups();
			$settings = $this->settings();
			$type = $settings["correlation"]["type"];
			$threshold = $settings["graph"]["threshold"];
			if (!$presets = $correlations){
				return null;
			}
			
			//	Graph initialization
			$n = $maxGroupSubid + min($limit, count($presets));
			$graph = array
			(	"nodes"		=>	array_fill(0, $n, array())
			,	"edges"		=>	array_fill(0, $n, array_fill(0, $n, 0))
			);
			//	Write in graph: groups
			for ($i=0; $i<$maxGroupSubid; $i++){
				$graph["nodes"][$i] = array
				(	"type"	=>	"group"
				,	"id"	=>	(int)$i
				,	"title"	=>	$this->groups[$i]->title()
				,	"hue"	=>	$this->groups[$i]->hue()
				);
			}
			//	Get the maximum score for this query
			$maxscore = mysql_single_value("
				SELECT
					MAX(score)
				FROM
					group_preset
				WHERE
					query_id = {$this->id}
				AND
					group_subid < {$maxGroupSubid}
				AND
					preset_type = '{$type}'
			");
			//	Write in graph: correlations with groups
			$indexFromId = array();
			foreach ($presets as $p=>$preset){
				$j = $p + $maxGroupSubid;
				if ($j >= $n){
					break;
				}
				$graph["nodes"][$j] = array
				(	"type"	=>	$type
				,	"id"	=>	$preset["id"]
				,	"title"	=>	$preset["title"]
				);
				$indexFromId[$preset["id"]] = $j;
				for ($i=0; $i<=$maxGroupSubid; $i++){
					$score = isset($preset["scores"][$i+1]) ? $preset["scores"][$i+1] : 0;
					$graph["edges"][$i][$j] = round($score/$maxscore, 6);
				}
			}
			//	Correlation between presets
			$ids = implode(",", array_keys($indexFromId));
			$rs = mysql_query("
				SELECT
					preset1_id
				,	preset2_id
				,	nscore
				FROM
					preset_preset
				WHERE
					preset1_type = '{$type}'
				AND
					preset1_id IN ($ids)
				AND
					preset2_type = '{$type}'
				AND
					preset2_id IN ($ids)
				AND
					preset2_id > preset1_id
			");
			while ($r = mysql_fetch_assoc($rs)){
				$i = $indexFromId[$r["preset1_id"]];
				$j = $indexFromId[$r["preset2_id"]];
				if ($i > $j){
					$k = $j;
					$j = $i;
					$i = $k;
				}
				$graph["edges"][$i][$j] = round($r["nscore"], 6);
			}
			foreach ($graph["edges"] as $i=>$row){
				if ($i >= $maxGroupSubid){
					foreach ($row as $j=>$score){
						if ($i < $j){
							$graph["edges"][$i][$j] += $threshold;
						}
					}
				}
			}
			
			//
			//	Create a file with the given links
			//
			
			$zData = $n;
			foreach ($graph["edges"] as $row){
				foreach ($row as $i=>$value){
					$value = sqrt($value * $value * $value);
					$zData .= $i ? " " : "\n";
					$zData .= $value;
				}
			}			
			// $filename = tempnam(sys_get_temp_dir(), "carma-graph-");
			// $f = fopen($filename, "w");
			// fwrite($f, $n);
			// foreach ($graph["edges"] as $row){
				// foreach ($row as $i=>$value){
					// $value = sqrt($value * $value * $value);
					// fwrite($f, $i ? " " : "\n");
					// fwrite($f, $value);
				// }
			// }
			// fclose($f);
			
			//
			//	Ask Zebulon for a nice looking graph
			//
			
			// $result = $file->c("graphFromFile", $filename);
			// $result = $file->post("graph", array("input" => file_get_contents($filename)));
			$result = $file->post("graph", array("input" => $zData));
			
			//
			//	Add calculated coordinates to the graph
			//
			// die($result);
			foreach (explode("\n", rtrim($result)) as $l=>$line){
				$coordinates = explode("\t", $line);
				array_map("floatval", $coordinates);
				$graph["nodes"][$l]["x"] = round($coordinates[0], 3);
				$graph["nodes"][$l]["y"] = round($coordinates[1], 3);
			}
			
			//
			//	Return the resulting graph it
			//
			return $graph;
		}
		
		function pointsets(){
			$pointsets = array();
			$rs = mysql_query("
				SELECT
					id
				FROM
					pointset
				WHERE
					query_id = {$this->id}
			");
			while ($id = mysql_fetch_value($rs)){
				$pointsets[] = new Pointset($id);
			}
			return $pointsets;
		}
		
		function viewGroups(){
			$array = array();
			$maxGroupSubid = $this->groups();
			for ($groupSubid=0; $groupSubid<$maxGroupSubid; $groupSubid++){
				$group = $this->groups[$groupSubid];
				$array[] = array
				(	"hue"		=>	(float)$group->hue()
				,	"title"		=>	$group->title()
				,	"points"	=>	$group->points()
				);
			}
			return $array;
		}
		
		function settings($newSettings = false){
			global $file;
			$settings = NULL;
			//	Old settings
			$json = $file->cache("query-settings-{$this->id}.json");
			if (is_string($json)){
				$settings = json_decode($json, true);
			}
			if (!$settings){
				$settings = json_decode($file->data("json/settings.json"), true);
			}
			if (is_object($newSettings) || is_array($newSettings)){
				copyValues($settings, $newSettings);
			}
			$file->cache("query-settings-{$this->id}.json", json_encode($settings));
			return $settings;
		}		
		function data($newData = array()){
			global $file;
			$queryData = array();
			//	Time values
			$creationTime = strtotime($this->creationTime());
			$lastUpdateTime = strtotime($this->lastUpdateTime());
			$lastGroupsTime = strtotime($this->lastGroupsTime());
			$lastCorrelationTime = strtotime($this->lastCorrelationTime());
			$lastGraphTime = strtotime($this->lastGraphTime());
			//	Retrieving data from cache
			$fileName = "query-{$this->id}.json";
			$json = $file->cache($fileName);
			if (!CARMA_DEBUG && $json){
				$queryData = json_decode($json, true);
			} else{
				$queryData = array
				(	"id"	=>	$this->id
				,	"title"	=>	$this->title()
				);
			}
			//	Settings
			$queryData["settings"] = $this->settings();
			//	Groups
			if (CARMA_DEBUG  ||  strtotime($this->lastUpdateTime()) > strtotime($this->lastGroupsTime())  ||  !isset($queryData["groups"])){
				$number = (int)$this->groups();
				$queryData["groups"] = array();
				foreach ($this->groups as $g=>$group){
					if ($g < $number){
						$queryData["groups"][] = $group->data();
					} else{
						break;
					}
				}
				$lastGroupsTime = $this->lastGroupsTime(true);
			}
			//	Correlations
			if ($this->lastUpdateTime() > $this->creationTime()  ||  !isset($queryData["correlations"], $queryData["graph"])){
				if (CARMA_DEBUG  ||  strtotime($this->lastGroupsTime()) > strtotime($this->lastCorrelationTime())  ||  !isset($queryData["correlations"])){
					$queryData["correlations"] = $this->correlate();
					$lastCorrelationTime = $this->lastCorrelationTime(true);
				}
				//	Graph
				if (CARMA_DEBUG  ||  strtotime($this->lastCorrelationTime()) > strtotime($this->lastGraphTime())  ||  !isset($queryData["graph"])){
					$queryData["graph"] = $this->graph($queryData["correlations"]);
					$lastGraphTime = $this->lastGraphTime(true);
				}
			}
			//	Update given values
			foreach ($newData as $key=>$value){
				$queryData[$key] = $value;
			}
			//	The end!
			$file->cache($fileName, json_encode($queryData));
			return $queryData;
		}
		function dataJSON(){
			return json_encode($this->data());
		}
	
	};
	
	
	class Queries {
		function __construct($where = array(), $orderBy = array()){
			$this->list = array();
			$this->filter($where, $orderBy);
		}
		function filter($where = array(), $orderBy = array()){
			global $session;
			$userId = $session->userId();
			$sessionId = $session->id();
			$sql = "
				SELECT
					q.id AS q_id
				,	q.title AS q_title
				,	q.creation_time AS q_creation_time
				,	q.last_update_time AS q_last_update_time
				,	g.title AS g_title
				,	g.hue AS g_hue
				FROM
					query AS q
				INNER JOIN
					session AS s ON s.id = q.session_id
				INNER JOIN
					`group` AS g ON g.query_id = q.id AND g.subid < q.groups
				WHERE
					q.last_update_time > '2001-01-01'
				AND
				(
						q.session_id = $sessionId
					OR
						s.user_id = $userId AND s.user_id <> 0
				)
				AND
					q.deleted = 0
			";
			$sql .= implode(",", $where);
			$sql .= "
				ORDER BY
					q.last_update_time DESC
				,	q.id DESC
				,	g.subid
			";
			$sql .= implode(",", $orderBy);
			$this->list = $this->request($sql);
		}
		private function request($sql){
			$rs = mysql_query($sql);
			$queries = array();
			$query = array("id" => 0);
			while ($r = mysql_fetch_assoc($rs)){
				if ($r["q_id"] != $query["id"]){
					if ($query["id"]){
						$queries[] = $query;
					}
					$query = array
					(	"id"				=>	(int)$r["q_id"]
					,	"title"				=>	$r["q_title"]
					,	"creationTime"		=>	strtotime($r["q_creation_time"])
					,	"lastUpdateTime"	=>	strtotime($r["q_last_update_time"])
					,	"groups"			=>	array()
					);
				}
				$query["groups"][] = array
				(	"title"	=>	$r["g_title"]
				,	"hue"	=>	(float)$r["g_hue"]
				);
			}
			if ($query["id"]){
				$queries[] = $query;
			}
			return $queries;
		}
	};
	

?>
