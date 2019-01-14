#! python3
# -*- coding: utf-8 -*-
from config import Config
import pysolr
import sys
import json
from datetime import datetime
from functools import reduce
import xml.etree.ElementTree as ET
from cip import CIP
import zlib, base64
import urllib3
from sns import SNS_Notifier
import urllib.request
import ftplib
import os
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)


def writeflush(str):
	sys.stdout.write(str + "\n")
	sys.stdout.flush()

# Create a function called "chunks" with two arguments, l and n:
def chunks(l, n):
    # For item i in a range that is a length of l,
    for i in range(0, len(l), n):
        # Create an index range for l of n items:
        yield l[i:i+n]

if __name__ == "__main__":
	try:
		writeflush("Connecting to Solr... ")
		solr = pysolr.Solr(Config['solr']['url'], timeout=300)
		writeflush("OK.\n")
	except Exception as e:
		writeflush("Failed.\nError: %s\n" % repr(e))
		SNS_Notifier.error(repr(e))
		sys.exit(1)

	# now
	now = str(datetime.today().strftime('%Y-%m-%d'))


	#r for [item in results if item.lastupdated > last_sitemap_update]:
	#create collections of 50.000 docs and add them to xml files
	docNum = 1
	chunksize = 50000
	start=0
	totalDocs = 0
	totalHits = solr.search('collection_id:(1 17 18)',**{"fl":'id'}).hits
	format = '%m/%d/%Y'
	while start < totalHits:
		#Loading documents
		results = solr.search('collection_id:(1 17 18)',**{"fl":'id,last_update',"start":start, "rows":chunksize})
		start = start + chunksize
		print("found %d documents" % len(results))
		totalDocs = totalDocs + len(results)
		root = ET.Element("urlset", xmlns="http://www.sitemaps.org/schemas/sitemap/0.9")
		for d in results.docs:
	
			# <?xml version="1.0" encoding="UTF-8"?>
			# <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
			# <url>
			# 	<loc>http://www.example.com/</loc>
			# 	<lastmod>2005-01-01</lastmod>
			# 	<changefreq>monthly</changefreq>
			# 	<priority>0.8</priority>
			# </url>
			# </urlset> 
			
			url = ET.SubElement(root, "url")
			ET.SubElement(url, "loc").text = "https://kbharkiv.dk/permalink/post/" + d['id']
			ET.SubElement(url, "lastmod").text = d['last_update'][0:10]
			ET.SubElement(url, "changefreq").text = 'monthly'
			ET.SubElement(url, "priority").text = '0.8'
		
		

		#Save doc as xml
		writeflush("saving sitemap_persons_{0}.xml".format(docNum))
		tree = ET.ElementTree(root)
		tree.write("sitemap_persons_{0}.xml".format(docNum))

		docNum = docNum+1

	writeflush("found and added %d documents in %d site map files" % (totalDocs, docNum))

	# creating meta sitemap
	# <?xml version="1.0" encoding="UTF-8"?>
	# <sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	# <sitemap>
	# 	<loc>http://www.example.com/sitemap1.xml.gz</loc>
	# 	<lastmod>2004-10-01T18:23:17+00:00</lastmod>
	# </sitemap>
	# <sitemap>
	# 	<loc>http://www.example.com/sitemap2.xml.gz</loc>
	# 	<lastmod>2005-01-01</lastmod>
	# </sitemap>
	# </sitemapindex>

	root = ET.Element("sitemapindex", xmlns="http://www.sitemaps.org/schemas/sitemap/0.9")
	i = 1
	while i < docNum:
		sm = ET.SubElement(root, "sitemap")
		ET.SubElement(sm, "loc").text = "https://kbharkiv.dk/sitemap_persons_{0}.xml".format(i)
		ET.SubElement(sm, "lastmod").text = now
		i = i+1

	#Save doc as xml
	writeflush("saving meta sitemap")
	tree = ET.ElementTree(root)
	tree.write("metasitemap_persons.xml")

	writeflush("connecting to FTP server")
	session = ftplib.FTP(Config['ftp_kbharkiv']['url'],Config['ftp_kbharkiv']['user'],Config['ftp_kbharkiv']['password'])
	session.cwd('/public_html')

	writeflush("uploading meta sitemap")
	upload_file = "metasitemap_persons.xml"
	file = open(upload_file,'rb')           # file to send
	session.storbinary('STOR ' + upload_file, file)   # send the file
	file.close()                         	# close file and FTP
	os.remove(upload_file)	

	#upload and remove docs
	i=1
	while i < docNum:
		upload_file = "sitemap_persons_{0}.xml".format(i)
		writeflush("uploading %s" % upload_file)
		file = open(upload_file,'rb')           # file to send
		session.storbinary('STOR ' + upload_file, file)   # send the file
		file.close()                         	# close file and FTP
		os.remove(upload_file)					# remove local file
		i = i+1


	session.quit()

	#Notify Google of new sitemap
	writeflush("notifying Google")
	contents = urllib.request.urlopen("http://google.com/ping?sitemap=https://kbharkiv.dk/posts_meta_sitemap.xml").read()
	print(contents)
	# on error:
	#SNS_Notifier.error(repr(e))
	#sys.exit(1)