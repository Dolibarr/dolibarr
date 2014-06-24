concat { '/tmp/concat':
  ensure => present,
  force  => true,
  owner  => 'root',
  group  => 'root',
  mode   => '0644',
}
