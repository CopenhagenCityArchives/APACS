## APACS (Archival Presentation And Crowsourcing System)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.

The system runs on Ubuntu using PHP and MySQL.

## Operating the server
### Docker compose

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

## Unit testing

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
