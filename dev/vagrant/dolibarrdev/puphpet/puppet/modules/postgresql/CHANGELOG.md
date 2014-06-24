##2014-03-04 - Supported Release 3.3.3
###Summary

This is a supported release.  This release removes a testing symlink that can
cause trouble on systems where /var is on a seperate filesystem from the
modulepath.

####Features
####Bugfixes
####Known Bugs
* SLES is not supported.

##2014-03-04 - Supported Release 3.3.2
###Summary
This is a supported release. It fixes a problem with updating passwords on postgresql.org distributed versions of PostgreSQL.

####Bugfixes
- Correct psql path when setting password on custom versions.
- Documentation updates
- Test updates

####Known Bugs
* SLES is not supported.


##2014-02-12 - Version 3.3.1
####Bugfix:
- Allow dynamic rubygems host


##2014-01-28 - Version 3.3.0

###Summary

This release rolls up a bunch of bugfixes our users have found and fixed for
us over the last few months.  This improves things for 9.1 users, and makes
this module usable on FreeBSD.

This release is dedicated to 'bma', who's suffering with Puppet 3.4.1 issues
thanks to Puppet::Util::SUIDManager.run_and_capture.

####Features
 - Add lc_ config entry settings
 - Can pass template at database creation.
 - Add FreeBSD support.
 - Add support for customer `xlogdir` parameter.
 - Switch tests from rspec-system to beaker.  (This isn't really a feature)

####Bugfixes
 - Properly fix the deprecated Puppet::Util::SUIDManager.run_and_capture errors.
 - Fix NOREPLICATION option for Postgres 9.1
 - Wrong parameter name: manage_pg_conf -> manage_pg_hba_conf
 - Add $postgresql::server::client_package_name, referred to by install.pp
 - Add missing service_provider/service_name descriptions in ::globals.
 - Fix several smaller typos/issues throughout.
 - Exec['postgresql_initdb'] needs to be done after $datadir exists
 - Prevent defined resources from floating in the catalog.
 - Fix granting all privileges on a table.
 - Add some missing privileges.
 - Remove deprecated and unused concat::fragment parameters.


##2013-11-05 - Version 3.2.0

###Summary

Add's support for Ubuntu 13.10 (and 14.04) as well as x, y, z.

####Features
- Add versions for Ubuntu 13.10 and 14.04.
- Use default_database in validate_db_connection instead of a hardcoded
'postgres'
- Add globals/params layering for default_database.
- Allow specification of default database name.

####Bugs
- Fixes to the README.


##2013-10-25 - Version 3.1.0

###Summary

This is a minor feature and bug fix release.

Firstly, the postgresql_psql type now includes a new parameter `search_path` which is equivalent to using `set search_path` which allows you to change the default schema search path.

The default version of Fedora 17 has now been added, so that Fedora 17 users can enjoy the module.

And finally we've extended the capabilities of the defined type postgresql::validate_db_connection so that now it can handle retrying and sleeping between retries. This feature has been monopolized to fix a bug we were seeing with startup race conditions, but it can also be used by remote systems to 'wait' for PostgreSQL to start before their Puppet run continues.

####Features
- Defined $default_version for Fedora 17 (Bret Comnes)
- add search_path attribute to postgresql_psql resource (Jeremy Kitchen)
- (GH-198) Add wait and retry capability to validate_db_connection (Ken Barber)

####Bugs
- enabling defined postgres user password without resetting on every puppet run (jonoterc)
- periods are valid in configuration variables also (Jeremy Kitchen)
- Add zero length string to join() function (Jarl Stefansson)
- add require of install to reload class (cdenneen)
- (GH-198) Fix race condition on postgresql startup (Ken Barber)
- Remove concat::setup for include in preparation for the next concat release (Ken Barber)


##2013-10-14 - Version 3.0.0

Final release of 3.0, enjoy!


##2013-10-14 - Version 3.0.0-rc3

###Summary

Add a parameter to unmanage pg_hba.conf to fix a regression from 2.5, as well
as allowing owner to be passed into x.

####Features
- `manage_pg_hba_conf` parameter added to control pg_hba.conf management.
- `owner` parameter added to server::db.


##2013-10-09 - Version 3.0.0-rc2

###Summary

A few bugfixes have been found since -rc1.

####Fixes
- Special case for $datadir on Amazon
- Fix documentation about username/password for the postgresql_hash function


##2013-10-01 - Version 3.0.0-rc1

###Summary

Version 3 was a major rewrite to fix some internal dependency issues, and to
make the new Public API more clear. As a consequence a lot of things have
changed for version 3 and older revisions that we will try to outline here.

(NOTE:  The format of this CHANGELOG differs to normal in an attempt to
explain the scope of changes)

* Server specific objects now moved under `postgresql::server::` namespace:

To restructure server specific elements under the `postgresql::server::`
namespaces the following objects were renamed as such:

`postgresql::database`       -> `postgresql::server::database`
`postgresql::database_grant` -> `postgresql::server::database_grant`
`postgresql::db`             -> `postgresql::server::db`
`postgresql::grant`          -> `postgresql::server::grant`
`postgresql::pg_hba_rule`    -> `postgresql::server::pg_hba_rule`
`postgresql::plperl`         -> `postgresql::server::plperl`
`postgresql::contrib`        -> `postgresql::server::contrib`
`postgresql::role`           -> `postgresql::server::role`
`postgresql::table_grant`    -> `postgresql::server::table_grant`
`postgresql::tablespace`     -> `postgresql::server::tablespace`

* New `postgresql::server::config_entry` resource for managing configuration:

Previously we used the `file_line` resource to modify `postgresql.conf`. This
new revision now adds a new resource named `postgresql::server::config_entry`
for managing this file. For example:

```puppet
    postgresql::server::config_entry { 'check_function_bodies':
      value => 'off',
    }
```

If you were using `file_line` for this purpose, you should change to this new
methodology.

* `postgresql_puppet_extras.conf` has been removed:

Now that we have a methodology for managing `postgresql.conf`, and due to
concerns over the file management methodology using an `exec { 'touch ...': }`
as a way to create an empty file the existing postgresql\_puppet\_extras.conf
file is no longer managed by this module.

If you wish to recreate this methodology yourself, use this pattern:

```puppet
    class { 'postgresql::server': }

    $extras = "/tmp/include.conf"

    file { $extras:
      content => 'max_connections = 123',
      notify  => Class['postgresql::server::service'],
    }->
    postgresql::server::config_entry { 'include':
      value   => $extras,
    }
```

* All uses of the parameter `charset` changed to `encoding`:

Since PostgreSQL uses the terminology `encoding` not `charset` the parameter
has been made consisent across all classes and resources.

* The `postgresql` base class is no longer how you set globals:

The old global override pattern was less then optimal so it has been fixed,
however we decided to demark this properly by specifying these overrides in
the class `postgresql::global`. Consult the documentation for this class now
to see what options are available.

Also, some parameter elements have been moved between this and the
`postgresql::server` class where it made sense.

* `config_hash` parameter collapsed for the `postgresql::server` class:

Because the `config_hash` was really passing data through to what was in
effect an internal class (`postgresql::config`). And since we don't want this
kind of internal exposure the parameters were collapsed up into the
`postgresql::server` class directly.

* Lots of changes to 'private' or 'undocumented' classes:

If you were using these before, these have changed names. You should only use
what is documented in this README.md, and if you don't have what you need you
should raise a patch to add that feature to a public API. All internal classes
now have a comment at the top indicating them as private to make sure the
message is clear that they are not supported as Public API.

* `pg_hba_conf_defaults` parameter included to turn off default pg\_hba rules:

The defaults should be good enough for most cases (if not raise a bug) but if
you simply need an escape hatch, this setting will turn off the defaults. If
you want to do this, it may affect the rest of the module so make sure you
replace the rules with something that continues operation.

* `postgresql::database_user` has now been removed:

Use `postgresql::server::role` instead.

* `postgresql::psql` resource has now been removed:

Use `postgresql_psql` instead. In the future we may recreate this as a wrapper
to add extra capability, but it will not match the old behaviour.

* `postgresql_default_version` fact has now been removed:

It didn't make sense to have this logic in a fact any more, the logic has been
moved into `postgresql::params`.

* `ripienaar/concat` is no longer used, instead we use `puppetlabs/concat`:

The older concat module is now deprecated and moved into the
`puppetlabs/concat` namespace. Functionality is more or less identical, but
you may need to intervene during the installing of this package - as both use
the same `concat` namespace.

---
##2013-09-09 Release 2.5.0

###Summary

The focus of this release is primarily to capture the fixes done to the
types and providers to make sure refreshonly works properly and to set
the stage for the large scale refactoring work of 3.0.0.

####Features


####Bugfixes 
- Use boolean for refreshonly.
- Fix postgresql::plperl documentation.
- Add two missing parameters to config::beforeservice
- Style fixes


##2013-08-01 Release 2.4.1

###Summary

This minor bugfix release solves an idempotency issue when using plain text
passwords for the password_hash parameter for the postgresql::role defined
type. Without this, users would continually see resource changes everytime
your run Puppet.

####Bugfixes
- Alter role call not idempotent with cleartext passwords (Ken Barber)


##2013-07-19 Release 2.4.0

###Summary

This updates adds the ability to change permissions on tables, create template
databases from normal databases, manage PL-Perl's postgres package, and
disable the management of `pg_hba.conf`.

####Features
- Add `postgresql::table_grant` defined resource
- Add `postgresql::plperl` class
- Add `manage_pg_hba_conf` parameter to the `postgresql::config` class
- Add `istemplate` parameter to the `postgresql::database` define

####Bugfixes
- Update `postgresql::role` class to be able to update roles when modified
instead of only on creation.
- Update tests
- Fix documentation of `postgresql::database_grant`


##2.3.0

This feature release includes the following changes:

* Add a new parameter `owner` to the `database` type.  This can be used to
  grant ownership of a new database to a specific user.  (Bruno Harbulot)
* Add support for operating systems other than Debian/RedHat, as long as the
  user supplies custom values for all of the required paths, package names, etc.
  (Chris Price)
* Improved integration testing (Ken Barber)


##2.2.1

This release fixes a bug whereby one of our shell commands (psql) were not ran from a globally accessible directory. This was causing permission denied errors when the command attempted to change user without changing directory.

Users of previous versions might have seen this error:

    Error: Error executing SQL; psql returned 256: 'could not change directory to "/root"

This patch should correct that.

#### Detail Changes

* Set /tmp as default CWD for postgresql_psql


##2.2.0

This feature release introduces a number of new features and bug fixes.

First of all it includes a new class named `postgresql::python` which provides you with a convenient way of install the python Postgresql client libraries.

    class { 'postgresql::python':
    }

You are now able to use `postgresql::database_user` without having to specify a password_hash, useful for different authentication mechanisms that do not need passwords (ie. cert, local etc.).

We've also provided a lot more advanced custom parameters now for greater control of your Postgresql installation. Consult the class documentation for PuppetDB in the README.

This release in particular has largely been contributed by the community members below, a big thanks to one and all.

#### Detailed Changes

* Add support for psycopg installation (Flaper Fesp and Dan Prince)
* Added default PostgreSQL version for Ubuntu 13.04 (Kamil Szymanski)
* Add ability to create users without a password (Bruno Harbulot)
* Three Puppet 2.6 fixes (Dominic Cleal)
* Add explicit call to concat::setup when creating concat file (Dominic Cleal)
* Fix readme typo (Jordi Boggiano)
* Update postgres_default_version for Ubuntu (Kamil Szymanski)
* Allow to set connection for noew role (Kamil Szymanski)
* Fix pg_hba_rule for postgres local access (Kamil Szymanski)
* Fix versions for travis-ci (Ken Barber)
* Add replication support (Jordi Boggiano)
* Cleaned up and added unit tests (Ken Barber)
* Generalization to provide more flexability in postgresql configuration (Karel Brezina)
* Create dependent directory for sudoers so tests work on Centos 5 (Ken Barber)
* Allow SQL commands to be run against a specific DB (Carlos Villela)
* Drop trailing comma to support Puppet 2.6 (Michael Arnold)


##2.1.1


This release provides a bug fix for RHEL 5 and Centos 5 systems, or specifically systems using PostgreSQL 8.1 or older. On those systems one would have received the error:

    Error: Could not start Service[postgresqld]: Execution of ‘/sbin/service postgresql start’ returned 1:

And the postgresql log entry:

    FATAL: unrecognized configuration parameter "include"

This bug is due to a new feature we had added in 2.1.0, whereby the `include` directive in `postgresql.conf` was not compatible. As a work-around we have added checks in our code to make sure systems running PostgreSQL 8.1 or older do not have this directive added.

#### Detailed Changes

2013-01-21 - Ken Barber <ken@bob.sh>
* Only install `include` directive and included file on PostgreSQL >= 8.2
* Add system tests for Centos 5


##2.1.0

This release is primarily a feature release, introducing some new helpful constructs to the module.

For starters, we've added the line `include 'postgresql_conf_extras.conf'` by default so extra parameters not managed by the module can be added by other tooling or by Puppet itself. This provides a useful escape-hatch for managing settings that are not currently managed by the module today.

We've added a new defined resource for managing your tablespace, so you can now create new tablespaces using the syntax:

    postgresql::tablespace { 'dbspace':
      location => '/srv/dbspace',
    }

We've added a locale parameter to the `postgresql` class, to provide a default. Also the parameter has been added to the `postgresql::database` and `postgresql::db` defined resources for changing the locale per database:

    postgresql::db { 'mydatabase':
      user     => 'myuser',
      password => 'mypassword',
      encoding => 'UTF8',
      locale   => 'en_NG',
    }

There is a new class for installing the necessary packages to provide the PostgreSQL JDBC client jars:

    class { 'postgresql::java': }

And we have a brand new defined resource for managing fine-grained rule sets within your pg_hba.conf access lists:

    postgresql::pg_hba { 'Open up postgresql for access from 200.1.2.0/24':
      type => 'host',
      database => 'app',
      user => 'app',
      address => '200.1.2.0/24',
      auth_method => 'md5',
    }

Finally, we've also added Travis-CI support and unit tests to help us iterate faster with tests to reduce regression. The current URL for these tests is here: https://travis-ci.org/puppetlabs/puppet-postgresql. Instructions on how to run the unit tests available are provided in the README for the module.

A big thanks to all those listed below who made this feature release possible :-).

