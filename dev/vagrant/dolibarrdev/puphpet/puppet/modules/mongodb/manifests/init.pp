# == Class: mongodb
#
# Direct use of this class is deprecated. Please use mongodb::server
#
# Manage mongodb installations on RHEL, CentOS, Debian and Ubuntu - either
# installing from the 10Gen repo or from EPEL in the case of EL systems.
#
# === Parameters
#
# enable_10gen (default: false) - Whether or not to set up 10gen software repositories
# init (auto discovered) - override init (sysv or upstart) for Debian derivatives
# location - override apt location configuration for Debian derivatives
# packagename (auto discovered) - override the package name
# servicename (auto discovered) - override the service name
# service-enable (default: true) - Enable the service and ensure it is running
#
# === Examples
#
# To install with defaults from the distribution packages on any system:
#   include mongodb
#
# To install from 10gen on a EL server
#   class { 'mongodb':
#     enable_10gen => true,
#   }
#
# === Authors
#
# Craig Dunn <craig@craigdunn.org>
#
# === Copyright
#
# Copyright 2013 PuppetLabs
#

class mongodb (
  # Deprecated parameters
  $enable_10gen    = undef,

  $init            = $mongodb::params::service_provider,
  $location        = '',
  $packagename     = undef,
  $version         = undef,
  $servicename     = $mongodb::params::service_name,
  $service_enable  = true, #deprecated
  $logpath         = $mongodb::params::logpath,
  $logappend       = true,
  $fork            = $mongodb::params::fork,
  $port            = 27017,
  $dbpath          = $mongodb::params::dbpath,
  $journal         = undef,
  $nojournal       = undef,
  $smallfiles      = undef,
  $cpu             = undef,
  $noauth          = undef,
  $auth            = undef,
  $verbose         = undef,
  $objcheck        = undef,
  $quota           = undef,
  $oplog           = undef, #deprecated it's on if replica set
  $oplog_size      = undef,
  $nohints         = undef,
  $nohttpinterface = undef,
  $noscripting     = undef,
  $notablescan     = undef,
  $noprealloc      = undef,
  $nssize          = undef,
  $mms_token       = undef,
  $mms_name        = undef,
  $mms_interval    = undef,
  $slave           = undef,
  $only            = undef,
  $master          = undef,
  $source          = undef,
  $replset         = undef,
  $rest            = undef,
  $slowms          = undef,
  $keyfile         = undef,
  $bind_ip         = undef,
  $pidfilepath     = undef
) inherits mongodb::params {

  if $enable_10gen {
    fail("Parameter enable_10gen is no longer supported. Please use class { 'mongodb::globals': manage_package_repo => true }")
  }

  if $version {
    fail("Parameter version is no longer supported. Please use class { 'mongodb::globals': version => VERSION }")
  }

  if $oplog {
    fail('Parameter is no longer supported. On replica set Oplog is enabled by default.')
  }

  notify { 'An attempt has been made below to automatically apply your custom
    settings to mongodb::server. Please verify this works in a safe test
    environment.': }

  class { 'mongodb::server':
    package_name    => $packagename,
    logpath         => $logpath,
    logappend       => $logappend,
    fork            => $fork,
    port            => $port,
    dbpath          => $dbpath,
    journal         => $journal,
    nojournal       => $nojournal,
    smallfiles      => $smallfiles,
    cpu             => $cpu,
    noauth          => $noauth,
    verbose         => $verbose,
    objcheck        => $objcheck,
    quota           => $quota,
    oplog_size      => $oplog_size,
    nohints         => $nohints,
    nohttpinterface => $nohttpinterface,
    noscripting     => $noscripting,
    notablescan     => $notablescan,
    noprealloc      => $noprealloc,
    nssize          => $nssize,
    mms_token       => $mms_token,
    mms_name        => $mms_name,
    mms_interval    => $mms_interval,
    slave           => $slave,
    only            => $only,
    master          => $master,
    source          => $source,
    replset         => $replset,
    rest            => $rest,
    slowms          => $slowms,
    keyfile         => $keyfile,
    bind_ip         => $bind_ip,
    pidfilepath     => $pidfilepath,
  }

}
