# -*- coding: utf-8 -*-

import boto3
from config import Config
import traceback
import sys

class SNS_Notifier(object):

	@staticmethod
	def error(details = ""):
		client = boto3.client(
			'sns',
			region_name='eu-west-1',
			aws_access_key_id=Config['aws_sns']['access_key_id'],
			aws_secret_access_key=Config['aws_sns']['secret_access_key']
		)

		type_, value_, traceback_ = sys.exc_info()
		stack = traceback.format_exception(type_, value_, traceback_)
		if Config['debug'] != True:
			client.publish(
				Message='Here are the details: %s \n\n %s' % (details, "".join(stack)),
				Subject="APACS to SOLR index error",
			    TopicArn='arn:aws:sns:eu-west-1:282251075226:apacs_index_status_error',
			)
