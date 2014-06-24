# PRIVATE CLASS: do not use directly
class postgresql::server::firewall {
  $ensure             = $postgresql::server::ensure
  $manage_firewall    = $postgresql::server::manage_firewall
  $firewall_supported = $postgresql::server::firewall_supported

  if ($manage_firewall and $firewall_supported) {
    if ($ensure == 'present' or $ensure == true) {
      # TODO: get rid of hard-coded port
      firewall { '5432 accept - postgres':
        port   => '5432',
        proto  => 'tcp',
        action => 'accept',
      }
    } else {
      firewall { '5432 accept - postgres':
        ensure => absent,
      }
    }
  }
}
