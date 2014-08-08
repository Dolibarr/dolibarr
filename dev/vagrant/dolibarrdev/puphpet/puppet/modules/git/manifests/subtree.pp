# == Class: git::subtree
#
# Installs and configure git-subtree
#
class git::subtree {

  include ::git

  Package['git'] -> Class['git::subtree']

  if (versioncmp('1.7.0', $::git_version) > 0) {
    fail 'git-subtree requires git 1.7 or later!'
  }

  if (versioncmp('1.7.11', $::git_version) > 0) {
    $source_dir = '/usr/src/git-subtree'
    vcsrepo { $source_dir:
      ensure   => present,
      source   => 'http://github.com/apenwarr/git-subtree.git',
      provider => 'git',
      revision => '2793ee6ba',
    }
  } else {
    $source_dir = '/usr/share/doc/git-core/contrib/subtree'
  }

  exec { "/usr/bin/make prefix=/usr libexecdir=${::git_exec_path}":
    creates => "${source_dir}/git-subtree",
    cwd     => $source_dir,
  }
  ->
  exec { "/usr/bin/make prefix=/usr libexecdir=${::git_exec_path} install":
    creates => "${::git_exec_path}/git-subtree",
    cwd     => $source_dir,
  }

  file { '/etc/bash_completion.d/git-subtree':
    ensure => file,
    source => 'puppet:///modules/git/subtree/bash_completion.sh',
    mode   => '0644',
  }

}
