<?

	define("CARMA_MAX_GROUPS", 9);
	define("CARMA_GROUPS_COLOR_SHIFT", 4/9);
	define("CARMA_DEBUG", true);
	
	function sqlCaseCallback($match){
		return $match[1] . "_" . strtolower($match[2]);
	}
	function sqlCase($string){
		return preg_replace_callback("@([a-z])([A-Z])@", "sqlCaseCallback", $string);
	}
	

	class Point {
		function __construct($x, $y=false, $z=false){
			if ($y === false  &&  $z === false){
				$this->id = $x;
				foreach (mysql_single_assoc("SELECT x, y, z FROM point WHERE id = $x") as $c=>$v){
					$this->$c = $v;
				}
			} else{
				$this->x = $x;
				$this->y = $y;
				$this->z = $z;
				if (!($this->id = mysql_single_value("SELECT id FROM point WHERE x = $x AND y = $y AND z = $z"))){
					mysql_query("INSERT INTO point (x, y, z) VALUES ($x, $y, $z)");
					$this->id = mysql_insert_id();
				}
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
		}
	};

	class Pointset {
		function __construct($pointsetId=false, $id=false){
			if ($id === false){
				$this->id = $pointsetId;
				$pointset = mysql_single_assoc("SELECT query_id, group_subid FROM pointset WHERE id = $pointsetId");
				$this->queryId = $pointset["query_id"];
				$this->groupSubid = $pointset["group_subid"];
			} else{
				$this->queryId = $pointsetId;
				$this->groupSubid = $id;
				mysql_query("INSERT INTO pointset (query_id, group_subid) VALUES ($this->queryId, $this->groupSubid)");
				$this->id = mysql_insert_id();
			}
			$this->points = array();
			$rs = mysql_query("SELECT point_id, value FROM pointset_point WHERE pointset_id = $this->id");
			while ($r = mysql_fetch_assoc($rs)){
				$point = new Point($r["point_id"]);
				$point->value = $r["value"];
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
			$count = 0;
			switch ($type){
				case "tasks":
				case "genes":
					$table = preg_replace("@s$@", "", $type);
					if (is_array($data)){
						$ids = array();
						foreach ($data as $id){
							if (preg_match("@^\d+$@", $id)){
								$ids[] = $id;
							}
						}
						if ($ids){
							$ids = implode(",", $ids);
							$rs = mysql_query("
								SELECT
									point_id AS id
								,	SUM(value) AS value
								FROM
									{$table}_point
								WHERE
									{$table}_id IN ($ids)
								GROUP BY
									point_id
							");
							$count = mysql_num_rows($rs);
							while ($point = mysql_fetch_assoc($rs)){
								$this->addPoint($point["id"], $point["value"]);
							}
						}
					}
					break;
				case "nifti":
					
				case "text":
					$filenames = $data;
					$data = "";
					foreach ($filenames as $filename){
						$data .= file_get_contents("$file->rootdir/data/upload/$filename");
						$data .= "\n";
					}
				case "input":
					$re = "[-+]?[0-9]*\.?[0-9]+(?:[eE][-+]?[0-9]+)?";
					$lines = explode("\n", $data);
					foreach ($lines as $line){
						if (preg_match("@($re)[,;\s]($re)[,;\s]($re)(?:[,;\s]($re))?@", $line, $match)){
							if (count($match) == 4){
								$match[] = 1;
							}
							$this->addPoint($match[1], $match[2], $match[3], $match[4]);
							$count++;
						}
					}
					break;
				default:
					return;
			}
			$this->title(ucfirst($type) . " ($count points)");
		}
		function delete(){
			mysql_query("DELETE FROM pointset WHERE id = $this->id");
			mysql_query("DELETE FROM pointset_point WHERE pointset_id = $this->id");
			$this->id = 0;
		}
	};

	class Group {
		function __construct($queryId, $groupSubid){
			$this->queryId = $queryId;
			$this->subid = $groupSubid;
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
			if (in_array($column, array("title","description","hue","lastUpdateTime","lastCorrelationTime"))){
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
					pointset_point AS pp ON pp.pointset_id = p.id
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
				$points[] = $point;
			}
			return $points;
		}
		function addPointset(){
			$pointset = new Pointset($this->queryId, $this->subid);
			$this->pointsets[] = $pointset;
			$this->query()->lastUpdateTime(date("Y-m-d H:i:s"));
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
		function delete(){
			mysql_query("
				DELETE FROM
					pointset
				WHERE
					query_id = {$this->queryId}
				AND
					group_subid = {$this->subid}
			");
		}
	};

	class Query {	
		function __construct($queryId=false){
			if ($queryId === false){
				mysql_query("INSERT INTO query (title) VALUES ('Untitled query')");
				$this->id = mysql_insert_id();
			} else{
				$this->id = $queryId;
			}
			$this->groups = array();
			for ($groupSubid=0; $groupSubid<CARMA_MAX_GROUPS; $groupSubid++){
				$group = new Group($this->id, $groupSubid);
				if ($queryId === false){
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
			if (in_array($column, array("groups","title","graphCache","creationTime","lastUpdateTime","lastGraphTime"))){
				$column = sqlCase($column);
				$where = " WHERE id = $this->id";
				if ($arguments){
					$value = str_replace("'", "''", $arguments[0]);
					mysql_query("UPDATE query SET $column = '$value' $where");
					$n = mysql_affected_rows();
					if ($column == "groups" && $n > 0){
						$this->lastUpdateTime(date("Y-m-d H:i:s"));
					}
					return $n;
				} else{
					return mysql_single_value("SELECT $column FROM query $where");
				}
			} else{
				return false;
			}
		}
		function graph($type="", $limit=25){
			global $file;
			$maxGroupSubid = $this->groups();
			$type = preg_replace("@s$@", "", $type);
			if (!in_array($type, array("gene","task"))){
				return array();
			}
			if (CARMA_DEBUG || strtotime($this->lastUpdateTime()) > strtotime($this->lastGraphTime())){
				$correlations = $this->correlate($type, $limit);
				//	Graph initialization
				$n = $maxGroupSubid + min($limit, count($correlations));
				$graph = array
				(	"nodes"	=>	array_fill(0, $n, array())
				,	"edges"	=>	array_fill(0, $n, array_fill(0, $n, 0))
				);
				//	Write in graph: groups
				for ($i=0; $i<$maxGroupSubid; $i++){
					$graph["nodes"][$i] = array
					(	"type"	=>	"group"
					,	"id"	=>	$i
					,	"title"	=>	$this->groups[$i]->title()
					);
				}
				//	Write in graph: correlations with groups
				$indexFromId = array();
				foreach ($correlations as $c=>$correlation){
					$j = $c + $maxGroupSubid;
					$graph["nodes"][$j] = array
					(	"type"	=>	$type
					,	"id"	=>	$correlation["id"]
					,	"title"	=>	$correlation["title"]
					);
					$indexFromId[$correlation["id"]] = $j;
					foreach ($correlation["scores"] as $i=>$score){
						$graph["edges"][$i][$j] = round($score, 6);
					}
				}
				//	Temporary table: correlation between presets
				$ids = implode(",", array_keys($indexFromId));
				$rs = mysql_query("
					SELECT
						tt.{$type}1_id AS id1
					,	tt.{$type}2_id AS id2
					,	tt.score / (tt1.score * tt2.score) AS score
					FROM
						{$type}_{$type} AS tt
					INNER JOIN
						{$type}_{$type} AS tt1
							ON	tt1.{$type}1_id = tt.{$type}1_id
							AND	tt1.{$type}2_id = tt.{$type}1_id
					INNER JOIN
						{$type}_{$type} AS tt2
							ON	tt2.{$type}1_id = tt.{$type}2_id
							AND	tt2.{$type}2_id = tt.{$type}2_id
					WHERE
						tt.{$type}1_id IN ($ids)
					AND
						tt.{$type}2_id IN ($ids)
					AND
						tt.{$type}1_id <> tt.{$type}2_id
				");
				while ($r = mysql_fetch_assoc($rs)){
					$i = $indexFromId[$r["id1"]];
					$j = $indexFromId[$r["id2"]];
					if ($i > $j){
						$k = $j;
						$j = $i;
						$i = $k;
					}
					$graph["edges"][$i][$j] = 0.05 + round(0.95 * $r["score"], 6);
				}
				//	Ask C for a nice looking graph
				$filename = tempnam(sys_get_temp_dir(), "carma-graph-");
				$f = fopen($filename, "w");
				fwrite($f, $n);
				foreach ($graph["edges"] as $row){
					foreach ($row as $i=>$value){
						$value = sqrt($value * $value * $value);
						fwrite($f, $i ? " " : "\n");
						fwrite($f, $value);
					}
				}
				fclose($f);
				$lines = explode("\n", trim($file->c("graphFromFile", $filename)));
				unlink($filename);
				//	Add coordinates to the graph
				foreach ($lines as $i=>$line){
					$coordinates = explode("\t", $line);
					array_map("floatval", $coordinates);
					$graph["nodes"][$i]["x"] = round($coordinates[0], 3);
					$graph["nodes"][$i]["y"] = round($coordinates[1], 3);
				}
				//	Store the resulting graph
				$this->graphCache(serialize($graph));
				$this->lastGraphTime(date("Y-m-d H:i:d"));
			} else{
				$graph = unserialize($this->graphCache());
			}
			//	The end!
			return $graph;
		}
		function correlate($type="", $limit=100){
			$type = preg_replace("@s$@", "", $type);
			if (!in_array($type, array("gene","task"))){
				if ($return){
					return array();
				} else{
					return;
				}
			}
			if (CARMA_DEBUG || strtotime($this->lastUpdateTime()) > strtotime($this->lastCorrelationTime())){
				//	Points belonging to this group
				mysql_query("
					DROP TABLE IF EXISTS
						tmp__point
				");
				mysql_query("
					CREATE TEMPORARY TABLE
						tmp__point
					AS SELECT
						ps_p.point_id AS id
					,	SUM(ps_p.value) AS value
					FROM
						pointset AS ps
					INNER JOIN
						pointset_point AS ps_p ON ps_p.pointset_id = ps.id
					WHERE
						ps.query_id = {$this->queryId}
					AND
						ps.group_subid = {$this->subid}
					GROUP BY
						ps_p.point_id
				");
				//	Group autocorrelation score
				mysql_query("
					CREATE TEMPORARY TABLE
						tmp__point2
					AS SELECT
						id
					,	value
					FROM
						tmp__point
				");
				$autocorrelation = mysql_single_value("
					SELECT
						SUM(p1.value * p2.value * p1_p2.score) AS score
					FROM
						tmp__point AS p1
					INNER JOIN
						point_point AS p1_p2 ON p1_p2.point1_id = p1.id
					INNER JOIN
						tmp__point2 AS p2 ON p2.id = p1_p2.point2_id
				");
				mysql_query("
					DROP TABLE
						tmp__point2
				");
				//	Correlate with existing sets
				mysql_query("
					DELETE FROM
						group_correlation
					WHERE
						query_id = {$this->queryId}
					AND
						group_subid = {$this->subid}
					AND
						type = '{$type}'
				");
				mysql_query("
					INSERT INTO
						group_correlation
					SELECT
						{$this->queryId} AS query_id
					,	{$this->subid} AS group_subid
					,	'{$type}' AS type
					,	p2.{$type}_id AS correlated_id
					,	SUM(p1.value * p2.value * p1_p2.score) / SQRT(c.autocorrelation_score * {$autocorrelation}) AS score
					FROM
						tmp__point AS p1
					INNER JOIN
						point_point AS p1_p2 ON p1_p2.point1_id = p1.id
					INNER JOIN
						{$type}_point AS p2
							ON	p2.point_id = p1_p2.point2_id
					INNER JOIN
						{$type} AS c ON c.id = p2.{$type}_id
					GROUP BY
						p2.{$type}_id
				");
				mysql_query("
					INSERT IGNORE INTO
						group_correlation
						(	query_id
						,	group_subid
						,	type
						,	correlated_id
						,	score
						)
					SELECT
						{$this->queryId} AS query_id
					,	{$this->subid} AS group_subid
					,	'{$type}' AS type
					,	id AS correlated_id
					,	0 AS score
					FROM
						{$type}
				");
				//	The end!
				$this->lastCorrelationTime(date("Y-m-d H:i:s"));
			}
			if ($return){
				$correlations = array();
				$rs_correlations = mysql_query("
					SELECT
						c.id
					,	c.path
					,	c.name
					,	gc.score
					FROM
						group_correlation AS gc
					INNER JOIN
						{$type} AS c ON c.id = gc.correlated_id
					WHERE
						gc.query_id = $this->queryId
					AND
						gc.group_subid = $this->groupSubid
					AND
						gc.type = '$type'
					ORDER BY
						gc.score DESC
				");
				while ($correlation = mysql_fetch_assoc($rs_correlations)){
					$correlations[] = $correlation;
				}
				return $correlations;
			}
		}
			
		}
		function delete(){
			foreach ($this->groups as &$group){
				$group->delete();
			}			
			mysql_query("
				DELETE FROM
					`group`
				WHERE
					query_id = {$this->id}
			");			
			mysql_query("
				DELETE FROM
					`query`
				WHERE
					query_id = {$this->id}
			");
		}
	};
	

?>