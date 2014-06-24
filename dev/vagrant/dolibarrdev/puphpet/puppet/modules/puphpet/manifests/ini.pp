# Defines where we can expect PHP ini files and paths to be located.
#
# Different OS, OS version, webserver and PHP versions all contributes
# to making a mess of where we can expect INI files to be found.
#
# I have listed a bunch of places:
#
# 5.3
#     DEBIAN 6 - squeeze
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d
#     DEBIAN 7 - wheezy
#         APACHE
#             NOT CORRECT; PHP 5.4 INSTALLED
#         NGINX
#             NOT CORRECT; PHP 5.4 INSTALLED
#     UBUNTU 10.04 - lucid
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d
#     UBUNTU 12.04 - precise
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d
#     UBUNTU 14.04 - trusty
#         APACHE
#             NOT CORRECT; PHP 5.5 INSTALLED
#         NGINX
#             NOT CORRECT; PHP 5.5 INSTALLED
# 5.4
#     CENTOS 6
#         CLI
#             /etc/php.d
#         APACHE
#             /etc/php.d
#     DEBIAN 6 - squeeze
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  mods-available/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/  mods-available/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#     DEBIAN 7 - wheezy
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  mods-available/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/  mods-available/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#     UBUNTU 10.04 - lucid
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  mods-available/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/  mods-available/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#     UBUNTU 12.04 - precise
#         CLI
#             /etc/php5/cli/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         APACHE
#             FOLDERS: apache2/  cli/  conf.d/  mods-available/  php.ini
#             /etc/php5/apache2/conf.d    -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  conf.d/  fpm/  mods-available/
#             /etc/php5/fpm/conf.d        -> /etc/php5/conf.d/*   -> /etc/php5/mods-available/*
#     UBUNTU 14.04 - trusty
#         APACHE
#             NOT CORRECT; PHP 5.5 INSTALLED
#         NGINX
#             NOT CORRECT; PHP 5.5 INSTALLED
# 5.5
#     DEBIAN 6 - squeeze
#         APACHE
#             NOT A VALID OPTION
#         NGINX
#             NOT A VALID OPTION
#     DEBIAN 7 - wheezy
#         CLI
#             /etc/php5/cli/conf.d/*      -> /etc/php5/mods-available/*
#         APACHE
#             FOLDERS: apache2/  cli/  mods-available/  php.ini
#             /etc/php5/apache2/conf.d/*  -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  fpm/  mods-available/
#             /etc/php5/fpm/conf.d/*      -> /etc/php5/mods-available/*
#     UBUNTU 10.04 - lucid
#         APACHE
#             NOT A VALID OPTION
#         NGINX
#             NOT A VALID OPTION
#     UBUNTU 12.04 - precise
#         CLI
#             /etc/php5/cli/conf.d/*      -> /etc/php5/mods-available/*
#         APACHE
#             FOLDERS: cli/  apache2/  mods-available/
#             /etc/php5/apache2/conf.d/*      -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  fpm/  mods-available/
#             /etc/php5/fpm/conf.d/*      -> /etc/php5/mods-available/*
#     UBUNTU 14.04 - trusty
#         CLI
#             /etc/php5/cli/conf.d/*      -> /etc/php5/conf.d/*
#         APACHE
#             FOLDERS: apache2/  cli/  mods-available/  php.ini
#             /etc/php5/cli/conf.d/*      -> /etc/php5/mods-available/*
#             /etc/php5/apache2/conf.d/*  -> /etc/php5/mods-available/*
#         NGINX
#             FOLDERS: cli/  fpm/  mods-available/
#             /etc/php5/cli/conf.d/*      -> /etc/php5/mods-available/*
#             /etc/php5/fpm/conf.d/*      -> /etc/php5/mods-available/*
#
# This depends on example42/puppet-php: https://github.com/example42/puppet-php
#
define puphpet::ini (
  $php_version,
  $webserver,
  $ini_filename = 'zzzz_custom.ini',
  $entry,
  $value  = '',
  $ensure = present
  ) {

  $real_webserver = $webserver ? {
    'apache'   => 'apache2',
    'httpd'    => 'apache2',
    'apache2'  => 'apache2',
    'nginx'    => 'fpm',
    'php5-fpm' => 'fpm',
    'php-fpm'  => 'fpm',
    'fpm'      => 'fpm',
    'cgi'      => 'cgi',
    'fcgi'     => 'cgi',
    'fcgid'    => 'cgi',
    undef      => undef,
  }

  case $php_version {
    '5.3', '53': {
      case $::osfamily {
        'debian': {
          $target_dir  = '/etc/php5/conf.d'
          $target_file = "${target_dir}/${ini_filename}"

          if ! defined(File[$target_file]) {
            file { $target_file:
              replace => no,
              ensure  => present,
              require => Package['php']
            }
          }
        }
        'redhat': {
          $target_dir  = '/etc/php.d'
          $target_file = "${target_dir}/${ini_filename}"

          if ! defined(File[$target_file]) {
            file { $target_file:
              replace => no,
              ensure  => present,
              require => Package['php']
            }
          }
        }
        default: { fail('This OS has not yet been defined for PHP 5.3!') }
      }
    }
    '5.4', '54': {
      case $::osfamily {
        'debian': {
          $target_dir  = '/etc/php5/mods-available'
          $target_file = "${target_dir}/${ini_filename}"

          if ! defined(File[$target_file]) {
            file { $target_file:
              replace => no,
              ensure  => present,
              require => Package['php']
            }
          }

          $symlink = "/etc/php5/conf.d/${ini_filename}"

          if ! defined(File[$symlink]) {
            file { $symlink:
              ensure  => link,
              target  => $target_file,
              require => File[$target_file],
            }
          }
        }
        'redhat': {
          $target_dir  = '/etc/php.d'
          $target_file = "${target_dir}/${ini_filename}"

          if ! defined(File[$target_file]) {
            file { $target_file:
              replace => no,
              ensure  => present,
              require => Package['php']
            }
          }
        }
        default: { fail('This OS has not yet been defined for PHP 5.4!') }
      }
    }
    '5.5', '55': {
      case $::osfamily {
        'debian': {
          $target_dir  = '/etc/php5/mods-available'
          $target_file = "${target_dir}/${ini_filename}"

          $webserver_ini_location = $real_webserver ? {
              'apache2' => '/etc/php5/apache2/conf.d',
              'cgi'     => '/etc/php5/cgi/conf.d',
              'fpm'     => '/etc/php5/fpm/conf.d',
              undef     => undef,
          }
          $cli_ini_location = '/etc/php5/cli/conf.d'

          if ! defined(File[$target_file]) {
            file { $target_file:
              replace => no,
              ensure  => present,
              require => Package['php']
            }
          }

          if $webserver_ini_location != undef and ! defined(File["${webserver_ini_location}/${ini_filename}"]) {
            file { "${webserver_ini_location}/${ini_filename}":
              ensure  => link,
              target  => $target_file,
              require => File[$target_file],
            }
          }

          if ! defined(File["${cli_ini_location}/${ini_filename}"]) {
            file { "${cli_ini_location}/${ini_filename}":
              ensure  => link,
              target  => $target_file,
              require => File[$target_file],
            }
          }
        }
        'redhat': {
          $target_dir  = '/etc/php.d'
          $target_file = "${target_dir}/${ini_filename}"

          if ! defined(File[$target_file]) {
            file { $target_file:
              replace => no,
              ensure  => present,
              require => Package['php']
            }
          }
        }
        default: { fail('This OS has not yet been defined for PHP 5.5!') }
      }
    }
    default: { fail('Unrecognized PHP version') }
  }

  if $real_webserver != undef {
    if $real_webserver == 'cgi' {
      $webserver_service = 'apache2'
    } else {
      $webserver_service = $webserver
    }

    $notify_service = Service[$webserver_service]
  } else {
    $notify_service = []
  }

  php::augeas{ "${entry}-${value}" :
    target  => $target_file,
    entry   => $entry,
    value   => $value,
    ensure  => $ensure,
    require => File[$target_file],
    notify  => $notify_service,
  }

}
