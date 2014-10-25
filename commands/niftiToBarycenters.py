import os
import sys
import glob
import numpy as np
import nifti
import pdb


if len(sys.argv) < 2:
	print 'Missing argument'
	sys.exit(1)
inputFile = sys.argv[1]
outputFile = sys.argv[1] + '.txt'


nim = nifti.NiftiImage(inputFile)
tat = nim.data

#pdb.set_trace()
tata = np.zeros_like(tat)
i = j = k = 0
t = tat[tat > 0]
th = 3
l = 0
C = []
cm = 0


#h = nim.header
#print h

#print name
#pdb.set_trace()

while( l < 90 - th + 1):


	m=0
	while( m < 100- th + 1):
	
		n = 0
		while( n < 90 - th + 1):
			ct = 0
			x = y = z = 0
			M=tat[l:l+th,m:m+th,n:n+th]
			#print M
		
			for a in range (0,th):
				for b in range (0,th):
					for c in range (0,th):
					
						if M[a][b][c] != 0:
							#print 'yes', i," ",j," ",k
						
							ct = ct + 1
							x = x + a+l
							y = y + b+m
							z = z + c+n
						
			n = n + th
			if ((x != 0 or y != 0 or z != 0) and ct > 10):
				cor = []
				tata[int(x/ct)][int(y/ct)][int(z/ct)] = 1
				cor.append(int(x/ct))
				cor.append(int(y/ct))
				cor.append(int(z/ct))					
				C.append(np.array(cor))
				#print ct
				
		m=m+th
	l=l+th
x = y = z = 0
ct = 0
M=tat[90:91,100:109,90:91]
for i in range(0, 1):
	for j in range(0, 8):
		for k in range(0,1):
			if M[i][j][k] != 0:
				#print 'yes', i," ",j," ",k
			
				ct = ct + 1
				x = x + 90
				y = y + 100
				z = z + 90

if ((x != 0 or y != 0 or z != 0) and ct > 10):
	cor = []
	tata[int(x/ct)][int(y/ct)][int(z/ct)] = 1
	cor.append(int(x/ct))
	cor.append(int(y/ct))
	cor.append(int(z/ct))					
	C.append(np.array(cor))
	#print ct

#print len(C)
rot = np.array([[-2.,0.,0.],[0.,2.,0.],[0.,0.,2.]])
trans = np.array([90.,-126.,-72.])
mni = []
for i in range (0, len(C)):
	rotCoord = []
	M = []
	M.append(C[i][2])
	M.append(C[i][1])
	M.append(C[i][0])
	#print "M ", M
	MC = M * rot
	for k in range(0, len(MC)):
		rotCoord.append(np.sum(MC[k,0:3]))
#			print rotCoord
	newCoord = rotCoord + trans
	mni.append(np.array(newCoord))
#print mni.shape
np.savetxt(outputFile, mni, fmt='%d', delimiter=" ")

#nimi =nifti.NiftiImage(tata,nim.header)
#ta = tata[tata > 0]
#print(len(ta))
#print ta
#nimi.save("speech_mask_barycenter.dat_ri_z_fdr.nii.gz") 

