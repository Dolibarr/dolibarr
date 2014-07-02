# This depends on puppetlabs/apache: https://github.com/puppetlabs/puppetlabs-apache

class puphpet::apache::modspdy (
  $url         = $puphpet::params::apache_mod_spdy_url,
  $package     = $puphpet::params::apache_mod_spdy_package,
  $php_package = 'php',
  $ensure      = 'present'
) {

  class { 'apache::mod::php':
    package_ensure => 'purged'
  }

  if $php_package == 'php' and ! defined(Package[$puphpet::params::apache_mod_spdy_cgi]) {
    package { $puphpet::params::apache_mod_spdy_cgi:
      ensure => present,
      notify => Service[$apache::params::service_name]
    }
  }

  if ! defined(Class['apache::mod::fastcgi']) {
    class { 'apache::mod::fastcgi': }
  }

  if ! defined(Class['apache::mod::cgi']) {
    class { 'apache::mod::cgi': }
  }

  $download_location = $::osfamily ? {
    'Debian' => '/.puphpet-stuff/mod-spdy.deb',
    'Redhat' => '/.puphpet-stuff/mod-spdy.rpm'
  }

  $provider = $::osfamily ? {
    'Debian' => 'dpkg',
    'Redhat' => 'yum'
  }

  exec { "download apache mod-spdy to ${download_location}":
    creates => $download_location,
    command => "wget ${url} -O ${download_location}",
    timeout => 30,
    path    => '/usr/bin'
  }

  package { $package:
    ensure   => $ensure,
    provider => $provider,
    source   => $download_location,
    notify   => Service[$apache::params::service_name]
  }

  file { [
    "${apache::params::mod_dir}/spdy.load",
    "${apache::params::mod_dir}/spdy.conf",
    "${apache::params::mod_dir}/php5filter.conf"
  ] :
    purge => false,
  }

  if $apache::params::mod_enable_dir != undef {
    file { [
      "${apache::params::mod_enable_dir}/spdy.load",
      "${apache::params::mod_enable_dir}/spdy.conf",
      "${apache::params::mod_enable_dir}/php5filter.conf"
    ] :
      purge => false,
    }
  }

  file { "${apache::params::confd_dir}/spdy.conf":
    content => template("${module_name}/apache/mod/spdy/spdy_conf.erb"),
    ensure  => $ensure,
    purge   => false,
    require => Package[$package]
  }

  file { '/usr/local/bin/php-wrapper':
    content => template("${module_name}/apache/mod/spdy/php-wrapper.erb"),
    ensure  => $ensure,
    mode    => 0775,
    purge   => false,
    require => Package[$package]
  }

}
