from os import listdir
from collections import defaultdict


i = 1
scale = 2.
rounder = lambda x: int(scale * round(x / scale))

for filename in listdir('labels'):
	# open the file and get the label's name
	f_in = open('labels/%s' % (filename,))
	label = f_in.readline().strip()
	# read coordinates, write them to dict
	barycenters = defaultdict(float)
	for line in f_in:
		try:
			coordinates = map(int, line.strip().split(';'))
			coordinates = map(rounder, coordinates)
			coordinates = tuple(coordinates)
			barycenters[coordinates] += 1.
		except:
			print filename, '=>', line
			exit()
	# control
	if not barycenters:
		continue
	# write new coordinates
	f_out = open('labels-grouped/%s' % (filename,), 'w')
	max_value = max(barycenters.values())
	f_out.write('%s\n' % (label, ))
	for coordinates, value in barycenters.items():
		f_out.write('%d;%d;%d;' % coordinates)
		f_out.write('%f\n' % (value/max_value, ))
	# debug
	print i, '-', filename, '=>', len(barycenters)
	i += 1