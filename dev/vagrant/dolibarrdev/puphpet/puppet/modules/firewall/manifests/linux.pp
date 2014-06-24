# = Class: firewall::linux
#
# Installs the `iptables` package for Linux operating systems and includes
# the appropriate sub-class for any distribution specific services and
# additional packages.
#
# == Parameters:
#
# [*ensure*]
#   Ensure parameter passed onto Service[] resources. When `running` the
#   service will be started on boot, and when `stopped` it will not.
#   Default: running
#
class firewall::linux (
  $ensure = running
) {
  $enable = $ensure ? {
    running => true,
    stopped => false,
  }

  package { 'iptables':
    ensure => present,
  }

  case $::operatingsystem {
    'RedHat', 'CentOS', 'Fedora', 'Scientific', 'SL', 'SLC', 'Ascendos',
    'CloudLinux', 'PSBM', 'OracleLinux', 'OVS', 'OEL', 'Amazon', 'XenServer': {
      class { "${title}::redhat":
        ensure  => $ensure,
        enable  => $enable,
        require => Package['iptables'],
      }
    }
    'Debian', 'Ubuntu': {
      class { "${title}::debian":
        ensure  => $ensure,
        enable  => $enable,
        require => Package['iptables'],
      }
    }
    'Archlinux': {
      class { "${title}::archlinux":
        ensure  => $ensure,
        enable  => $enable,
        require => Package['iptables'],
      }
    }
    default: {}
  }
}
