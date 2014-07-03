class apache::mod::itk (
  $startservers        = '8',
  $minspareservers     = '5',
  $maxspareservers     = '20',
  $serverlimit         = '256',
  $maxclients          = '256',
  $maxrequestsperchild = '4000',
  $apache_version      = $::apache::apache_version,
) {
  if defined(Class['apache::mod::event']) {
    fail('May not include both apache::mod::itk and apache::mod::event on the same node')
  }
  if defined(Class['apache::mod::peruser']) {
    fail('May not include both apache::mod::itk and apache::mod::peruser on the same node')
  }
  if defined(Class['apache::mod::prefork']) {
    fail('May not include both apache::mod::itk and apache::mod::prefork on the same node')
  }
  if defined(Class['apache::mod::worker']) {
    fail('May not include both apache::mod::itk and apache::mod::worker on the same node')
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
  file { "${::apache::mod_dir}/itk.conf":
    ensure  => file,
    content => template('apache/mod/itk.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }

  case $::osfamily {
    'debian', 'freebsd': {
      apache::mpm{ 'itk':
        apache_version => $apache_version,
      }
    }
    default: {
      fail("Unsupported osfamily ${::osfamily}")
    }
  }
}
