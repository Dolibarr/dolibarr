vcsrepo { '/tmp/vcstest-hg-clone':
  ensure   => present,
  provider => hg,
  source   => 'http://hg.basho.com/riak',
  revision => 'riak-0.5.3',
}
