class apache::dev {
  if $::osfamily == 'FreeBSD' and !defined(Class['apache::package']) {
    fail('apache::dev requires apache::package; please include apache or apache::package class first')
  }
  include ::apache::params
  $packages = $::apache::params::dev_packages
  package { $packages:
    ensure  => present,
    require => Package['httpd'],
  }
}
