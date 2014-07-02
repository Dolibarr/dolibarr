# Define puppi::check
#
# This define creates a file with a check command that can be used locally.
# It uses Nagios plugins for all checks so that $command is just the
# plugin name with its arguments
#
# == Usage
# Basic Usage:
# puppi::check { "checkname":
#   command => "check_tcp -H localhost -p 80"
# }
#
# :include:../README.check
#
define puppi::check (
  $command,
  $base_dir = '',
  $hostwide = 'no',
  $priority = '50',
  $project  = 'default',
  $enable   = true ) {

  require puppi
  require puppi::params

  $ensure = bool2ensure($enable)
  $bool_hostwide = any2bool($hostwide)

  $real_base_dir = $base_dir ? {
    ''      => $puppi::params::checkpluginsdir,
    default => $base_dir,
  }

  $path = $bool_hostwide ? {
    true  => "${puppi::params::checksdir}/${priority}-${name}" ,
    false => "${puppi::params::projectsdir}/${project}/check/${priority}-${name}",
  }

  file { "Puppi_check_${project}_${priority}_${name}":
    ensure  => $ensure,
    path    => $path,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => "${real_base_dir}/${command}\n",
    tag     => 'puppi_check',
  }

}
