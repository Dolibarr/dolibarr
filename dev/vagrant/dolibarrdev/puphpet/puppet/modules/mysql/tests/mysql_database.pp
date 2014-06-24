class { 'mysql::server':
  config_hash => {'root_password' => 'password'}
}
database{ ['test1', 'test2', 'test3']:
  ensure  => present,
  charset => 'utf8',
  require => Class['mysql::server'],
}
database{ 'test4':
  ensure  => present,
  charset => 'latin1',
}
