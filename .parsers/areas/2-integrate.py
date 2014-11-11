from os import listdir
from db import connection, cursor, pointsCache

#clean previously existing presets
cursor.execute('DELETE FROM preset WHERE type = "area"')
cursor.execute('DELETE FROM preset_point WHERE preset_type = "area"')
cursor.execute('DELETE FROM preset_point_gui WHERE preset_type = "area"')
cursor.execute('DELETE FROM preset_preset WHERE preset1_type = "area" OR preset2_type = "area"')
cursor.execute('DELETE FROM preset_publication WHERE preset_type = "area"')

# integrate all presets
preset_id = 0
for filename in listdir('labels-grouped'):	
	# open the file and get the label's name
	f = open('labels-grouped/%s' % (filename,))
	label = f.readline().strip()
	# integrate the preset in the database
	preset_id += 1
	cursor.execute('INSERT INTO preset (type, id, path, title, description) VALUES ("area", %s, %s, %s, "")', (preset_id, label, label, ))
	# integrate the points in the database
	i = 0
	sql1 = sql2 = ''
	for line in f:
		values = line.strip().split(';')
		weight = float(values.pop())
		coordinates = tuple(map(int, values))
		point_id = pointsCache[coordinates]
		sql1 += ',' if i else 'INSERT INTO preset_point (preset_type, preset_id, point_id, visible, value, nvalue) VALUES'
		sql1 += ' ("area", %d, %d, 0, %f, 0.0)' % (preset_id, point_id, weight, )
		sql2 += ',' if i else 'INSERT INTO preset_point_gui (preset_type, preset_id, point_id, value) VALUES'
		sql2 += ' ("area", %d, %d, %f)' % (preset_id, point_id, weight, )
		i += 1
	print '%d\t(%d)' % (preset_id, i)
	if i:
		cursor.execute(sql1)
		cursor.execute(sql2)
