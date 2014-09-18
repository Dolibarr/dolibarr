$caller_module_name = 'demo'

class { 'staging':
  path => '/tmp/staging',
}

staging::file { 'sample.tar.gz':
  source => 'puppet:///modules/staging/sample.tar.gz'
}

staging::extract { 'sample.tar.gz':
  target  => '/tmp/staging',
  creates => '/tmp/staging/sample',
  require => Staging::File['sample.tar.gz'],
}

staging::file { 'sample.tar.bz2':
  source => 'puppet:///modules/staging/sample.tar.bz2'
}

staging::extract { 'sample.tar.bz2':
  target  => '/tmp/staging',
  creates => '/tmp/staging/sample-tar-bz2',
  require => Staging::File['sample.tar.bz2'],
}
