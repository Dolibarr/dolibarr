# == Class: elasticsearch::params
#
# This class exists to
# 1. Declutter the default value assignment for class parameters.
# 2. Manage internally used module variables in a central place.
#
# Therefore, many operating system dependent differences (names, paths, ...)
# are addressed in here.
#
#
# === Parameters
#
# This class does not provide any parameters.
#
#
# === Examples
#
# This class is not intended to be used directly.
#
#
# === Links
#
# * {Puppet Docs: Using Parameterized Classes}[http://j.mp/nVpyWY]
#
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard@ispavailability.com>
#
class elasticsearch::params {

  #### Default values for the parameters of the main module class, init.pp

  # ensure
  $ensure = 'present'

  # autoupgrade
  $autoupgrade = false

  # service status
  $status = 'enabled'

  # restart on configuration change?
  $restart_on_change = true

  # Package dir. Temporary place to download the package to for installation
  $package_dir = '/var/lib/elasticsearch'

  # User and Group for the files and user to run the service as.
  $elasticsearch_user  = 'elasticsearch'
  $elasticsearch_group = 'elasticsearch'

  # Purge configuration directory
  $purge_confdir = true

  ## init service provider

  # configuration directory
  $confdir = '/etc/elasticsearch'

  # plugins directory
  $plugindir = '/usr/share/elasticsearch/plugins'

  # plugins helper binary
  $plugintool = '/usr/share/elasticsearch/bin/plugin'

  # Download tool
  $dlcmd = 'wget -O'

  $purge_package_dir = false

  #### Internal module values

  # packages
  case $::operatingsystem {
    'RedHat', 'CentOS', 'Fedora', 'Scientific', 'Amazon', 'OracleLinux': {
      # main application
      $package = [ 'elasticsearch' ]
    }
    'Debian', 'Ubuntu': {
      # main application
      $package = [ 'elasticsearch' ]
    }
    default: {
      fail("\"${module_name}\" provides no package default value
            for \"${::operatingsystem}\"")
    }
  }

  # service parameters
  case $::operatingsystem {
    'RedHat', 'CentOS', 'Fedora', 'Scientific', 'Amazon', 'OracleLinux': {
      $service_name       = 'elasticsearch'
      $service_hasrestart = true
      $service_hasstatus  = true
      $service_pattern    = $service_name
      $service_providers  = [ 'init' ]
      $defaults_location  = '/etc/sysconfig'
    }
    'Debian', 'Ubuntu': {
      $service_name       = 'elasticsearch'
      $service_hasrestart = true
      $service_hasstatus  = true
      $service_pattern    = $service_name
      $service_providers  = [ 'init' ]
      $defaults_location  = '/etc/default'
    }
    'Darwin': {
      $service_name       = 'FIXME/TODO'
      $service_hasrestart = true
      $service_hasstatus  = true
      $service_pattern    = $service_name
      $service_providers  = [ 'launchd' ]
      $defaults_location  = false
    }
    default: {
      fail("\"${module_name}\" provides no service parameters
            for \"${::operatingsystem}\"")
    }
  }

}
