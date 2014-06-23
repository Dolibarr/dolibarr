define apache::namevirtualhost {
  $addr_port = $name

  # Template uses: $addr_port
  concat::fragment { "NameVirtualHost ${addr_port}":
    ensure  => present,
    target  => $::apache::ports_file,
    content => template('apache/namevirtualhost.erb'),
  }
}
