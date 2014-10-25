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