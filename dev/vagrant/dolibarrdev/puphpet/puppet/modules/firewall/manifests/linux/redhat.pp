# = Class: firewall::linux::redhat
#
# Manages the `iptables` service on RedHat-alike systems.
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
class firewall::linux::redhat (
  $ensure = running,
  $enable = true
) {

  # RHEL 7 and later and Fedora 15 and later require the iptables-services
  # package, which provides the /usr/libexec/iptables/iptables.init used by
  # lib/puppet/util/firewall.rb.
  if $::operatingsystem == RedHat and $::operatingsystemrelease >= 7 {
    package { 'iptables-services':
      ensure => present,
    }
  }

  if ($::operatingsystem == 'Fedora' and (( $::operatingsystemrelease =~ /^\d+/ and $::operatingsystemrelease >= 15 ) or $::operatingsystemrelease == "Rawhide")) {
    package { 'iptables-services':
      ensure => present,
    }
  }

  service { 'iptables':
    ensure    => $ensure,
    enable    => $enable,
    hasstatus => true,
  }
}
