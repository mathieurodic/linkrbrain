from db import connection, cursor, pointsCache
from math import sqrt
from time import time


print '\nRestoring all the presets-presets scores...'
cursor.execute(
'''	UPDATE
		preset_preset
	SET
		nscore = score
	WHERE
		preset1_type = "area"
	OR
		preset2_type = "area"
''')


print '\nGetting all the presets...'
cursor.execute(
'''	SELECT
		type,
		id,
		rscore
	FROM
		preset
	WHERE
		type = "area"
''')
rows = cursor.fetchall()


print '\nUpdating...'
t0 = time()
for preset_type, preset_id, rscore in rows:
	t1 = time()
	# hopla
	cursor.execute(
	'''	UPDATE
			preset_preset
		SET
			nscore = nscore / %s
		WHERE
			preset1_type = %s
		AND
			preset1_id = %s
	''', (rscore, preset_type, preset_id))
	cursor.execute(
	'''	UPDATE
			preset_preset
		SET
			nscore = nscore / %s
		WHERE
			preset2_type = %s
		AND
			preset2_id = %s
	''', (rscore, preset_type, preset_id))
	t = time()
	print '%s %-5d  [ %-4f | %4f ]' % (preset_type, preset_id, t-t0, t-t1, )