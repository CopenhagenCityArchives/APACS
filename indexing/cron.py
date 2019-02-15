#! python3

import schedule
import time
import os
from sns import SNS_Notifier

print("Job scheduling starting...")

def job():
    print("Still alive")

def erindringer():
    print('erindringer')
    os.system("python erindringer.py")

def begravelser():
    print('begravelser')
    os.system("python begravelsesprotokoller.py")

def polle():
    print('polle')
    os.system("python polle.py")

def efterretninger():
    print('efterretninger')
    os.system("python efterretninger.py")

#Only run scheduling if in PROD mode
if os.getenv('PYTHON_ENV', 'DEV') != 'DEV':
    schedule.every().day.at("23:00").do(erindringer)
    schedule.every().day.at("23:10").do(efterretninger)
    schedule.every().day.at("23:15").do(begravelser)
    schedule.every().day.at("00:30").do(polle)

    schedule.every(60).minutes.do(job)

    print("Job scheduling done...")
else:
    print("Running in DEV mode. Indexing is NOT scheduled.")

try:
    while True:
        schedule.run_pending()
        time.sleep(1)
except Exception as e:
    SNS_Notifier.error(repr(e))
