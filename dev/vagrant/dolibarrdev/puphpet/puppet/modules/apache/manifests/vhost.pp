# Definition: apache::vhost
#
# This class installs Apache Virtual Hosts
#
# Parameters:
# - The $port to configure the host on
# - The $docroot provides the DocumentRoot variable
# - The $virtual_docroot provides VirtualDocumentationRoot variable
# - The $serveradmin will specify an email address for Apache that it will
#   display when it renders one of it's error pages
# - The $ssl option is set true or false to enable SSL for this Virtual Host
# - The $priority of the site
# - The $servername is the primary name of the virtual host
# - The $serveraliases of the site
# - The $ip to configure the host on, defaulting to *
# - The $options for the given vhost
# - The $override for the given vhost (list of AllowOverride arguments)
# - The $vhost_name for name based virtualhosting, defaulting to *
# - The $logroot specifies the location of the virtual hosts logfiles, default
#   to /var/log/<apache log location>/
# - The $log_level specifies the verbosity of the error log for this vhost. Not
#   set by default for the vhost, instead the global server configuration default
#   of 'warn' is used.
# - The $access_log specifies if *_access.log directives should be configured.
# - The $ensure specifies if vhost file is present or absent.
# - The $headers is a list of Header statement strings as per http://httpd.apache.org/docs/2.2/mod/mod_headers.html#header
# - The $request_headers is a list of RequestHeader statement strings as per http://httpd.apache.org/docs/2.2/mod/mod_headers.html#requestheader
# - $aliases is a list of Alias hashes for mod_alias as per http://httpd.apache.org/docs/current/mod/mod_alias.html
#   each statement is a hash in the form of { alias => '/alias', path => '/real/path/to/directory' }
# - $directories is a lost of hashes for creating <Directory> statements as per http://httpd.apache.org/docs/2.2/mod/core.html#directory
#   each statement is a hash in the form of { path => '/path/to/directory', <directive> => <value>}
#   see README.md for list of supported directives.
#
# Actions:
# - Install Apache Virtual Hosts
#
# Requires:
# - The apache class
#
# Sample Usage:
#
#  # Simple vhost definition:
#  apache::vhost { 'site.name.fqdn':
#    port => '80',
#    docroot => '/path/to/docroot',
#  }
#
#  # Multiple Mod Rewrites:
#  apache::vhost { 'site.name.fqdn':
#    port => '80',
#    docroot => '/path/to/docroot',
#    rewrites => [
#      {
#        comment       => 'force www domain',
#        rewrite_cond => ['%{HTTP_HOST} ^([a-z.]+)?example.com$ [NC]', '%{HTTP_HOST} !^www. [NC]'],
#        rewrite_rule => ['.? http://www.%1example.com%{REQUEST_URI} [R=301,L]']
#      },
#      {
#        comment       => 'prevent image hotlinking',
#        rewrite_cond => ['%{HTTP_REFERER} !^$', '%{HTTP_REFERER} !^http://(www.)?example.com/ [NC]'],
#        rewrite_rule => ['.(gif|jpg|png)$ - [F]']
#      },
#    ]
#  }
#
#  # SSL vhost with non-SSL rewrite:
#  apache::vhost { 'site.name.fqdn':
#    port    => '443',
#    ssl     => true,
#    docroot => '/path/to/docroot',
#  }
#  apache::vhost { 'site.name.fqdn':
#    port            => '80',
#    docroot         => '/path/to/other_docroot',
#    custom_fragment => template("${module_name}/my_fragment.erb"),
#  }
#
define apache::vhost(
    $docroot,
    $virtual_docroot             = false,
    $port                        = undef,
    $ip                          = undef,
    $ip_based                    = false,
    $add_listen                  = true,
    $docroot_owner               = 'root',
    $docroot_group               = $::apache::params::root_group,
    $docroot_mode                = undef,
    $serveradmin                 = undef,
    $ssl                         = false,
    $ssl_cert                    = $::apache::default_ssl_cert,
    $ssl_key                     = $::apache::default_ssl_key,
    $ssl_chain                   = $::apache::default_ssl_chain,
    $ssl_ca                      = $::apache::default_ssl_ca,
    $ssl_crl_path                = $::apache::default_ssl_crl_path,
    $ssl_crl                     = $::apache::default_ssl_crl,
    $ssl_certs_dir               = $::apache::params::ssl_certs_dir,
    $ssl_protocol                = undef,
    $ssl_cipher                  = undef,
    $ssl_honorcipherorder        = undef,
    $ssl_verify_client           = undef,
    $ssl_verify_depth            = undef,
    $ssl_options                 = undef,
    $ssl_proxyengine             = false,
    $priority                    = undef,
    $default_vhost               = false,
    $servername                  = $name,
    $serveraliases               = [],
    $options                     = ['Indexes','FollowSymLinks','MultiViews'],
    $override                    = ['None'],
    $directoryindex              = '',
    $vhost_name                  = '*',
    $logroot                     = $::apache::logroot,
    $log_level                   = undef,
    $access_log                  = true,
    $access_log_file             = undef,
    $access_log_pipe             = undef,
    $access_log_syslog           = undef,
    $access_log_format           = undef,
    $access_log_env_var          = undef,
    $aliases                     = undef,
    $directories                 = undef,
    $error_log                   = true,
    $error_log_file              = undef,
    $error_log_pipe              = undef,
    $error_log_syslog            = undef,
    $error_documents             = [],
    $fallbackresource            = undef,
    $scriptalias                 = undef,
    $scriptaliases               = [],
    $proxy_dest                  = undef,
    $proxy_pass                  = undef,
    $suphp_addhandler            = $::apache::params::suphp_addhandler,
    $suphp_engine                = $::apache::params::suphp_engine,
    $suphp_configpath            = $::apache::params::suphp_configpath,
    $php_admin_flags             = [],
    $php_admin_values            = [],
    $no_proxy_uris               = [],
    $proxy_preserve_host         = false,
    $redirect_source             = '/',
    $redirect_dest               = undef,
    $redirect_status             = undef,
    $redirectmatch_status        = undef,
    $redirectmatch_regexp        = undef,
    $rack_base_uris              = undef,
    $headers                     = undef,
    $request_headers             = undef,
    $rewrites                    = undef,
    $rewrite_base                = undef,
    $rewrite_rule                = undef,
    $rewrite_cond                = undef,
    $setenv                      = [],
    $setenvif                    = [],
    $block                       = [],
    $ensure                      = 'present',
    $wsgi_application_group      = undef,
    $wsgi_daemon_process         = undef,
    $wsgi_daemon_process_options = undef,
    $wsgi_import_script          = undef,
    $wsgi_import_script_options  = undef,
    $wsgi_process_group          = undef,
    $wsgi_script_aliases         = undef,
    $wsgi_pass_authorization     = undef,
    $custom_fragment             = undef,
    $itk                         = undef,
    $action                      = undef,
    $fastcgi_server              = undef,
    $fastcgi_socket              = undef,
    $fastcgi_dir                 = undef,
    $additional_includes         = [],
    $apache_version              = $::apache::apache_version,
    $suexec_user_group           = undef,
  ) {
  # The base class must be included first because it is used by parameter defaults
  if ! defined(Class['apache']) {
    fail('You must include the apache base class before using any apache defined resources')
  }

  $apache_name = $::apache::params::apache_name

  validate_re($ensure, '^(present|absent)$',
  "${ensure} is not supported for ensure.
  Allowed values are 'present' and 'absent'.")
  validate_re($suphp_engine, '^(on|off)$',
  "${suphp_engine} is not supported for suphp_engine.
  Allowed values are 'on' and 'off'.")
  validate_bool($ip_based)
  validate_bool($access_log)
  validate_bool($error_log)
  validate_bool($ssl)
  validate_bool($default_vhost)
  validate_bool($ssl_proxyengine)
  if $rewrites {
    validate_array($rewrites)
    validate_hash($rewrites[0])
  }

  if $suexec_user_group {
    validate_re($suexec_user_group, '^\w+ \w+$',
    "${suexec_user_group} is not supported for suexec_user_group.  Must be 'user group'.")
  }

  # Deprecated backwards-compatibility
  if $rewrite_base {
    warning('Apache::Vhost: parameter rewrite_base is deprecated in favor of rewrites')
  }
  if $rewrite_rule {
    warning('Apache::Vhost: parameter rewrite_rule is deprecated in favor of rewrites')
  }
  if $rewrite_cond {
    warning('Apache::Vhost parameter rewrite_cond is deprecated in favor of rewrites')
  }

  if $wsgi_script_aliases {
    validate_hash($wsgi_script_aliases)
  }
  if $wsgi_daemon_process_options {
    validate_hash($wsgi_daemon_process_options)
  }
  if $wsgi_import_script_options {
    validate_hash($wsgi_import_script_options)
  }
  if $itk {
    validate_hash($itk)
  }

  if $log_level {
    validate_re($log_level, '^(emerg|alert|crit|error|warn|notice|info|debug)$',
    "Log level '${log_level}' is not one of the supported Apache HTTP Server log levels.")
  }

  if $access_log_file and $access_log_pipe {
    fail("Apache::Vhost[${name}]: 'access_log_file' and 'access_log_pipe' cannot be defined at the same time")
  }

  if $error_log_file and $error_log_pipe {
    fail("Apache::Vhost[${name}]: 'error_log_file' and 'error_log_pipe' cannot be defined at the same time")
  }

  if $fallbackresource {
    validate_re($fallbackresource, '^/|disabled', 'Please make sure fallbackresource starts with a / (or is "disabled")')
  }

  if $ssl and $ensure == 'present' {
    include ::apache::mod::ssl
    # Required for the AddType lines.
    include ::apache::mod::mime
  }

  if $virtual_docroot {
    include ::apache::mod::vhost_alias
  }

  if $wsgi_daemon_process {
    include ::apache::mod::wsgi
  }

  if $suexec_user_group {
    include ::apache::mod::suexec
  }

  # This ensures that the docroot exists
  # But enables it to be specified across multiple vhost resources
  if ! defined(File[$docroot]) {
    file { $docroot:
      ensure  => directory,
      owner   => $docroot_owner,
      group   => $docroot_group,
      mode    => $docroot_mode,
      require => Package['httpd'],
    }
  }

  # Same as above, but for logroot
  if ! defined(File[$logroot]) {
    file { $logroot:
      ensure  => directory,
      require => Package['httpd'],
    }
  }


  # Is apache::mod::passenger enabled (or apache::mod['passenger'])
  $passenger_enabled = defined(Apache::Mod['passenger'])

  # Define log file names
  if $access_log_file {
    $access_log_destination = "${logroot}/${access_log_file}"
  } elsif $access_log_pipe {
    $access_log_destination = $access_log_pipe
  } elsif $access_log_syslog {
    $access_log_destination = $access_log_syslog
  } else {
    if $ssl {
      $access_log_destination = "${logroot}/${name}_access_ssl.log"
    } else {
      $access_log_destination = "${logroot}/${name}_access.log"
    }
  }

  if $error_log_file {
    $error_log_destination = "${logroot}/${error_log_file}"
  } elsif $error_log_pipe {
    $error_log_destination = $error_log_pipe
  } elsif $error_log_syslog {
    $error_log_destination = $error_log_syslog
  } else {
    if $ssl {
      $error_log_destination = "${logroot}/${name}_error_ssl.log"
    } else {
      $error_log_destination = "${logroot}/${name}_error.log"
    }
  }

  # Set access log format
  if $access_log_format {
    $_access_log_format = "\"${access_log_format}\""
  } else {
    $_access_log_format = 'combined'
  }

  if $access_log_env_var {
    $_access_log_env_var = "env=${access_log_env_var}"
  }

  if $ip {
    if $port {
      $listen_addr_port = "${ip}:${port}"
      $nvh_addr_port = "${ip}:${port}"
    } else {
      $listen_addr_port = undef
      $nvh_addr_port = $ip
      if ! $servername and ! $ip_based {
        fail("Apache::Vhost[${name}]: must pass 'ip' and/or 'port' parameters for name-based vhosts")
      }
    }
  } else {
    if $port {
      $listen_addr_port = $port
      $nvh_addr_port = "${vhost_name}:${port}"
    } else {
      $listen_addr_port = undef
      $nvh_addr_port = $name
      if ! $servername {
        fail("Apache::Vhost[${name}]: must pass 'ip' and/or 'port' parameters, and/or 'servername' parameter")
      }
    }
  }
  if $add_listen {
    if $ip and defined(Apache::Listen[$port]) {
      fail("Apache::Vhost[${name}]: Mixing IP and non-IP Listen directives is not possible; check the add_listen parameter of the apache::vhost define to disable this")
    }
    if ! defined(Apache::Listen[$listen_addr_port]) and $listen_addr_port and $ensure == 'present' {
      ::apache::listen { $listen_addr_port: }
    }
  }
  if ! $ip_based {
    if ! defined(Apache::Namevirtualhost[$nvh_addr_port]) and $ensure == 'present' and (versioncmp($apache_version, '2.4') < 0) {
      ::apache::namevirtualhost { $nvh_addr_port: }
    }
  }

  # Load mod_rewrite if needed and not yet loaded
  if $rewrites or $rewrite_cond {
    if ! defined(Class['apache::mod::rewrite']) {
      include ::apache::mod::rewrite
    }
  }

  # Load mod_alias if needed and not yet loaded
  if ($scriptalias or $scriptaliases != []) or ($redirect_source and $redirect_dest) {
    if ! defined(Class['apache::mod::alias']) {
      include ::apache::mod::alias
    }
  }

  # Load mod_proxy if needed and not yet loaded
  if ($proxy_dest or $proxy_pass) {
    if ! defined(Class['apache::mod::proxy']) {
      include ::apache::mod::proxy
    }
    if ! defined(Class['apache::mod::proxy_http']) {
      include ::apache::mod::proxy_http
    }
  }

  # Load mod_passenger if needed and not yet loaded
  if $rack_base_uris {
    if ! defined(Class['apache::mod::passenger']) {
      include ::apache::mod::passenger
    }
  }

  # Load mod_fastci if needed and not yet loaded
  if $fastcgi_server and $fastcgi_socket {
    if ! defined(Class['apache::mod::fastcgi']) {
      include ::apache::mod::fastcgi
    }
  }

  # Configure the defaultness of a vhost
  if $priority {
    $priority_real = $priority
  } elsif $default_vhost {
    $priority_real = '10'
  } else {
    $priority_real = '25'
  }

  # Check if mod_headers is required to process $headers/$request_headers
  if $headers or $request_headers {
    if ! defined(Class['apache::mod::headers']) {
      include ::apache::mod::headers
    }
  }

  ## Apache include does not always work with spaces in the filename
  $filename = regsubst($name, ' ', '_', 'G')

  ## Create a default directory list if none defined
  if $directories {
    if !is_hash($directories) and !(is_array($directories) and is_hash($directories[0])) {
      fail("Apache::Vhost[${name}]: 'directories' must be either a Hash or an Array of Hashes")
    }
    $_directories = $directories
  } else {
    $_directory = {
      provider       => 'directory',
      path           => $docroot,
      options        => $options,
      allow_override => $override,
      directoryindex => $directoryindex,
    }

    if versioncmp($apache_version, '2.4') >= 0 {
      $_directory_version = {
        require => 'all granted',
      }
    } else {
      $_directory_version = {
        order => 'allow,deny',
        allow => 'from all',
      }
    }

    $_directories = [ merge($_directory, $_directory_version) ]
  }

  # Template uses:
  # - $nvh_addr_port
  # - $servername
  # - $serveradmin
  # - $docroot
  # - $virtual_docroot
  # - $options
  # - $override
  # - $logroot
  # - $name
  # - $aliases
  # - $_directories
  # - $log_level
  # - $access_log
  # - $access_log_destination
  # - $_access_log_format
  # - $_access_log_env_var
  # - $error_log
  # - $error_log_destination
  # - $error_documents
  # - $fallbackresource
  # - $custom_fragment
  # - $additional_includes
  # block fragment:
  #   - $block
  # directories fragment:
  #   - $passenger_enabled
  #   - $php_admin_flags
  #   - $php_admin_values
  #   - $directories (a list of key-value hashes is expected)
  # fastcgi fragment:
  #   - $fastcgi_server
  #   - $fastcgi_socket
  #   - $fastcgi_dir
  # proxy fragment:
  #   - $proxy_dest
  #   - $no_proxy_uris
  #   - $proxy_preserve_host (true to set ProxyPreserveHost to on and false to off
  # rack fragment:
  #   - $rack_base_uris
  # redirect fragment:
  #   - $redirect_source
  #   - $redirect_dest
  #   - $redirect_status
  # header fragment
  #   - $headers
  # requestheader fragment:
  #   - $request_headers
  # rewrite fragment:
  #   - $rewrites
  # scriptalias fragment:
  #   - $scriptalias
  #   - $scriptaliases
  #   - $ssl
  # serveralias fragment:
  #   - $serveraliases
  # setenv fragment:
  #   - $setenv
  #   - $setenvif
  # ssl fragment:
  #   - $ssl
  #   - $ssl_cert
  #   - $ssl_key
  #   - $ssl_chain
  #   - $ssl_certs_dir
  #   - $ssl_ca
  #   - $ssl_crl
  #   - $ssl_crl_path
  #   - $ssl_verify_client
  #   - $ssl_verify_depth
  #   - $ssl_options
  # suphp fragment:
  #   - $suphp_addhandler
  #   - $suphp_engine
  #   - $suphp_configpath
  # wsgi fragment:
  #   - $wsgi_application_group
  #   - $wsgi_daemon_process
  #   - $wsgi_import_script
  #   - $wsgi_process_group
  #   - $wsgi_script_aliases
  file { "${priority_real}-${filename}.conf":
    ensure  => $ensure,
    path    => "${::apache::vhost_dir}/${priority_real}-${filename}.conf",
    content => template('apache/vhost.conf.erb'),
    owner   => 'root',
    group   => $::apache::params::root_group,
    mode    => '0644',
    require => [
      Package['httpd'],
      File[$docroot],
      File[$logroot],
    ],
    notify  => Service['httpd'],
  }
  if $::osfamily == 'Debian' {
    $vhost_enable_dir = $::apache::vhost_enable_dir
    $vhost_symlink_ensure = $ensure ? {
      present => link,
      default => $ensure,
    }
    file{ "${priority_real}-${filename}.conf symlink":
      ensure  => $vhost_symlink_ensure,
      path    => "${vhost_enable_dir}/${priority_real}-${filename}.conf",
      target  => "${::apache::vhost_dir}/${priority_real}-${filename}.conf",
      owner   => 'root',
      group   => $::apache::params::root_group,
      mode    => '0644',
      require => File["${priority_real}-${filename}.conf"],
      notify  => Service['httpd'],
    }
  }
}
