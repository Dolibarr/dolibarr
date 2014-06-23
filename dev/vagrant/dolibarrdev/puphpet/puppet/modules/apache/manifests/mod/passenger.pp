class apache::mod::passenger (
  $passenger_conf_file            = $::apache::params::passenger_conf_file,
  $passenger_conf_package_file    = $::apache::params::passenger_conf_package_file,
  $passenger_high_performance     = undef,
  $passenger_pool_idle_time       = undef,
  $passenger_max_requests         = undef,
  $passenger_stat_throttle_rate   = undef,
  $rack_autodetect                = undef,
  $rails_autodetect               = undef,
  $passenger_root                 = $::apache::params::passenger_root,
  $passenger_ruby                 = $::apache::params::passenger_ruby,
  $passenger_default_ruby         = $::apache::params::passenger_default_ruby,
  $passenger_max_pool_size        = undef,
  $passenger_use_global_queue     = undef,
  $mod_package                    = undef,
  $mod_package_ensure             = undef,
  $mod_lib                        = undef,
  $mod_lib_path                   = undef,
  $mod_id                         = undef,
  $mod_path                       = undef,
) {
  # Managed by the package, but declare it to avoid purging
  if $passenger_conf_package_file {
    file { 'passenger_package.conf':
      path => "${::apache::mod_dir}/${passenger_conf_package_file}",
    }
  } else {
    # Remove passenger_extra.conf left over from before Passenger support was
    # reworked for Debian. This is a temporary fix for users running this
    # module from master after release 1.0.1 It will be removed in two
    # releases from now.
    $passenger_package_conf_ensure = $::osfamily ? {
      'Debian' => 'absent',
      default  => undef,
    }

    file { 'passenger_package.conf':
      ensure => $passenger_package_conf_ensure,
      path   => "${::apache::mod_dir}/passenger_extra.conf",
    }
  }

  $_package = $mod_package
  $_package_ensure = $mod_package_ensure
  $_lib = $mod_lib
  if $::osfamily == 'FreeBSD' {
    if $mod_lib_path {
      $_lib_path = $mod_lib_path
    } else {
      $_lib_path = "${passenger_root}/buildout/apache2"
    }
  } else {
    $_lib_path = $mod_lib_path
  }

  $_id = $mod_id
  $_path = $mod_path
  ::apache::mod { 'passenger':
    package        => $_package,
    package_ensure => $_package_ensure,
    lib            => $_lib,
    lib_path       => $_lib_path,
    id             => $_id,
    path           => $_path,
  }

  # Template uses:
  # - $passenger_root
  # - $passenger_ruby
  # - $passenger_default_ruby
  # - $passenger_max_pool_size
  # - $passenger_high_performance
  # - $passenger_max_requests
  # - $passenger_stat_throttle_rate
  # - $passenger_use_global_queue
  # - $rack_autodetect
  # - $rails_autodetect
  file { 'passenger.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/${passenger_conf_file}",
    content => template('apache/mod/passenger.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
