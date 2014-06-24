puppet-beanstalkd
=================
[![Build Status](https://travis-ci.org/keen99/puppet-beanstalkd.png?branch=master)](https://travis-ci.org/keen99/puppet-beanstalkd)

puppet module for managing beanstalkd, a simple and fast work queue - https://github.com/kr/beanstalkd


## Supported OSes

redhat/centos and debian/ubuntu currently.  Please PR updates for others!  

Requires packages (rpm, etc) with traditional init scripts supported by service{} for your OS.


## Basic Usage

Drop the beanstalkd directory into your modules tree and realize the define:

	beanstalkd::config{"my beanstalk install": }

## Optional parameters
	
	listenaddress => '0.0.0.0',
	listenport => '13000',
	maxjobsize => '65535',
	maxconnections => '1024',
	binlogdir => '/var/lib/beanstalkd/binlog',	# set empty ( '' ) to disable binlog
	binlogfsync => undef,							
	binlogsize => '10485760',
	ensure => 'running',		# running, stopped, absent
	packageversion => 'latest',	# latest, present, or specific version
	packagename => undef,		# override package name						
	servicename => undef		# override service name





## Tests

To run unit tests, cd into beanstalkd and execute "run-tests.sh"

Requires ruby and bundler, everything else should get installed by the test.

```
$$ puppet-beanstalkd/beanstalkd# ./run-tests.sh 
Using rake (10.0.4) 
Using diff-lcs (1.2.4) 
Using facter (1.7.0) 
Using json_pure (1.7.7) 
Using hiera (1.2.1) 
Using metaclass (0.0.1) 
Using mocha (0.13.3) 
Using puppet (3.1.1) 
Using rspec-core (2.13.1) 
Using rspec-expectations (2.13.0) 
Using rspec-mocks (2.13.1) 
Using rspec (2.13.0) 
Using rspec-puppet (0.1.6) 
Using puppetlabs_spec_helper (0.4.1) 
Using bundler (1.1.4) 
Your bundle is complete! Use `bundle show [gemname]` to see where a bundled gem is installed.
/usr/bin/ruby1.9.1 -S rspec spec/defines/config_spec.rb
...................

Finished in 0.84772 seconds
19 examples, 0 failures
```
