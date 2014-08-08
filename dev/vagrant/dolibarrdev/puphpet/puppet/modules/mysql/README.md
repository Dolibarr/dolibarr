#MySQL

####Table of Contents

1. [Overview](#overview)
2. [Module Description - What the module does and why it is useful](#module-description)
3. [Setup - The basics of getting started with mysql](#setup)
    * [What mysql affects](#what-mysql-affects)
    * [Setup requirements](#setup-requirements)
    * [Beginning with mysql](#beginning-with-mysql)
4. [Usage - Configuration options and additional functionality](#usage)
5. [Reference - An under-the-hood peek at what the module is doing and how](#reference)
5. [Limitations - OS compatibility, etc.](#limitations)
6. [Development - Guide for contributing to the module](#development)

##Overview

The mysql module installs, configures, and manages the MySQL service.

##Module Description

The mysql module manages both the installation and configuration of MySQL as
well as extends Puppet to allow management of MySQL resources, such as
databases, users, and grants.

##Backwards Compatibility

This module has just undergone a very large rewrite.  Some new classes have been added, and many previous classes and configurations work differently than before.  We've attempted to handle backwards compatibility automatically by adding a
`attempt_compatibility_mode` parameter to the main mysql class.  If you set
this to 'true' it will attempt to map your previous parameters into the new
`mysql::server` class.

#####WARNING

Compatibility mode may fail. It may eat your MySQL server. PLEASE test it before running it live, even if the test is just a no-op and manual comparison. Please be careful!

##Setup

###What MySQL affects

* MySQL package
* MySQL configuration files
* MySQL service

###Beginning with MySQL

If you just want a server installed with the default options you can run
`include '::mysql::server'`.  

If you need to customize options, such as the root
password or `/etc/my.cnf` settings, then you must also pass in an override hash:

```puppet
class { '::mysql::server':
  root_password    => 'strongpassword',
  override_options => { 'mysqld' => { 'max_connections' => '1024' } }
}
```

##Usage

All interaction for the server is done via `mysql::server`.  To install the
client you use `mysql::client`, and to install bindings you can use
`mysql::bindings`.

###Overrides

The hash structure for overrides in `mysql::server` is as follows:

```puppet
$override_options = {
  'section' => {
    'item'             => 'thing',
  }
}
```

For items that you would traditionally represent as:

<pre>
[section]
thing = X
</pre>

You can just make an entry like `thing => true`, `thing => value`, or `thing => "` in the hash. You can also pass an array `thing => ['value', 'value2']` or even list each `thing => value` separately on separate lines. MySQL doesn't care if 'thing' is alone or set to a value; it'll happily accept both.  To keep an option out of the my.cnf file, e.g. when using override_options to revert to a default value, you can pass thing => undef.
If an option needs multiple instances, you can pass an array. For example

```puppet
$override_options = {
  'mysqld' => {
    'replicate-do-db' => ['base1', 'base2'],
  }
}
```

will produce

<pre>
[mysql]
replicate-do-db = base1
replicate-do-db = base2
</pre>

###Custom configuration

To add custom MySQL configuration, drop additional files into
`/etc/mysql/conf.d/`. Dropping files into conf.d allows you to override settings or add additional ones, which is helpful if you choose not to use `override_options` in `mysql::server`. The conf.d location is hardcoded into the my.cnf template file.

##Reference

###Classes

####Public classes
* `mysql::server`: Installs and configures MySQL.
* `mysql::server::account_security`: Deletes default MySQL accounts.
* `mysql::server::monitor`: Sets up a monitoring user.
* `mysql::server::mysqltuner`: Installs MySQL tuner script.
* `mysql::server::backup`: Sets up MySQL backups via cron.
* `mysql::bindings`: Installs various MySQL language bindings.
* `mysql::client`: Installs MySQL client (for non-servers).

####Private classes
* `mysql::server::install`: Installs packages.
* `mysql::server::config`: Configures MYSQL.
* `mysql::server::service`: Manages service.
* `mysql::server::root_password`: Sets MySQL root password.
* `mysql::server::providers`: Creates users, grants, and databases.
* `mysql::bindings::java`: Installs Java bindings.
* `mysql::bindings::perl`: Installs Perl bindings.
* `mysql::bindings::python`: Installs Python bindings.
* `mysql::bindings::ruby`: Installs Ruby bindings.
* `mysql::client::install`:  Installs MySQL client.

###Parameters

####mysql::server

#####`root_password`

The MySQL root password.  Puppet will attempt to set the root password and update `/root/.my.cnf` with it.

#####`old_root_password`

The previous root password (**REQUIRED** if you wish to change the root password via Puppet.)

#####`override_options`

The hash of override options to pass into MySQL.  It can be structured
like a hash in the my.cnf file, so entries look like

```puppet
$override_options = {
  'section' => {
    'item'             => 'thing',
  }
}
```

For items that you would traditionally represent as:

<pre>
[section]
thing = X
</pre>

You can just make an entry like `thing => true`, `thing => value`, or `thing => "` in the hash. You can also pass an array `thing => ['value', 'value2']` or even list each `thing => value` separately on separate lines. MySQL doesn't care if 'thing' is alone or set to a value; it'll happily accept both.  To keep an option out of the my.cnf file, e.g. when using override_options to revert to a default value, you can pass thing => undef.

#####`config_file`

The location of the MySQL configuration file.

#####`manage_config_file`

Whether the MySQL configuration file should be managed.

#####`purge_conf_dir`

Whether the conf.d directory should be purged.

#####`restart`

Whether the service should be restarted when things change.

#####`root_group`

What is the group used for root?

#####`package_ensure`

What to set the package to.  Can be 'present', 'absent', or 'x.y.z'.

#####`package_name`

The name of the mysql server package to install.

#####`remove_default_accounts`

Boolean to decide if we should automatically include
`mysql::server::account_security`.

#####`service_enabled`

Boolean to decide if the service should be enabled.

#####`service_manage`

Boolean to decide if the service should be managed.

#####`service_name`

The name of the mysql server service.

#####`service_provider`

The provider to use to manage the service.

#####`users`

Optional hash of users to create, which are passed to [mysql_user](#mysql_user). 

```puppet
$users = {
  'someuser@localhost' => {
    ensure                   => 'present',
    max_connections_per_hour => '0',
    max_queries_per_hour     => '0',
    max_updates_per_hour     => '0',
    max_user_connections     => '0',
    password_hash            => '*F3A2A51A9B0F2BE2468926B4132313728C250DBF',
  },
}
```

#####`grants`

Optional hash of grants, which are passed to [mysql_grant](#mysql_grant). 

```puppet
$grants = {
  'someuser@localhost/somedb.*' => {
    ensure     => 'present',
    options    => ['GRANT'],
    privileges => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
    table      => 'somedb.*',
    user       => 'someuser@localhost',
  },
}
```

#####`databases`

Optional hash of databases to create, which are passed to [mysql_database](#mysql_database).

```puppet
$databases = {
  'somedb' => {
    ensure  => 'present',
    charset => 'utf8',
  },
}
```

####mysql::server::backup

#####`backupuser`

MySQL user to create for backups.

#####`backuppassword`

MySQL user password for backups.

#####`backupdir`

Directory to back up into.

#####`backupdirmode`

Permissions applied to the backup directory. This parameter is passed directly
to the `file` resource.

#####`backupdirowner`

Owner for the backup directory. This parameter is passed directly to the `file`
resource.

#####`backupdirgroup`

Group owner for the backup directory. This parameter is passed directly to the
`file` resource.

#####`backupcompress`

Boolean to determine if backups should be compressed.

#####`backuprotate`

How many days to keep backups for.

#####`delete_before_dump`

Boolean to determine if you should cleanup before backing up or after.

#####`backupdatabases`

Array of databases to specifically back up.

#####`file_per_database`

Whether a separate file be used per database.

#####`ensure`

Allows you to remove the backup scripts. Can be 'present' or 'absent'.

#####`time`

An array of two elements to set the backup time.  Allows ['23', '5'] or ['3', '45'] for HH:MM times.

#####`postscript`

A script that is executed at when the backup is finished. This could be used to (r)sync the backup to a central store. This script can be either a single line that is directly executed or a number of lines, when supplied as an array. It could also be one or more externally managed (executable) files.

####mysql::server::monitor

#####`mysql_monitor_username`

The username to create for MySQL monitoring.

#####`mysql_monitor_password`

The password to create for MySQL monitoring.

#####`mysql_monitor_hostname`

The hostname to allow to access the MySQL monitoring user.

####mysql::bindings

#####`java_enable`

Boolean to decide if the Java bindings should be installed.

#####`perl_enable`

Boolean to decide if the Perl bindings should be installed.

#####`php_enable`

Boolean to decide if the PHP bindings should be installed.

#####`python_enable`

Boolean to decide if the Python bindings should be installed.

#####`ruby_enable`

Boolean to decide if the Ruby bindings should be installed.

#####`java_package_ensure`

What to set the package to.  Can be 'present', 'absent', or 'x.y.z'.

#####`java_package_name`

The name of the package to install.

#####`java_package_provider`

What provider should be used to install the package.

#####`perl_package_ensure`

What to set the package to.  Can be 'present', 'absent', or 'x.y.z'.

#####`perl_package_name`

The name of the package to install.

#####`perl_package_provider`

What provider should be used to install the package.

#####`python_package_ensure`

What to set the package to.  Can be 'present', 'absent', or 'x.y.z'.

#####`python_package_name`

The name of the package to install.

#####`python_package_provider`

What provider should be used to install the package.

#####`ruby_package_ensure`

What to set the package to.  Can be 'present', 'absent', or 'x.y.z'.

#####`ruby_package_name`

The name of the package to install.

#####`ruby_package_provider`

What provider should be used to install the package.

####mysql::client

#####`bindings_enable`

Boolean to automatically install all bindings.

#####`package_ensure`

What to set the package to.  Can be 'present', 'absent', or 'x.y.z'.

#####`package_name`

What is the name of the mysql client package to install.

###Defines

####mysql::db

Creates a database with a user and assigns some privileges.

```puppet
    mysql::db { 'mydb':
      user     => 'myuser',
      password => 'mypass',
      host     => 'localhost',
      grant    => ['SELECT', 'UPDATE'],
    }
```

###Providers

####mysql_database

`mysql_database` can be used to create and manage databases within MySQL.

```puppet
mysql_database { 'information_schema':
  ensure  => 'present',
  charset => 'utf8',
  collate => 'utf8_swedish_ci',
}
mysql_database { 'mysql':
  ensure  => 'present',
  charset => 'latin1',
  collate => 'latin1_swedish_ci',
}
```

####mysql_user

`mysql_user` can be used to create and manage user grants within MySQL.

```puppet
mysql_user { 'root@127.0.0.1':
  ensure                   => 'present',
  max_connections_per_hour => '0',
  max_queries_per_hour     => '0',
  max_updates_per_hour     => '0',
  max_user_connections     => '0',
}
```

####mysql_grant

`mysql_grant` can be used to create grant permissions to access databases within
MySQL.  To use it you must create the title of the resource as shown below,
following the pattern of `username@hostname/database.table`:

```puppet
mysql_grant { 'root@localhost/*.*':
  ensure     => 'present',
  options    => ['GRANT'],
  privileges => ['ALL'],
  table      => '*.*',
  user       => 'root@localhost',
}
```

##Limitations

This module has been tested on:

* RedHat Enterprise Linux 5/6
* Debian 6/7
* CentOS 5/6
* Ubuntu 12.04

Testing on other platforms has been light and cannot be guaranteed.

#Development

Puppet Labs modules on the Puppet Forge are open projects, and community
contributions are essential for keeping them great. We can’t access the
huge number of platforms and myriad of hardware, software, and deployment
configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our
modules work in your environment. There are a few guidelines that we need
contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)

### Authors

This module is based on work by David Schmitt. The following contributors have contributed patches to this module (beyond Puppet Labs):

* Larry Ludwig
* Christian G. Warden
* Daniel Black
* Justin Ellison
* Lowe Schmidt
* Matthias Pigulla
* William Van Hevelingen
* Michael Arnold
* Chris Weyl

