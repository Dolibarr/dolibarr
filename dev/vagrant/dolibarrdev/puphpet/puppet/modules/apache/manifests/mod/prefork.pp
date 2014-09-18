class apache::mod::prefork (
  $startservers        = '8',
  $minspareservers     = '5',
  $maxspareservers     = '20',
  $serverlimit         = '256',
  $maxclients          = '256',
  $maxrequestsperchild = '4000',
  $apache_version      = $::apache::apache_version,
) {
  if defined(Class['apache::mod::event']) {
    fail('May not include both apache::mod::prefork and apache::mod::event on the same node')
  }
  if defined(Class['apache::mod::itk']) {
    fail('May not include both apache::mod::prefork and apache::mod::itk on the same node')
  }
  if defined(Class['apache::mod::peruser']) {
    fail('May not include both apache::mod::prefork and apache::mod::peruser on the same node')
  }
  if defined(Class['apache::mod::worker']) {
    fail('May not include both apache::mod::prefork and apache::mod::worker on the same node')
  }
  File {
    owner => 'root',
    group => $::apache::params::root_group,
    mode  => '0644',
  }

  # Template uses:
  # - $startservers
  # - $minspareservers
  # - $maxspareservers
  # - $serverlimit
  # - $maxclients
  # - $maxrequestsperchild
  file { "${::apache::mod_dir}/prefork.conf":
    ensure  => file,
    content => template('apache/mod/prefork.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }

  case $::osfamily {
    'redhat': {
      if versioncmp($apache_version, '2.4') >= 0 {
        ::apache::mpm{ 'prefork':
          apache_version => $apache_version,
        }
      }
      else {
        file_line { '/etc/sysconfig/httpd prefork enable':
          ensure  => present,
          path    => '/etc/sysconfig/httpd',
          line    => '#HTTPD=/usr/sbin/httpd.worker',
          match   => '#?HTTPD=/usr/sbin/httpd.worker',
          require => Package['httpd'],
          notify  => Service['httpd'],
        }
      }
    }
    'debian', 'freebsd' : {
      ::apache::mpm{ 'prefork':
        apache_version => $apache_version,
      }
    }
    default: {
      fail("Unsupported osfamily ${::osfamily}")
    }
  }
}
