from db import connection, cursor, pointsCache

cursor.execute('DELETE FROM point_preset WHERE preset_type = "area"')
cursor.execute('SELECT id FROM preset WHERE type = "area"')
presets_ids = [row[0] for row in cursor.fetchall()]

for preset_id in presets_ids:
	cursor.execute(
	'''	INSERT INTO
	       	point_preset
	        (   point_id
	        ,   preset_type
	        ,   preset_id
	        ,   score
	        ,   nscore
	        )
		SELECT
		    p_p.point1_id AS point_id
		,   "area" AS preset_type
		,   %s AS preset_id
		,   SUM(p_p.score * pr_p.value) AS score
		,   SUM(p_p.score * pr_p.value) AS nscore
		FROM
	        preset_point AS pr_p
		INNER JOIN
	        point_point AS p_p ON p_p.point2_id = pr_p.point_id
		WHERE
	        pr_p.preset_type = "area"
        AND
	        pr_p.preset_id = %s
       	GROUP BY
       		p_p.point1_id
	''', (preset_id, preset_id, ))
	print preset_id, '\t=>', cursor.rowcount