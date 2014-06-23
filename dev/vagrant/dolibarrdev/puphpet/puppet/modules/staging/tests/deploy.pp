staging::deploy { 'sample.tar.gz':
  source => 'puppet:///modules/staging/sample.tar.gz',
  target => '/usr/local',
}
