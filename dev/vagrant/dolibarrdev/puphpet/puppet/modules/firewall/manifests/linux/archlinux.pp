# = Class: firewall::linux::archlinux
#
# Manages `iptables` and `ip6tables` services, and creates files used for
# persistence, on Arch Linux systems.
#
# == Parameters:
#
# [*ensure*]
#   Ensure parameter passed onto Service[] resources.
#   Default: running
#
# [*enable*]
#   Enable parameter passed onto Service[] resources.
#   Default: true
#
class firewall::linux::archlinux (
  $ensure = 'running',
  $enable = true
) {
  service { 'iptables':
    ensure    => $ensure,
    enable    => $enable,
    hasstatus => true,
  }

  service { 'ip6tables':
    ensure    => $ensure,
    enable    => $enable,
    hasstatus => true,
  }

  file { '/etc/iptables/iptables.rules':
    ensure => present,
    before => Service['iptables'],
  }

  file { '/etc/iptables/ip6tables.rules':
    ensure => present,
    before => Service['ip6tables'],
  }
}
