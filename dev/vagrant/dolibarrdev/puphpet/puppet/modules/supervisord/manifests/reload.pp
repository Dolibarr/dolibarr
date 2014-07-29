# Class: supervisord::reload
#
# Class to reread and update supervisord with supervisorctl
#
class supervisord::reload inherits supervisord {

  $supervisorctl = $::supervisord::executable_ctl

  exec { 'supervisorctl_reread':
    command     => "${supervisorctl} reread",
    refreshonly => true,
    require     => Service[$supervisord::service_name],
  }
  exec { 'supervisorctl_update':
    command     => "${supervisorctl} update",
    refreshonly => true,
    require     => Service[$supervisord::service_name],
  }
}
