include nginx

nginx::resource::vhost { 'test.local test':
  ensure       => present,
  ipv6_enable  => true,
  proxy        => 'http://proxypass',
}

nginx::resource::vhost { 'test.local:8080':
  ensure       => present,
  listen_port  => 8080,
  server_name  => ['test.local test'],
  ipv6_enable  => true,
  proxy        => 'http://proxypass',
}

