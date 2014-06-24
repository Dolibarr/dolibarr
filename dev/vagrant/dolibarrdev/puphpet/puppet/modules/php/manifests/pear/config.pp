# Define: php::pear::config
#
# Configures pear
#
# Usage:
# php::pear::config { http_proxy: value => "myproxy:8080" }
#
define php::pear::config ($value) {

  include php::pear

  exec { "pear-config-set-${name}":
    command => "pear config-set ${name} ${value}",
    path    => $php::pear::path,
    unless  => "pear config-get ${name} | grep ${value}",
    require => Package['php-pear'],
  }

}
