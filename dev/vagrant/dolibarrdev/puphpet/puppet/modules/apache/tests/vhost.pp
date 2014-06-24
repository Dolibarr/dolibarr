## Default vhosts, and custom vhosts
# NB: Please see the other vhost_*.pp example files for further
# examples.

# Base class. Declares default vhost on port 80 and default ssl
# vhost on port 443 listening on all interfaces and serving
# $apache::docroot
class { 'apache': }

# Most basic vhost
apache::vhost { 'first.example.com':
  port    => '80',
  docroot => '/var/www/first',
}

# Vhost with different docroot owner/group/mode
apache::vhost { 'second.example.com':
  port          => '80',
  docroot       => '/var/www/second',
  docroot_owner => 'third',
  docroot_group => 'third',
  docroot_mode  => '0770',
}

# Vhost with serveradmin
apache::vhost { 'third.example.com':
  port        => '80',
  docroot     => '/var/www/third',
  serveradmin => 'admin@example.com',
}

# Vhost with ssl (uses default ssl certs)
apache::vhost { 'ssl.example.com':
  port    => '443',
  docroot => '/var/www/ssl',
  ssl     => true,
}

# Vhost with ssl and specific ssl certs
apache::vhost { 'fourth.example.com':
  port     => '443',
  docroot  => '/var/www/fourth',
  ssl      => true,
  ssl_cert => '/etc/ssl/fourth.example.com.cert',
  ssl_key  => '/etc/ssl/fourth.example.com.key',
}

# Vhost with english title and servername parameter
apache::vhost { 'The fifth vhost':
  servername => 'fifth.example.com',
  port       => '80',
  docroot    => '/var/www/fifth',
}

# Vhost with server aliases
apache::vhost { 'sixth.example.com':
  serveraliases => [
    'sixth.example.org',
    'sixth.example.net',
  ],
  port          => '80',
  docroot       => '/var/www/fifth',
}

# Vhost with alternate options
apache::vhost { 'seventh.example.com':
  port    => '80',
  docroot => '/var/www/seventh',
  options => [
    'Indexes',
    'MultiViews',
  ],
}

# Vhost with AllowOverride for .htaccess
apache::vhost { 'eighth.example.com':
  port     => '80',
  docroot  => '/var/www/eighth',
  override => 'All',
}

# Vhost with access and error logs disabled
apache::vhost { 'ninth.example.com':
  port       => '80',
  docroot    => '/var/www/ninth',
  access_log => false,
  error_log  => false,
}

# Vhost with custom access and error logs and logroot
apache::vhost { 'tenth.example.com':
  port            => '80',
  docroot         => '/var/www/tenth',
  access_log_file => 'tenth_vhost.log',
  error_log_file  => 'tenth_vhost_error.log',
  logroot         => '/var/log',
}

# Vhost with a cgi-bin
apache::vhost { 'eleventh.example.com':
  port        => '80',
  docroot     => '/var/www/eleventh',
  scriptalias => '/usr/lib/cgi-bin',
}

# Vhost with a proxypass configuration
apache::vhost { 'twelfth.example.com':
  port          => '80',
  docroot       => '/var/www/twelfth',
  proxy_dest    => 'http://internal.example.com:8080/twelfth',
  no_proxy_uris => ['/login','/logout'],
}

# Vhost to redirect /login and /logout
apache::vhost { 'thirteenth.example.com':
  port            => '80',
  docroot         => '/var/www/thirteenth',
  redirect_source => [
    '/login',
    '/logout',
  ],
  redirect_dest   => [
    'http://10.0.0.10/login',
    'http://10.0.0.10/logout',
  ],
}

# Vhost to permamently redirect
apache::vhost { 'fourteenth.example.com':
  port            => '80',
  docroot         => '/var/www/fourteenth',
  redirect_source => '/blog',
  redirect_dest   => 'http://blog.example.com',
  redirect_status => 'permanent',
}

# Vhost with a rack configuration
apache::vhost { 'fifteenth.example.com':
  port           => '80',
  docroot        => '/var/www/fifteenth',
  rack_base_uris => ['/rackapp1', '/rackapp2'],
}

# Vhost to redirect non-ssl to ssl
apache::vhost { 'sixteenth.example.com non-ssl':
  servername   => 'sixteenth.example.com',
  port         => '80',
  docroot      => '/var/www/sixteenth',
  rewrites     => [
    {
      comment       => 'redirect non-SSL traffic to SSL site',
      rewrite_cond  => ['%{HTTPS} off'],
      rewrite_rule  => ['(.*) https://%{HTTPS_HOST}%{REQUEST_URI}'],
    }
  ]
}
apache::vhost { 'sixteenth.example.com ssl':
  servername => 'sixteenth.example.com',
  port       => '443',
  docroot    => '/var/www/sixteenth',
  ssl        => true,
}

# Vhost to redirect non-ssl to ssl using old rewrite method
apache::vhost { 'sixteenth.example.com non-ssl old rewrite':
  servername   => 'sixteenth.example.com',
  port         => '80',
  docroot      => '/var/www/sixteenth',
  rewrite_cond => '%{HTTPS} off',
  rewrite_rule => '(.*) https://%{HTTPS_HOST}%{REQUEST_URI}',
}
apache::vhost { 'sixteenth.example.com ssl old rewrite':
  servername => 'sixteenth.example.com',
  port       => '443',
  docroot    => '/var/www/sixteenth',
  ssl        => true,
}

# Vhost to block repository files
apache::vhost { 'seventeenth.example.com':
  port    => '80',
  docroot => '/var/www/seventeenth',
  block   => 'scm',
}

# Vhost with special environment variables
apache::vhost { 'eighteenth.example.com':
  port    => '80',
  docroot => '/var/www/eighteenth',
  setenv  => ['SPECIAL_PATH /foo/bin','KILROY was_here'],
}

apache::vhost { 'nineteenth.example.com':
  port     => '80',
  docroot  => '/var/www/nineteenth',
  setenvif => 'Host "^([^\.]*)\.website\.com$" CLIENT_NAME=$1',
}

# Vhost with additional include files
apache::vhost { 'twentyieth.example.com':
  port                => '80',
  docroot             => '/var/www/twelfth',
  additional_includes => ['/tmp/proxy_group_a','/tmp/proxy_group_b'],
}

# Vhost with alias for subdomain mapped to same named directory
# http://example.com.loc => /var/www/example.com
apache::vhost { 'subdomain.loc':
  vhost_name      => '*',
  port            => '80',
  virtual_docroot => '/var/www/%-2+',
  docroot         => '/var/www',
  serveraliases   => ['*.loc',],
}

# Vhost with SSLProtocol,SSLCipherSuite, SSLHonorCipherOrder
apache::vhost { 'securedomain.com':
        priority              => '10',
        vhost_name            => 'www.securedomain.com',
        port                  => '443',
        docroot               => '/var/www/secure',
        ssl                   => true,
        ssl_cert              => '/etc/ssl/securedomain.cert',
        ssl_key               => '/etc/ssl/securedomain.key',
        ssl_chain             => '/etc/ssl/securedomain.crt',
        ssl_protocol          => '-ALL +SSLv3 +TLSv1',
        ssl_cipher            => 'ALL:!aNULL:!ADH:!eNULL:!LOW:!EXP:RC4+RSA:+HIGH:+MEDIUM',
        ssl_honorcipherorder  => 'On',
        add_listen            => false,
}

# Vhost with access log environment variables writing control
apache::vhost { 'twentyfirst.example.com':
  port               => '80',
  docroot            => '/var/www/twentyfirst',
  access_log_env_var => 'admin',
}

