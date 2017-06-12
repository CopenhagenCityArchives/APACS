## APACS (Archival Presentation And Crowdsourcing System)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.

The system runs on Ubuntu using PHP and MySQL.

##Deploy to AWS EBS

Run the following command using eb cli:

```
eb deploy
```

Setup cli:

eb init

If not provided, add IAM credentials.


## Operating the server
### Running webserver (Apache) and database (MySQL)

Start services
```
docker-compose up -d
```

Stop services
```
docker-compose stop
```

Remove all services
```
docker-compose rm --all
```

Access bash in the webserver
```
docker exec -i -t webserver /bin/bash
```

### Running webserver (nginx) and remote database
```
docker build -t apacs_dev .
```

```
docker run -v d:/Udviklingsprojekter/KSA_backend/apacs:/var/www/html -p 8005:80 --name apacs_test apacs_dev
```

### Stats to solr
Stats from the system are feed to Solr using Solrs DataImportHandler.

Stats consists of data from image requests (the source is not included in this code), and system exceptions.

The datatimport is run using these cron commands:
```0,2 * * * * /usr/bin/wget http://localhost:8983/solr/apacs_stats/dataimport?command=full-import&wt=json&clean=false&entity=system_exceptions```

```0,10 * * * * /usr/bin/wget http://localhost:8983/solr/apacs_stats/dataimport?command=full-import&wt=json&clean=false&entity=image_requests```

## Dependencies
Install PHP dependencies by running:

php composer.phar install

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

Go to /vagrant/www/tests/

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
