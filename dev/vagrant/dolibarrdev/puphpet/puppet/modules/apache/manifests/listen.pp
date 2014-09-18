define apache::listen {
  $listen_addr_port = $name

  # Template uses: $listen_addr_port
  concat::fragment { "Listen ${listen_addr_port}":
    ensure  => present,
    target  => $::apache::ports_file,
    content => template('apache/listen.erb'),
  }
}
