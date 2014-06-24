class apache::mod::peruser (
  $minspareprocessors = '2',
  $minprocessors = '2',
  $maxprocessors = '10',
  $maxclients = '150',
  $maxrequestsperchild = '1000',
  $idletimeout = '120',
  $expiretimeout = '120',
  $keepalive = 'Off',
) {
  if defined(Class['apache::mod::event']) {
    fail('May not include both apache::mod::peruser and apache::mod::event on the same node')
  }
  if defined(Class['apache::mod::itk']) {
    fail('May not include both apache::mod::peruser and apache::mod::itk on the same node')
  }
  if defined(Class['apache::mod::prefork']) {
    fail('May not include both apache::mod::peruser and apache::mod::prefork on the same node')
  }
  if defined(Class['apache::mod::worker']) {
    fail('May not include both apache::mod::peruser and apache::mod::worker on the same node')
  }
  File {
    owner => 'root',
    group => $::apache::params::root_group,
    mode  => '0644',
  }

  $mod_dir = $::apache::mod_dir

  # Template uses:
  # - $minspareprocessors
  # - $minprocessors
  # - $maxprocessors
  # - $maxclients
  # - $maxrequestsperchild
  # - $idletimeout
  # - $expiretimeout
  # - $keepalive
  # - $mod_dir
  file { "${::apache::mod_dir}/peruser.conf":
    ensure  => file,
    content => template('apache/mod/peruser.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
  file { "${::apache::mod_dir}/peruser":
    ensure  => directory,
    require => File[$::apache::mod_dir],
  }
  file { "${::apache::mod_dir}/peruser/multiplexers":
    ensure  => directory,
    require => File["${::apache::mod_dir}/peruser"],
  }
  file { "${::apache::mod_dir}/peruser/processors":
    ensure  => directory,
    require => File["${::apache::mod_dir}/peruser"],
  }

  ::apache::peruser::multiplexer { '01-default': }

  case $::osfamily {
    'freebsd' : {
      class { '::apache::package':
        mpm_module => 'peruser'
      }
    }
    default: {
      fail("Unsupported osfamily ${::osfamily}")
    }
  }
}
