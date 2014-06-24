class apache::mod::proxy_html {
  Class['::apache::mod::proxy'] -> Class['::apache::mod::proxy_html']
  Class['::apache::mod::proxy_http'] -> Class['::apache::mod::proxy_html']

  # Add libxml2
  case $::osfamily {
    /RedHat|FreeBSD/: {
      ::apache::mod { 'xml2enc': }
      $loadfiles = undef
    }
    'Debian': {
      $gnu_path = $::hardwaremodel ? {
        'i686'  => 'i386',
        default => $::hardwaremodel,
      }
      $loadfiles = $::apache::params::distrelease ? {
        '6'     => ['/usr/lib/libxml2.so.2'],
        '10'    => ['/usr/lib/libxml2.so.2'],
        default => ["/usr/lib/${gnu_path}-linux-gnu/libxml2.so.2"],
      }
    }
  }

  ::apache::mod { 'proxy_html':
    loadfiles => $loadfiles,
  }

  # Template uses $icons_path
  file { 'proxy_html.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/proxy_html.conf",
    content => template('apache/mod/proxy_html.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
