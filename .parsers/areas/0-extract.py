from collections import defaultdict
import nibabel
import nifti

# open the file
img = nibabel.load('talairach.nii')
header = img.get_header()
data = img.get_data()

# extract labels
nim = nifti.NiftiImage('talairach.nii')
labels = nim.extensions[0].split('\n')

# extract coordinates informations
X, Y, Z = img.shape
x0, y0, z0 = header.get('qoffset_x'), header.get('qoffset_y'), header.get('qoffset_z')

# extract data
values = defaultdict(list)
for x in range(X):
	for y in range(Y):
		for z in range(Z):
			value = data[x, y, z]
			if value:
				values[value].append((x+x0, y+y0, z+z0))
	print '%d / %d' % (x+1, X)

# write to files
i = 1
for value, coordinates in values.items():
	label = labels[value]
	f = open('labels/%d.txt' % (value,), 'w')
	f.write('%s\n' % (label))
	for x, y, z in coordinates:
		f.write('%d;%d;%d\n' % (x, y, z))
	f.close()
	print '%d / %d' % (i+1, len(values))
	i += 1


