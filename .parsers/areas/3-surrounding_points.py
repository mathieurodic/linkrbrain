from db import connection, cursor, pointsCache
from collections import defaultdict
from math import sqrt


# get all points around those
print 'Query points...'
cursor.execute('SELECT p.id, p.x, p.y, p.z FROM point AS p INNER JOIN preset_point AS pp ON pp.point_id = p.id WHERE pp.preset_type = "area" GROUP BY p.id ORDER BY p.id DESC')
print 'Fetch points...'
points = cursor.fetchall()
print 'Insert points...'
i = 0
n = len(points)
# for id1, x, y, z in points:
# 	cursor.execute('''
# 		INSERT IGNORE INTO
# 			point
# 			(	x
# 			,	y
# 			,	z
# 			)
# 		SELECT
# 			%s + dx AS x
# 		,	%s + dy AS y
# 		,	%s + dz AS z
# 		FROM
# 			dpoint
# 	''', (x, y, z))
# 	i += 1
# 	if i % 100:
# 		print '%d / %d\r' % (i, n)


# connect the points
print 'Connect everything...'
i = 0
r = 0
for id1, x, y, z in points:
	cursor.execute('''
		INSERT IGNORE INTO
			point_point
			(	point1_id
			,	point2_id
			,	score
			)
		SELECT
			%s
		,	p.id
		,	d.score
		FROM
			dpoint AS d
		INNER JOIN
			point
				AS
					p
				ON
					p.x = %s + d.dx
				AND
					p.y = %s + d.dy
				AND
					p.z = %s + d.dz
	''', (id1, x, y, z))
	i += 1
	r += cursor.rowcount
	if i % 100 == 0:
		print '%d / %d\t(%d)\r' % (i, n, r)
