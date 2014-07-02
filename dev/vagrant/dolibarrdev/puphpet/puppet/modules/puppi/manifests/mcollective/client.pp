# = Class puppi::mcollective::client
#
# This class installs the mc puppi command for mcollective clients
# (Note that in mcollective terminology a client is an host from
# where you can manage mcollective servers)
#
# The class installs also the puppideploy and puppicheck commands
# that are simple wrappers about mco puppi that correctly trap
# remote errors and can be used in automatic procedures or
# to give limited access (typically via sudo) to mc puppi commands
#
# They can be integrated, for example, in Jenkins as remote ssh
# commands to manage deployments or tests
#
# == Usage:
# include puppi::mcollective::client
#
# :include:../README.mcollective
#
class puppi::mcollective::client {

  require puppi::params
  require puppi::mcollective::server

# OLD STYLE mc-puppi command
  file { '/usr/local/bin/mc-puppi':
    ensure  => 'present',
    mode    => '0755',
    owner   => 'root',
    group   => 'root',
    source  => 'puppet:///modules/puppi/mcollective/mc-puppi',
    require => Class['mcollective'],
    }

# mco application TODO
#  file { "${puppi::params::mcollective}/application/puppi.rb":
#    ensure  => 'present',
#    mode    => '0644',
#    owner   => 'root',
#    group   => 'root',
#    source  => 'puppet:///modules/puppi/mcollective/mcpuppi.rb',
#  }

  file { '/usr/bin/puppicheck':
    ensure  => 'present',
    mode    => '0750',
    owner   => $puppi::params::mcollective_user,
    group   => $puppi::params::mcollective_group,
    source  => 'puppet:///modules/puppi/mcollective/puppicheck',
  }

  file { '/usr/bin/puppideploy':
    ensure  => 'present',
    mode    => '0750',
    owner   => $puppi::params::mcollective_user,
    group   => $puppi::params::mcollective_group,
    source  => 'puppet:///modules/puppi/mcollective/puppideploy',
  }

}
