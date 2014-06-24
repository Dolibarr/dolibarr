# = Class puppi::mcollective::server
#
# This class installs the puppi agent on mcollective servers
# (Note that in mcollective terminology a server is an host
# managed by a mcollective client)
#
# == Usage:
# include puppi::mcollective::server
#
# :include:../README.mcollective
#
class puppi::mcollective::server {

  require puppi::params

  file { "${puppi::params::mcollective}/agent/puppi.ddl":
    ensure  => 'present',
    mode    => '0644',
    owner   => 'root',
    group   => 'root',
    source  => 'puppet:///modules/puppi/mcollective/puppi.ddl',
    require => Class['mcollective'],
  }

  file { "${puppi::params::mcollective}/agent/puppi.rb":
    ensure  => 'present',
    mode    => '0644',
    owner   => 'root',
    group   => 'root',
    source  => 'puppet:///modules/puppi/mcollective/puppi.rb',
    require => Class['mcollective'],
  }

}
