define supervisord::eventlistener(
  $command,
  $ensure                  = present,
  $events                  = undef,
  $buffer_size             = undef,
  $result_handler          = undef,
  $env_var                 = undef,
  $process_name            = undef,
  $numprocs                = undef,
  $numprocs_start          = undef,
  $priority                = undef,
  $autostart               = undef,
  $autorestart             = undef,
  $startsecs               = undef,
  $startretries            = undef,
  $exitcodes               = undef,
  $stopsignal              = undef,
  $stopwaitsec             = undef,
  $stopasgroup             = undef,
  $killasgroup             = undef,
  $user                    = undef,
  $redirect_stderr         = undef,
  $stdout_logfile          = "${supervisord::log_path}/eventlistener_${name}.log",
  $stdout_logfile_maxbytes = undef,
  $stdout_logfile_backups  = undef,
  $stdout_events_enabled   = undef,
  $stderr_logfile          = "${supervisord::log_path}/eventlistener_${name}.error",
  $stderr_logfile_maxbytes = undef,
  $stderr_logfile_backups  = undef,
  $stderr_events_enabled   = undef,
  $environment             = undef,
  $directory               = undef,
  $umask                   = undef,
  $serverurl               = undef
) {

  include supervisord

  if $env_var {
    $env_hash = hiera($env_var)
    $env_string = hash2csv($env_hash)
  }
  elsif $environment {
    $env_string = hash2csv($environment)
  }

  if $events {
    $events_string = array2csv($events)
  }

  $conf = "${supervisord::config_include}/eventlistener_${name}.conf"

  file { $conf:
    ensure  => $ensure,
    owner   => 'root',
    mode    => '0755',
    content => template('supervisord/conf/eventlistener.erb'),
    notify  => Class['supervisord::service']
  }
}
