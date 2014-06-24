# Define puppi::info
#
# This define creates a basic info file that simply contains a set
# of commands that show infos about custom topics.
# To be used by the puppi info command.
# By default it builds the info script based on the minimal puppi/info.erb
# template but you can choose a custom template.
# Other info defines are used to gather and create puppi info scripts with
# different arguments and contents.
# Check puppi/manifests/info/ for alternative puppi::info::  plugins
#
# == Usage:
# puppi::info { "network":
#   description => "Network status and information" ,
#   run  => [ "ifconfig" , "route -n" ],
# }
#
# :include:../README.info
#
define puppi::info (
  $description  = '',
  $templatefile = 'puppi/info.erb',
  $run          = '' ) {

  require puppi
  require puppi::params

  $array_run = is_array($run) ? {
    false     => $run ? {
      ''      => [],
      default => split($run, ','),
    },
    default   => $run,
  }

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
