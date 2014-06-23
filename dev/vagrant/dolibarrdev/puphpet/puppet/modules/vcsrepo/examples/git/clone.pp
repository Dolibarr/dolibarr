vcsrepo { '/tmp/vcstest-git-clone':
  ensure   => present,
  provider => git,
  source   => 'git://github.com/bruce/rtex.git',
}
