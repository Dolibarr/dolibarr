vcsrepo { '/tmp/vcstest-svn-checkout':
  ensure   => present,
  provider => svn,
  source   => 'http://svn.edgewall.org/repos/babel/trunk',
}
