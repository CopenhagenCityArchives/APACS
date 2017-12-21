## APACS (Archival Presentation And Crowdsourcing System)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.

The system consists of four separate services:
* A webserver running Apache and PHP (using the Phalcon framework) exposing a RESTful API
* A MySQL database having all metadata and data for collections and indexed informations
* A SOLR database that exposes all indexed persons locally or through a proxy in the API service
* An indexer that feeds data to SOLR running Python

##Starting services using docker-composer
All services can be started using docker-compose.
There are two docker-compose entities:
The docker-compose.yml and docker-compose.override.yml is used to start and run the webserver, the MySQL database and the Solr server.

The docker-compose-index.yml and docker-compose-index.override.yml are used to start and run the Solr server and the indexer scripts.

Notice that the Solr service is included in both files, as the service is used by both the API and the indexer scripts.

The two .override.yml files are used when in development mode. When using docker-compose with these files the code directories are mapped to the instances so changes made in the code appears immediately.

The two other docker-compose files are used when deploying or running the code at external hosts. In these files the local code are copied to the docker images being used, and no drive mapping occurs.

Notice that the docker compose files can be used to run individual services. If there is a need to test the indexer service, it can be initiated using ``docker-compose -f docker-compose-index.override up -d indexer`` command.

It is also possible to run a local debugable instance of a PHP server.

### Running webserver (Apache) and database (MySQL)

Start services:
``
docker-compose up -d
``

### Running webserver (nginx) and remote database
Build Docker image:
``
docker build -t apacs_dev .
``
And start it making the webserver accessible at port 8005:
``
docker run -v d:/Udviklingsprojekter/KSA_backend/apacs:/var/www/html -p 8005:80 --name apacs_test apacs_dev
``

## PHP dependencies
Are installed when using the docker-compose.yml file. The docker-compose.override.yml does not install PHP dependencies, so here you have to use this command on the webserver service:

``php composer.phar install``

## Tests

### Unit tests

The test is configured using phpunit.xml.

Go to /apacs/tests

Run:

```
phpunit -c phpunit.xml
```

### API endpoint tests
Go to /apacs/tests_api
```
jasmine-node /tests
```

### Code coverage

Go to /apacs/tests

Run:
```
sudo phpunit --coverage-html ./coverage
```

Note that test coverage requires XDEBUG to be installed, and to be set up in not only in php5/apache2/php.ini but ALSO in /php5/cli/php.ini

## Dependencies:

* Phalcon: A C-extension library for PHP (find it [here](https://phalconphp.com/en/))
* Composer (for installing dependencies)
* PHP Unit (for tests)
* XDEBUG (only required for PHPUnit code coverage)
