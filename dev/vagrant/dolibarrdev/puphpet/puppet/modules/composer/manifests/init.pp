# == Class: composer
#
# The parameters for the composer class and corresponding definitions
#
# === Parameters
#
# Document parameters here.
#
# [*target_dir*]
#   The target dir that composer should be installed to.
#   Defaults to ```/usr/local/bin```.
#
# [*composer_file*]
#   The name of the composer binary, which will reside in ```target_dir```.
#
# [*download_method*]
#   Either ```curl``` or ```wget```.
#
# [*logoutput*]
#   If the output should be logged. Defaults to FALSE.
#
# [*tmp_path*]
#   Where the composer.phar file should be temporarily put.
#
# [*php_package*]
#   The Package name of tht PHP CLI package.
#
# [*curl_package*]
#   The name of the curl package to override the default set in the
#   composer::params class.
#
# [*wget_package*]
#   The name of the wget package to override the default set in the
#   composer::params class.
#
# [*composer_home*]
#   Folder to use as the COMPOSER_HOME environment variable. Default comes
#   from our composer::params class which derives from our own $composer_home
#   fact. The fact returns the current users $HOME environment variable.
#
# [*php_bin*]
#   The name or path of the php binary to override the default set in the
#   composer::params class.
#
# === Authors
#
# Thomas Ploch <profiploch@gmail.com>
#
class composer(
  $target_dir      = $composer::params::target_dir,
  $composer_file   = $composer::params::composer_file,
  $download_method = $composer::params::download_method,
  $logoutput       = $composer::params::logoutput,
  $tmp_path        = $composer::params::tmp_path,
  $php_package     = $composer::params::php_package,
  $curl_package    = $composer::params::curl_package,
  $wget_package    = $composer::params::wget_package,
  $composer_home   = $composer::params::composer_home,
  $php_bin         = $composer::params::php_bin,
  $suhosin_enabled = $composer::params::suhosin_enabled
) inherits composer::params {

  Exec { path => "/bin:/usr/bin/:/sbin:/usr/sbin:${target_dir}" }

  if defined(Package[$php_package]) == false {
    package { $php_package: ensure => present, }
  }

  # download composer
  case $download_method {
    'curl': {
      $download_command = "curl -s http://getcomposer.org/installer | ${composer::php_bin}"
      $download_require = $suhosin_enabled ? {
        true  => [ Package['curl', $php_package], Augeas['allow_url_fopen', 'whitelist_phar'] ],
        false => [ Package['curl', $php_package] ]
      }
      $method_package = $curl_package
    }
    'wget': {
      $download_command = 'wget http://getcomposer.org/composer.phar -O composer.phar'
      $download_require = $suhosin_enabled ? {
        true  => [ Package['wget', $php_package], Augeas['allow_url_fopen', 'whitelist_phar'] ],
        false => [ Package['wget', $php_package] ]
      }
      $method_package = $wget_package
    }
    default: {
      fail("The param download_method ${download_method} is not valid. Please set download_method to curl or wget.")
    }
  }

  if defined(Package[$method_package]) == false {
    package { $method_package: ensure => present, }
  }

  exec { 'download_composer':
    command   => $download_command,
    cwd       => $tmp_path,
    require   => $download_require,
    creates   => "${tmp_path}/composer.phar",
    logoutput => $logoutput,
  }

  # check if directory exists
  file { $target_dir:
    ensure => directory,
  }

  # move file to target_dir
  file { "${target_dir}/${composer_file}":
    ensure  => present,
    source  => "${tmp_path}/composer.phar",
    require => [ Exec['download_composer'], File[$target_dir] ],
    mode    => 0755,
  }

  if $suhosin_enabled {
    case $family {

      'Redhat','Centos': {

        # set /etc/php5/cli/php.ini/suhosin.executor.include.whitelist = phar
        augeas { 'whitelist_phar':
          context     => '/files/etc/suhosin.ini/suhosin',
          changes     => 'set suhosin.executor.include.whitelist phar',
          require     => Package[$php_package],
        }

        # set /etc/cli/php.ini/PHP/allow_url_fopen = On
        augeas{ 'allow_url_fopen':
          context     => '/files/etc/php.ini/PHP',
          changes     => 'set allow_url_fopen On',
          require     => Package[$php_package],
        }

      }
     'Debian': {

        # set /etc/php5/cli/php.ini/suhosin.executor.include.whitelist = phar
        augeas { 'whitelist_phar':
          context     => '/files/etc/php5/conf.d/suhosin.ini/suhosin',
          changes     => 'set suhosin.executor.include.whitelist phar',
          require     => Package[$php_package],
        }

        # set /etc/php5/cli/php.ini/PHP/allow_url_fopen = On
        augeas{ 'allow_url_fopen':
          context     => '/files/etc/php5/cli/php.ini/PHP',
          changes     => 'set allow_url_fopen On',
          require     => Package[$php_package],
        }

      }
    }
  }
}
