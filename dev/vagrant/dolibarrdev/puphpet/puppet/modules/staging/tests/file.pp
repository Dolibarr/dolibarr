$caller_module_name = 'demo'

class { 'staging':
  path => '/tmp/staging',
}

staging::file { 'sample':
  source => 'puppet:///modules/staging/sample',
}

staging::file { 'passwd':
  source => '/etc/passwd',
}

staging::file { 'manpage.html':
  source => 'http://curl.haxx.se/docs/manpage.html',
}
