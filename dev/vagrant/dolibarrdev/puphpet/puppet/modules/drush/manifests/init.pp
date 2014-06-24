class drush (
  $api    = $drush::params::api,
  $dist   = $drush::params::dist,
  $ensure = $drush::params::ensure
  ) inherits drush::params {

  include drush::params

  package { 'drush':
    ensure  => $ensure,
  }

  case $operatingsystem {
    /^(Debian|Ubuntu)$/: {
      include drush::apt
      Package['drush'] { require => Exec['drush_update_apt'] }
    }
  }

  if $dist {

    Package['drush'] { require => Class['drush::apt'] }

    if $api == 4 { $backports = 'squeeze' }
    else { $backports = '' }

    class {'drush::apt':
      dist => $dist,
      backports => $backports,
    }
  }
}

