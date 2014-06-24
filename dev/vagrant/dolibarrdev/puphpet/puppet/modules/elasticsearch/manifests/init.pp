# == Class: elasticsearch
#
# This class is able to install or remove elasticsearch on a node.
# It manages the status of the related service.
#
# === Parameters
#
# [*ensure*]
#   String. Controls if the managed resources shall be <tt>present</tt> or
#   <tt>absent</tt>. If set to <tt>absent</tt>:
#   * The managed software packages are being uninstalled.
#   * Any traces of the packages will be purged as good as possible. This may
#     include existing configuration files. The exact behavior is provider
#     dependent. Q.v.:
#     * Puppet type reference: {package, "purgeable"}[http://j.mp/xbxmNP]
#     * {Puppet's package provider source code}[http://j.mp/wtVCaL]
#   * System modifications (if any) will be reverted as good as possible
#     (e.g. removal of created users, services, changed log settings, ...).
#   * This is thus destructive and should be used with care.
#   Defaults to <tt>present</tt>.
#
# [*autoupgrade*]
#   Boolean. If set to <tt>true</tt>, any managed package gets upgraded
#   on each Puppet run when the package provider is able to find a newer
#   version than the present one. The exact behavior is provider dependent.
#   Q.v.:
#   * Puppet type reference: {package, "upgradeable"}[http://j.mp/xbxmNP]
#   * {Puppet's package provider source code}[http://j.mp/wtVCaL]
#   Defaults to <tt>false</tt>.
#
# [*status*]
#   String to define the status of the service. Possible values:
#   * <tt>enabled</tt>: Service is running and will be started at boot time.
#   * <tt>disabled</tt>: Service is stopped and will not be started at boot
#     time.
#   * <tt>running</tt>: Service is running but will not be started at boot time.
#     You can use this to start a service on the first Puppet run instead of
#     the system startup.
#   * <tt>unmanaged</tt>: Service will not be started at boot time and Puppet
#     does not care whether the service is running or not. For example, this may
#     be useful if a cluster management software is used to decide when to start
#     the service plus assuring it is running on the desired node.
#   Defaults to <tt>enabled</tt>. The singular form ("service") is used for the
#   sake of convenience. Of course, the defined status affects all services if
#   more than one is managed (see <tt>service.pp</tt> to check if this is the
#   case).
#
# [*version*]
#   String to set the specific version you want to install.
#   Defaults to <tt>false</tt>.
#
# [*restart_on_change*]
#   Boolean that determines if the application should be automatically restarted
#   whenever the configuration changes. Disabling automatic restarts on config
#   changes may be desired in an environment where you need to ensure restarts
#   occur in a controlled/rolling manner rather than during a Puppet run.
#
#   Defaults to <tt>true</tt>, which will restart the application on any config
#   change. Setting to <tt>false</tt> disables the automatic restart.
#
# [*confdir*]
#   Path to directory containing the elasticsearch configuration.
#   Use this setting if your packages deviate from the norm (/etc/elasticsearch)
#
# [*plugindir*]
#   Path to directory containing the elasticsearch plugins
#   Use this setting if your packages deviate from the norm (/usr/share/elasticsearch/plugins)
#
# [*plugintool*]
#   Path to directory containing the elasticsearch plugin installation script
#   Use this setting if your packages deviate from the norm (/usr/share/elasticsearch/bin/plugin)
#
# The default values for the parameters are set in elasticsearch::params. Have
# a look at the corresponding <tt>params.pp</tt> manifest file if you need more
# technical information about them.
#
# === Examples
#
# * Installation, make sure service is running and will be started at boot time:
#     class { 'elasticsearch': }
#
# * Removal/decommissioning:
#     class { 'elasticsearch':
#       ensure => 'absent',
#     }
#
# * Install everything but disable service(s) afterwards
#     class { 'elasticsearch':
#       status => 'disabled',
#     }
#
#
# === Authors
#
# * Richard Pijnenburg <mailto:richard@ispavailability.com>
#
class elasticsearch(
  $ensure              = $elasticsearch::params::ensure,
  $status              = $elasticsearch::params::status,
  $restart_on_change   = $elasticsearch::params::restart_on_change,
  $autoupgrade         = $elasticsearch::params::autoupgrade,
  $version             = false,
  $package_provider    = 'package',
  $package_url         = undef,
  $package_dir         = $elasticsearch::params::package_dir,
  $purge_package_dir   = $elasticsearch::params::purge_package_dir,
  $elasticsearch_user  = $elasticsearch::params::elasticsearch_user,
  $elasticsearch_group = $elasticsearch::params::elasticsearch_group,
  $purge_confdir       = $elasticsearch::params::purge_confdir,
  $service_provider    = 'init',
  $init_defaults       = undef,
  $init_defaults_file  = undef,
  $init_template       = undef,
  $config              = {},
  $confdir             = $elasticsearch::params::confdir,
  $datadir             = undef,
  $plugindir           = $elasticsearch::params::plugindir,
  $plugintool          = $elasticsearch::params::plugintool,
  $java_install        = false,
  $java_package        = undef
) inherits elasticsearch::params {

  anchor {'elasticsearch::begin': }
  anchor {'elasticsearch::end': }


  #### Validate parameters

  # ensure
  if ! ($ensure in [ 'present', 'absent' ]) {
    fail("\"${ensure}\" is not a valid ensure parameter value")
  }

  # autoupgrade
  validate_bool($autoupgrade)

  # service status
  if ! ($status in [ 'enabled', 'disabled', 'running', 'unmanaged' ]) {
    fail("\"${status}\" is not a valid status parameter value")
  }

  # restart on change
  validate_bool($restart_on_change)

  # purge conf dir
  validate_bool($purge_confdir)

  if ! ($service_provider in $elasticsearch::params::service_providers) {
    fail("\"${service_provider}\" is not a valid provider for \"${::operatingsystem}\"")
  }

  if ($package_url != undef and $version != false) {
    fail('Unable to set the version number when using package_url option.')
  }

  # validate config hash
  validate_hash($config)

  # java install validation
  validate_bool($java_install)

  #### Manage actions

  # package(s)
  class { 'elasticsearch::package': }

  # configuration
  class { 'elasticsearch::config': }

  # service(s)
  class { 'elasticsearch::service': }

  if $java_install == true {
    # Install java
    class { 'elasticsearch::java': }

    # ensure we first java java and then manage the service
    Anchor['elasticsearch::begin']
    -> Class['elasticsearch::java']
    -> Class['elasticsearch::service']
  }

  #### Manage relationships

  if $ensure == 'present' {

    # we need the software before configuring it
    Anchor['elasticsearch::begin']
    -> Class['elasticsearch::package']
    -> Class['elasticsearch::config']

    # we need the software and a working configuration before running a service
    Class['elasticsearch::package'] -> Class['elasticsearch::service']
    Class['elasticsearch::config']  -> Class['elasticsearch::service']

    Class['elasticsearch::service'] -> Anchor['elasticsearch::end']

  } else {

    # make sure all services are getting stopped before software removal
    Anchor['elasticsearch::begin']
    -> Class['elasticsearch::service']
    -> Class['elasticsearch::package']
    -> Anchor['elasticsearch::end']

  }

}
