import MySQLdb


# database connection & cursor
connection = MySQLdb.connect('polux.iscpif.fr', 'carma', 'afo7ahPh', 'carma_test');
cursor = connection.cursor()


# cache for points ids
class PointsCache(dict):
	def __missing__(self, key):
		cursor.execute('SELECT id FROM point WHERE x = %s AND y = %s AND z = %s', key)
		row = cursor.fetchone()
		if row:
			_id = row[0]
		else:
			cursor.execute('INSERT INTO point (x, y, z) VALUES (%s, %s, %s)', key)
			_id = cursor.lastrowid
		self[key] = _id
		return _id

pointsCache = PointsCache()