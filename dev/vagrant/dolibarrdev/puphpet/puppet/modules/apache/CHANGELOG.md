## 2014-03-04 Supported Release 1.0.1
###Summary

This is a supported release.  This release removes a testing symlink that can
cause trouble on systems where /var is on a seperate filesystem from the
modulepath.

####Features
####Bugfixes
####Known Bugs
* By default, the version of Apache that ships with Ubuntu 10.04 does not work with `wsgi_import_script`.
* SLES is unsupported.
 
## 2014-03-04 Supported Release 1.0.0
###Summary

This is a supported release. This release introduces Apache 2.4 support for
Debian and RHEL based osfamilies.

####Features

- Add apache24 support
- Add rewrite_base functionality to rewrites
- Updated README documentation
- Add WSGIApplicationGroup and WSGIImportScript directives

####Bugfixes

- Replace mutating hashes with merge() for Puppet 3.5
- Fix WSGI import_script and mod_ssl issues on Lucid

####Known Bugs
* By default, the version of Apache that ships with Ubuntu 10.04 does not work with `wsgi_import_script`.
* SLES is unsupported.

---

## 2014-01-31 Release 0.11.0
### Summary:

This release adds preliminary support for Windows compatibility and multiple rewrite support.

#### Backwards-incompatible Changes:

- The rewrite_rule parameter is deprecated in favor of the new rewrite parameter
  and will be removed in a future release.

#### Features:

- add Match directive
- quote paths for windows compatibility
- add auth_group_file option to README.md
- allow AuthGroupFile directive for vhosts
- Support Header directives in vhost context
- Don't purge mods-available dir when separate enable dir is used
- Fix the servername used in log file name
- Added support for mod_include
- Remove index parameters.
- Support environment variable control for CustomLog
- added redirectmatch support
- Setting up the ability to do multiple rewrites and conditions.
- Convert spec tests to beaker.
- Support php_admin_(flag|value)s

#### Bugfixes:

- directories are either a Hash or an Array of Hashes
- Configure Passenger in separate .conf file on RH so PassengerRoot isn't lost
- (docs) Update list of `apache::mod::[name]` classes
- (docs) Fix apache::namevirtualhost example call style
- Fix $ports_file reference in apache::listen.
- Fix $ports_file reference in Namevirtualhost.


## 2013-12-05 Release 0.10.0
### Summary:

This release adds FreeBSD osfamily support and various other improvements to some mods.

#### Features:

- Add suPHP_UserGroup directive to directory context
- Add support for ScriptAliasMatch directives
- Set SSLOptions StdEnvVars in server context
- No implicit <Directory> entry for ScriptAlias path
- Add support for overriding ErrorDocument
- Add support for AliasMatch directives
- Disable default "allow from all" in vhost-directories
- Add WSGIPythonPath as an optional parameter to mod_wsgi. 
- Add mod_rpaf support
- Add directives: IndexOptions, IndexOrderDefault
- Add ability to include additional external configurations in vhost
- need to use the provider variable not the provider key value from the directory hash for matches
- Support for FreeBSD and few other features
- Add new params to apache::mod::mime class
- Allow apache::mod to specify module id and path
- added $server_root parameter
- Add Allow and ExtendedStatus support to mod_status
- Expand vhost/_directories.pp directive support
- Add initial support for nss module (no directives in vhost template yet)
- added peruser and event mpms
- added $service_name parameter
- add parameter for TraceEnable
- Make LogLevel configurable for server and vhost
- Add documentation about $ip
- Add ability to pass ip (instead of wildcard) in default vhost files

#### Bugfixes:

- Don't listen on port or set NameVirtualHost for non-existent vhost
- only apply Directory defaults when provider is a directory
- Working mod_authnz_ldap support on Debian/Ubuntu

## 2013-09-06 Release 0.9.0
### Summary:
This release adds more parameters to the base apache class and apache defined
resource to make the module more flexible. It also adds or enhances SuPHP,
WSGI, and Passenger mod support, and support for the ITK mpm module.

#### Backwards-incompatible Changes:
- Remove many default mods that are not normally needed.
- Remove `rewrite_base` `apache::vhost` parameter; did not work anyway.
- Specify dependencies on stdlib >=2.4.0 (this was already the case, but
making explicit)
- Deprecate `a2mod` in favor of the `apache::mod::*` classes and `apache::mod`
defined resource.

#### Features:
- `apache` class
  - Add `httpd_dir` parameter to change the location of the configuration
  files.
  - Add `logroot` parameter to change the logroot
  - Add `ports_file` parameter to changes the `ports.conf` file location
  - Add `keepalive` parameter to enable persistent connections
  - Add `keepalive_timeout` parameter to change the timeout
  - Update `default_mods` to be able to take an array of mods to enable.
