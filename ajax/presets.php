<?

	define("CARMA_MAX_RESULTS_PRESET", 50);


	class AjaxPresets extends Ajax{
	
		function __construct(){
			parent::__construct();
		}
		
		function sql(){
			
			$maxResults = CARMA_MAX_RESULTS_PRESET;
			$query = preg_replace("@[-_]+@", "", strtolower($this->data->query));
			$type = $this->data->type;
			$this->data->page = (int)preg_replace("@[^\d]+@", "", $this->data->page);
			$startResult = $this->data->page ? $maxResults*$this->data->page : 0;
			
			//	Start query
			$sql = "
				SELECT SQL_CALC_FOUND_ROWS
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
					GROUP BY
						p.id
					,	p.path
					,	p.title
					ORDER BY
						SUM(kd" . implode(".score * kd", $indexes) . ".score) DESC
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
			//	Limit number of rows
			$sql .= "	
				LIMIT
					$startResult, $maxResults
			";
			//	That's it!
			return $sql;
		}
		function render(){
			$this->data->type = preg_replace("@s$@", "", $this->data->type);
			if (!in_array($this->data->type, array("task","gene","area"))){
				return;
			}
			$rs = mysql_query($this->sql());
			$results = mysql_single_value("SELECT FOUND_ROWS()");
			$list = array();
			while ($r = mysql_fetch_assoc($rs)){
				$r["title"] = ucfirst($r["title"]);
				$list[] = $r;
			}
			$this->output = array
			(	"time"			=>	$this->data->time
			,	"list"			=>	$list
			,	"page"			=>	$this->data->page
			,	"results"		=>	$results
			,	"maxResults"	=>	CARMA_MAX_RESULTS_PRESET
			);
			return parent::render();
		}
	
	};

?>
