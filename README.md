# APACS (Archival Presentation And Crowdsourcing System)
[![Build Status](https://travis-ci.com/CopenhagenCityArchives/APACS.svg?branch=master)](https://travis-ci.com/CopenhagenCityArchives/APACS)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.

# Services
The system consists of several services:

* A PHP-FPM server with Phalcon installed. This server executes the PHP code and exposes a JSON-based REST API. The service is based on the Phalcon base image
* An nginx server used in front of the PHP-FPM server
* A MySQL database having all metadata and data for collections and indexed informations
* A SOLR database that exposes all indexed persons locally or through a proxy in the API service
* An indexer that feeds data to SOLR running Python

# docker-compose files
All services are designed to be started with docker-compose.

There are several docker-compose-files:
* ``docker-compose-apacs-dev.yml``: Used for local development and testing
* ``docker-compose-ci-apacs-tests.yml``: Used to run tests before deployment in Travis
* ``docker-compose-index.dev.yml``: Used development and deployment of the Solr server as well as the indexation scripts
* ``docker-compose-index.prod.yml``: Used when deploying the indexing services (Solr and indexer) in a remote docker-machine. In this file the local code is copied to the docker images being used, and no drive mapping occurs.

## Building the Phalcon-base image
When changes are necessary to the Phalcon base image, it must be built and pushed manually.
This is necessary when requirements to PHP extensions changes or upgrades to the Phalcon library is required.
* docker build -f ./infrastructure/phalcon-base/Dockerfile -t phalcon-base .
* docker tag phalcon-base 282251075226.dkr.ecr.eu-west-1.amazonaws.com/phalcon-base:latest
* docker push 282251075226.dkr.ecr.eu-west-1.amazonaws.com/phalcon-base:latest


# Config
All configuration are set using a .env file located in the root directory.

See .env_example for possible settings

# Development
## Branches
This repository consists at the moment of 3 main branches:
* ``master``: Used in production at https://api.kbharkiv.dk
* ``development``: Used for internal tests at https://api-dev-auth0.kbharkiv.dk
  
## API
All PHP dependencies are installed with Composer, which is run during docker-compose up.

* ``
docker-compose -f docker-compose-apacs-dev.yml up -d
``

## Indexing
The services are declared in *docker-compose-index.dev.yml*

* ``
docker-compose -f docker-compose-index.dev.yml up -d [indexer|solr]
`` 

# Deployment
## API
### Production
The services are deployed using Travis and AWS Elastic Beanstalk. See .travis.yml for details.

### Testing (at Elastic Beanstalk)
Copy the file Repositories\env-files\APACS\apacs-test-v1.cfg.yml to .elasticbeanstalk/saved_configs/ 

Deploy a test environment at Elastic Beanstalk using this command: 

* ``eb create apacs-test-environment-name --cname apacs-test --cfg apacs-dev-config-v2``

This will build a brand new test environment in Elastic Beanstalk.

Use the new environment: ``eb use apacs-test-environtment-name``

Changes to the application are deployed using ``eb deploy``

NOTE that this environment runs on the production database! Change the AWS Beanstalk environment variables to use another database.

## Indexing script and Solr
Indexing are done using the repository https://github.com/copenhagencityarchives/APACS-solr

# Tests

## Unit tests

PHPUnit and phpunit-watcher are installed with Composer during docker-compose up.

Run the test in the docker container:
* ``docker exec -it apacs /bin/bash``
* To run a single test run: ``/code/vendor/bin/phpunit --testdox``
* To watch for changes use phpunit-watcher: ``/code/vendor/bin/phpunit-watcher watch --testdox``

Run the test from outside the container using docker-compose:
* Start the services: ``docker-compose -f docker-compose-apacs-dev.yml up -d --force-recreate``
* Run all tests: ``docker-compose -f docker-compose-apacs-dev.yml exec apacs vendor/bin/phpunit`` (you can use --testdox to get unit test documentation)
* Watch for changes use phpunit-watcher: ``docker-compose -f docker-compose-apacs-dev.yml exec apacs vendor/bin/phpunit-watcher watch``


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

# Statistics

Indsættes hvis banana-int coren nulstilles, eksempelvis ved rebuild af Solr.
Husk at lave schemaet om, så dashboard-feltet er string og ikke multivalued (må ikke være et array), EFTER at dokumentet er indsat.
Indsættes her: https://aws.kbhkilder.dk/solr/#/banana-int
Statistikken kan ses her: http://kbhkilder.dk/stats/#/dashboard/solr/Brugerstats
Credentials: kbharkiv og samme kodeord som APACS-databasen.
