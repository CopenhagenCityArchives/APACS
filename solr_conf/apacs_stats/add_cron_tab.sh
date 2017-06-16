
#write out current crontab
crontab -l > mycron

#echo new cron into cron file
echo "00 02 * * * * wget htt://localhost:8989/solr/apacs_stats/dataimport?command=full-import&wt=json&clean=false&entity=system_exceptions" >> mycron

echo "00 10 * * * * wget htt://localhost:8989/solr/apacs_stats/dataimport?command=full-import&wt=json&clean=false&entity=image_requests" >> mycron

#install new cron file
crontab mycron
rm mycron