# Class: supervisord::reload
#
# Class to reread and update supervisord with supervisorctl
#
class supervisord::reload {

  Exec { path => [ '/usr/bin/', '/usr/local/bin' ] }

  $supervisorctl = $::supervisord::executable_ctl

  exec { 'supervisorctl_reread':
    command     => "${supervisorctl} reread",
    refreshonly => true,
    returns     => [0, 2],
  }
  exec { 'supervisorctl_update':
    command     => "${supervisorctl} update",
    refreshonly => true,
    returns     => [0, 2],
  }
}
