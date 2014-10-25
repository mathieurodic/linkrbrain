<?

	class Tasks{
		
		function __construct(){
		}
		
		function search($words, $limit = 10){
			$result = array();
			if ($words){
				$clause = "k.word LIKE '"  . implode("%' OR k.word LIKE '", $words) . "%'";
				// die("
				$rs = mysql_query("
					SELECT
						t.name
					,	SUM(kt.weight) AS score
					FROM
						keyword AS k
					INNER JOIN
						keyword_task AS kt ON kt.keyword_id = k.id
					INNER JOIN
						task AS t ON t.id = kt.task_id
					WHERE
						$clause
					GROUP BY
						t.name
					ORDER BY
						score DESC
					LIMIT
						$limit
				");
				while ($word = mysql_fetch_value($rs)){
					$result[] = $word;
				}
			}
			return $result;
		}
		
	};

?>