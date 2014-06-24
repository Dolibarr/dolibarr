# Define puppi::log
#
# This define creates a basic log file that simply contains
# the list of logs to show when issuing the puppi log command.
#
# == Usage:
# puppi::log { "system":
#   description => "General System Logs" ,
#   log  => [ "/var/log/syslog" , "/var/log/messages" ],
# }
#
# :include:../README.log
#
define puppi::log (
  $log,
  $description = '' ) {

  require puppi
  require puppi::params

  $array_log = is_array($log) ? {
    false     => split($log, ','),
    default   => $log,
  }

  file { "${puppi::params::logsdir}/${name}":
    ensure  => 'present',
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => template('puppi/log.erb'),
    tag     => 'puppi_log',
  }

}
