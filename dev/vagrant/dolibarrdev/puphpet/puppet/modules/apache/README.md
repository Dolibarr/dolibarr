#apache

[![Build Status](https://travis-ci.org/puppetlabs/puppetlabs-apache.png?branch=master)](https://travis-ci.org/puppetlabs/puppetlabs-apache)

####Table of Contents

1. [Overview - What is the apache module?](#overview)
2. [Module Description - What does the module do?](#module-description)
3. [Setup - The basics of getting started with apache](#setup)
    * [Beginning with apache - Installation](#beginning-with-apache)
    * [Configure a virtual host - Basic options for getting started](#configure-a-virtual-host)
4. [Usage - The classes and defined types available for configuration](#usage)
    * [Classes and Defined Types](#classes-and-defined-types)
        * [Class: apache](#class-apache)
        * [Class: apache::default_mods](#class-apachedefault_mods)
        * [Defined Type: apache::mod](#defined-type-apachemod)
        * [Classes: apache::mod::*](#classes-apachemodname)
        * [Class: apache::mod::pagespeed](#class-apachemodpagespeed)
        * [Class: apache::mod::php](#class-apachemodphp)
        * [Class: apache::mod::ssl](#class-apachemodssl)
        * [Class: apache::mod::wsgi](#class-apachemodwsgi)
        * [Class: apache::mod::fcgid](#class-apachemodfcgid)
        * [Defined Type: apache::vhost](#defined-type-apachevhost)
        * [Parameter: `directories` for apache::vhost](#parameter-directories-for-apachevhost)
        * [SSL parameters for apache::vhost](#ssl-parameters-for-apachevhost)
    * [Virtual Host Examples - Demonstrations of some configuration options](#virtual-host-examples)
    * [Load Balancing](#load-balancing)
        * [Defined Type: apache::balancer](#defined-type-apachebalancer)
        * [Defined Type: apache::balancermember](#defined-type-apachebalancermember)
        * [Examples - Load balancing with exported and non-exported resources](#examples)
5. [Reference - An under-the-hood peek at what the module is doing and how](#reference)
    * [Classes](#classes)
        * [Public Classes](#public-classes)
        * [Private Classes](#private-classes)
    * [Defined Types](#defined-types)
        * [Public Defined Types](#public-defined-types)
        * [Private Defined Types](#private-defined-types)
    * [Templates](#templates)
6. [Limitations - OS compatibility, etc.](#limitations)
7. [Development - Guide for contributing to the module](#development)
    * [Contributing to the apache module](#contributing)
    * [Running tests - A quick guide](#running-tests)

##Overview

The apache module allows you to set up virtual hosts and manage web services with minimal effort.

##Module Description

Apache is a widely-used web server, and this module provides a simplified way of creating configurations to manage your infrastructure. This includes the ability to configure and manage a range of different virtual host setups, as well as a streamlined way to install and configure Apache modules.

##Setup

**What apache affects:**

* configuration files and directories (created and written to)
    * **WARNING**: Configurations that are *not* managed by Puppet will be purged.
* package/service/configuration files for Apache
* Apache modules
* virtual hosts
* listened-to ports
* `/etc/make.conf` on FreeBSD 

###Beginning with Apache

To install Apache with the default parameters

```puppet
    class { 'apache':  }
```

The defaults are determined by your operating system (e.g. Debian systems have one set of defaults, and RedHat systems have another, as do FreeBSD systems). These defaults will work well in a testing environment, but are not suggested for production. To establish customized parameters

```puppet
    class { 'apache':
      default_mods        => false,
      default_confd_files => false,
    }
```

###Configure a virtual host

Declaring the `apache` class will create a default virtual host by setting up a vhost on port 80, listening on all interfaces and serving `$apache::docroot`.

```puppet
    class { 'apache': }
```

To configure a very basic, name-based virtual host

```puppet
    apache::vhost { 'first.example.com':
      port    => '80',
      docroot => '/var/www/first',
    }
```

*Note:* The default priority is 15. If nothing matches this priority, the alphabetically first name-based vhost will be used. This is also true if you pass a higher priority and no names match anything else.

A slightly more complicated example, changes the docroot owner/group from the default 'root'

```puppet
    apache::vhost { 'second.example.com':
      port          => '80',
      docroot       => '/var/www/second',
      docroot_owner => 'third',
      docroot_group => 'third',
    }
```

To set up a virtual host with SSL and default SSL certificates

```puppet
    apache::vhost { 'ssl.example.com':
      port    => '443',
      docroot => '/var/www/ssl',
      ssl     => true,
    }
```

To set up a virtual host with SSL and specific SSL certificates

```puppet
    apache::vhost { 'fourth.example.com':
      port     => '443',
      docroot  => '/var/www/fourth',
      ssl      => true,
      ssl_cert => '/etc/ssl/fourth.example.com.cert',
      ssl_key  => '/etc/ssl/fourth.example.com.key',
    }
```

Virtual hosts listen on '*' by default. To listen on a specific IP address

```puppet
    apache::vhost { 'subdomain.example.com':
      ip      => '127.0.0.1',
      port    => '80',
      docroot => '/var/www/subdomain',
    }
```

To set up a virtual host with a wildcard alias for the subdomain mapped to a same-named directory, for example: `http://example.com.loc` to `/var/www/example.com`

```puppet
    apache::vhost { 'subdomain.loc':
      vhost_name       => '*',
      port             => '80',
      virtual_docroot' => '/var/www/%-2+',
      docroot          => '/var/www',
      serveraliases    => ['*.loc',],
    }
```

To set up a virtual host with suPHP

```puppet
    apache::vhost { 'suphp.example.com':
      port                => '80',
      docroot             => '/home/appuser/myphpapp',
      suphp_addhandler    => 'x-httpd-php',
      suphp_engine        => 'on',
      suphp_configpath    => '/etc/php5/apache2',
      directories         => { path => '/home/appuser/myphpapp',
        'suphp'           => { user => 'myappuser', group => 'myappgroup' },
      }
    }
```

To set up a virtual host with WSGI

```puppet
    apache::vhost { 'wsgi.example.com':
      port                        => '80',
      docroot                     => '/var/www/pythonapp',
      wsgi_application_group      => '%{GLOBAL}',
      wsgi_daemon_process         => 'wsgi',
      wsgi_daemon_process_options => { 
        processes    => '2', 
        threads      => '15', 
        display-name => '%{GROUP}',
       },
      wsgi_import_script          => '/var/www/demo.wsgi',
      wsgi_import_script_options  =>
        { process-group => 'wsgi', application-group => '%{GLOBAL}' },
      wsgi_process_group          => 'wsgi',
      wsgi_script_aliases         => { '/' => '/var/www/demo.wsgi' },
    }
```

Starting in Apache 2.2.16, HTTPD supports [FallbackResource](https://httpd.apache.org/docs/current/mod/mod_dir.html#fallbackresource), a simple replacement for common RewriteRules.

```puppet
    apache::vhost { 'wordpress.example.com':
      port                => '80',
      docroot             => '/var/www/wordpress',
      fallbackresource    => '/index.php',
    }
```

Please note that the 'disabled' argument to FallbackResource is only supported since Apache 2.2.24.

See a list of all [virtual host parameters](#defined-type-apachevhost). See an extensive list of [virtual host examples](#virtual-host-examples).

##Usage

###Classes and Defined Types

This module modifies Apache configuration files and directories, and will purge any configuration not managed by Puppet. Configuration of Apache should be managed by Puppet, as non-Puppet configuration files can cause unexpected failures.

It is possible to temporarily disable full Puppet management by setting the [`purge_configs`](#purge_configs) parameter within the base `apache` class to 'false'. This option should only be used as a temporary means of saving and relocating customized configurations. See the [`purge_configs` parameter](#purge_configs) for more information.

####Class: `apache`

The apache module's primary class, `apache`, guides the basic setup of Apache on your system.

You may establish a default vhost in this class, the `vhost` class, or both. You may add additional vhost configurations for specific virtual hosts using a declaration of the `vhost` type.

**Parameters within `apache`:**

#####`apache_version`

Configures the behavior of the module templates, package names, and default mods by setting the Apache version. Default is determined by the class `apache::version` using the OS family and release. It should not be configured manually without special reason.

#####`confd_dir`

Changes the location of the configuration directory your custom configuration files are placed in. Defaults to '/etc/httpd/conf' on RedHat, '/etc/apache2' on Debian, and '/usr/local/etc/apache22' on FreeBSD.

#####`conf_template`

Overrides the template used for the main apache configuration file. Defaults to 'apache/httpd.conf.erb'.

*Note:* Using this parameter is potentially risky, as the module has been built for a minimal configuration file with the configuration primarily coming from conf.d/ entries.

#####`default_confd_files`

Generates default set of include-able Apache configuration files under  `${apache::confd_dir}` directory. These configuration files correspond to what is usually installed with the Apache package on a given platform.

#####`default_mods`

Sets up Apache with default settings based on your OS. Valid values are 'true', 'false', or an array of mod names. 

Defaults to 'true', which will include the default [HTTPD mods](https://github.com/puppetlabs/puppetlabs-apache/blob/master/manifests/default_mods.pp).

If false, it will only include the mods required to make HTTPD work, and any other mods can be declared on their own.

If an array, the apache module will include the array of mods listed.

#####`default_ssl_ca`

The default certificate authority, which is automatically set to 'undef'. This default will work out of the box but must be updated with your specific certificate information before being used in production.

#####`default_ssl_cert`

The default SSL certification, which is automatically set based on your operating system  ('/etc/pki/tls/certs/localhost.crt' for RedHat, '/etc/ssl/certs/ssl-cert-snakeoil.pem' for Debian, and '/usr/local/etc/apache22/server.crt' for FreeBSD). This default will work out of the box but must be updated with your specific certificate information before being used in production.

#####`default_ssl_chain`

The default SSL chain, which is automatically set to 'undef'. This default will work out of the box but must be updated with your specific certificate information before being used in production.

#####`default_ssl_crl`

The default certificate revocation list to use, which is automatically set to 'undef'. This default will work out of the box but must be updated with your specific certificate information before being used in production.

#####`default_ssl_crl_path`

The default certificate revocation list path, which is automatically set to 'undef'. This default will work out of the box but must be updated with your specific certificate information before being used in production.

#####`default_ssl_key`

The default SSL key, which is automatically set based on your operating system ('/etc/pki/tls/private/localhost.key' for RedHat, '/etc/ssl/private/ssl-cert-snakeoil.key' for Debian, and '/usr/local/etc/apache22/server.key' for FreeBSD). This default will work out of the box but must be updated with your specific certificate information before being used in production.

#####`default_ssl_vhost`

Sets up a default SSL virtual host. Defaults to 'false'. If set to 'true', will set up the following vhost:

```puppet
    apache::vhost { 'default-ssl':
      port            => 443,
      ssl             => true,
      docroot         => $docroot,
      scriptalias     => $scriptalias,
      serveradmin     => $serveradmin,
      access_log_file => "ssl_${access_log_file}",
      }
```

SSL vhosts only respond to HTTPS queries.

#####`default_vhost`

Sets up a default virtual host. Defaults to 'true', set to 'false' to set up [customized virtual hosts](#configure-a-virtual-host).

#####`error_documents`

Enables custom error documents. Defaults to 'false'.

#####`httpd_dir`

Changes the base location of the configuration directories used for the apache service. This is useful for specially repackaged HTTPD builds, but may have unintended consequences when used in combination with the default distribution packages. Defaults to '/etc/httpd' on RedHat, '/etc/apache2' on Debian, and '/usr/local/etc/apache22' on FreeBSD.

#####`keepalive`

Enables persistent connections.

#####`keepalive_timeout`

Sets the amount of time the server will wait for subsequent requests on a persistent connection. Defaults to '15'.

#####`max_keepalive_requests`

Sets the limit of the number of requests allowed per connection when KeepAlive is on. Defaults to '100'.

#####`loadfile_name`

Sets the file name for the module loadfile. Should be in the format *.load.  This can be used to set the module load order.

#####`log_level`

Changes the verbosity level of the error log. Defaults to 'warn'. Valid values are 'emerg', 'alert', 'crit', 'error', 'warn', 'notice', 'info', or 'debug'.

#####`log_formats`

Define additional [LogFormats](https://httpd.apache.org/docs/current/mod/mod_log_config.html#logformat). This is done in a Hash:

```puppet
  $log_formats = { vhost_common => '%v %h %l %u %t \"%r\" %>s %b' }
```

#####`logroot`

Changes the directory where Apache log files for the virtual host are placed. Defaults to '/var/log/httpd' on RedHat, '/var/log/apache2' on Debian, and '/var/log/apache22' on FreeBSD.

#####`manage_group`

Setting this to 'false' will stop the group resource from being created. This is for when you have a group, created from another Puppet module, you want to use to run Apache. Without this parameter, attempting to use a previously established group would result in a duplicate resource error.

#####`manage_user`

Setting this to 'false' will stop the user resource from being created. This is for instances when you have a user, created from another Puppet module, you want to use to run Apache. Without this parameter, attempting to use a previously established user would result in a duplicate resource error.

#####`mod_dir`

Changes the location of the configuration directory your Apache modules configuration files are placed in. Defaults to '/etc/httpd/conf.d' for RedHat, '/etc/apache2/mods-available' for Debian, and '/usr/local/etc/apache22/Modules' for FreeBSD.

#####`mpm_module`

Determines which MPM is loaded and configured for the HTTPD process. Valid values are 'event', 'itk', 'peruser', 'prefork', 'worker', or 'false'. Defaults to 'prefork' on RedHat and FreeBSD, and 'worker' on Debian. Must be set to 'false' to explicitly declare the following classes with custom parameters:

* `apache::mod::event`
* `apache::mod::itk`
* `apache::mod::peruser`
* `apache::mod::prefork`
* `apache::mod::worker` 

*Note:* Switching between different MPMs on FreeBSD is possible but quite difficult. Before changing `$mpm_module` you must uninstall all packages that depend on your currently-installed Apache. 

#####`package_ensure`

Allows control over the package ensure attribute. Can be 'present','absent', or a version string.

#####`ports_file`

Changes the name of the file containing Apache ports configuration. Default is `${conf_dir}/ports.conf`.

#####`purge_configs`

Removes all other Apache configs and vhosts, defaults to 'true'. Setting this to 'false' is a stopgap measure to allow the apache module to coexist with existing or otherwise-managed configuration. It is recommended that you move your configuration entirely to resources within this module.

#####`sendfile`

Makes Apache use the Linux kernel sendfile to serve static files. Defaults to 'On'.

#####`serveradmin`

Sets the server administrator. Defaults to 'root@localhost'.

#####`servername`

Sets the server name. Defaults to `fqdn` provided by Facter.

#####`server_root`

Sets the root directory in which the server resides. Defaults to '/etc/httpd' on RedHat, '/etc/apache2' on Debian, and '/usr/local' on FreeBSD.

#####`server_signature`

Configures a trailing footer line under server-generated documents. More information about [ServerSignature](http://httpd.apache.org/docs/current/mod/core.html#serversignature). Defaults to 'On'.

#####`server_tokens`

Controls how much information Apache sends to the browser about itself and the operating system. More information about [ServerTokens](http://httpd.apache.org/docs/current/mod/core.html#servertokens). Defaults to 'OS'.

#####`service_enable`

Determines whether the HTTPD service is enabled when the machine is booted. Defaults to 'true'.

#####`service_ensure`

Determines whether the service should be running. Valid values are true, false, 'running' or 'stopped' when Puppet should manage the service. Any other value will set ensure to false for the Apache service, which is useful when you want to let the service be managed by some other application like Pacemaker. Defaults to 'running'.

#####`service_name`

Name of the Apache service to run. Defaults to: 'httpd' on RedHat, 'apache2' on Debian, and 'apache22' on FreeBSD.

#####`trace_enable`

Controls how TRACE requests per RFC 2616 are handled. More information about [TraceEnable](http://httpd.apache.org/docs/current/mod/core.html#traceenable). Defaults to 'On'.

#####`vhost_dir`

Changes the location of the configuration directory your virtual host configuration files are placed in. Defaults to 'etc/httpd/conf.d' on RedHat, '/etc/apache2/sites-available' on Debian, and '/usr/local/etc/apache22/Vhosts' on FreeBSD.

####Class: `apache::default_mods`

Installs default Apache modules based on what OS you are running.

```puppet
    class { 'apache::default_mods': }
```

####Defined Type: `apache::mod`

Used to enable arbitrary Apache HTTPD modules for which there is no specific `apache::mod::[name]` class. The `apache::mod` defined type will also install the required packages to enable the module, if any.

```puppet
    apache::mod { 'rewrite': }
    apache::mod { 'ldap': }
```

####Classes: `apache::mod::[name]`

There are many `apache::mod::[name]` classes within this module that can be declared using `include`:

* `actions`
* `alias`
* `auth_basic`
* `auth_kerb`
* `authnz_ldap`*
* `autoindex`
* `cache`
* `cgi`
* `cgid`
* `dav`
* `dav_fs`
* `dav_svn`*
* `deflate`
* `dev`
* `dir`*
* `disk_cache`
* `event`
* `expires`
* `fastcgi`
* `fcgid`
* `headers`
* `include`
* `info`
* `itk`
* `ldap`
* `mime`
* `mime_magic`*
* `negotiation`
* `nss`*
* `pagespeed` (see [`apache::mod::pagespeed`](#class-apachemodpagespeed) below)
* `passenger`*
* `perl`
* `peruser`
* `php` (requires [`mpm_module`](#mpm_module) set to `prefork`)
* `prefork`*
* `proxy`*
* `proxy_ajp`
* `proxy_balancer`
* `proxy_html`
* `proxy_http`
* `python`
* `reqtimeout`
* `rewrite`
* `rpaf`*
* `setenvif`
* `speling`
* `ssl`* (see [`apache::mod::ssl`](#class-apachemodssl) below)
* `status`*
* `suphp`
* `userdir`*
* `vhost_alias`
* `worker`*
* `wsgi` (see [`apache::mod::wsgi`](#class-apachemodwsgi) below)
* `xsendfile`

Modules noted with a * indicate that the module has settings and, thus, a template that includes parameters. These parameters control the module's configuration. Most of the time, these parameters will not require any configuration or attention.

The modules mentioned above, and other Apache modules that have templates, will cause template files to be dropped along with the mod install and the module will not work without the template. Any module without a template will install the package but drop no files.

####Class: `apache::mod::pagespeed`

Installs and manages mod_pagespeed, which is a Google module that rewrites web pages to reduce latency and bandwidth.

This module does *not* manage the software repositories needed to automatically install the
mod-pagespeed-stable package. The module does however require that the package be installed,
or be installable using the system's default package provider.  You should ensure that this
pre-requisite is met or declaring `apache::mod::pagespeed` will cause the puppet run to fail.

These are the defaults:

```puppet
    class { 'apache::mod::pagespeed':
      inherit_vhost_config          => 'on',
      filter_xhtml                  => false,
      cache_path                    => '/var/cache/mod_pagespeed/',
      log_dir                       => '/var/log/pagespeed',
      memache_servers               => [],
      rewrite_level                 => 'CoreFilters',
      disable_filters               => [],
      enable_filters                => [],
      forbid_filters                => [],
      rewrite_deadline_per_flush_ms => 10,
      additional_domains            => undef,
      file_cache_size_kb            => 102400,
      file_cache_clean_interval_ms  => 3600000,
      lru_cache_per_process         => 1024,
      lru_cache_byte_limit          => 16384,
      css_flatten_max_bytes         => 2048,
      css_inline_max_bytes          => 2048,
      css_image_inline_max_bytes    => 2048,
      image_inline_max_bytes        => 2048,
      js_inline_max_bytes           => 2048,
      css_outline_min_bytes         => 3000,
      js_outline_min_bytes          => 3000,
      inode_limit                   => 500000,
      image_max_rewrites_at_once    => 8,
      num_rewrite_threads           => 4,
      num_expensive_rewrite_threads => 4,
      collect_statistics            => 'on',
      statistics_logging            => 'on',
      allow_view_stats              => [],
      allow_pagespeed_console       => [],
      allow_pagespeed_message       => [],
      message_buffer_size           => 100000,
      additional_configuration      => { }
    }
```

Full documentation for mod_pagespeed is available from [Google](http://modpagespeed.com).

####Class: `apache::mod::php`

Installs and configures mod_php. The defaults are OS-dependant.

Overriding the package name:
```
  class {'::apache::mod::php':
    package_name => "php54-php",
    path         => "${::apache::params::lib_path}/libphp54-php5.so",
  }
```

Overriding the default configuartion:
```puppet
  class {'::apache::mod::php':
    source => 'puppet:///modules/apache/my_php.conf',
  }
```

or 
```puppet
  class {'::apache::mod::php':
    template => 'apache/php.conf.erb',
  }
```

or

```puppet
  class {'::apache::mod::php':
    content => '
AddHandler php5-script .php
AddType text/html .php',
  }
```
####Class: `apache::mod::ssl`

Installs Apache SSL capabilities and uses the ssl.conf.erb template. These are the defaults:

```puppet
    class { 'apache::mod::ssl':
      ssl_compression => false,
      ssl_options     => [ 'StdEnvVars' ],
  }
```

To *use* SSL with a virtual host, you must either set the`default_ssl_vhost` parameter in `::apache` to 'true' or set the `ssl` parameter in `apache::vhost` to 'true'.

####Class: `apache::mod::wsgi`

Enables Python support in the WSGI module. To use, simply `include 'apache::mod::wsgi'`. 

For customized parameters, which tell Apache how Python is currently configured on the operating system,

```puppet
    class { 'apache::mod::wsgi':
      wsgi_socket_prefix => "\${APACHE_RUN_DIR}WSGI",
      wsgi_python_home   => '/path/to/venv',
      wsgi_python_path   => '/path/to/venv/site-packages',
    }
```

More information about [WSGI](http://modwsgi.readthedocs.org/en/latest/).

####Class: `apache::mod::fcgid`

Installs and configures mod_fcgid.

The class makes no effort to list all available options, but rather uses an options hash to allow for ultimate flexibility:

```puppet
    class { 'apache::mod::fcgid':
      options => {
        'FcgidIPCDir'  => '/var/run/fcgidsock',
        'SharememPath' => '/var/run/fcgid_shm',
        'AddHandler'   => 'fcgid-script .fcgi',
      },
    }
```

For a full list op options, see the [official mod_fcgid documentation](https://httpd.apache.org/mod_fcgid/mod/mod_fcgid.html).

It is also possible to set the FcgidWrapper per directory per vhost. You must ensure the fcgid module is loaded because there is no auto loading.

```puppet
    include apache::mod::fcgid
    apache::vhost { 'example.org':
      docroot     => '/var/www/html',
      directories => {
        path        => '/var/www/html',
        fcgiwrapper => {
          command => '/usr/local/bin/fcgiwrapper',
        }
      },
    }
```

See [FcgidWrapper documentation](https://httpd.apache.org/mod_fcgid/mod/mod_fcgid.html#fcgidwrapper) for more information.

####Defined Type: `apache::vhost`

The Apache module allows a lot of flexibility in the setup and configuration of virtual hosts. This flexibility is due, in part, to `vhost`'s being a defined resource type, which allows it to be evaluated multiple times with different parameters.

The `vhost` defined type allows you to have specialized configurations for virtual hosts that have requirements outside the defaults. You can set up a default vhost within the base `::apache` class, as well as set a customized vhost as default. Your customized vhost (priority 10) will be privileged over the base class vhost (15).

If you have a series of specific configurations and do not want a base `::apache` class default vhost, make sure to set the base class `default_vhost` to 'false'.

```puppet
    class { 'apache':
      default_vhost => false,
    }
```

**Parameters within `apache::vhost`:**

#####`access_log`

Specifies whether `*_access.log` directives (`*_file`,`*_pipe`, or `*_syslog`) should be configured. Setting the value to 'false' will choose none. Defaults to 'true'. 

#####`access_log_file`

Sets the `*_access.log` filename that is placed in `$logroot`. Given a vhost, example.com, it defaults to 'example.com_ssl.log' for SSL vhosts and 'example.com_access.log' for non-SSL vhosts.

#####`access_log_pipe`

Specifies a pipe to send access log messages to. Defaults to 'undef'.

#####`access_log_syslog`

Sends all access log messages to syslog. Defaults to 'undef'.

#####`access_log_format`

Specifies the use of either a LogFormat nickname or a custom format string for the access log. Defaults to 'combined'. See [these examples](http://httpd.apache.org/docs/current/mod/mod_log_config.html).

#####`access_log_env_var`

Specifies that only requests with particular environment variables be logged. Defaults to 'undef'.

#####`add_listen`

Determines whether the vhost creates a Listen statement. The default value is 'true'.

Setting `add_listen` to 'false' stops the vhost from creating a Listen statement, and this is important when you combine vhosts that are not passed an `ip` parameter with vhosts that *are* passed the `ip` parameter.

#####`additional_includes`

Specifies paths to additional static, vhost-specific Apache configuration files. Useful for implementing a unique, custom configuration not supported by this module. Can be an array. Defaults to '[]'.

#####`aliases`

Passes a list of hashes to the vhost to create Alias or AliasMatch directives as per the [mod_alias documentation](http://httpd.apache.org/docs/current/mod/mod_alias.html). These hashes are formatted as follows:

```puppet
aliases => [
  { aliasmatch => '^/image/(.*)\.jpg$', 
    path       => '/files/jpg.images/$1.jpg',
  }
  { alias      => '/image',
    path       => '/ftp/pub/image', 
  },
],
```

For `alias` and `aliasmatch` to work, each will need a corresponding context, such as '< Directory /path/to/directory>' or '<Location /path/to/directory>'. The Alias and AliasMatch directives are created in the order specified in the `aliases` parameter. As described in the [`mod_alias` documentation](http://httpd.apache.org/docs/current/mod/mod_alias.html), more specific `alias` or `aliasmatch` parameters should come before the more general ones to avoid shadowing.

*Note:* If `apache::mod::passenger` is loaded and `PassengerHighPerformance => true` is set, then Alias may have issues honoring the `PassengerEnabled => off` statement. See [this article](http://www.conandalton.net/2010/06/passengerenabled-off-not-working.html) for details.

#####`block`

Specifies the list of things Apache will block access to. The default is an empty set, '[]'. Currently, the only option is 'scm', which blocks web access to .svn, .git and .bzr directories.

#####`custom_fragment`

Passes a string of custom configuration directives to be placed at the end of the vhost configuration. Defaults to 'undef'.

#####`default_vhost`

Sets a given `apache::vhost` as the default to serve requests that do not match any other `apache::vhost` definitions. The default value is 'false'.

#####`directories`

See the [`directories` section](#parameter-directories-for-apachevhost).

#####`directoryindex`

Sets the list of resources to look for when a client requests an index of the directory by specifying a '/' at the end of the directory name. [DirectoryIndex](http://httpd.apache.org/docs/current/mod/mod_dir.html#directoryindex) has more information. Defaults to 'undef'.

#####`docroot`

Provides the [DocumentRoot](http://httpd.apache.org/docs/current/mod/core.html#documentroot) directive, which identifies the directory Apache serves files from. Required. 

#####`docroot_group`

Sets group access to the docroot directory. Defaults to 'root'.

#####`docroot_owner`

Sets individual user access to the docroot directory. Defaults to 'root'.

#####`docroot_mode`

Sets access permissions of the docroot directory. Defaults to 'undef'.

#####`error_log`

Specifies whether `*_error.log` directives should be configured. Defaults to 'true'.

#####`error_log_file`

Points to the `*_error.log` file. Given a vhost, example.com, it defaults to 'example.com_ssl_error.log' for SSL vhosts and 'example.com_access_error.log' for non-SSL vhosts.

#####`error_log_pipe`

Specifies a pipe to send error log messages to. Defaults to 'undef'.

#####`error_log_syslog`

Sends all error log messages to syslog. Defaults to 'undef'.

#####`error_documents`

A list of hashes which can be used to override the [ErrorDocument](https://httpd.apache.org/docs/current/mod/core.html#errordocument) settings for this vhost. Defaults to '[]'. Example:

```puppet
    apache::vhost { 'sample.example.net':
      error_documents => [
        { 'error_code' => '503', 'document' => '/service-unavail' },
        { 'error_code' => '407', 'document' => 'https://example.com/proxy/login' },
      ],
    }
```

#####`ensure`

Specifies if the vhost file is present or absent. Defaults to 'present'.

#####`fallbackresource`

Sets the [FallbackResource](http://httpd.apache.org/docs/current/mod/mod_dir.html#fallbackresource) directive, which specifies an action to take for any URL that doesn't map to anything in your filesystem and would otherwise return 'HTTP 404 (Not Found)'. Valid values must either begin with a / or be 'disabled'. Defaults to 'undef'.

#####`headers`

Adds lines to replace, merge, or remove response headers. See [Header](http://httpd.apache.org/docs/current/mod/mod_headers.html#header) for more information. Can be an array. Defaults to 'undef'.

#####`ip`

Sets the IP address the vhost listens on. Defaults to listen on all IPs.

#####`ip_based`

Enables an [IP-based](http://httpd.apache.org/docs/current/vhosts/ip-based.html) vhost. This parameter inhibits the creation of a NameVirtualHost directive, since those are used to funnel requests to name-based vhosts. Defaults to 'false'.

#####`itk`

Configures [ITK](http://mpm-itk.sesse.net/) in a hash. Keys may be:

* user + group
* `assignuseridexpr`
* `assigngroupidexpr`
* `maxclientvhost`
* `nice`
* `limituidrange` (Linux 3.5.0 or newer)
* `limitgidrange` (Linux 3.5.0 or newer)

Usage will typically look like:

```puppet
    apache::vhost { 'sample.example.net':
      docroot => '/path/to/directory',
      itk     => {
        user  => 'someuser',
        group => 'somegroup',
      },
    }
```

#####`logroot`

Specifies the location of the virtual host's logfiles. Defaults to '/var/log/<apache log location>/'.

#####`log_level`

Specifies the verbosity of the error log. Defaults to 'warn' for the global server configuration and can be overridden on a per-vhost basis. Valid values are 'emerg', 'alert', 'crit', 'error', 'warn', 'notice', 'info' or 'debug'.

#####`no_proxy_uris`

Specifies URLs you do not want to proxy. This parameter is meant to be used in combination with [`proxy_dest`](#proxy_dest).

#####`proxy_preserve_host`

Sets the [ProxyPreserveHost Directive](http://httpd.apache.org/docs/2.2/mod/mod_proxy.html#proxypreservehost).  true Enables the Host: line from an incoming request to be proxied to the host instead of hostname .  false sets this option to off (default).

#####`options`

Sets the [Options](http://httpd.apache.org/docs/current/mod/core.html#options) for the specified virtual host. Defaults to '['Indexes','FollowSymLinks','MultiViews']', as demonstrated below:

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      options => ['Indexes','FollowSymLinks','MultiViews'],
    }
```

*Note:* If you use [`directories`](#parameter-directories-for-apachevhost), 'Options', 'Override', and 'DirectoryIndex' are ignored because they are parameters within `directories`.

#####`override`

Sets the overrides for the specified virtual host. Accepts an array of [AllowOverride](http://httpd.apache.org/docs/current/mod/core.html#allowoverride) arguments. Defaults to '[none]'.

#####`php_admin_flags & values`

Allows per-vhost setting [`php_admin_value`s or `php_admin_flag`s](http://php.net/manual/en/configuration.changes.php). These flags or values cannot be overwritten by a user or an application. Defaults to '[]'.

#####`port`

Sets the port the host is configured on. The module's defaults ensure the host listens on port 80 for non-SSL vhosts and port 443 for SSL vhosts. The host will only listen on the port set in this parameter. 

#####`priority`

Sets the relative load-order for Apache HTTPD VirtualHost configuration files. Defaults to '25'.

If nothing matches the priority, the first name-based vhost will be used. Likewise, passing a higher priority will cause the alphabetically first name-based vhost to be used if no other names match.

*Note:* You should not need to use this parameter. However, if you do use it, be aware that the `default_vhost` parameter for `apache::vhost` passes a priority of '15'.

#####`proxy_dest`

Specifies the destination address of a [ProxyPass](http://httpd.apache.org/docs/current/mod/mod_proxy.html#proxypass) configuration. Defaults to 'undef'.

#####`proxy_pass`

Specifies an array of `path => URI` for a [ProxyPass](http://httpd.apache.org/docs/current/mod/mod_proxy.html#proxypass) configuration. Defaults to 'undef'.

```puppet
apache::vhost { 'site.name.fdqn':
  … 
  proxy_pass => [
    { 'path' => '/a', 'url' => 'http://backend-a/' },
    { 'path' => '/b', 'url' => 'http://backend-b/' },
    { 'path' => '/c', 'url' => 'http://backend-a/c' },
  ],
}
```

#####`rack_base_uris`

Specifies the resource identifiers for a rack configuration. The file paths specified will be listed as rack application roots for [Phusion Passenger](http://www.modrails.com/documentation/Users%20guide%20Apache.html#_railsbaseuri_and_rackbaseuri) in the _rack.erb template. Defaults to 'undef'.

#####`redirect_dest`

Specifies the address to redirect to. Defaults to 'undef'.

#####`redirect_source`

Specifies the source URIs that will redirect to the destination specified in `redirect_dest`. If more than one item for redirect is supplied, the source and destination must be the same length and the items will be order-dependent. 

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      redirect_source => ['/images','/downloads'],
      redirect_dest   => ['http://img.example.com/','http://downloads.example.com/'],
    }
```

#####`redirect_status`

Specifies the status to append to the redirect. Defaults to 'undef'.

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      redirect_status => ['temp','permanent'],
    }
```

#####`redirectmatch_regexp` & `redirectmatch_status`

Determines which server status should be raised for a given regular expression. Entered as an array. Defaults to 'undef'.

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      redirectmatch_status => ['404','404'],
      redirectmatch_regexp => ['\.git(/.*|$)/','\.svn(/.*|$)'],
    }
```

#####`request_headers`

Modifies collected [request headers](http://httpd.apache.org/docs/current/mod/mod_headers.html#requestheader) in various ways, including adding additional request headers, removing request headers, etc. Defaults to 'undef'.

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      request_headers => [
        'append MirrorID "mirror 12"',
        'unset MirrorID',
      ],
    }
```

#####`rewrites`

Creates URL rewrite rules. Expects an array of hashes, and the hash keys can be any of 'comment', 'rewrite_base', 'rewrite_cond', or 'rewrite_rule'. Defaults to 'undef'. 

For example, you can specify that anyone trying to access index.html will be served welcome.html

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      rewrites => [ { rewrite_rule => ['^index\.html$ welcome.html'] } ]
    }
```

The parameter allows rewrite conditions that, when true, will execute the associated rule. For instance, if you wanted to rewrite URLs only if the visitor is using IE

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      rewrites => [
        {
          comment      => 'redirect IE',
          rewrite_cond => ['%{HTTP_USER_AGENT} ^MSIE'],
          rewrite_rule => ['^index\.html$ welcome.html'],
        },
      ],
    }
```

You can also apply multiple conditions. For instance, rewrite index.html to welcome.html only when the browser is Lynx or Mozilla (version 1 or 2)

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      rewrites => [
        {
          comment      => 'Lynx or Mozilla v1/2',
          rewrite_cond => ['%{HTTP_USER_AGENT} ^Lynx/ [OR]', '%{HTTP_USER_AGENT} ^Mozilla/[12]'],
          rewrite_rule => ['^index\.html$ welcome.html'],
        },
      ],
    }
```

Multiple rewrites and conditions are also possible

```puppet
    apache::vhost { 'site.name.fdqn':
      …
      rewrites => [
        {
          comment      => 'Lynx or Mozilla v1/2',
          rewrite_cond => ['%{HTTP_USER_AGENT} ^Lynx/ [OR]', '%{HTTP_USER_AGENT} ^Mozilla/[12]'],
          rewrite_rule => ['^index\.html$ welcome.html'],
        },
        {
          comment      => 'Internet Explorer',
          rewrite_cond => ['%{HTTP_USER_AGENT} ^MSIE'],
          rewrite_rule => ['^index\.html$ /index.IE.html [L]'],
        },
        {
          rewrite_base => /apps/,
          rewrite_rule => ['^index\.cgi$ index.php', '^index\.html$ index.php', '^index\.asp$ index.html'],
        },
     ], 
    }
```

Refer to the [`mod_rewrite` documentation](http://httpd.apache.org/docs/current/mod/mod_rewrite.html) for more details on what is possible with rewrite rules and conditions.

#####`scriptalias`

Defines a directory of CGI scripts to be aliased to the path '/cgi-bin', for example: '/usr/scripts'. Defaults to 'undef'.

#####`scriptaliases`

Passes an array of hashes to the vhost to create either ScriptAlias or ScriptAliasMatch statements as per the [`mod_alias` documentation](http://httpd.apache.org/docs/current/mod/mod_alias.html). These hashes are formatted as follows:

```puppet
    scriptaliases => [
      {
        alias => '/myscript',
        path  => '/usr/share/myscript',
      },
      {
        aliasmatch => '^/foo(.*)',
        path       => '/usr/share/fooscripts$1',
      },
      {
        aliasmatch => '^/bar/(.*)',
        path       => '/usr/share/bar/wrapper.sh/$1',
      },
      {
        alias => '/neatscript',
        path  => '/usr/share/neatscript',
      },
    ]
```

The ScriptAlias and ScriptAliasMatch directives are created in the order specified. As with [Alias and AliasMatch](#aliases) directives, more specific aliases should come before more general ones to avoid shadowing.

#####`serveradmin`

Specifies the email address Apache will display when it renders one of its error pages. Defaults to 'undef'.

#####`serveraliases`

Sets the [ServerAliases](http://httpd.apache.org/docs/current/mod/core.html#serveralias) of the site. Defaults to '[]'.

#####`servername`

Sets the servername corresponding to the hostname you connect to the virtual host at. Defaults to the title of the resource.

#####`setenv`

Used by HTTPD to set environment variables for vhosts. Defaults to '[]'.

#####`setenvif`

Used by HTTPD to conditionally set environment variables for vhosts. Defaults to '[]'.

#####`suphp_addhandler`, `suphp_configpath`, & `suphp_engine`

Set up a virtual host with [suPHP](http://suphp.org/DocumentationView.html?file=apache/CONFIG). 

`suphp_addhandler` defaults to 'php5-script' on RedHat and FreeBSD, and 'x-httpd-php' on Debian.

`suphp_configpath` defaults to 'undef' on RedHat and FreeBSD, and '/etc/php5/apache2' on Debian.

`suphp_engine` allows values 'on' or 'off'. Defaults to 'off'

To set up a virtual host with suPHP

```puppet
    apache::vhost { 'suphp.example.com':
      port                => '80',
      docroot             => '/home/appuser/myphpapp',
      suphp_addhandler    => 'x-httpd-php',
      suphp_engine        => 'on',
      suphp_configpath    => '/etc/php5/apache2',
      directories         => { path => '/home/appuser/myphpapp',
        'suphp'           => { user => 'myappuser', group => 'myappgroup' },
      }
    }
```

#####`vhost_name`

Enables name-based virtual hosting. If no IP is passed to the virtual host but the vhost is assigned a port, then the vhost name will be 'vhost_name:port'. If the virtual host has no assigned IP or port, the vhost name will be set to the title of the resource. Defaults to '*'.

#####`virtual_docroot` 

Sets up a virtual host with a wildcard alias subdomain mapped to a directory with the same name. For example, 'http://example.com' would map to '/var/www/example.com'. Defaults to 'false'. 

```puppet
    apache::vhost { 'subdomain.loc':
      vhost_name       => '*',
      port             => '80',
      virtual_docroot' => '/var/www/%-2+',
      docroot          => '/var/www',
      serveraliases    => ['*.loc',],
    }
```

#####`wsgi_daemon_process`, `wsgi_daemon_process_options`, `wsgi_process_group`, `wsgi_script_aliases`, & `wsgi_pass_authorization`

Set up a virtual host with [WSGI](https://code.google.com/p/modwsgi/).

`wsgi_daemon_process` sets the name of the WSGI daemon. It is a hash, accepting [these keys](http://modwsgi.readthedocs.org/en/latest/configuration-directives/WSGIDaemonProcess.html), and it defaults to 'undef'.

`wsgi_daemon_process_options` is optional and defaults to 'undef'.

`wsgi_process_group` sets the group ID the virtual host will run under. Defaults to 'undef'.

`wsgi_script_aliases` requires a hash of web paths to filesystem .wsgi paths. Defaults to 'undef'.

`wsgi_pass_authorization` the WSGI application handles authorisation instead of Apache when set to 'On'. For more information see [here] (http://modwsgi.readthedocs.org/en/latest/configuration-directives/WSGIPassAuthorization.html).  Defaults to 'undef' where apache will set the defaults setting to 'Off'.

To set up a virtual host with WSGI

```puppet
    apache::vhost { 'wsgi.example.com':
      port                        => '80',
      docroot                     => '/var/www/pythonapp',
      wsgi_daemon_process         => 'wsgi',
      wsgi_daemon_process_options =>
        { processes    => '2', 
          threads      => '15', 
          display-name => '%{GROUP}',
         },
      wsgi_process_group          => 'wsgi',
      wsgi_script_aliases         => { '/' => '/var/www/demo.wsgi' },
    }
```

####Parameter `directories` for `apache::vhost`

The `directories` parameter within the `apache::vhost` class passes an array of hashes to the vhost to create [Directory](http://httpd.apache.org/docs/current/mod/core.html#directory), [File](http://httpd.apache.org/docs/current/mod/core.html#files), and [Location](http://httpd.apache.org/docs/current/mod/core.html#location) directive blocks. These blocks take the form, '< Directory /path/to/directory>...< /Directory>'.

Each hash passed to `directories` must contain `path` as one of the keys.  You may also pass in `provider` which, if missing, defaults to 'directory'. (A full list of acceptable keys is below.) General usage will look something like

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [
        { path => '/path/to/directory', <key> => <value> },
        { path => '/path/to/another/directory', <key> => <value> },
      ],
    }
```

*Note:* At least one directory should match the `docroot` parameter. Once you start declaring directories, `apache::vhost` assumes that all required Directory blocks will be declared. If not defined, a single default Directory block will be created that matches the `docroot` parameter.

The `provider` key can be set to 'directory', 'files', or 'location'. If the path starts with a [~](https://httpd.apache.org/docs/current/mod/core.html#files), HTTPD will interpret this as the equivalent of DirectoryMatch, FilesMatch, or LocationMatch.

```puppet
    apache::vhost { 'files.example.net':
      docroot     => '/var/www/files',
      directories => [
        { 'path'     => '/var/www/files', 
          'provider' => 'files', 
          'deny'     => 'from all' 
         },
      ],
    }
```

Available handlers, represented as keys, should be placed within the  `directory`,`'files`, or `location` hashes.  This looks like

```puppet
  apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ { path => '/path/to/directory', handler => value } ],
}
```

Any handlers you do not set in these hashes will be considered 'undefined' within Puppet and will not be added to the virtual host, resulting in the module using their default values. Currently this is the list of supported handlers:

######`addhandlers`

Sets [AddHandler](http://httpd.apache.org/docs/current/mod/mod_mime.html#addhandler) directives, which map filename extensions to the specified handler. Accepts a list of hashes, with `extensions` serving to list the extensions being managed by the handler, and takes the form: `{ handler => 'handler-name', extensions => ['extension']}`. 

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path        => '/path/to/directory',
          addhandlers => [{ handler => 'cgi-script', extensions => ['.cgi']}],
        }, 
      ],
    }
```

######`allow`

Sets an [Allow](http://httpd.apache.org/docs/2.2/mod/mod_authz_host.html#allow) directive, which groups authorizations based on hostnames or IPs. **Deprecated:** This parameter is being deprecated due to a change in Apache. It will only work with Apache 2.2 and lower. You can use it as a single string for one rule or as an array for more than one.

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path  => '/path/to/directory', 
          allow => 'from example.org', 
        }, 
      ],
    }
```

######`allow_override`

Sets the types of directives allowed in [.htaccess](http://httpd.apache.org/docs/current/mod/core.html#allowoverride) files. Accepts an array.

```puppet
    apache::vhost { 'sample.example.net':
      docroot      => '/path/to/directory',
      directories  => [ 
        { path           => '/path/to/directory', 
          allow_override => ['AuthConfig', 'Indexes'], 
        }, 
      ],
    }
```

######`auth_basic_authoritative`

Sets the value for [AuthBasicAuthoritative](https://httpd.apache.org/docs/current/mod/mod_auth_basic.html#authbasicauthoritative), which determines whether authorization and authentication are passed to lower level Apache modules.

######`auth_basic_fake`

Sets the value for [AuthBasicFake](http://httpd.apache.org/docs/current/mod/mod_auth_basic.html#authbasicfake), which statically configures authorization credentials for a given directive block.

######`auth_basic_provider`

Sets the value for [AuthBasicProvider] (http://httpd.apache.org/docs/current/mod/mod_auth_basic.html#authbasicprovider), which sets the authentication provider for a given location.

######`auth_digest_algorithm`

Sets the value for [AuthDigestAlgorithm](http://httpd.apache.org/docs/current/mod/mod_auth_digest.html#authdigestalgorithm), which selects the algorithm used to calculate the challenge and response hashes.

######`auth_digest_domain`

Sets the value for [AuthDigestDomain](http://httpd.apache.org/docs/current/mod/mod_auth_digest.html#authdigestdomain), which allows you to specify one or more URIs in the same protection space for digest authentication.

######`auth_digest_nonce_lifetime`

Sets the value for [AuthDigestNonceLifetime](http://httpd.apache.org/docs/current/mod/mod_auth_digest.html#authdigestnoncelifetime), which controls how long the server nonce is valid.

######`auth_digest_provider`

Sets the value for [AuthDigestProvider](http://httpd.apache.org/docs/current/mod/mod_auth_digest.html#authdigestprovider), which sets the authentication provider for a given location.

######`auth_digest_qop`

Sets the value for [AuthDigestQop](http://httpd.apache.org/docs/current/mod/mod_auth_digest.html#authdigestqop), which determines the quality-of-protection to use in digest authentication.

######`auth_digest_shmem_size`

Sets the value for [AuthAuthDigestShmemSize](http://httpd.apache.org/docs/current/mod/mod_auth_digest.html#authdigestshmemsize), which defines the amount of shared memory allocated to the server for keeping track of clients.

######`auth_group_file`

Sets the value for [AuthGroupFile](https://httpd.apache.org/docs/current/mod/mod_authz_groupfile.html#authgroupfile), which sets the name of the text file containing the list of user groups for authorization.

######`auth_name`

Sets the value for [AuthName](http://httpd.apache.org/docs/current/mod/mod_authn_core.html#authname), which sets the name of the authorization realm.

######`auth_require`

Sets the entity name you're requiring to allow access. Read more about [Require](http://httpd.apache.org/docs/current/mod/mod_authz_host.html#requiredirectives).

######`auth_type`

Sets the value for [AuthType](http://httpd.apache.org/docs/current/mod/mod_authn_core.html#authtype), which guides the type of user authentication.

######`auth_user_file`

Sets the value for [AuthUserFile](http://httpd.apache.org/docs/current/mod/mod_authn_file.html#authuserfile), which sets the name of the text file containing the users/passwords for authentication.

######`custom_fragment`

Pass a string of custom configuration directives to be placed at the end of the directory configuration.

```puppet
  apache::vhost { 'monitor':
    … 
    custom_fragment => '
  <Location /balancer-manager>
    SetHandler balancer-manager
    Order allow,deny
    Allow from all
  </Location>
  <Location /server-status>
    SetHandler server-status
    Order allow,deny
    Allow from all
  </Location>
  ProxyStatus On',
}
```

######`deny`

Sets a [Deny](http://httpd.apache.org/docs/2.2/mod/mod_authz_host.html#deny) directive, specifying which hosts are denied access to the server. **Deprecated:** This parameter is being deprecated due to a change in Apache. It will only work with Apache 2.2 and lower. 

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path => '/path/to/directory', 
          deny => 'from example.org', 
        }, 
      ],
    }
```

######`error_documents`

An array of hashes used to override the [ErrorDocument](https://httpd.apache.org/docs/current/mod/core.html#errordocument) settings for the directory. 

```puppet
    apache::vhost { 'sample.example.net':
      directories => [ 
        { path            => '/srv/www',
          error_documents => [
            { 'error_code' => '503', 
              'document'   => '/service-unavail',
            },
          ],
        },
      ],
    }
```

######`headers`

Adds lines for [Header](http://httpd.apache.org/docs/current/mod/mod_headers.html#header) directives.

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => {
        path    => '/path/to/directory',
        headers => 'Set X-Robots-Tag "noindex, noarchive, nosnippet"',
      },
    }
```

######`index_options`

Allows configuration settings for [directory indexing](http://httpd.apache.org/docs/current/mod/mod_autoindex.html#indexoptions).

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path          => '/path/to/directory', 
          options       => ['Indexes','FollowSymLinks','MultiViews'], 
          index_options => ['IgnoreCase', 'FancyIndexing', 'FoldersFirst', 'NameWidth=*', 'DescriptionWidth=*', 'SuppressHTMLPreamble'],
        },
      ],
    }
```

######`index_order_default`

Sets the [default ordering](http://httpd.apache.org/docs/current/mod/mod_autoindex.html#indexorderdefault) of the directory index.

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path                => '/path/to/directory', 
          order               => 'Allow,Deny', 
          index_order_default => ['Descending', 'Date'],
        }, 
      ],
    }
```

######`options`

Lists the [Options](http://httpd.apache.org/docs/current/mod/core.html#options) for the given Directory block.

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path    => '/path/to/directory', 
          options => ['Indexes','FollowSymLinks','MultiViews'], 
        },
      ],
    }
```

######`order`

Sets the order of processing Allow and Deny statements as per [Apache core documentation](httpd.apache.org/docs/2.2/mod/mod_authz_host.html#order). **Deprecated:** This parameter is being deprecated due to a change in Apache. It will only work with Apache 2.2 and lower.

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path  => '/path/to/directory', 
          order => 'Allow,Deny', 
        },
      ],
    }
```

######`sethandler`

Sets a `SetHandler` directive as per the [Apache Core documentation](http://httpd.apache.org/docs/2.2/mod/core.html#sethandler). An example:

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path       => '/path/to/directory', 
          sethandler => 'None', 
        }
      ],
    }
```

######`passenger_enabled`

Sets the value for the [PassengerEnabled](http://www.modrails.com/documentation/Users%20guide%20Apache.html#PassengerEnabled) directory to 'on' or 'off'. Requires `apache::mod::passenger` to be included.

```puppet
    apache::vhost { 'sample.example.net':
      docroot     => '/path/to/directory',
      directories => [ 
        { path              => '/path/to/directory', 
          passenger_enabled => 'on',
        }, 
      ],
    }
```

*Note:* Be aware that there is an [issue](http://www.conandalton.net/2010/06/passengerenabled-off-not-working.html) using the PassengerEnabled directive with the PassengerHighPerformance directive.

######`php_admin_value` and `php_admin_flag`

`php_admin_value` sets the value of the directory, and `php_admin_flag` uses a boolean to configure the directory. Further information can be found [here](http://php.net/manual/en/configuration.changes.php).

######`ssl_options`

String or list of [SSLOptions](https://httpd.apache.org/docs/current/mod/mod_ssl.html#ssloptions), which configure SSL engine run-time options. This handler takes precedence over SSLOptions set in the parent block of the vhost.

```puppet
    apache::vhost { 'secure.example.net':
      docroot     => '/path/to/directory',
      directories => [
        { path        => '/path/to/directory', 
          ssl_options => '+ExportCertData', 
        },
        { path        => '/path/to/different/dir', 
          ssl_options => [ '-StdEnvVars', '+ExportCertData'],
        },
      ],
    }
```

######`suphp`

A hash containing the 'user' and 'group' keys for the [suPHP_UserGroup](http://www.suphp.org/DocumentationView.html?file=apache/CONFIG) setting. It must be used with `suphp_engine => on` in the vhost declaration, and may only be passed within `directories`.

```puppet
    apache::vhost { 'secure.example.net':
      docroot     => '/path/to/directory',
      directories => [
        { path  => '/path/to/directory', 
          suphp => 
            { user  =>  'myappuser', 
              group => 'myappgroup', 
            },
        },
      ],
    }
```

####SSL parameters for `apache::vhost`

All of the SSL parameters for `::vhost` will default to whatever is set in the base `apache` class. Use the below parameters to tweak individual SSL settings for specific vhosts.

#####`ssl`

Enables SSL for the virtual host. SSL vhosts only respond to HTTPS queries. Valid values are 'true' or 'false'. Defaults to 'false'. 

#####`ssl_ca`

Specifies the SSL certificate authority. Defaults to 'undef'.

#####`ssl_cert`

Specifies the SSL certification. Defaults are based on your OS: '/etc/pki/tls/certs/localhost.crt' for RedHat, '/etc/ssl/certs/ssl-cert-snakeoil.pem' for Debian, and '/usr/local/etc/apache22/server.crt' for FreeBSD.

#####`ssl_protocol`

Specifies [SSLProtocol](http://httpd.apache.org/docs/current/mod/mod_ssl.html#sslprotocol). Defaults to 'undef'. 

If you do not use this parameter, it will use the HTTPD default from ssl.conf.erb, 'all -SSLv2'.

#####`ssl_cipher`

Specifies [SSLCipherSuite](http://httpd.apache.org/docs/current/mod/mod_ssl.html#sslciphersuite). Defaults to 'undef'.

If you do not use this parameter, it will use the HTTPD default from ssl.conf.erb, 'HIGH:MEDIUM:!aNULL:!MD5'.

#####`ssl_honorcipherorder`

Sets [SSLHonorCipherOrder](http://httpd.apache.org/docs/current/mod/mod_ssl.html#sslhonorcipherorder), which is used to prefer the server's cipher preference order. Defaults to 'On' in the base `apache` config.

#####`ssl_certs_dir`

Specifies the location of the SSL certification directory. Defaults to '/etc/ssl/certs' on Debian, '/etc/pki/tls/certs' on RedHat, and '/usr/local/etc/apache22' on FreeBSD.

#####`ssl_chain`

Specifies the SSL chain. Defaults to 'undef'. (This default will work out of the box but must be updated in the base `apache` class with your specific certificate information before being used in production.)

#####`ssl_crl`

Specifies the certificate revocation list to use. Defaults to 'undef'. (This default will work out of the box but must be updated in the base `apache` class with your specific certificate information before being used in production.)

#####`ssl_crl_path`

Specifies the location of the certificate revocation list. Defaults to 'undef'. (This default will work out of the box but must be updated in the base `apache` class with your specific certificate information before being used in production.)

#####`ssl_key`

Specifies the SSL key. Defaults are based on your operating system: '/etc/pki/tls/private/localhost.key' for RedHat, '/etc/ssl/private/ssl-cert-snakeoil.key' for Debian, and '/usr/local/etc/apache22/server.key' for FreeBSD. (This default will work out of the box but must be updated in the base `apache` class with your specific certificate information before being used in production.)

#####`ssl_verify_client`

Sets the [SSLVerifyClient](http://httpd.apache.org/docs/current/mod/mod_ssl.html#sslverifyclient) directive, which sets the certificate verification level for client authentication. Valid values are: 'none', 'optional', 'require', and 'optional_no_ca'. Defaults to 'undef'.

```puppet
    apache::vhost { 'sample.example.net':
      …
      ssl_verify_client => 'optional',
    }
```

#####`ssl_verify_depth`

Sets the [SSLVerifyDepth](http://httpd.apache.org/docs/current/mod/mod_ssl.html#sslverifydepth) directive, which specifies the maximum depth of CA certificates in client certificate verification. Defaults to 'undef'.

```puppet
    apache::vhost { 'sample.example.net':
      …
      ssl_verify_depth => 1,
    }
```

#####`ssl_options`

Sets the [SSLOptions](http://httpd.apache.org/docs/current/mod/mod_ssl.html#ssloptions) directive, which configures various SSL engine run-time options. This is the global setting for the given vhost and can be a string or an array. Defaults to 'undef'. 

A string:

```puppet
    apache::vhost { 'sample.example.net':
      …
      ssl_options => '+ExportCertData',
    }
```

An array:

```puppet
    apache::vhost { 'sample.example.net':
      …
      ssl_options => [ '+StrictRequire', '+ExportCertData' ],
    }
```

#####`ssl_proxyengine`

Specifies whether or not to use [SSLProxyEngine](http://httpd.apache.org/docs/current/mod/mod_ssl.html#sslproxyengine). Valid values are 'true' and 'false'. Defaults to 'false'.


###Virtual Host Examples

The apache module allows you to set up pretty much any configuration of virtual host you might need. This section will address some common configurations, but look at the [Tests section](https://github.com/puppetlabs/puppetlabs-apache/tree/master/tests) for even more examples.

Configure a vhost with a server administrator

```puppet
    apache::vhost { 'third.example.com':
      port        => '80',
      docroot     => '/var/www/third',
      serveradmin => 'admin@example.com',
    }
```

- - -

Set up a vhost with aliased servers

```puppet
    apache::vhost { 'sixth.example.com':
      serveraliases => [
        'sixth.example.org',
        'sixth.example.net',
      ],
      port          => '80',
      docroot       => '/var/www/fifth',
    }
```

- - -

Configure a vhost with a cgi-bin

```puppet
    apache::vhost { 'eleventh.example.com':
      port        => '80',
      docroot     => '/var/www/eleventh',
      scriptalias => '/usr/lib/cgi-bin',
    }
```

- - -

Set up a vhost with a rack configuration

```puppet
    apache::vhost { 'fifteenth.example.com':
      port           => '80',
      docroot        => '/var/www/fifteenth',
      rack_base_uris => ['/rackapp1', '/rackapp2'],
    }
```

- - -

Set up a mix of SSL and non-SSL vhosts at the same domain

```puppet
    #The non-ssl vhost
    apache::vhost { 'first.example.com non-ssl':
      servername => 'first.example.com',
      port       => '80',
      docroot    => '/var/www/first',
    }

    #The SSL vhost at the same domain
    apache::vhost { 'first.example.com ssl':
      servername => 'first.example.com',
      port       => '443',
      docroot    => '/var/www/first',
      ssl        => true,
    }
```

- - -

Configure a vhost to redirect non-SSL connections to SSL

```puppet
    apache::vhost { 'sixteenth.example.com non-ssl':
      servername      => 'sixteenth.example.com',
      port            => '80',
      docroot         => '/var/www/sixteenth',
      redirect_status => 'permanent',
      redirect_dest   => 'https://sixteenth.example.com/'
    }
    apache::vhost { 'sixteenth.example.com ssl':
      servername => 'sixteenth.example.com',
      port       => '443',
      docroot    => '/var/www/sixteenth',
      ssl        => true,
    }
```

- - -

Set up IP-based vhosts on any listen port and have them respond to requests on specific IP addresses. In this example, we will set listening on ports 80 and 81. This is required because the example vhosts are not declared with a port parameter.

```puppet
    apache::listen { '80': }
    apache::listen { '81': }
```

Then we will set up the IP-based vhosts

```puppet
    apache::vhost { 'first.example.com':
      ip       => '10.0.0.10',
      docroot  => '/var/www/first',
      ip_based => true,
    }
    apache::vhost { 'second.example.com':
      ip       => '10.0.0.11',
      docroot  => '/var/www/second',
      ip_based => true,
    }
```

- - -

Configure a mix of name-based and IP-based vhosts. First, we will add two IP-based vhosts on 10.0.0.10, one SSL and one non-SSL

```puppet
    apache::vhost { 'The first IP-based vhost, non-ssl':
      servername => 'first.example.com',
      ip         => '10.0.0.10',
      port       => '80',
      ip_based   => true,
      docroot    => '/var/www/first',
    }
    apache::vhost { 'The first IP-based vhost, ssl':
      servername => 'first.example.com',
      ip         => '10.0.0.10',
      port       => '443',
      ip_based   => true,
      docroot    => '/var/www/first-ssl',
      ssl        => true,
    }
```

Then, we will add two name-based vhosts listening on 10.0.0.20

```puppet
    apache::vhost { 'second.example.com':
      ip      => '10.0.0.20',
      port    => '80',
      docroot => '/var/www/second',
    }
    apache::vhost { 'third.example.com':
      ip      => '10.0.0.20',
      port    => '80',
      docroot => '/var/www/third',
    }
```

If you want to add two name-based vhosts so that they will answer on either 10.0.0.10 or 10.0.0.20, you **MUST** declare `add_listen => 'false'` to disable the otherwise automatic 'Listen 80', as it will conflict with the preceding IP-based vhosts.

```puppet
    apache::vhost { 'fourth.example.com':
      port       => '80',
      docroot    => '/var/www/fourth',
      add_listen => false,
    }
    apache::vhost { 'fifth.example.com':
      port       => '80',
      docroot    => '/var/www/fifth',
      add_listen => false,
    }
```

###Load Balancing

####Defined Type: `apache::balancer`

`apache::balancer` creates an Apache balancer cluster. Each balancer cluster needs one or more balancer members, which are declared with [`apache::balancermember`](#defined-type-apachebalancermember). 

One `apache::balancer` defined resource should be defined for each Apache load balanced set of servers. The `apache::balancermember` resources for all balancer members can be exported and collected on a single Apache load balancer server using exported resources.

**Parameters within `apache::balancer`:**

#####`name`

Sets the balancer cluster's title. This parameter will also set the title of the conf.d file.

#####`proxy_set`

Configures key-value pairs as [ProxySet](http://httpd.apache.org/docs/current/mod/mod_proxy.html#proxyset) lines. Accepts a hash, and defaults to '{}'.

#####`collect_exported`

Determines whether or not to use exported resources. Valid values 'true' and 'false', defaults to 'true'. 

If you statically declare all of your backend servers, you should set this to 'false' to rely on existing declared balancer member resources. Also make sure to use `apache::balancermember` with array arguments.

If you wish to dynamically declare your backend servers via [exported resources](http://docs.puppetlabs.com/guides/exported_resources.html) collected on a central node, you must set this parameter to 'true' in order to collect the exported balancer member resources that were exported by the balancer member nodes.

If you choose not to use exported resources, all balancer members will be configured in a single puppet run. If you are using exported resources, Puppet has to run on the balanced nodes, then run on the balancer.

####Defined Type: `apache::balancermember`

Defines members of [mod_proxy_balancer](http://httpd.apache.org/docs/current/mod/mod_proxy_balancer.html), which will set up a balancer member inside a listening service configuration block in etc/apache/apache.cfg on the load balancer.

**Parameters within `apache::balancermember`:**

#####`name`

Sets the title of the resource. This name will also set the name of the concat fragment.

#####`balancer_cluster`

Sets the Apache service's instance name. This must match the name of a declared `apache::balancer` resource. Required.

#####`url`

Specifies the URL used to contact the balancer member server. Defaults to 'http://${::fqdn}/'.

#####`options`

An array of [options](http://httpd.apache.org/docs/2.2/mod/mod_proxy.html#balancermember) to be specified after the URL. Accepts any key-value pairs available to [ProxyPass](http://httpd.apache.org/docs/2.2/mod/mod_proxy.html#proxypass).

####Examples

To load balance with exported resources, export the `balancermember` from the balancer member

```puppet
      @@apache::balancermember { "${::fqdn}-puppet00":
        balancer_cluster => 'puppet00',
        url              => "ajp://${::fqdn}:8009"
        options          => ['ping=5', 'disablereuse=on', 'retry=5', 'ttl=120'],
      }
```

Then, on the proxy server, create the balancer cluster

```puppet
      apache::balancer { 'puppet00': }
```

To load balance without exported resources, declare the following on the proxy

```puppet
    apache::balancer { 'puppet00': }
    apache::balancermember { "${::fqdn}-puppet00":
        balancer_cluster => 'puppet00',
        url              => "ajp://${::fqdn}:8009"
        options          => ['ping=5', 'disablereuse=on', 'retry=5', 'ttl=120'],
      }
```

Then declare `apache::balancer` and `apache::balancermember` on the proxy server.

If you need to use ProxySet in the balancer config

```puppet
      apache::balancer { 'puppet01':
        proxy_set => {'stickysession' => 'JSESSIONID'},
      }
```

##Reference

###Classes

####Public Classes

* [`apache`](#class-apache): Guides the basic setup of Apache.
* `apache::dev`: Installs Apache development libraries. (*Note:* On FreeBSD, you must declare `apache::package` or `apache` before `apache::dev`.)
* [`apache::mod::[name]`](#classes-apachemodname): Enables specific Apache HTTPD modules.
 
####Private Classes

* `apache::confd::no_accf`: Creates the no-accf.conf configuration file in conf.d, required by FreeBSD's Apache 2.4.
* `apache::default_confd_files`: Includes conf.d files for FreeBSD.
* `apache::default_mods`: Installs the Apache modules required to run the default configuration.
* `apache::package`: Installs and configures basic Apache packages.
* `apache::params`: Manages Apache parameters.
* `apache::service`: Manages the Apache daemon.

###Defined Types

####Public Defined Types

* `apache::balancer`: Creates an Apache balancer cluster.
* `apache::balancermember`: Defines members of [mod_proxy_balancer](http://httpd.apache.org/docs/current/mod/mod_proxy_balancer.html).
* `apache::listen`: Based on the title, controls which ports Apache binds to for listening. Adds [Listen](http://httpd.apache.org/docs/current/bind.html) directives to ports.conf in the Apache HTTPD configuration directory. Titles take the form '<port>', '<ipv4>:<port>', or '<ipv6>:<port>'.
* `apache::mod`: Used to enable arbitrary Apache HTTPD modules for which there is no specific `apache::mod::[name]` class.
* `apache::namevirtualhost`: Enables name-based hosting of a virtual host. Adds all [NameVirtualHost](http://httpd.apache.org/docs/current/vhosts/name-based.html) directives to the `ports.conf` file in the Apache HTTPD configuration directory. Titles take the form '\*', '*:<port>', '\_default_:<port>, '<ip>', or '<ip>:<port>'.
* `apache::vhost`: Allows specialized configurations for virtual hosts that have requirements outside the defaults. 

####Private Defined Types

* `apache::peruser::multiplexer`: Enables the [Peruser](http://www.freebsd.org/cgi/url.cgi?ports/www/apache22-peruser-mpm/pkg-descr) module for FreeBSD only.
* `apache::peruser::processor`: Enables the [Peruser](http://www.freebsd.org/cgi/url.cgi?ports/www/apache22-peruser-mpm/pkg-descr) module for FreeBSD only.

###Templates

The Apache module relies heavily on templates to enable the `vhost` and `apache::mod` defined types. These templates are built based on Facter facts around your operating system. Unless explicitly called out, most templates are not meant for configuration.

##Limitations

###Ubuntu 10.04

The `apache::vhost::WSGIImportScript` parameter creates a statement inside the VirtualHost which is unsupported on older versions of Apache, causing this to fail.  This will be remedied in a future refactoring.

###RHEL/CentOS 5

The `apache::mod::passenger` and `apache::mod::proxy_html` classes are untested since repositories are missing compatible packages.   

###RHEL/CentOS 7

The `apache::mod::passenger` class is untested as the repository does not have packages for EL7 yet.  The fact that passenger packages aren't available also makes us unable to test the `rack_base_uri` parameter in `apache::vhost`.

###General

This module is CI tested on Centos 5 & 6, Ubuntu 12.04 & 14.04, Debian 7, and RHEL 5, 6 & 7 platforms against both the OSS and Enterprise version of Puppet. 

The module contains support for other distributions and operating systems, such as FreeBSD and Amazon Linux, but is not formally tested on those and regressions may occur.

###SELinux and Custom Paths

If you are running with SELinux in enforcing mode and want to use custom paths for your `logroot`, `mod_dir`, `vhost_dir`, and `docroot`, you will need to manage the context for the files yourself.

Something along the lines of:

```puppet
        exec { 'set_apache_defaults':
          command => 'semanage fcontext -a -t httpd_sys_content_t "/custom/path(/.*)?"',
          path    => '/bin:/usr/bin/:/sbin:/usr/sbin',
          require => Package['policycoreutils-python'],
        }
        package { 'policycoreutils-python': ensure => installed }
        exec { 'restorecon_apache':
          command => 'restorecon -Rv /apache_spec',
          path    => '/bin:/usr/bin/:/sbin:/usr/sbin',
          before  => Service['httpd'],
          require => Class['apache'],
        }
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        file { '/custom/path': ensure => directory, }
        file { '/custom/path/include': ensure => present, content => '#additional_includes' }
        apache::vhost { 'test.server':
          docroot             => '/custom/path',
          additional_includes => '/custom/path/include',
        }
```

You need to set the contexts using `semanage fcontext` not `chcon` because `file {...}` resources will reset the context to the values in the database if the resource isn't specifying the context.

##Development

###Contributing

Puppet Labs modules on the Puppet Forge are open projects, and community contributions are essential for keeping them great. We can’t access the huge number of platforms and myriad of hardware, software, and deployment configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our modules work in your environment. There are a few guidelines that we need contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)

###Running tests

This project contains tests for both [rspec-puppet](http://rspec-puppet.com/) and [beaker-rspec](https://github.com/puppetlabs/beaker-rspec) to verify functionality. For in-depth information please see their respective documentation.

Quickstart:

    gem install bundler
    bundle install
    bundle exec rake spec
    bundle exec rspec spec/acceptance
    RS_DEBUG=yes bundle exec rspec spec/acceptance
