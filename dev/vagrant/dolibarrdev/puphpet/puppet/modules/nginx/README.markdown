# NGINX Module

[![Build Status](https://travis-ci.org/jfryman/puppet-nginx.png)](https://travis-ci.org/jfryman/puppet-nginx)

James Fryman <james@frymanet.com>

This module manages NGINX configuration.

## Quick Start

### Requirements

* Puppet-2.7.0 or later
* Ruby-1.9.3 or later (Ruby-1.8.7 does not work)

### Install and bootstrap an NGINX instance

```puppet
class { 'nginx': }
```

### Setup a new virtual host

```puppet
nginx::resource::vhost { 'www.puppetlabs.com':
  www_root => '/var/www/www.puppetlabs.com',
}
```

### Add a Proxy Server

```puppet
nginx::resource::upstream { 'puppet_rack_app':
  members => [
    'localhost:3000',
    'localhost:3001',
    'localhost:3002',
  ],
}

nginx::resource::vhost { 'rack.puppetlabs.com':
  proxy => 'http://puppet_rack_app',
}
```

### Add a smtp proxy

```puppet
class { 'nginx':
  mail => true,
}

nginx::resource::mailhost { 'domain1.example':
  auth_http   => 'server2.example/cgi-bin/auth',
  protocol    => 'smtp',
  listen_port => 587,
  ssl_port    => 465,
  starttls    => 'only',
  xclient     => 'off',
  ssl         => true,
  ssl_cert    => '/tmp/server.crt',
  ssl_key     => '/tmp/server.pem',
}
```

## SSL configuration

By default, creating a vhost resource will only create a HTTP vhost. To also create a HTTPS (SSL-enabled) vhost, set `ssl => true` on the vhost. You will have a HTTP server listening on `listen_port` (port `80` by default) and a HTTPS server listening on `ssl_port` (port `443` by default). Both vhosts will have the same `server_name` and a similar configuration.

To create only a HTTPS vhost, set `ssl => true` and also set `listen_port` to the same value as `ssl_port`. Setting these to the same value disables the HTTP vhost. The resulting vhost will be listening on `ssl_port`.

### Locations

Locations require specific settings depending on whether they should be included in the HTTP, HTTPS or both vhosts.

#### HTTP only vhost (default)
If you only have a HTTP vhost (i.e. `ssl => false` on the vhost) maks sure you don't set `ssl => true` on any location you associate with the vhost.

#### HTTP and HTTPS vhost
If you set `ssl => true` and also set `listen_port` and `ssl_port` to different values on the vhost you will need to be specific with the location settings since you will have a HTTP vhost listening on `listen_port` and a HTTPS vhost listening on `ssl_port`:

* To add a location to only the HTTP server, set `ssl => false` on the location (this is the default).
* To add a location to both the HTTP and HTTPS server, set `ssl => true` on the location, and ensure `ssl_only => false` (which is the default value for `ssl_only`).
* To add a location only to the HTTPS server, set both `ssl => true` and `ssl_only => true` on the location.

#### HTTPS only vhost
If you have set `ssl => true` and also set `listen_port` and `ssl_port` to the same value on the vhost, you will have a single HTTPS vhost listening on `ssl_port`. To add a location to this vhost set `ssl => true` and `ssl_only => true` on the location.

## Hiera Support

Defining nginx resources in Hiera.

```yaml
nginx::nginx_upstreams:
  'puppet_rack_app':
    ensure: present
    members:
      - localhost:3000
      - localhost:3001
      - localhost:3002
nginx::nginx_vhosts:
  'www.puppetlabs.com':
    www_root: '/var/www/www.puppetlabs.com'
  'rack.puppetlabs.com':
    proxy: 'http://puppet_rack_app'
nginx::nginx_locations:
  'static':
    location: '~ "^/static/[0-9a-fA-F]{8}\/(.*)$"'
    vhost: www.puppetlabs.com
  'userContent':
    location: /userContent
    vhost: www.puppetlabs.com
    www_root: /var/www/html
```

## Nginx with precompiled Passenger

Currently this works only for Debian family.

```puppet
class { 'nginx':
  package_source  => 'passenger',
  http_cfg_append => {
    'passenger_root' => '/usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini',
  }
}
```

Package source `passenger` will add [Phusion Passenger repository](https://oss-binaries.phusionpassenger.com/apt/passenger) to APT sources.
For each virtual host you should specify which ruby should be used.

```puppet
nginx::resource::vhost { 'www.puppetlabs.com':
  www_root         => '/var/www/www.puppetlabs.com',
  vhost_cfg_append => {
    'passenger_enabled' => 'on',
    'passenger_ruby'    => '/usr/bin/ruby',
  }
}
```

### Puppet master served by Nginx and Passenger

Virtual host config for serving puppet master:

```puppet
nginx::resource::vhost { 'puppet':
  ensure               => present,
  server_name          => ['puppet'],
  listen_port          => 8140,
  ssl                  => true,
  ssl_cert             => '/var/lib/puppet/ssl/certs/example.com.pem',
  ssl_key              => '/var/lib/puppet/ssl/private_keys/example.com.pem',
  ssl_port             => 8140,
  vhost_cfg_append     => {
    'passenger_enabled'      => 'on',
    'passenger_ruby'         => '/usr/bin/ruby',
    'ssl_crl'                => '/var/lib/puppet/ssl/ca/ca_crl.pem',
    'ssl_client_certificate' => '/var/lib/puppet/ssl/certs/ca.pem',
    'ssl_verify_client'      => 'optional',
    'ssl_verify_depth'       => 1,
  },
  www_root             => '/etc/puppet/rack/public',
  use_default_location => false,
  access_log           => '/var/log/nginx/puppet_access.log',
  error_log            => '/var/log/nginx/puppet_error.log',
  passenger_cgi_param  => {
    'HTTP_X_CLIENT_DN'     => '$ssl_client_s_dn',
    'HTTP_X_CLIENT_VERIFY' => '$ssl_client_verify',
  },
}
```

### Example puppet class calling nginx::vhost with HTTPS FastCGI and redirection of HTTP

```puppet

$full_web_path = '/var/www'

define web::nginx_ssl_with_redirect (
  $backend_port         = 9000,
  $php                  = true,
  $proxy                = undef,
  $www_root             = "${full_web_path}/${name}/",
  $location_cfg_append  = undef,
) {
  nginx::resource::vhost { "${name}.${::domain}":
    ensure              => present,
    www_root            => "${full_web_path}/${name}/",
    location_cfg_append => { 'rewrite' => '^ https://$server_name$request_uri? permanent' },
  }

  if !$www_root {
    $tmp_www_root = undef
  } else {
    $tmp_www_root = $www_root
  }
   
  nginx::resource::vhost { "${name}.${::domain} ${name}":
    ensure                => present,
    listen_port           => 443,
    www_root              => $tmp_www_root,
    proxy                 => $proxy,
    location_cfg_append   => $location_cfg_append,
    index_files           => [ 'index.php' ],
    ssl                   => true,
    ssl_cert              => 'puppet:///modules/sslkey/whildcard_mydomain.crt',
    ssl_key               => 'puppet:///modules/sslkey/whildcard_mydomain.key',
  }
   
   
  if $php {
    nginx::resource::location { "${name}_root":
      ensure          => present,
      ssl             => true,   
      ssl_only        => true,   
      vhost           => "${name}.${::domain} ${name}",
      www_root        => "${full_web_path}/${name}/",  
      location        => '~ \.php$',
      index_files     => ['index.php', 'index.html', 'index.htm'],
      proxy           => undef,
      fastcgi         => "127.0.0.1:${backend_port}",
      fastcgi_script  => undef,
      location_cfg_append => { 
        fastcgi_connect_timeout => '3m',
        fastcgi_read_timeout    => '3m',
        fastcgi_send_timeout    => '3m' 
      }
    }  
  }    
}      
```       

# Call class web::nginx_ssl_with_redirect

```puppet
web::nginx_ssl_with_redirect { 'sub-domain-name':
    backend_port => 9001,
  }
```
