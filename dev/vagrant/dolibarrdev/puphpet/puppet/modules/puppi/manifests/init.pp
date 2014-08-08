# = Class: puppi
#
# This is Puppi NextGen
# Includes both first generation of Puppi and the
# NextGen developments and modules integration
#
# == Parameters
#
# [*version*]
#   Define the Puppi version to use:
#   1 - First generation of Puppi, compatible with old modules
#   2 - NextGen version. Intended to work with NextGen modules
#   Default: 1 , for the moment
#
# [*install_dependencies*]
#   Set to false if you want to manage the sofware puppi needs
#   with your local modules.
#
# [*template*]
#   Sets the path to a custom template for /etc/puppi/puppi.conf
#
# [*helpers_class*]
#   Name of the class there default helpers are defined
#   (Used on in Puppi 2)
#
# [*logs_retention_days*]
#   Number of days for retenton of puppi logs. Default 30
#   This option creates a script in /etc/cron.daily that purges
#   all the old logs. Set to false or to 0 to remove the purge script.
#
# [*extra_class*]
#   Name of the class where extra puppi resources are added
#   Here, by default are placed general system commands for
#   puppi info, check and log
#
class puppi (
  $version              = params_lookup( 'version' ),
  $install_dependencies = params_lookup( 'install_dependencies' ),
  $template             = params_lookup( 'template' ),
  $helpers_class        = params_lookup( 'helpers_class' ),
  $logs_retention_days  = params_lookup( 'logs_retention_days' ),
  $extra_class          = params_lookup( 'extra_class' )
  ) inherits puppi::params {

  $bool_install_dependencies=any2bool($install_dependencies)

  # Manage Version
  $puppi_ensure = $puppi::version ? {
    1 => '/usr/sbin/puppi.one',
    2 => '/usr/local/bin/puppi',
  }

  file { 'puppi.link':
    ensure => $puppi_ensure,
    path   => '/usr/sbin/puppi',
  }

  # Puppi version one is always installed
  include puppi::one

  # Puppi 2 gem (still experimental) is installed only when forced
  if $puppi::version == '2' {
    include puppi::two
  }

  # Create Puppi common dirs and scripts
  include puppi::skel

  # Include extra resources
  include $puppi::extra_class

  # Include some packages needed by Puppi
  if $bool_install_dependencies {
    include puppi::dependencies
  }

}
