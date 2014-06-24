define drush::make (
  $makefile,
  $make_path  = false,
  $options    = $drush::params::options,
  $site_path  = $drush::params::site_path,
  $drush_user = $drush::params::drush_user,
  $drush_home = $drush::params::drush_home,
  $log        = $drush::params::log
  ) {

  if $make_path { $real_make_path = $make_path }
  else { $real_make_path = $name }
  $arguments = "${makefile} ${real_make_path}"

  drush::run {"drush-make:${name}":
    command    => 'make',
    creates    => $make_path,
    options    => $options,
    arguments  => $arguments,
    drush_user => $drush_user,
    drush_home => $drush_home,
    log        => $log,
    timeout    => 0,
  }

}
