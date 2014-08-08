# = Define: php::augeas
#
# Manage php.ini through augeas
#
# Here's an example how to find the augeas path to a variable:
#
#     # augtool --noload
#     augtool> rm /augeas/load
#     rm : /augeas/load 781
#     augtool> set /augeas/load/myfile/lens @PHP
#     augtool> set /augeas/load/myfile/incl /usr/local/etc/php5/cgi/php.ini
#     augtool> load
#     augtool> print
#     ...
#     /files/usr/local/etc/php5/cgi/php.ini/soap/soap.wsdl_cache_limit = "5"
#     /files/usr/local/etc/php5/cgi/php.ini/ldap/ldap.max_links = "-1"
#     ...
#     augtool> exit
#     #
#
# The part after 'php.ini/' is what you need to use as 'entry'.
#
# == Parameters
#
# [*entry*]
#   Augeas path to entry to be modified.
#
# [*ensure*]
#   Standard puppet ensure variable
#
# [*target*]
#   Which php.ini to manipulate. Default is $php::config_file
#
# [*value*]
#   Value to set
#
# == Examples
#
# php::augeas {
#   'php-memorylimit':
#     entry  => 'PHP/memory_limit',
#     value  => '128M';
#   'php-error_log':
#     entry  => 'PHP/error_log',
#     ensure => absent;
#   'php-sendmail_path':
#     entry  => 'mail function/sendmail_path',
#     value  => '/usr/sbin/sendmail -t -i -f info@example.com';
#   'php-date_timezone':
#     entry  => 'Date/date.timezone',
#     value  => 'Europe/Amsterdam';
# }
#
define php::augeas (
  $entry,
  $ensure = present,
  $target = $php::config_file,
  $value  = '',
  ) {

  include php

  $service = $php::service

  $changes = $ensure ? {
    present => [ "set '${entry}' '${value}'" ],
    absent  => [ "rm '${entry}'" ],
  }

  augeas { "php_ini-${name}":
    incl    => $target,
    lens    => 'Php.lns',
    changes => $changes,
  }

}
