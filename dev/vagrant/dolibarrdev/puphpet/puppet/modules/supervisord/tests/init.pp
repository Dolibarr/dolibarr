class { 'supervisord':
  install_pip  => true,
  install_init => true,
  nocleanup    => true,
}
