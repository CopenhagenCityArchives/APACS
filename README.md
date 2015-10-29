##APACS (Archival Presentation And Crowsourcing System)
Copenhagen City Archives' configurable backend system used to present and crowdsource digitized collections.

The system runs on Ubuntu using PHP and MySQL.

##
Dependencies:

* Phalcon: A C-extension library for PHP (find it [here](https://phalconphp.com/en/))

##Unit testing

###Unit tests

The test is configured using phpunit.xml.

Go to /vagrant/www/tests

Run:

```
phpunit -c phpunit.xml
```

###Code coverage

Go to /vagrant/www/tests/

Run:


```
sudo phpunit --coverage-html ./coverage
```

Note that test coverage requires XDEBUG to be installed, and to be set up in not only in php5/apache2/php.ini but ALSO in /php5/cli/php.ini
