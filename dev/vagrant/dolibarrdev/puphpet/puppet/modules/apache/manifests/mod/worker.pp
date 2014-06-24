class apache::mod::worker (
  $startservers        = '2',
  $maxclients          = '150',
  $minsparethreads     = '25',
  $maxsparethreads     = '75',
  $threadsperchild     = '25',
  $maxrequestsperchild = '0',
  $serverlimit         = '25',
  $threadlimit         = '64',
  $apache_version      = $::apache::apache_version,
) {
  if defined(Class['apache::mod::event']) {
    fail('May not include both apache::mod::worker and apache::mod::event on the same node')
  }
  if defined(Class['apache::mod::itk']) {
    fail('May not include both apache::mod::worker and apache::mod::itk on the same node')
  }
  if defined(Class['apache::mod::peruser']) {
    fail('May not include both apache::mod::worker and apache::mod::peruser on the same node')
  }
  if defined(Class['apache::mod::prefork']) {
    fail('May not include both apache::mod::worker and apache::mod::prefork on the same node')
  }
  File {
    owner => 'root',
    group => $::apache::params::root_group,
    mode  => '0644',
  }

  # Template uses:
  # - $startservers
  # - $maxclients
  # - $minsparethreads
  # - $maxsparethreads
  # - $threadsperchild
  # - $maxrequestsperchild
  # - $serverlimit
  # - $threadLimit
  file { "${::apache::mod_dir}/worker.conf":
    ensure  => file,
    content => template('apache/mod/worker.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }

  case $::osfamily {
    'redhat': {
      if versioncmp($apache_version, '2.4') >= 0 {
        ::apache::mpm{ 'worker':
          apache_version => $apache_version,
        }
      }
      else {
        file_line { '/etc/sysconfig/httpd worker enable':
          ensure  => present,
          path    => '/etc/sysconfig/httpd',
          line    => 'HTTPD=/usr/sbin/httpd.worker',
          match   => '#?HTTPD=/usr/sbin/httpd.worker',
          require => Package['httpd'],
          notify  => Service['httpd'],
        }
      }
    }
    'debian', 'freebsd': {
      ::apache::mpm{ 'worker':
        apache_version => $apache_version,
      }
    }
    default: {
      fail("Unsupported osfamily ${::osfamily}")
    }
  }
}
