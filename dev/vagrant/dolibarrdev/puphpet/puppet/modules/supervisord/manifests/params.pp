class supervisord::params {
  $package_ensure       = 'installed'
  $service_ensure       = 'running'
  $package_name         = 'supervisor'
  $executable           = '/usr/local/bin/supervisord'

  $run_path             = '/var/run'
  $pid_file             = "${run_path}/supervisord.pid"
  $log_path             = '/var/log/supervisor'
  $log_file             = "${log_path}/supervisord.log"
  $logfile_maxbytes     = '50MB'
  $logfile_backups      = '10'
  $log_level            = 'info'
  $nodaemon             = false
  $minfds               = '1024'
  $minprocs             = '200'
  $umask                = '022'
  $config_include       = '/etc/supervisor.d'
  $config_file          = '/etc/supervisord.conf'
  $setuptools_url       = 'https://bitbucket.org/pypa/setuptools/raw/bootstrap/ez_setup.py'

  $unix_socket          = true
  $unix_socket_file     = "${run_path}/supervisor.sock"
  $unix_socket_mode     = '0700'
  $unix_socket_owner    = 'nobody'

  $inet_server          = false
  $inet_server_hostname = '127.0.0.1'
  $inet_server_port     = '9001'
  $inet_auth            = false

  case $::osfamily {
    'RedHat': {
      $init_extras       = '/etc/sysconfig/supervisord'
      $unix_socket_group = 'nobody'
      $install_init      = true
    }
    'Debian': {
      $init_extras       = '/etc/default/supervisor'
      $unix_socket_group = 'nogroup'
      $install_init      = true
    }
    default:  {
      $init_extras       = false
      $unix_socket_group = 'nogroup'
      $install_init      = false
    }
  }
}
