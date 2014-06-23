class drush::params {

  case $::operatingsystem {
    'centos', 'redhat': {
      $php_cli_package = 'php-cli'
    }
    'ubuntu', 'debian': {
      $php_cli_package = 'php5-cli'
    }
    default: {
      fail('The puppet-drush module only supports RHEL and Debian systems')
    }
  }

  $drush_user = 'root'
  $drush_home = '/root'
  $site_alias = '@none'
  $options    = ''
  $arguments  = ''
  $api        = 5
  $dist       = false
  $ensure     = 'present'
  $site_path  = false
  $log        = false
  $creates    = false
  $paths      = [ '/bin/', '/sbin/' , '/usr/bin/', '/usr/sbin/' ]

  if defined(Class['drush::git::drush']) {
    $installed = Class['drush::git::drush']
  }
  else {
    $installed = Class['drush']
  }

}
