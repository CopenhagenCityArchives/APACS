#! python3

import schedule
import time
import os
from sns import SNS_Notifier

print("Job scheduling starting...")

def job():
    print("Still alive")

def erindringer():
    print 'erindringer'
    os.system("python erindringer.py")

def begravelser():
    print 'begravelser'
    os.system("python begravelsesprotokoller.py")

def polle():
    print 'polle'
    os.system("python polle.py")

schedule.every().day.at("01:00").do(erindringer)
schedule.every().day.at("01:15").do(begravelser)
schedule.every().day.at("02:45").do(polle)

schedule.every(60).minutes.do(job)

print("Job scheduling done...")

try:
    while True:
        schedule.run_pending()
        time.sleep(1)
except Exception as e:
    SNS_Notifier.error(repr(e))
