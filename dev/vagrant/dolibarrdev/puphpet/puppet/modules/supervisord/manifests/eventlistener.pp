# Define: supervisord::eventlistener
#
# This define creates an eventlistener configuration file
#
# Documentation on parameters available at:
# http://supervisord.org/configuration.html#eventlistener-x-section-settings
#
define supervisord::eventlistener(
  $command,
  $ensure                  = present,
  $ensure_process          = 'running',
  $buffer_size             = 10,
  $events                  = undef,
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
  $stopwaitsecs            = undef,
  $stopasgroup             = undef,
  $killasgroup             = undef,
  $user                    = undef,
  $redirect_stderr         = undef,
  $stdout_logfile          = "eventlistener_${name}.log",
  $stdout_logfile_maxbytes = undef,
  $stdout_logfile_backups  = undef,
  $stdout_events_enabled   = undef,
  $stderr_logfile          = "eventlistener_${name}.error",
  $stderr_logfile_maxbytes = undef,
  $stderr_logfile_backups  = undef,
  $stderr_events_enabled   = undef,
  $environment             = undef,
  $directory               = undef,
  $umask                   = undef,
  $serverurl               = undef
) {

  include supervisord

  # parameter validation
  validate_string($command)
  validate_re($ensure_process, ['running', 'stopped', 'removed'])
  validate_re($buffer_size, '^\d+')
  if $events { validate_array($events) }
  if $result_handler { validate_string($result_handler) }
  if $numprocs { validate_re($numprocs, '^\d+')}
  if $numprocs_start { validate_re($numprocs_start, '^\d+')}
  if $priority { validate_re($priority, '^\d+') }
  if $autostart { validate_bool($autostart) }
  if $autorestart { validate_re($autorestart, ['true', 'false', 'unexpected']) }
  if $startsecs { validate_re($startsecs, '^\d+')}
  if $startretries { validate_re($startretries, '^\d+')}
  if $exitcodes { validate_string($exitcodes)}
  if $stopsignal { validate_re($stopsignal, ['TERM', 'HUP', 'INT', 'QUIT', 'KILL', 'USR1', 'USR2']) }
  if $stopwaitsecs { validate_re($stopwaitsecs, '^\d+')}
  if $stopasgroup { validate_bool($stopasgroup) }
  if $killasgroup { validate_bool($killasgroup) }
  if $user { validate_string($user) }
  if $redirect_stderr { validate_bool($redirect_stderr) }
  validate_string($stdout_logfile)
  if $stdout_logfile_maxbytes { validate_string($stdout_logfile_maxbytes) }
  if $stdout_logfile_backups { validate_re($stdout_logfile_backups, '^\d+')}
  if $stdout_events_enabled { validate_bool($stdout_events_enabled) }
  validate_string($stderr_logfile)
  if $stderr_logfile_maxbytes { validate_string($stderr_logfile_maxbytes) }
  if $stderr_logfile_backups { validate_re($stderr_logfile_backups, '^\d+')}
  if $stderr_events_enabled { validate_bool($stderr_events_enabled) }
  if $directory { validate_absolute_path($directory) }
  if $umask { validate_re($umask, '^[0-7][0-7][0-7]$') }

  # convert environment data into a csv
  if $env_var {
    $env_hash = hiera_hash($env_var)
    validate_hash($env_hash)
    $env_string = hash2csv($env_hash)
  }
  elsif $environment {
    validate_hash($environment)
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
    notify  => Class['supervisord::reload']
  }

  case $ensure_process {
    'stopped': {
      supervisord::supervisorctl { "stop_${name}":
        command => 'stop',
        process => $name
      }
    }
    'removed': {
      supervisord::supervisorctl { "remove_${name}":
        command => 'remove',
        process => $name
      }
    }
    default: { }
  }
}
