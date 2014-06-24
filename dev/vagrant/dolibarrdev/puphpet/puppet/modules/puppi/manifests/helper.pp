# Define puppi::helper
#
# The Puppi 2.0 define that creates an helper file that contains
# the commands to execute, for the different puppi actions, using
# the variables present in the datafile
#
# == Usage
# Basic Usage:
# puppi::helper { "myhelper":
#   template => 'myproject/puppi/helpers/myhelper.erb',
# }
#
define puppi::helper (
  $template,
  $ensure = 'present' ) {

  require puppi
  require puppi::params

  file { "puppi_helper_${name}":
    ensure  => $ensure,
    path    => "${puppi::params::helpersdir}/${name}.yml",
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => template($template),
  }

}
