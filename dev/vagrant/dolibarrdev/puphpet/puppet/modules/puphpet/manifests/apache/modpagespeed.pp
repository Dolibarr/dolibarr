# This depends on puppetlabs/apache: https://github.com/puppetlabs/puppetlabs-apache

class puphpet::apache::modpagespeed (
  $url     = $puphpet::params::apache_mod_pagespeed_url,
  $package = $puphpet::params::apache_mod_pagespeed_package,
  $ensure  = 'present'
) {

  $download_location = $::osfamily ? {
    'Debian' => '/.puphpet-stuff/mod-pagespeed.deb',
    'Redhat' => '/.puphpet-stuff/mod-pagespeed.rpm'
  }

  $provider = $::osfamily ? {
    'Debian' => 'dpkg',
    'Redhat' => 'yum'
  }

  exec { "download apache mod-pagespeed to ${download_location}":
    creates => $download_location,
    command => "wget ${url} -O ${download_location}",
    timeout => 30,
    path    => '/usr/bin'
  }

  package { $package:
    ensure   => $ensure,
    provider => $provider,
    source   => $download_location,
    notify   => Service['httpd']
  }

  file { [
    "${apache::params::mod_dir}/pagespeed.load",
    "${apache::params::mod_dir}/pagespeed.conf",
    "${apache::params::confd_dir}/pagespeed_libraries.conf"
  ] :
    purge => false,
  }

  if $apache::params::mod_enable_dir != undef {
    file { [
      "${apache::params::mod_enable_dir}/pagespeed.load",
      "${apache::params::mod_enable_dir}/pagespeed.conf"
    ] :
      purge => false,
    }
  }

}
