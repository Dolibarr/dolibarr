# mongodb puppet module

[![Build Status](https://travis-ci.org/puppetlabs/puppetlabs-mongodb.png?branch=master)](https://travis-ci.org/puppetlabs/puppetlabs-mongodb)

####Table of Contents

1. [Overview] (#overview)
2. [Module Description - What does the module do?](#module-description)
3. [Setup - The basics of getting started with mongodb](#setup)
4. [Usage - Configuration options and additional functionality](#usage)
5. [Reference - An under-the-hood peek at what the module is doing and how](#reference)
6. [Limitations - OS compatibility, etc.] (#limitations)
7. [Development - Guide for contributing to the module] (#development)

## Overview

Installs MongoDB on RHEL/Ubuntu/Debian from OS repo, or alternatively from
10gen repository [installation documentation](http://www.mongodb.org/display/DOCS/Ubuntu+and+Debian+packages).

### Deprecation Warning ###

This release is a major refactoring of the module which means that the API may
have changed in backwards incompatible ways. If your project depends on the old API,
please pin your dependencies to 0.3 version to ensure your environments don't break.

The current module design is undergoing review for potential 1.0 release. We welcome
any feedback with regard to the APIs and patterns used in this release.

##Module Description

The MongoDB module manages mongod server installation and configuration of the
mongod daemon. For the time being it supports only a single MongoDB server
instance, without sharding functionality.

For the 0.5 release, the MongoDB module now supports database and user types.

For the 0.6 release, the MongoDB module now supports basic replicaset features
(initiating a replicaset and adding members, but without specific options).

## Setup

###What MongoDB affects

* MongoDB package.
* MongoDB configuration files.
* MongoDB service.
* MongoDB client.
* 10gen/mongodb apt/yum repository.

###Beginning with MongoDB

If you just want a server installation with the default options you can run
`include '::mongodb::server'`. If you need to customize configuration
options you need to do the following:

```puppet
class {'::mongodb::server':
  port    => 27018,
  verbose => true,
}
```

For Red Hat family systems, the client can be installed in a similar fashion:

```
puppet class {'::mongodb::client':}
```

Note that for Debian/Ubuntu family systems the client is installed with the 
server. Using the client class will by default install the server.

Although most distros come with a prepacked MongoDB server we recommend to
use the 10gen/MongoDB software repository, because most of the current OS
packages are outdated and not appropriate for a production environment.
To install MongoDB from 10gen repository:

```puppet
class {'::mongodb::globals':
  manage_package_repo => true,
}->
class {'::mongodb::server': }->
class {'::mongodb::client': }
```

## Usage

Most of the interaction for the server is done via `mongodb::server`. For
more options please have a look at [mongodb::server](#class-mongodbserver).
Also in this version we introduced `mongodb::globals`, which is meant more
for future implementation, where you can configure the main settings for
this module in a global way, to be used by other classes and defined resources.
On its own it does nothing.

### Create MongoDB database

To install MongoDB server, create database "testdb" and user "user1" with password "pass1".

```puppet
class {'::mongodb::server':
  auth => true,
}

mongodb::db { 'testdb':
  user          => 'user1',
  password_hash => 'a15fbfca5e3a758be80ceaf42458bcd8',
}
```
Parameter 'password_hash' is hex encoded md5 hash of "user1:mongo:pass1".
Unsafe plain text password could be used with 'password' parameter instead of 'password_hash'.

## Reference

### Classes

####Public classes
* `mongodb::server`: Installs and configure MongoDB
* `mongodb::client`: Installs the MongoDB client shell (for Red Hat family systems)
* `mongodb::globals`: Configure main settings in a global way

####Private classes
* `mongodb::repo`: Manage 10gen/MongoDB software repository
* `mongodb::repo::apt`: Manage Debian/Ubuntu apt 10gen/MongoDB repository
* `mongodb::repo::yum`: Manage Redhat/CentOS apt 10gen/MongoDB repository
* `mongodb::server::config`: Configures MongoDB configuration files
* `mongodb::server::install`: Install MongoDB software packages
* `mongodb::server::service`: Manages service
* `mongodb::client::install`: Installs the MongoDB client software package

####Class: mongodb::globals
*Note:* most server specific defaults should be overridden in the `mongodb::server`
class. This class should only be used if you are using a non-standard OS or
if you are changing elements such as `version` or `manage_package_repo` that
can only be changed here.

This class allows you to configure the main settings for this module in a
global way, to be used by the other classes and defined resources. On its
own it does nothing.

#####`server_package_name`
This setting can be used to override the default MongoDB server package
name. If not specified, the module will use whatever package name is the
default for your OS distro.

#####`service_name`
This setting can be used to override the default MongoDB service name. If not
specified, the module will use whatever service name is the default for your OS distro.

#####`service_provider`
This setting can be used to override the default MongoDB service provider. If
not specified, the module will use whatever service provider is the default for
your OS distro.

#####`service_status`
This setting can be used to override the default status check command for
your MongoDB service. If not specified, the module will use whatever service
name is the default for your OS distro.

#####`user`
This setting can be used to override the default MongoDB user and owner of the
service and related files in the file system. If not specified, the module will
use the default for your OS distro.

#####`group`
This setting can be used to override the default MongoDB user group to be used
for related files in the file system. If not specified, the module will use
the default for your OS distro.

#####`bind_ip`
This setting can be used to configure MonogDB process to bind to and listen
for connections from applications on this address. If not specified, the
module will use the default for your OS distro.
*Note:* This value should be passed as an array.

#####`version`
The version of MonogDB to install/manage. This is a simple way of providing
a specific version such as '2.2' or '2.4' for example. If not specified,
the module will use the default for your OS distro.

####Class: mongodb::server

Most of the parameters manipulate the mongod.conf file.

For more details about configuration parameters consult the
[MongoDB Configuration File Options](http://docs.mongodb.org/manual/reference/configuration-options/).

#####`ensure`
Used to ensure that the package is installed and the service is running, or that the package is absent/purged and the service is stopped. Valid values are true/false/present/absent/purged.

#####`config`
Path of the config file. If not specified, the module will use the default
for your OS distro.

#####`dbpath`
Set this value to designate a directory for the mongod instance to store
it's data. If not specified, the module will use the default for your OS distro.

#####`pidfilepath`
Specify a file location to hold the PID or process ID of the mongod process.
If not specified, the module will use the default for your OS distro.

#####`logpath`
Specify the path to a file name for the log file that will hold all diagnostic
logging information. Unless specified, mongod will output all log information
to the standard output.

#####`bind_ip`
Set this option to configure the mongod or mongos process to bind to and listen
for connections from applications on this address. If not specified, the module
will use the default for your OS distro. Example: bind_ip=['127.0.0.1', '192.168.0.3']
*Note*: bind_ip accepts an array as a value.

#####`logappend`
Set to true to add new entries to the end of the logfile rather than overwriting
the content of the log when the process restarts. Default: True

#####`fork`
Set to true to fork server process at launch time. The default setting depends on
the operating system.

#####`port`
Specifies a TCP port for the server instance to listen for client connections.
Default: 27017

#####`journal`
Set to true to enable operation journaling to ensure write durability and
data consistency. Default: on 64-bit systems true and on 32-bit systems false

#####`nojournal`
Set nojournal = true to disable durability journaling. By default, mongod
enables journaling in 64-bit versions after v2.0.
Default: on 64-bit systems  false and on 32-bit systems true

*Note*: You must use journal to enable journaling on 32-bit systems.

#####`smallfiles`
Set to true to modify MongoDB to use a smaller default data file size.
Specifically, smallfiles reduces the initial size for data files and
limits them to 512 megabytes.  Default: false

#####`cpu`
Set to true to force mongod to report every four seconds CPU utilization
and the amount of time that the processor waits for I/O operations to
complete (i.e. I/O wait.) Default: false

#####`auth`
Set to true to enable database authentication for users connecting from
remote hosts. If no users exist, the localhost interface will continue
to have access to the database until you create the first user.
Default: false

#####`noauth`
Disable authentication. Currently the default. Exists for future compatibility
 and clarity.

#####`verbose`
Increases the amount of internal reporting returned on standard output or in
the log file generated by `logpath`. Default: false

#####`verbositylevel`
MongoDB has the following levels of verbosity: v, vv, vvv, vvvv and vvvvv.
Default: None

#####`objcheck`
Forces the mongod to validate all requests from clients upon receipt to ensure
that clients never insert invalid documents into the database.
Default: on v2.4 default to true and on earlier version to false

#####`quota`
Set to true to enable a maximum limit for the number of data files each database
can have. The default quota is 8 data files, when quota is true. Default: false

#####`quotafiles`
Modify limit on the number of data files per database. This option requires the
`quota` setting. Default: 8

#####`diaglog`
Creates a very verbose diagnostic log for troubleshooting and recording various
errors.  Valid values: 0, 1, 2, 3 and 7.
For more information please refer to [MongoDB Configuration File Options](http://docs.mongodb.org/manual/reference/configuration-options/).

#####`directoryperdb`
Set to true to modify the storage pattern of the data directory to store each
database’s files in a distinct folder. Default: false

#####`profile`
Modify this value to changes the level of database profiling, which inserts
information about operation performance into output of mongod or the
log file if specified by `logpath`.

#####`maxconns`
Specifies a value to set the maximum number of simultaneous connections
that MongoDB will accept. Default: depends on system (i.e. ulimit and file descriptor)
limits. Unless set, MongoDB will not limit its own connections.

#####`oplog_size`
Specifies a maximum size in megabytes for the replication operation log
(e.g. oplog.) mongod creates an oplog based on the maximum amount of space
available. For 64-bit systems, the oplog is typically 5% of available disk space.

#####`nohints`
Ignore query hints. Default: None

#####`nohttpinterface`
Set to true to disable the HTTP interface. This command will override the rest
and disable the HTTP interface if you specify both. Default: false

#####`noscripting`
Set noscripting = true to disable the scripting engine. Default: false

#####`notablescan`
Set notablescan = true to forbid operations that require a table scan. Default: false

#####`noprealloc`
Set noprealloc = true to disable the preallocation of data files. This will shorten
the start up time in some cases, but can cause significant performance penalties
during normal operations. Default: false

#####`nssize`
Use this setting to control the default size for all newly created namespace
files (i.e .ns). Default: 16

#####`mms_token`
MMS token for mms monitoring. Default: None

#####`mms_name`
MMS identifier for mms monitoring. Default: None

#####`mms_interval`
MMS interval for mms monitoring. Default: None

#####`replset`
Use this setting to configure replication with replica sets. Specify a replica
set name as an argument to this set. All hosts must have the same set name.

#####`rest`
Set to true to enable a simple REST interface. Default: false

#####`slowms`
Sets the threshold for mongod to consider a query “slow” for the database profiler.
Default: 100 ms

#####`keyfile`
Specify the path to a key file to store authentication information. This option
is only useful for the connection between replica set members. Default: None

#####`master`
Set to true to configure the current instance to act as master instance in a
replication configuration. Default: False  *Note*: deprecated – use replica sets

#####`set_parameter`
Specify extra configuration file parameters (i.e.
textSearchEnabled=true). Default: None

#####`syslog`
Sends all logging output to the host’s syslog system rather than to standard
output or a log file. Default: None
*Important*: You cannot use syslog with logpath.

#####`slave`
Set to true to configure the current instance to act as slave instance in a
replication configuration. Default: false
*Note*: deprecated – use replica sets

#####`only`
Used with the slave option, only specifies only a single database to
replicate. Default: <>
*Note*: deprecated – use replica sets

#####`source`
Used with the slave setting to specify the master instance from which
this slave instance will replicate. Default: <>
*Note*: deprecated – use replica sets

### Definitions

#### Definition: mongodb:db

Creates database with user. Resource title used as database name.

#####`user`
Name of the user for database

#####`password_hash`
Hex encoded md5 hash of "$username:mongo:$password".
For more information please refer to [MongoDB Authentication Process](http://docs.mongodb.org/meta-driver/latest/legacy/implement-authentication-in-driver/#authentication-process).

#####`password`
Plain-text user password (will be hashed)

#####`roles`
Array with user roles. Default: ['dbAdmin']

### Providers

#### Provider: mongodb_database
'mongodb_database' can be used to create and manage databases within MongoDB.

```puppet
mongodb_database { testdb:
  ensure   => present,
  tries    => 10,
  require  => Class['mongodb::server'],
}
```
#####`tries`
The maximum amount of two second tries to wait MongoDB startup. Default: 10


#### Provider: mongodb_user
'mongodb_user' can be used to create and manage users within MongoDB database.

```puppet
mongodb_user { testuser:
  ensure        => present,
  password_hash => mongodb_password('testuser', 'p@ssw0rd'),
  database      => testdb,
  roles         => ['readWrite', 'dbAdmin'],
  tries         => 10,
  require       => Class['mongodb::server'],
}
```
#####`password_hash`
Hex encoded md5 hash of "$username:mongo:$password".

#####`database`
Name of database. It will be created, if not exists.

#####`roles`
Array with user roles. Default: ['dbAdmin']

#####`tries`
The maximum amount of two second tries to wait MongoDB startup. Default: 10

#### Provider: mongodb_replset
'mongodb_replset' can be used to create and manage MongoDB replicasets.

```puppet
mongodb_replset { rsmain:
  ensure  => present,
  members => ['host1:27017', 'host2:27017', 'host3:27017']
}
```

Ideally the ```mongodb_replset``` resource will be declared on the initial
desired primary node (arbitrarily the first of the list) and this node will be
processed once the secondary nodes are up. This will ensure all the nodes are
in the first configuration of the replicaset, else it will require running
puppet again to add them.

#####`members`
Array of 'host:port' of the replicaset members.

It currently only adds members without options.

## Limitation

This module has been tested on:

* Debian 7.* (Wheezy)
* Debian 6.* (squeeze)
* Ubuntu 12.04.2 (precise)
* Ubuntu 10.04.4 LTS (lucid)
* RHEL 5/6
* CentOS 5/6

For a full list of tested operating systems please have a look at the [.nodeset.xml](https://github.com/puppetlabs/puppetlabs-mongodb/blob/master/.nodeset.yml) definition.

This module should support `service_ensure` separate from the `ensure` value on `Class[mongodb::server]` but it does not yet.

## Development

Puppet Labs modules on the Puppet Forge are open projects, and community
contributions are essential for keeping them great. We can’t access the
huge number of platforms and myriad of hardware, software, and deployment
configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our
modules work in your environment. There are a few guidelines that we need
contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)

### Testing

There are two types of tests distributed with this module. Unit tests with
rspec-puppet and system tests using rspec-system.


unit tests should be run under Bundler with the gem versions as specified
in the Gemfile. To install the necessary gems:

    bundle install --path=vendor

Test setup and teardown is handled with rake tasks, so the
supported way of running tests is with

    bundle exec rake spec


For system test you will also need to install vagrant > 1.3.x and virtualbox > 4.2.10.
To run the system tests

    bundle exec rake spec:system

To run the tests on different operating systems, see the sets available in [.nodeset.xml](https://github.com/puppetlabs/puppetlabs-mongodb/blob/master/.nodeset.yml)
and run the specific set with the following syntax:

    RSPEC_SET=ubuntu-server-12042-x64 bundle exec rake spec:system

### Authors

We would like to thank everyone who has contributed issues and pull requests to this module.
A complete list of contributors can be found on the
[GitHub Contributor Graph](https://github.com/puppetlabs/puppetlabs-mongodb/graphs/contributors)
for the [puppetlabs-mongodb module](https://github.com/puppetlabs/puppetlabs-mongodb).
