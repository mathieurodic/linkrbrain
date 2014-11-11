from db import connection, cursor, keywordsCache
from collections import defaultdict
from time import time
import re


print '\nRemoving all the previous keywords...'
cursor.execute(
'''	DELETE FROM
		keyword_document
	WHERE
		document_type = "preset-area"
''')


print '\nGetting all the presets...'
cursor.execute(
'''	SELECT
		type,
		id,
		title
	FROM
		preset
	WHERE
		type = "area"
''')
rows = cursor.fetchall()


print '\nUpdating...'
t0 = time()
for preset_type, preset_id, title in rows:
	t1 = time()
	# prepare words
	words = re.findall(r'\w+', title)
	words = map(str.lower, words)
	dweight = 1 / float(len(words))
	# weigh words by id
	words_weights = defaultdict(float)
	for word in words:
		words_weights[keywordsCache[word]] += dweight
	# insert in the database
	sql = ''
	for keyword_id, score in words_weights.items():
		sql += ',' if sql else 'INSERT INTO keyword_document (keyword_id, document_type, document_id, score) VALUES'
		sql += ' (%d, "preset-%s", %d, %f)' % (keyword_id, preset_type, preset_id, score+1)
	# print sql
	# print 
	# continue
	cursor.execute(sql)
	# timer
	t = time()
	print '%s %-5d  [ %-4f | %4f ]' % (preset_type, preset_id, t-t0, t-t1, )