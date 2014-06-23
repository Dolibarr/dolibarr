define apache::mpm (
  $lib_path       = $::apache::params::lib_path,
  $apache_version = $::apache::apache_version,
) {
  if ! defined(Class['apache']) {
    fail('You must include the apache base class before using any apache defined resources')
  }

  $mpm     = $name
  $mod_dir = $::apache::mod_dir

  $_lib  = "mod_mpm_${mpm}.so"
  $_path = "${lib_path}/${_lib}"
  $_id   = "mpm_${mpm}_module"

  if versioncmp($apache_version, '2.4') >= 0 {
    file { "${mod_dir}/${mpm}.load":
      ensure  => file,
      path    => "${mod_dir}/${mpm}.load",
      content => "LoadModule ${_id} ${_path}\n",
      require => [
        Package['httpd'],
        Exec["mkdir ${mod_dir}"],
      ],
      before  => File[$mod_dir],
      notify  => Service['httpd'],
    }
  }

  case $::osfamily {
    'debian': {
      file { "${::apache::mod_enable_dir}/${mpm}.conf":
        ensure  => link,
        target  => "${::apache::mod_dir}/${mpm}.conf",
        require => Exec["mkdir ${::apache::mod_enable_dir}"],
        before  => File[$::apache::mod_enable_dir],
        notify  => Service['httpd'],
      }

      if versioncmp($apache_version, '2.4') >= 0 {
        file { "${::apache::mod_enable_dir}/${mpm}.load":
          ensure  => link,
          target  => "${::apache::mod_dir}/${mpm}.load",
          require => Exec["mkdir ${::apache::mod_enable_dir}"],
          before  => File[$::apache::mod_enable_dir],
          notify  => Service['httpd'],
        }
      }

      if versioncmp($apache_version, '2.4') < 0 {
        package { "apache2-mpm-${mpm}":
          ensure => present,
        }
      }
    }
    'freebsd': {
      class { '::apache::package':
        mpm_module => $mpm
      }
    }
    'redhat': {
      # so we don't fail
    }
    default: {
      fail("Unsupported osfamily ${::osfamily}")
    }
  }
}