- `apache::vhost`
  - Add `wsgi_daemon_process`, `wsgi_daemon_process_options`,
  `wsgi_process_group`, and `wsgi_script_aliases` parameters for per-vhost
  WSGI configuration.
  - Add `access_log_syslog` parameter to enable syslogging.
  - Add `error_log_syslog` parameter to enable syslogging of errors.
  - Add `directories` hash parameter. Please see README for documentation.
  - Add `sslproxyengine` parameter to enable SSLProxyEngine
  - Add `suphp_addhandler`, `suphp_engine`, and `suphp_configpath` for
  configuring SuPHP.
  - Add `custom_fragment` parameter to allow for arbitrary apache
  configuration injection. (Feature pull requests are prefered over using
  this, but it is available in a pinch.)
- Add `apache::mod::suphp` class for configuring SuPHP.
- Add `apache::mod::itk` class for configuring ITK mpm module.
- Update `apache::mod::wsgi` class for global WSGI configuration with
`wsgi_socket_prefix` and `wsgi_python_home` parameters.
- Add README.passenger.md to document the `apache::mod::passenger` usage.
Added `passenger_high_performance`, `passenger_pool_idle_time`,
`passenger_max_requests`, `passenger_stat_throttle_rate`, `rack_autodetect`,
and `rails_autodetect` parameters.
- Separate the httpd service resource into a new `apache::service` class for
dependency chaining of `Class['apache'] -> <resource> ~>
Class['apache::service']`
- Added `apache::mod::proxy_balancer` class for `apache::balancer`

#### Bugfixes:
- Change dependency to puppetlabs-concat
- Fix ruby 1.9 bug for `a2mod`
- Change servername to be `$::hostname` if there is no `$::fqdn`
- Make `/etc/ssl/certs` the default ssl certs directory for RedHat non-5.
- Make `php` the default php package for RedHat non-5.
- Made `aliases` able to take a single alias hash instead of requiring an
array.

## 2013-07-26 Release 0.8.1
#### Bugfixes:
- Update `apache::mpm_module` detection for worker/prefork
- Update `apache::mod::cgi` and `apache::mod::cgid` detection for
worker/prefork

## 2013-07-16 Release 0.8.0
#### Features:
- Add `servername` parameter to `apache` class
- Add `proxy_set` parameter to `apache::balancer` define

#### Bugfixes:
- Fix ordering for multiple `apache::balancer` clusters
- Fix symlinking for sites-available on Debian-based OSs
- Fix dependency ordering for recursive confdir management
- Fix `apache::mod::*` to notify the service on config change
- Documentation updates

## 2013-07-09 Release 0.7.0
#### Changes:
- Essentially rewrite the module -- too many to list
- `apache::vhost` has many abilities -- see README.md for details
- `apache::mod::*` classes provide httpd mod-loading capabilities
- `apache` base class is much more configurable

#### Bugfixes:
- Many. And many more to come

## 2013-03-2 Release 0.6.0
- update travis tests (add more supported versions)
- add access log_parameter
- make purging of vhost dir configurable

## 2012-08-24 Release 0.4.0
#### Changes:
- `include apache` is now required when using `apache::mod::*`

#### Bugfixes:
- Fix syntax for validate_re
- Fix formatting in vhost template
- Fix spec tests such that they pass

##2012-05-08 Puppet Labs <info@puppetlabs.com> - 0.0.4
* e62e362 Fix broken tests for ssl, vhost, vhost::*
* 42c6363 Changes to match style guide and pass puppet-lint without error
* 42bc8ba changed name => path for file resources in order to name namevar by it's name
* 72e13de One end too much
* 0739641 style guide fixes: 'true' <> true, $operatingsystem needs to be $::operatingsystem, etc.
* 273f94d fix tests
* a35ede5 (#13860) Make a2enmod/a2dismo commands optional
* 98d774e (#13860) Autorequire Package['httpd']
* 05fcec5 (#13073) Add missing puppet spec tests
* 541afda (#6899) Remove virtual a2mod definition
* 976cb69 (#13072) Move mod python and wsgi package names to params
* 323915a (#13060) Add .gitignore to repo
* fdf40af (#13060) Remove pkg directory from source tree
* fd90015 Add LICENSE file and update the ModuleFile
* d3d0d23 Re-enable local php class
* d7516c7 Make management of firewalls configurable for vhosts
* 60f83ba Explicitly lookup scope of apache_name in templates.
* f4d287f (#12581) Add explicit ordering for vdir directory
* 88a2ac6 (#11706) puppetlabs-apache depends on puppetlabs-firewall
* a776a8b (#11071) Fix to work with latest firewall module
* 2b79e8b (#11070) Add support for Scientific Linux
* 405b3e9 Fix for a2mod
* 57b9048 Commit apache::vhost::redirect Manifest
* 8862d01 Commit apache::vhost::proxy Manifest
* d5c1fd0 Commit apache::mod::wsgi Manifest
* a825ac7 Commit apache::mod::python Manifest
* b77062f Commit Templates
* 9a51b4a Vhost File Declarations
* 6cf7312 Defaults for Parameters
* 6a5b11a Ensure installed
* f672e46 a2mod fix
* 8a56ee9 add pthon support to apache