#### Detailed Changes

2013-01-18 - Simão Fontes <simaofontes@gmail.com> & Flaper Fesp <flaper87@gmail.com>
* Remove trailing commas from params.pp property definition for Puppet 2.6.0 compatibility

2013-01-18 - Lauren Rother <lauren.rother@puppetlabs.com>
* Updated README.md to conform with best practices template

2013-01-09 - Adrien Thebo <git@somethingsinistral.net>
* Update postgresql_default_version to 9.1 for Debian 7.0

2013-01-28 - Karel Brezina <karel.brezina@gmail.com>
* Add support for tablespaces

2013-01-16 - Chris Price <chris@puppetlabs.com> & Karel Brezina <karel.brezina@gmail.com>
* Provide support for an 'include' config file 'postgresql_conf_extras.conf' that users can modify manually or outside of the module.

2013-01-31 - jv <jeff@jeffvier.com>
* Fix typo in README.pp for postgresql::db example

2013-02-03 - Ken Barber <ken@bob.sh>
* Add unit tests and travis-ci support

2013-02-02 - Ken Barber <ken@bob.sh>
* Add locale parameter support to the 'postgresql' class

2013-01-21 - Michael Arnold <github@razorsedge.org>
* Add a class for install the packages containing the PostgreSQL JDBC jar

