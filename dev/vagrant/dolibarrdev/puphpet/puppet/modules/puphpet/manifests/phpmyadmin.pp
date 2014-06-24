# This depends on
#   puppetlabs/apt: https://github.com/puppetlabs/puppetlabs-apt

class puphpet::phpmyadmin(
  $dbms             = 'mysql::server',
  $webroot_location = '/var/www'
) inherits puphpet::params {

  if $::osfamily == 'debian' {
    if $::operatingsystem == 'ubuntu' {
      apt::key { '80E7349A06ED541C': key_server => 'hkp://keyserver.ubuntu.com:80' }
      apt::ppa { 'ppa:nijel/phpmyadmin': require => Apt::Key['80E7349A06ED541C'] }
    }

    $phpMyAdmin_package = 'phpmyadmin'
    $phpMyAdmin_folder  = 'phpmyadmin'
  } elsif $::osfamily == 'redhat' {
    $phpMyAdmin_package = 'phpMyAdmin.noarch'
    $phpMyAdmin_folder  = 'phpMyAdmin'
  } else {
    error('phpMyAdmin module currently only works with Debian or RHEL families')
  }

  if ! defined(Package[$phpMyAdmin_package]) {
    package { $phpMyAdmin_package:
      require => Class[$dbms]
    }
  }

  if ! defined(File[$webroot_location]) {
    file { $webroot_location:
      ensure  => directory,
      require => Package[$phpMyAdmin_package]
    }
  }

  exec { 'cp phpmyadmin to webroot':
    command => "cp -LR /usr/share/${phpMyAdmin_folder} ${webroot_location}/phpmyadmin",
    onlyif  => "test ! -d ${mysql_pma_webroot_location}/phpmyadmin",
    require => [
      Package[$phpMyAdmin_package],
      File[$webroot_location]
    ]
  }

}
