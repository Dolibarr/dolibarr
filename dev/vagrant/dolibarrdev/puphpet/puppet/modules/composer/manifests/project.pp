# == Type: composer::project
#
# Installs a given project with composer create-project
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
#   The Package name of the PHP CLI package.
#
# [*user*]
#   The user name to exec the composer commands as. Default is undefined.
#
# === Authors
#
# Thomas Ploch <profiploch@gmail.com>
#
# === Copyright
#
# Copyright 2013 Thomas Ploch
#
define composer::project(
  $project_name,
  $target_dir,
  $version        = undef,
  $dev            = false,
  $prefer_source  = false,
  $stability      = 'dev',
  $repository_url = undef,
  $keep_vcs       = false,
  $tries          = 3,
  $timeout        = 1200,
  $user           = undef,
) {
  require git
  require composer

  Exec {
    path        => "/bin:/usr/bin/:/sbin:/usr/sbin:${composer::target_dir}",
    environment => "COMPOSER_HOME=${composer::composer_home}",
    user        => $user,
  }

  $exec_name    = "composer_create_project_${title}"
  $base_command = "${composer::php_bin} ${composer::target_dir}/${composer::composer_file} --stability=${stability}"
  $end_command  = "${project_name} ${target_dir}"

  $dev_arg = $dev ? {
    true    => ' --dev',
    default => '',
  }

  $vcs = $keep_vcs? {
    true    => ' --keep-vcs',
    default => '',
  }

  $repo = $repository_url? {
    undef   => '',
    default => " --repository-url=${repository_url}",
  }

  $pref_src = $prefer_source? {
    true  => ' --prefer-source',
    false => ''
  }

  $v = $version? {
    undef   => '',
    default => " ${version}",
  }

  exec { $exec_name:
    command => "${base_command}${dev_arg}${repo}${pref_src}${vcs} create-project ${end_command}${v}",
    tries   => $tries,
    timeout => $timeout,
    creates => $target_dir,
  }
}
