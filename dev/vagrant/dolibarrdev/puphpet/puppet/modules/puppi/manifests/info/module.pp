# Define puppi::info::module
#
# This is a puppi info plugin that provides automatic info to modules
# It uses a default template puppi/info/module.erb that can be changed
# and adapted
#
# == Usage
# (Sample from Example42 apache module where there's wide use of
# qualified variables, note that you can provide direct values to it
# without using variables):
#
#  puppi::info::module { "apache":
#    packagename => "${apache::params::packagename}",
#    servicename => "${apache::params::servicename}",
#    processname => "${apache::params::processname}",
#    configfile  => "${apache::params::configfile}",
#    configdir   => "${apache::params::configdir}",
#    pidfile     => "${apache::params::pidfile}",
#    datadir     => "${apache::params::datadir}",
#    logfile     => "${apache::params::logfile}",
#    logdir      => "${apache::params::logdir}",
#    protocol    => "${apache::params::protocol}",
#    port        => "${apache::params::port}",
#    description => "What Puppet knows about apache" ,
#    run         => "httpd -V",
#  }
#
define puppi::info::module (
  $packagename    = '',
  $servicename    = '',
  $processname    = '',
  $configfile     = '',
  $configdir      = '',
  $initconfigfile = '',
  $pidfile        = '',
  $datadir        = '',
  $logfile        = '',
  $logdir         = '',
  $protocol       = '',
  $port           = '',
  $description    = '',
  $run            = '',
  $verbose        = 'no',
  $templatefile   = 'puppi/info/module.erb' ) {

  require puppi
  require puppi::params

  file { "${puppi::params::infodir}/${name}":
    ensure  => present,
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => template($templatefile),
    tag     => 'puppi_info',
  }

}
