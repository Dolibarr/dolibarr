# Class: apache::params
#
# This class manages Apache parameters
#
# Parameters:
# - The $user that Apache runs as
# - The $group that Apache runs as
# - The $apache_name is the name of the package and service on the relevant
#   distribution
# - The $php_package is the name of the package that provided PHP
# - The $ssl_package is the name of the Apache SSL package
# - The $apache_dev is the name of the Apache development libraries package
# - The $conf_contents is the contents of the Apache configuration file
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
class apache::params inherits ::apache::version {
  if($::fqdn) {
    $servername = $::fqdn
  } else {
    $servername = $::hostname
  }

  # The default error log level
  $log_level = 'warn'

  if $::osfamily == 'RedHat' or $::operatingsystem == 'amazon' {
    $user                 = 'apache'
    $group                = 'apache'
    $root_group           = 'root'
    $apache_name          = 'httpd'
    $service_name         = 'httpd'
    $httpd_dir            = '/etc/httpd'
    $server_root          = '/etc/httpd'
    $conf_dir             = "${httpd_dir}/conf"
    $confd_dir            = "${httpd_dir}/conf.d"
    $mod_dir              = "${httpd_dir}/conf.d"
    $mod_enable_dir       = undef
    $vhost_dir            = "${httpd_dir}/conf.d"
    $vhost_enable_dir     = undef
    $conf_file            = 'httpd.conf'
    $ports_file           = "${conf_dir}/ports.conf"
    $logroot              = '/var/log/httpd'
    $lib_path             = 'modules'
    $mpm_module           = 'prefork'
    $dev_packages         = 'httpd-devel'
    $default_ssl_cert     = '/etc/pki/tls/certs/localhost.crt'
    $default_ssl_key      = '/etc/pki/tls/private/localhost.key'
    $ssl_certs_dir        = '/etc/pki/tls/certs'
    $passenger_conf_file  = 'passenger_extra.conf'
    $passenger_conf_package_file = 'passenger.conf'
    $passenger_root       = undef
    $passenger_ruby       = undef
    $passenger_default_ruby = undef
    $suphp_addhandler     = 'php5-script'
    $suphp_engine         = 'off'
    $suphp_configpath     = undef
    $mod_packages         = {
      'auth_kerb'   => 'mod_auth_kerb',
      'authnz_ldap' => 'mod_authz_ldap',
      'fastcgi'     => 'mod_fastcgi',
      'fcgid'       => 'mod_fcgid',
      'pagespeed'   => 'mod-pagespeed-stable',
      'passenger'   => 'mod_passenger',
      'perl'        => 'mod_perl',
      'php5'        => $::apache::version::distrelease ? {
        '5'     => 'php53',
        default => 'php',
      },
      'proxy_html'  => 'mod_proxy_html',
      'python'      => 'mod_python',
      'shibboleth'  => 'shibboleth',
      'ssl'         => 'mod_ssl',
      'wsgi'        => 'mod_wsgi',
      'dav_svn'     => 'mod_dav_svn',
      'suphp'       => 'mod_suphp',
      'xsendfile'   => 'mod_xsendfile',
      'nss'         => 'mod_nss',
    }
    $mod_libs             = {
      'php5' => 'libphp5.so',
      'nss'  => 'libmodnss.so',
    }
    $conf_template        = 'apache/httpd.conf.erb'
    $keepalive            = 'Off'
    $keepalive_timeout    = 15
    $max_keepalive_requests = 100
    $fastcgi_lib_path     = undef
    $mime_support_package = 'mailcap'
    $mime_types_config    = '/etc/mime.types'
  } elsif $::osfamily == 'Debian' {
    $user                = 'www-data'
    $group               = 'www-data'
    $root_group          = 'root'
    $apache_name         = 'apache2'
    $service_name        = 'apache2'
    $httpd_dir           = '/etc/apache2'
    $server_root         = '/etc/apache2'
    $conf_dir            = $httpd_dir
    $confd_dir           = "${httpd_dir}/conf.d"
    $mod_dir             = "${httpd_dir}/mods-available"
    $mod_enable_dir      = "${httpd_dir}/mods-enabled"
    $vhost_dir           = "${httpd_dir}/sites-available"
    $vhost_enable_dir    = "${httpd_dir}/sites-enabled"
    $conf_file           = 'apache2.conf'
    $ports_file          = "${conf_dir}/ports.conf"
    $logroot             = '/var/log/apache2'
    $lib_path            = '/usr/lib/apache2/modules'
    $mpm_module          = 'worker'
    $dev_packages        = ['libaprutil1-dev', 'libapr1-dev', 'apache2-prefork-dev']
    $default_ssl_cert    = '/etc/ssl/certs/ssl-cert-snakeoil.pem'
    $default_ssl_key     = '/etc/ssl/private/ssl-cert-snakeoil.key'
    $ssl_certs_dir       = '/etc/ssl/certs'
    $suphp_addhandler    = 'x-httpd-php'
    $suphp_engine        = 'off'
    $suphp_configpath    = '/etc/php5/apache2'
    $mod_packages        = {
      'auth_kerb'   => 'libapache2-mod-auth-kerb',
      'dav_svn'     => 'libapache2-svn',
      'fastcgi'     => 'libapache2-mod-fastcgi',
      'fcgid'       => 'libapache2-mod-fcgid',
      'nss'         => 'libapache2-mod-nss',
      'pagespeed'   => 'mod-pagespeed-stable',
      'passenger'   => 'libapache2-mod-passenger',
      'perl'        => 'libapache2-mod-perl2',
      'php5'        => 'libapache2-mod-php5',
      'proxy_html'  => 'libapache2-mod-proxy-html',
      'python'      => 'libapache2-mod-python',
      'rpaf'        => 'libapache2-mod-rpaf',
      'suphp'       => 'libapache2-mod-suphp',
      'wsgi'        => 'libapache2-mod-wsgi',
      'xsendfile'   => 'libapache2-mod-xsendfile',
    }
    $mod_libs             = {
      'php5' => 'libphp5.so',
    }
    $conf_template          = 'apache/httpd.conf.erb'
    $keepalive              = 'Off'
    $keepalive_timeout      = 15
    $max_keepalive_requests = 100
    $fastcgi_lib_path       = '/var/lib/apache2/fastcgi'
    $mime_support_package = 'mime-support'
    $mime_types_config    = '/etc/mime.types'

    #
    # Passenger-specific settings
    #

    $passenger_conf_file         = 'passenger.conf'
    $passenger_conf_package_file = undef

    case $::operatingsystem {
      'Ubuntu': {
        case $::lsbdistrelease {
          '12.04': {
            $passenger_root         = '/usr'
            $passenger_ruby         = '/usr/bin/ruby'
            $passenger_default_ruby = undef
          }
          '14.04': {
            $passenger_root         = '/usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini'
            $passenger_ruby         = undef
            $passenger_default_ruby = '/usr/bin/ruby'
          }
          default: {
            # The following settings may or may not work on Ubuntu releases not
            # supported by this module.
            $passenger_root         = '/usr'
            $passenger_ruby         = '/usr/bin/ruby'
            $passenger_default_ruby = undef
          }
        }
      }
      'Debian': {
        case $::lsbdistcodename {
          'wheezy': {
            $passenger_root         = '/usr'
            $passenger_ruby         = '/usr/bin/ruby'
            $passenger_default_ruby = undef
          }
          default: {
            # The following settings may or may not work on Debian releases not
            # supported by this module.
            $passenger_root         = '/usr'
            $passenger_ruby         = '/usr/bin/ruby'
            $passenger_default_ruby = undef
          }
        }
      }
    }
  } elsif $::osfamily == 'FreeBSD' {
    $user             = 'www'
    $group            = 'www'
    $root_group       = 'wheel'
    $apache_name      = 'apache22'
    $service_name     = 'apache22'
    $httpd_dir        = '/usr/local/etc/apache22'
    $server_root      = '/usr/local'
    $conf_dir         = $httpd_dir
    $confd_dir        = "${httpd_dir}/Includes"
    $mod_dir          = "${httpd_dir}/Modules"
    $mod_enable_dir   = undef
    $vhost_dir        = "${httpd_dir}/Vhosts"
    $vhost_enable_dir = undef
    $conf_file        = 'httpd.conf'
    $ports_file       = "${conf_dir}/ports.conf"
    $logroot          = '/var/log/apache22'
    $lib_path         = '/usr/local/libexec/apache22'
    $mpm_module       = 'prefork'
    $dev_packages     = undef
    $default_ssl_cert = '/usr/local/etc/apache22/server.crt'
    $default_ssl_key  = '/usr/local/etc/apache22/server.key'
    $ssl_certs_dir    = '/usr/local/etc/apache22'
    $passenger_conf_file = 'passenger.conf'
    $passenger_conf_package_file = undef
    $passenger_root   = '/usr/local/lib/ruby/gems/1.9/gems/passenger-4.0.10'
    $passenger_ruby   = '/usr/bin/ruby'
    $passenger_default_ruby = undef
    $suphp_addhandler = 'php5-script'
    $suphp_engine     = 'off'
    $suphp_configpath = undef
    $mod_packages     = {
      # NOTE: I list here only modules that are not included in www/apache22
      # NOTE: 'passenger' needs to enable APACHE_SUPPORT in make config
      # NOTE: 'php' needs to enable APACHE option in make config
      # NOTE: 'dav_svn' needs to enable MOD_DAV_SVN make config
      # NOTE: not sure where the shibboleth should come from
      # NOTE: don't know where the shibboleth module should come from
      'auth_kerb'  => 'www/mod_auth_kerb2',
      'fcgid'      => 'www/mod_fcgid',
      'passenger'  => 'www/rubygem-passenger',
      'perl'       => 'www/mod_perl2',
      'php5'       => 'lang/php5',
      'proxy_html' => 'www/mod_proxy_html',
      'python'     => 'www/mod_python3',
      'wsgi'       => 'www/mod_wsgi',
      'dav_svn'    => 'devel/subversion',
      'xsendfile'  => 'www/mod_xsendfile',
      'rpaf'       => 'www/mod_rpaf2'
    }
    $mod_libs         = {
      'php5' => 'libphp5.so',
    }
    $conf_template        = 'apache/httpd.conf.erb'
    $keepalive            = 'Off'
    $keepalive_timeout    = 15
    $max_keepalive_requests = 100
    $fastcgi_lib_path     = undef # TODO: revisit
    $mime_support_package = 'misc/mime-support'
    $mime_types_config    = '/usr/local/etc/mime.types'
  } else {
    fail("Class['apache::params']: Unsupported osfamily: ${::osfamily}")
  }
}
