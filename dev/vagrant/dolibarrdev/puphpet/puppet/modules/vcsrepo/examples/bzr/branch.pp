vcsrepo { '/tmp/vcstest-bzr-branch':
  ensure   => present,
  provider => bzr,
  source   => 'lp:do',
  revision => '1312',
}
