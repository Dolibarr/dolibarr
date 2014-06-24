# Class: apache::service
#
# Manages the Apache daemon
#
# Parameters:
#
# Actions:
#   - Manage Apache service
#
# Requires:
#
# Sample Usage:
#
#    sometype { 'foo':
#      notify => Class['apache::service'],
#    }
#
#
class apache::service (
  $service_name   = $::apache::params::service_name,
  $service_enable = true,
  $service_ensure = 'running',
) {
  # The base class must be included first because parameter defaults depend on it
  if ! defined(Class['apache::params']) {
    fail('You must include the apache::params class before using any apache defined resources')
  }
  validate_bool($service_enable)

  case $service_ensure {
    true, false, 'running', 'stopped': {
      $_service_ensure = $service_ensure
    }
    default: {
      $_service_ensure = undef
    }
  }

  service { 'httpd':
    ensure => $_service_ensure,
    name   => $service_name,
    enable => $service_enable,
  }
}
