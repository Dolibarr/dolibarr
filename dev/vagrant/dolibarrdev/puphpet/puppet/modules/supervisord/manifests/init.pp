# Class: supervisord
#
# This class installs supervisord via pip
#
class supervisord(
  $package_ensure       = $supervisord::params::package_ensure,
  $package_provider     = $supervisord::params::package_provider,
  $service_ensure       = $supervisord::params::service_ensure,
  $service_name         = $supervisord::params::service_name,
  $install_init         = $supervisord::params::install_init,
  $install_pip          = false,
  $init_defaults        = $supervisord::params::init_defaults,
  $setuptools_url       = $supervisord::params::setuptools_url,
  $executable           = $supervisord::params::executable,
  $executable_ctl       = $supervisord::params::executable_ctl,

  $log_path             = $supervisord::params::log_path,
  $log_file             = $supervisord::params::log_file,
  $log_level            = $supervisord::params::log_level,
  $logfile_maxbytes     = $supervisord::params::logfile_maxbytes,
  $logfile_backups      = $supervisord::params::logfile_backups,

  $run_path             = $supervisord::params::run_path,
  $pid_file             = $supervisord::params::pid_file,
  $nodaemon             = $supervisord::params::nodaemon,
  $minfds               = $supervisord::params::minfds,
  $minprocs             = $supervisord::params::minprocs,
  $config_include       = $supervisord::params::config_include,
  $config_file          = $supervisord::params::config_file,
  $config_dirs          = undef,
  $umask                = $supervisord::params::umask,

  $unix_socket          = $supervisord::params::unix_socket,
  $unix_socket_file     = $supervisord::params::unix_socket_file,
  $unix_socket_mode     = $supervisord::params::unix_socket_mode,
  $unix_socket_owner    = $supervisord::params::unix_socket_owner,
  $unix_socket_group    = $supervisord::params::unix_socket_group,

  $inet_server          = $supervisord::params::inet_server,
  $inet_server_hostname = $supervisord::params::inet_server_hostname,
  $inet_server_port     = $supervisord::params::inet_server_port,

  $unix_auth            = false,
  $unix_username        = undef,
  $unix_password        = undef,

  $inet_auth            = false,
  $inet_username        = undef,
  $inet_password        = undef,

  $user                 = undef,
  $identifier           = undef,
  $childlogdir          = undef,
  $environment          = undef,
  $env_var              = undef,
  $directory            = undef,
  $strip_ansi           = false,
  $nocleanup            = false,

  $eventlisteners       = {},
  $fcgi_programs        = {},
  $groups               = {},
  $programs             = {}

) inherits supervisord::params {

  validate_bool($install_pip)
  validate_bool($install_init)
  validate_bool($nodaemon)
  validate_bool($unix_auth)
  validate_bool($inet_auth)
  validate_bool($strip_ansi)
  validate_bool($nocleanup)

  validate_hash($eventlisteners)
  validate_hash($fcgi_programs)
  validate_hash($groups)
  validate_hash($programs)

  validate_absolute_path($config_include)
  validate_absolute_path($log_path)
  validate_absolute_path($run_path)
  if $childlogdir { validate_absolute_path($childlogdir) }
  if $directory { validate_absolute_path($directory) }

  $log_levels = ['^critical$', '^error$', '^warn$', '^info$', '^debug$', '^trace$', '^blather$']
  validate_re($log_level, $log_levels, "invalid log_level: ${log_level}")
  validate_re($umask, '^0[0-7][0-7]$', "invalid umask: ${umask}.")
  validate_re($unix_socket_mode, '^[0-7][0-7][0-7][0-7]$', "invalid unix_socket_mode: ${unix_socket_mode}")

  if ! is_integer($logfile_backups) { fail("invalid logfile_backups: ${logfile_backups}.")}
  if ! is_integer($minfds) { fail("invalid minfds: ${minfds}.")}
  if ! is_integer($minprocs) { fail("invalid minprocs: ${minprocs}.")}
  if ! is_integer($inet_server_port) { fail("invalid inet_server_port: ${inet_server_port}.")}

  if $env_var {
    validate_hash($env_var)
    $env_hash = hiera($env_var)
    $env_string = hash2csv($env_hash)
  }
  elsif $environment {
    validate_hash($environment)
    $env_string = hash2csv($environment)
  }

  if $config_dirs {
    validate_array($config_dirs)
    $config_include_string = join($config_dirs, " ")
  }
  else {
    $config_include_string = "${config_include}/*.conf"
  }

  create_resources('supervisord::eventlistener', $eventlisteners)
  create_resources('supervisord::fcgi_program', $fcgi_programs)
  create_resources('supervisord::group', $groups)
  create_resources('supervisord::program', $programs)

  if $install_pip {
    include supervisord::pip
    Class['supervisord::pip'] -> Class['supervisord::install']
  }

  include supervisord::install, supervisord::config, supervisord::service, supervisord::reload

  Class['supervisord::install'] -> Class['supervisord::config'] ~> Class['supervisord::service']
  Class['supervisord::reload'] -> Supervisord::Supervisorctl <| |>
}
