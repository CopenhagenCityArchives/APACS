#! python3
# -*- coding: utf-8 -*-

Config = {
    "debug" : False,
    "cumulus" : {
        "url": "https://neaonline.dk",
        #"url": "https://cumulus",
        "port": 8443,
        "user": "CIP-erindringsbilleder",
        "password": "***REMOVED***",
        "catalog": "CIP-erindringsbilleder",
        "layout": "erindringskatalog"
    },
    "kbhbilleder_db" : {
        "host": "kbhbilleder-stats-cluster.cluster-cf2nstf005qs.eu-west-1.rds.amazonaws.com",
        "port": 3306,
        "user": "kbhbilleder-stat",
        "password": "Ggc4Zpjm",
        "database": "kbhbilleder-stats"
    },
    'polle_db' : {
        "host": "138.201.87.24",
        "port": 3306,
        "user": "politietsregiste",
        "password": "***REMOVED***",
        "database": "politietsregisterblade"
    },
    "apacs_db" : {
        "host": "148.251.122.164",
        "port": 3306,
        "user":"kbharkiv",
        "password":"***REMOVED***",
        "database": "kbharkiv"
    },
    "aws_sns" : {
        "access_key_id": "***REMOVED***",
        "secret_access_key": "***REMOVED***"
    },
    "solr": {
        #"url":"https://aws.kbhkilder.dk/solr/apacs_core"
        #"url":"http://ec2-34-240-1-32.eu-west-1.compute.amazonaws.com/solr/apacs_core"
        #"url":"http://ec2-54-194-89-54.eu-west-1.compute.amazonaws.com/solr/apacs_core"
        #"url": "http://solr:pass@solr:8983/solr/apacs_core"
        "url": "http://solr:8983/solr/apacs_core"
    }
}
