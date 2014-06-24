# class mailcatcher::package
#
class mailcatcher::package {
  each( $mailcatcher::params::packages ) |$package| {
    if ! defined(Package[$package]) {
      package { $package:
        ensure => present
      }
    }
  }

  package { 'mailcatcher':
    ensure   => present,
    provider => 'gem',
    require  => Package[$mailcatcher::params::packages]
  }
}
