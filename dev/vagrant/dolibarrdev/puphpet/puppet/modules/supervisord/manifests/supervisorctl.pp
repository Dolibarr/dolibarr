# Define: supervisord:supervisorctl
#
# This define executes command with the supervisorctl tool
#
define supervisord::supervisorctl(
  $command,
  $process       = undef,
  $refreshonly   = false
) {

  Exec { path => [ '/usr/bin/', '/usr/local/bin' ] }

  validate_string($command)
  validate_string($process)

  $supervisorctl = $::supervisord::executable_ctl

  if $process {
    $cmd = join([$supervisorctl, $command, $process], ' ')
  }
  else {
    $cmd = join([$supervisorctl, $command])
  }

  exec { "supervisorctl_command_${name}":
    command     => $cmd,
    refreshonly => $refreshonly
  }
}
