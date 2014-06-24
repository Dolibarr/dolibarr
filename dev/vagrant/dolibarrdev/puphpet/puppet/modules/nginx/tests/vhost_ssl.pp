include nginx

nginx::resource::vhost { 'test2.local test2':
  ensure   => present,
  www_root => '/var/www/nginx-default',
  ssl      => true,
  ssl_cert => 'puppet:///modules/sslkey/whildcard_mydomain.crt',
  ssl_key  => 'puppet:///modules/sslkey/whildcard_mydomain.key' 
}

nginx::resource::location { 'test2.local-bob':
  ensure   => present,
  www_root => '/var/www/bob',
  location => '/bob',
  vhost    => 'test2.local test2',
}