2013-02-06 - fhrbek <filip.hbrek@gmail.com>
* Coding style fixes to reduce warnings in puppet-lint and Geppetto

2013-02-10 - Ken Barber <ken@bob.sh>
* Provide new defined resource for managing pg_hba.conf

2013-02-11 - Ken Barber <ken@bob.sh>
* Fix bug with reload of Postgresql on Redhat/Centos

2013-02-15 - Erik Dalén <dalen@spotify.com>
* Fix more style issues to reduce warnings in puppet-lint and Geppetto

2013-02-15 - Erik Dalén <dalen@spotify.com>
* Fix case whereby we were modifying a hash after creation


##2.0.1

Minor bugfix release.

2013-01-16 - Chris Price <chris@puppetlabs.com>
 * Fix revoke command in database.pp to support postgres 8.1 (43ded42)

2013-01-15 - Jordi Boggiano <j.boggiano@seld.be>
 * Add support for ubuntu 12.10 status (3504405)

##2.0.0

Many thanks to the following people who contributed patches to this
release:

* Adrien Thebo
* Albert Koch
* Andreas Ntaflos
* Brett Porter
* Chris Price
* dharwood
* Etienne Pelletier
* Florin Broasca
* Henrik
* Hunter Haugen
* Jari Bakken
* Jordi Boggiano
* Ken Barber
* nzakaria
* Richard Arends
* Spenser Gilliland
* stormcrow
* William Van Hevelingen

