# -*- coding: utf-8 -*-

import requests
import ssl
import json
import logging
import urllib
import re
import datetime
from base64 import b64encode

class CIP(object):
	"""A connection to the CIP server."""

	def __init__(self, host, port, user, password, location):
		self.host = host
		self.port = port
		self.user = user
		self.password = password
		self.location = location
		self.fields = None
		self.log = logging.getLogger("CIP")
		self.auth_headers = { '' }

	def get(self, url, query={}):
		url = "%s:%d/%s/%s" % (self.host, self.port, self.location, url)
		if query:
			url += "?%s" % urllib.parse.urlencode(query)
		r = requests.get(url, auth=(self.user, self.password), verify=False)
		if r.status_code == 200:
			return r.json()
		else:
			raise Exception((r.status_code, r.reason, r.text))

	def post(self, url, query={}, params={}, retries=5):
		url = "%s:%d/%s/%s" % (self.host, self.port, self.location, url)
		if query:
			url += "?%s" % urllib.parse.urlencode(query)
		r = requests.post(url, data=params, auth=(self.user, self.password), verify=False)
		if r.status_code == 200:
			try:
				json = r.json()
				return json
			except Exception as e:
				if retries > 0:
					return self.post(url, query=query, params=params, retries=retries-1)
				print("URL: %s" % url)
				print("POST data: %s"% params)
				print("Data length: %d" % len(r.text))
				print("Data: %s" % r.text)
				print("Error: %s" % repr(e))
				raise e
		else:
			raise Exception((r.status_code, r.reason, r.text))

	def load_layout(self, catalog, view=None):
		url = "metadata/getlayout/%s" % catalog
		if view != None:
			url = "%s/%s" % (url, view)
		self.fields = {}
		for field in self.get(url)['fields']:
			self.fields[field['key']] = field

	def search(self, catalog, view=None, querystring=None, maxreturned=None, startindex=None):
		url = "metadata/search/%s" % catalog
		if view != None:
			url = "%s/%s" % (url, view)
		params = {}
		if querystring is not None:
			params['querystring'] = querystring
		if maxreturned is not None:
			params['maxreturned'] = maxreturned
		if startindex is not None:
			params['startindex'] = startindex
		return self.post(url, params=params)

	def process_fielddata(self, fieldkey, value):
		name = fieldkey
		if fieldkey in self.fields:
			field = self.fields[fieldkey]
			name = field['name']
			ftype = field['type']
			if ftype == "DateTime":
				value = datetime.datetime.fromtimestamp(float(re.match(r"/Date\((\d+)\)/", value).group(1)) / 1000.0)
			elif ftype == "Enum":
				value = value['displaystring']
			elif ftype == "Date":
				if value["year"] is not None and value["month"] is None or value["day"] is None:
					value = value["year"]
				else:
					value = datetime.datetime(value["year"], value["month"], value["day"])
			elif ftype == "DataSize":
				value = value['value'] # bytes?
		return (name, value)

	def process_item(self, item):
		return dict([self.process_fielddata(fieldkey, value) for (fieldkey, value) in item.items() if value is not None and (fieldkey in self.fields and self.fields[fieldkey]['type'] != 'Picture')])

	def searchall(self, catalog, view=None, querystring=None, startindex=0, maxchunks=None, chunk=128):
		currentindex = startindex
		while True:
			if maxchunks is not None:
				if maxchunks == 0:
					break
				maxchunks = maxchunks - 1
			result = self.search(catalog, view=view, querystring=querystring, startindex=currentindex, maxreturned=chunk)
			for item in result['items']:
				if self.fields is not None:
					yield self.process_item(item)
				else:
					yield item
			if len(result['items']) < chunk:
				break
			currentindex = currentindex + len(result['items'])
