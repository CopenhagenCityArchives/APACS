###APACS (Archival presentation and crowsourcing system)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.


##Unit testing
Unit testing is performed with the phpunit command while being in the /tests directory. The test is configured using phpunit.xml.

Go to /vagrant/www/tests

Run:

```
phpunit -c phpunit.xml
```

Unit test code coverage report is generated with the following command:

Go to /vagrant/www/tests/

Run:


```
sudo phpunit --coverage-html ./coverage
```

Note that test coverage requires XDEBUG to be installed, and to be set up in not only in php5/apache2/php.ini but ALSO in /php5/cli/php.ini
