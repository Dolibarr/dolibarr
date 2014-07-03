define drush::git (
  $path,
  $git_branch = '',
  $git_tag    = '',
  $git_repo   = false,
  $dir_name   = false,
  $update     = false,
  $paths      = $drush::params::paths,
  $user       = 'root',
  ) {

  # Default to the resource name if no explicit git repo is provided.
  if $git_repo { $real_git_repo = $git_repo }
  else         { $real_git_repo = $name }

  # Figure out the path and directory name.
  if $dir_name {
    $real_path = "${path}/${dir_name}"
    $real_dir  = $dir_name
  }
  else {
    # Figure out the name of the cloned into directory from the git repo.
    $repo_array   = split($real_git_repo, '[/]')
    $last_element = $repo_array[-1]
    $real_dir     = regsubst($last_element, '\.git$', '')
    $real_path    = "${path}/${real_dir}"
  }

  exec {"drush-clone-repo:${name}":
    command => "git clone ${real_git_repo} ${real_dir}",
    creates => $real_path,
    cwd     => $path,
    user    => $user,
    path    => $paths,
    timeout => 0,
  }

  # The specific (tag) overrides the general (branch).
  if $git_tag { $git_ref = $git_tag }
  else        { $git_ref = $git_branch }

  if $git_ref {
    exec {"drush-checkout-ref:${name}":
      command => "git checkout ${git_ref}",
      cwd     => $real_path,
      path    => $paths,
      require => Exec["drush-clone-repo:${name}"],
    }
  }

  if $update {
    exec {"drush-update-repo:${name}":
      command => 'git pull -r',
      cwd     => $real_path,
      path    => $paths,
      require => Exec["drush-clone-repo:${name}"],
    }
  }

}
