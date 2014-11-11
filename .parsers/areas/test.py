from db import connection, cursor, pointsCache


cursor.execute(
'''	SELECT
		*
	FROM
		preset_preset
	LIMIT
		50
''')

for row in cursor.fetchall():
	for metadata, value in zip(cursor.description, row):
		print '%-16s%s' % (metadata[0], value)
	print