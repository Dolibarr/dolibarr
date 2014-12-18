# Class: supervisord::params
#
# Default parameters for supervisord
#
class supervisord::params {
  case $::osfamily {
    'RedHat': {
      $init_defaults     = '/etc/sysconfig/supervisord'
      $unix_socket_group = 'nobody'
      $install_init      = true
      $executable_path   = '/usr/bin'
    }
    'Debian': {
      $init_defaults     = '/etc/default/supervisor'
      $unix_socket_group = 'nogroup'
      $install_init      = true
      $executable_path   = '/usr/local/bin'
    }
    default:  {
      $init_defaults     = false
      $unix_socket_group = 'nogroup'
      $install_init      = false
      $executable_path   = '/usr/local/bin'
    }
  }

  # default supervisord params
  $package_ensure       = 'installed'
  $package_provider     = 'pip'
  $service_ensure       = 'running'
  $service_name         = 'supervisord'
  $package_name         = 'supervisor'
  $executable           = "${$executable_path}/supervisord"
  $executable_ctl       = "${executable_path}/supervisorctl"

  $run_path             = '/var/run'
  $pid_file             = 'supervisord.pid'
  $log_path             = '/var/log/supervisor'
  $log_file             = 'supervisord.log'
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
  $unix_socket_file     = 'supervisor.sock'
  $unix_socket_mode     = '0700'
  $unix_socket_owner    = 'nobody'

  $inet_server          = false
  $inet_server_hostname = '127.0.0.1'
  $inet_server_port     = '9001'
  $inet_auth            = false
}
