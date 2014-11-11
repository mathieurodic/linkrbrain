from db import connection, cursor, pointsCache
from math import sqrt
from time import time

print '\nGetting all the presets...'
cursor.execute(
'''	SELECT
		preset1_type,
		preset1_id,
		score
	FROM
		preset_preset
	WHERE
		preset1_type = "area"
	AND
		preset2_type = "area"
	AND
		preset2_id = preset1_id
''')
rows = cursor.fetchall()

print '\nUpdating...'
t0 = time()
for preset_type, preset_id, rscore in rows:
	t1 = time()
	# hopla
	cursor.execute(
	'''	UPDATE
			preset
		SET
			rscore = %s
		WHERE
			type = %s
		AND
			id = %s
	''', (rscore, preset_type, preset_id))
	t = time()
	print '%s %-5d | total %-4f | iteration %4f' % (preset_type, preset_id, t-t0, t-t1, )