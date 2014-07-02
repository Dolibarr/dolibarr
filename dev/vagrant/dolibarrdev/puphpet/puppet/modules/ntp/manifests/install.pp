#
class ntp::install inherits ntp {

  package { 'ntp':
    ensure => $package_ensure,
    name   => $package_name,
  }

}
