class supervisord::service inherits supervisord  {
  service { 'supervisord':
    ensure     => $supervisord::service_ensure,
    enable     => true,
    hasrestart => true,
    hasstatus  => true
  }
}
