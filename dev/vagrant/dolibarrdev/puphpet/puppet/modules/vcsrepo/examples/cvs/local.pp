vcsrepo { '/tmp/vcstest-cvs-repo':
  ensure   => present,
  provider => cvs,
}

vcsrepo { '/tmp/vcstest-cvs-workspace-local':
  ensure   => present,
  provider => cvs,
  source   => '/tmp/vcstest-cvs-repo',
  require  => Vcsrepo['/tmp/vcstest-cvs-repo'],
}
