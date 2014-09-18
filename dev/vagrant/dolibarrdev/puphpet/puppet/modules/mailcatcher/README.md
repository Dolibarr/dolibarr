# Mailcatcher

[![Build Status](https://travis-ci.org/actionjack/puppet-mailcatcher.png?branch=master)](https://travis-ci.org/actionjack/puppet-mailcatcher)

This puppet module is used to install and configure the mailcatcher application.
MailCatcher runs a super simple SMTP server which catches any message sent to it to display in a web interface.
http://mailcatcher.me/

* * *

## Configuration


## Dependencies

Current dependencies are:

 * 'puppetlabs/stdlib', '>= 2.1.0'

## Usage



```ruby
class {'mailcatcher': }
```

## Documentation

This module is written in puppetdoc compliant format so details on configuration and usage can be found by executing:

```bash
$ puppet doc manifest/init.pp
```
