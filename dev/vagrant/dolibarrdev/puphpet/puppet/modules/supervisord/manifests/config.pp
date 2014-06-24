class supervisord::config inherits supervisord {

  file { [ "${supervisord::config_include}", "${supervisord::log_path}"]:
    ensure => directory,
    owner  => 'root',
    mode   => '0755'
  }

  if $supervisord::run_path != '/var/run' {
    file { $supervisord::run_path:
      ensure => directory,
      owner  => 'root',
      mode   => '0755'
    }
  }

  if $supervisord::install_init {

    $osname = downcase($::osfamily)

    file { '/etc/init.d/supervisord':
      ensure  => present,
      owner   => 'root',
      mode    => '0755',
      content => template("supervisord/init/${osname}_init.erb")
    }

    if $supervisord::init_extras {
      file { $supervisord::init_extras:
        ensure  => present,
        owner   => 'root',
        mode    => '0755',
        content => template("supervisord/init/${osname}_extra.erb")
      }
    }

  }

  concat { $supervisord::config_file:
    owner => 'root',
    group => 'root',
    mode  => '0755'
  }

  if $supervisord::unix_socket {
    concat::fragment { 'supervisord_unix':
      target  => $supervisord::config_file,
      content => template('supervisord/supervisord_unix.erb'),
      order   => 01
    }
  }

  if $supervisord::inet_server {
    concat::fragment { 'supervisord_inet':
      target  => $supervisord::config_file,
      content => template('supervisord/supervisord_inet.erb'),
      order   => 01
    }
  }

  concat::fragment { 'supervisord_main':
    target  => $supervisord::config_file,
    content => template('supervisord/supervisord_main.erb'),
    order   => 02
  }
}
