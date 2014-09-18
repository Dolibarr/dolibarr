# = Class: firewall::linux::debian
#
# Installs the `iptables-persistent` package for Debian-alike systems. This
# allows rules to be stored to file and restored on boot.
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
class firewall::linux::debian (
  $ensure = running,
  $enable = true
) {
  package { 'iptables-persistent':
    ensure => present,
  }

  if($::operatingsystemrelease =~ /^6\./ and $enable == true
  and versioncmp($::iptables_persistent_version, '0.5.0') < 0 ) {
    # This fixes a bug in the iptables-persistent LSB headers in 6.x, without it
    # we lose idempotency
    exec { 'iptables-persistent-enable':
      logoutput => on_failure,
      command   => '/usr/sbin/update-rc.d iptables-persistent enable',
      unless    => '/usr/bin/test -f /etc/rcS.d/S*iptables-persistent',
      require   => Package['iptables-persistent'],
    }
  } else {
    # This isn't a real service/daemon. The start action loads rules, so just
    # needs to be called on system boot.
    service { 'iptables-persistent':
      ensure    => undef,
      enable    => $enable,
      hasstatus => true,
      require   => Package['iptables-persistent'],
    }
  }
}
