# Staging module for Puppet

Manages staging directory, along with download/extraction of compressed files.

[![Build Status](https://secure.travis-ci.org/nanliu/puppet-staging.png?branch=master)](http://travis-ci.org/nanliu/puppet-staging)

WARNING: Version 0.2.0 no longer uses hiera functions. The same behavior should be available in Puppet 3.0.

## Usage

Specify a different default staging path (must be declared before using resource):

    class { 'staging':
      path  => '/var/staging',
      owner => 'puppet',
      group => 'puppet',
    }

Staging files from various sources:

    staging::file { 'sample':
      source => 'puppet://modules/staging/sample',
    }

    staging::file { 'apache-tomcat-6.0.35':
      source => 'http://apache.cs.utah.edu/tomcat/tomcat-6/v6.0.35/bin/apache-tomcat-6.0.35.tar.gz',
    }


Staging and extracting files:

    staging::file { 'sample.tar.gz':
      source => 'puppet:///modules/staging/sample.tar.gz'
    }

    staging::extract { 'sample.tar.gz':
      target  => '/tmp/staging',
      creates => '/tmp/staging/sample',
      require => Staging::File['sample.tar.gz'],
    }

Staging files currently support the following source:

* http(s)://
* puppet://
* ftp://
* local (though this doesn't serve any real purpose.)

## Contributor

* Adrien Thebo
* gizero
* Harald Skoglund
* Hunter Haugen
* Justin Clayton
* Owen Jacobson
* Reid Vandewiele
