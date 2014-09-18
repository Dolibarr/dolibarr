define drush::run (
  $command     = false,
  $site_alias  = $drush::params::site_alias,
  $options     = $drush::params::options,
  $arguments   = $drush::params::arguments,
  $site_path   = $drush::params::site_path,
  $drush_user  = $drush::params::drush_user,
  $drush_home  = $drush::params::drush_home,
  $log         = $drush::params::log,
  $installed   = $drush::params::installed,
  $creates     = $drush::params::creates,
  $paths       = $drush::params::paths,
  $timeout     = false,
  $unless      = false,
  $onlyif      = false,
  $refreshonly = false
  ) {

  if $log { $log_output = " >> ${log} 2>&1" }

  if $command { $real_command = $command }
  else { $real_command = $name}

  exec {"drush-run:${name}":
    command     => "drush ${site_alias} --yes ${options} ${real_command} ${arguments} ${log_output}",
    user        => $drush_user,
    group       => $drush_user,
    path        => $paths,
    environment => "HOME=${drush_home}",
    require     => $installed,
  }

  if $site_path {
    Exec["drush-run:${name}"] { cwd => $site_path }
  }

  if $creates {
    Exec["drush-run:${name}"] { creates => $creates }
  }

  if $timeout {
    Exec["drush-run:${name}"] { timeout => $timeout }
  }

  if $unless {
    Exec["drush-run:${name}"] { unless => $unless }
  }

  if $onlyif {
    Exec["drush-run:${name}"] { onlyif => $onlyif }
  }

  if $refreshonly {
    Exec["drush-run:${name}"] { refreshonly => $refreshonly }
  }

}
