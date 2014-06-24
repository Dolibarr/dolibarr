# Define puppi::run
#
# This define triggers a puppi deploy run directly during Puppet
# execution. It can be used to automate FIRST TIME applications
# deployments directly during the first Puppet execution
#
# == Variables
#
# [*name*]
#   The title/name you use has to be the name of an existing puppi::project
#   procedure define
#
# == Usage
# Basic Usage:
# puppi::run { "myapp": }
#
define puppi::run (
  $project = '',
  $timeout = 300) {

  require puppi

  exec { "Run_Puppi_${name}":
    command => "puppi deploy ${name}; [ $? -le \"1\" ] && touch ${puppi::params::archivedir}/puppirun_${name}",
    path    => '/bin:/sbin:/usr/sbin:/usr/bin',
    creates => "${puppi::params::archivedir}/puppirun_${name}",
    timeout => $timeout,
    # require => File[ tag == 'puppi_deploy' ],
  }

}
