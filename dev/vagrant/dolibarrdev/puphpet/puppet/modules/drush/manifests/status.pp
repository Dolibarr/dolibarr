define drush::status (
  $site_alias = $drush::params::site_alias,
  $options    = $drush::params::options,
  $arguments  = $drush::params::arguments,
  $site_path  = $drush::params::site_path,
  $drush_user = $drush::params::drush_user,
  $drush_home = $drush::params::drush_home,
  $log        = $drush::params::log
  ) {

  drush::run {"drush-status:${name}":
    command    => 'core-status',
    site_alias => $site_alias,
    options    => $options,
    arguments  => $arguments,
    site_path  => $site_path,
    drush_user => $drush_user,
    drush_home => $drush_home,
    log        => $log,
  }

}
