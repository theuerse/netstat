import glob, os
os.chdir("/home/theuers/history")

for index in xrange(0,20,1):
    with open('PI' + str(index) + '.json', 'w') as outfile:
        outfile.write('{"history": [')
        for fname in sorted(glob.glob("PI" + str(index) + ".json.*")):
            with open(fname) as infile:
                outfile.write(infile.read()+",")
        outfile.write("]}")

# manually remove spurious ',' before the closing "]}" !
