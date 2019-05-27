# APACS (Archival Presentation And Crowdsourcing System)
[![Build Status](https://travis-ci.org/CopenhagenCityArchives/APACS.svg?branch=task2)](https://travis-ci.org/CopenhagenCityArchives/APACS)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.

# Services
The system consists of several services:

* A PHP-FPM server with Phalcon installed. This server executes the PHP code and exposes a JSON-based REST API.
* An nginx server used in front of the PHP-FPM server.
* A MySQL database having all metadata and data for collections and indexed informations
* A SOLR database that exposes all indexed persons locally or through a proxy in the API service
* An indexer that feeds data to SOLR running Python

# docker-compose files
All services are designed to be started with docker-compose.

There are several groups of docker-compose-files:
* ``docker-compose-index.dev.yml`` and ``docker-compose-index.prod.yml``: These files are used for development and deployment of the Solr server as well as the indexation scripts
* ``docker-compose.webserver.dev.yml`` and ``docker-compose.webserver.prod.yml``: These files are used for development of the webserver and the database.
* ``docker-compose.complete.dev.yml``: A development infrastructure used when all services are needed.

Notice that the Solr service is included in both files, as the service is used by both the API and the indexer scripts.

The two *.prod* docker-compose files are used when deploying or running the code at external hosts. In these files the local code are copied to the docker images being used, and no drive mapping occurs.

# Config
All configuration are set using a .env file located in the root directory.

See .env_example for possible settings

# Development
## Branches
This repository consists at the moment of 3 main branches:
* ``master``: Used in production at kbhkilder.dk/api
* ``development``: Used for internal tests at kbhkilder.dk/1508/experimental/api
* ``task2``: Used for internal and external tests of new task and config structure at kbhkilder.dk/1508/public_beta/api
  
## Webserver, database and Solr
All PHP dependencies are installed with Composer, which is run during docker-compose up.
The services are declared in *docker-compose.dev.yml*

* ``
docker-compose -f docker-compose.dev.yml up -d
``
## Indexing script and Solr
The services are declared in *docker-compose-index.dev.yml*

* ``
docker-compose -f docker-compose-index.dev.yml up -d [indexer|solr]
`` 

# Deployment
## Indexing script and Solr
The services are declared in *docker-compose-index.prod.yml*.

Use the following docker-machine (running at AWS): ``apacs-persons``

Get machine env:
``docker-machine env apacs-persons``
``& "C:\Program Files\Docker\Docker\Resources\bin\docker-machine.exe" env apacs-persons | Invoke-Expression``

The index service is deployed to AWS using this command:
``docker-compose -f docker-compose-index.prod.yml up -d --force-recreate --build indexer``

## Update Solr schema
It is sometimes necessary to add new fields to the Solr service.

Instead of recreating the Solr container, it is enough to update the core schema.
Connect to docker machine and run this command:

* ``docker cp ./infrastructure/solr/solr_conf/apacs_core/conf/schema.xml solr:/opt/solr/server/solr/mycores/apacs_core/conf/schema.xml``

This will replace the schema file on the server.

Remember to reload the core in Solr admin.

## Webserver and database
The services (together with Solr) are declared in *docker-compose.prod.yml*.

The webserver is currently running on a shared host, and as so must be deployed using FTP.

To deploy using FTP and PHP run: ``docker-compose -f docker-compose-webserver.dev.yml exec phalcon php /code/app/deployment/deploy.php``

See deploy.php for details.

# Tests

## Unit tests

PHPUnit and phpunit-watcher are installed with Composer during docker-compose up.

Run the test in the docker container:
* ``docker exec -it phalcon /bin/bash``
* To run a single test run: ``/code/vendor/bin/phpunit --testdox``
* To watch for changes use phpunit-watcher: ``/code/vendor/bin/phpunit-watcher watch --testdox``

Run the test from outside the container using docker-compose:
* ``docker-compose -f docker-compose-webserver.dev.yml up -d --force-recreate``
* To run a single test run: ``docker-compose -f docker-compose-webserver.dev.yml exec phalcon /code/vendor/bin/phpunit --testdox``
* To watch for changes use phpunit-watcher: ``docker-compose -f docker-compose-webserver.dev.yml exec phalcon /code/vendor/bin/phpunit-watcher watch --testdox``


## Code coverage (propably unsupported currently)

Go to /apacs/tests

Run:
```
sudo phpunit --coverage-html ./coverage
```

Note that test coverage requires XDEBUG to be installed, and to be set up in not only in php5/apache2/php.ini but ALSO in /php5/cli/php.ini

## API endpoint tests (probably outdated)
Go to /apacs/tests_api
```
jasmine-node /tests
```