Notable features:

   * Add support for versions of postgres other than the system default version
     (which varies depending on OS distro).  This includes optional support for
     automatically managing the package repo for the "official" postgres yum/apt
     repos.  (Major thanks to Etienne Pelletier <epelletier@maestrodev.com> and
     Ken Barber <ken@bob.sh> for their tireless efforts and patience on this
     feature set!)  For example usage see `tests/official-postgresql-repos.pp`.

   * Add some support for Debian Wheezy and Ubuntu Quantal

   * Add new `postgres_psql` type with a Ruby provider, to replace the old
     exec-based `psql` type.  This gives us much more flexibility around
     executing SQL statements and controlling their logging / reports output.

   * Major refactor of the "spec" tests--which are actually more like
     acceptance tests.  We now support testing against multiple OS distros
     via vagrant, and the framework is in place to allow us to very easily add
     more distros.  Currently testing against Cent6 and Ubuntu 10.04.

   * Fixed a bug that was preventing multiple databases from being owned by the
     same user
     (9adcd182f820101f5e4891b9f2ff6278dfad495c - Etienne Pelletier <epelletier@maestrodev.com>)

   * Add support for ACLs for finer-grained control of user/interface access
     (b8389d19ad78b4fb66024897097b4ed7db241930 - dharwood <harwoodd@cat.pdx.edu>)

   * Many other bug fixes and improvements!

---
##1.0.0

2012-09-17 - Version 0.3.0 released

2012-09-14 - Chris Price <chris@puppetlabs.com>
 * Add a type for validating a postgres connection (ce4a049)

2012-08-25 - Jari Bakken <jari.bakken@gmail.com>
 * Remove trailing commas. (e6af5e5)

2012-08-16 - Version 0.2.0 released
