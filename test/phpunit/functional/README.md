Functional tests for Dolibarr
=============================
A.k.a. end-to-end or acceptance tests.

Prerequisites
-------------

### Web server

Any web server compatible with Dolibarr will do.

For the full test, it should be configured for serving Dolibarr's htdocs directory at `https://dev.dolibarr.org` with SSL/TLS enabled.

If you want to test at another address and/or without SSL/TLS, you will have to alter the test configuration.

### Database server

#### MySQL or MariaDB.

Running on localhost with the root user without password.

The database used for the test is `dolibarr_test`.

**WARNING:**  
This database will be dropped before and after the test.  
Make sure you don't hold any valuable information in it!

A user called `dolibarr` with a password `dolibarr` will be created as part of the test.

You can alter the test configuration to use another host, users and/or database.

#### Other

Unsupported at the moment.  
Patches welcome.

### Browser automation

#### Server

[Selenium](http://www.seleniumhq.org/)

#### Driver

##### Firefox

Unsupported at the moment.

I can't get the new [marionette](https://developer.mozilla.org/en-US/docs/Mozilla/QA/Marionette/WebDriver) [webdriver](https://github.com/mozilla/geckodriver/releases) to work on my workstation.
Patches welcome.

##### Chrome
[Google Chrome](https://www.google.com/chrome)
[ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver)

### Test runner
We leverage PHPUnit's selenium integration to run the tests.

You can install it using composer.
```
composer --dev require phpunit/phpunit-selenium
```

Configuration
-------------

There is only one test at the moment.  
Edit the test file — the configuration values are declared at the top of the class.

Usage
-----

Make sure your servers (web, database and browser automation) are started.

Then from Dolibarr's root directory, run:

```htdocs/includes/bin/phpunit test/phpunit/functional```
