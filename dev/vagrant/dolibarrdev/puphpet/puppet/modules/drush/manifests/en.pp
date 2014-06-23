define drush::en (
  $site_alias  = $drush::params::site_alias,
  $options     = $drush::params::options,
  $arguments   = $drush::params::arguments,
  $drush_user  = $drush::params::drush_user,
  $drush_home  = $drush::params::drush_home,
  $log         = $drush::params::log,
  $refreshonly = false
  ) {

  if $arguments { $real_args = $arguments }
  else { $real_args = $name }

  drush::run {"drush-en:${name}":
    command     => 'pm-enable',
    site_alias  => $site_alias,
    options     => $options,
    arguments   => $real_args,
    drush_user  => $drush_user,
    drush_home  => $drush_home,
    refreshonly => $refreshonly,
    log         => $log,
    unless      => "drush ${site_alias} pm-list --status=enabled | grep ${name}",
  }

}
