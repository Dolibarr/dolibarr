class supervisord::install inherits supervisord {
  package { 'supervisor':
    ensure   => $supervisord::package_ensure,
    provider => 'pip'
  }
}
