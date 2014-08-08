# Define: php::pecl::config
#
# Configures pecl
#
# Usage:
# php::pecl::config { http_proxy: value => "myproxy:8080" }
#
define php::pecl::config (
  $value,
  $layer = 'user',
  $path  = '/usr/bin:/bin:/usr/sbin:/sbin'
  ) {

  include php::pear

  exec { "pecl-config-set-${name}":
    command => "pecl config-set ${name} ${value} ${layer}",
    path    => $path,
    unless  => "pecl config-get ${name} | grep ${value}",
    require => Package['php-pear'],
  }

}
