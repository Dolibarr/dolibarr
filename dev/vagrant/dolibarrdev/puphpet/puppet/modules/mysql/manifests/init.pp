#
class mysql(
  $basedir               = '',
  $bind_address          = '',
  $client_package_ensure = '',
  $client_package_name   = '',
  $config_file           = '',
  $config_template       = '',
  $datadir               = '',
  $default_engine        = '',
  $etc_root_password     = '',
  $log_error             = '',
  $manage_config_file    = '',
  $manage_service        = '',
  $max_allowed_packet    = '',
  $max_connections       = '',
  $old_root_password     = '',
  $package_ensure        = '',
  $php_package_name      = '',
  $pidfile               = '',
  $port                  = '',
  $purge_conf_dir        = '',
  $restart               = '',
  $root_group            = '',
  $root_password         = '',
  $server_package_name   = '',
  $service_name          = '',
  $service_provider      = '',
  $socket                = '',
  $ssl                   = '',
  $ssl_ca                = '',
  $ssl_cert              = '',
  $ssl_key               = '',
  $tmpdir                = '',
  $attempt_compatibility_mode = false,
) {

  if $attempt_compatibility_mode {
    notify { "An attempt has been made below to automatically apply your custom
    settings to mysql::server. Please verify this works in a safe test
    environment.": }

    $override_options = {
      'client'                 => {
        'port'                 => $port,
        'socket'               => $socket
      },
      'mysqld_safe'            => {
        'log_error'            => $log_error,
        'socket'               => $socket,
      },
      'mysqld'               => {
        'basedir'            => $basedir,
        'bind_address'       => $bind_address,
        'datadir'            => $datadir,
        'log_error'          => $log_error,
        'max_allowed_packet' => $max_allowed_packet,
        'max_connections'    => $max_connections,
        'pid_file'           => $pidfile,
        'port'               => $port,
        'socket'             => $socket,
        'ssl-ca'             => $ssl_ca,
        'ssl-cert'           => $ssl_cert,
        'ssl-key'            => $ssl_key,
        'tmpdir'             => $tmpdir,
      },
      'mysqldump'              => {
        'max_allowed_packet'  => $max_allowed_packet,
      },
      'config_file'          => $config_file,
      'etc_root_password'    => $etc_root_password,
      'manage_config_file'   => $manage_config_file,
      'old_root_password'    => $old_root_password,
      'purge_conf_dir'       => $purge_conf_dir,
      'restart'              => $restart,
      'root_group'           => $root_group,
      'root_password'        => $root_password,
      'service_name'         => $service_name,
      'ssl'                  => $ssl
    }
    $filtered_options = mysql_strip_hash($override_options)
    validate_hash($filtered_options)
    notify { $filtered_options: }
    class { 'mysql::server':
      override_options => $filtered_options,
    }

  } else {
    fail("ERROR:  This class has been deprecated and the functionality moved
    into mysql::server.  If you run mysql::server without correctly calling
    mysql:: server with the new override_options hash syntax you will revert
    your MySQL to the stock settings.  Do not proceed without removing this
    class and using mysql::server correctly.

    If you are brave you may set attempt_compatibility_mode in this class which
    attempts to automap the previous settings to appropriate calls to
    mysql::server")
  }

}
