from db import connection, cursor, pointsCache
from time import time

print 'Getting ready...'
cursor.execute('DELETE FROM preset_preset WHERE preset1_type = "area" OR preset2_type = "area"')
cursor.execute('SELECT id FROM preset WHERE type = "area"')
presets_ids = [row[0] for row in cursor.fetchall()]
print 'Fetched %d presets.\n' % (len(presets_ids), )

print 'Starting...'
t0 = time()
for preset_id in presets_ids:
	t1 = time()
	cursor.execute(
	'''	INSERT INTO
	       	preset_preset
	        (   preset1_type
	        ,   preset1_id
	        ,   preset2_type
	        ,   preset2_id
	        ,   score
	        ,   nscore
	        )
		SELECT
		    "area" AS preset1_type
		,   %s AS preset1_id
		,   p_pr.preset_type AS preset2_type
		,   p_pr.preset_id AS preset2_id
		,   SUM(pr_p.value * p_pr.score) AS score
		,   SUM(pr_p.value * p_pr.score) AS nscore
		FROM
	        preset_point AS pr_p
		INNER JOIN
	        point_preset AS p_pr ON p_pr.point_id = pr_p.point_id
		WHERE
	        pr_p.preset_type = "area"
        AND
	        pr_p.preset_id = %s
        AND
	        p_pr.preset_type = "area"
		GROUP BY
	        p_pr.preset_type
		,   p_pr.preset_id
	''', (preset_id, preset_id, ))
	cursor.execute(
	'''	INSERT IGNORE INTO
	       	preset_preset
	        (   preset1_type
	        ,   preset1_id
	        ,   preset2_type
	        ,   preset2_id
	        ,   score
	        ,   nscore
	        )
		SELECT
		    "area" AS preset1_type
		,   %s AS preset1_id
		,   "area" AS preset2_type
		,   pr.id AS preset2_id
		,   0.0 AS score
		,   0.0 AS nscore
		FROM
	        preset AS pr
		WHERE
	        pr.type = "area"
	''', (preset_id, ))
	t = time()
	print 'area %-6d | total %-4f | iteration %-4f | %d empty' % (preset_id, t-t0, t-t1, cursor.rowcount, )
