  #
class rabbitmq(
  $admin_enable               = $rabbitmq::params::admin_enable,
  $cluster_disk_nodes         = $rabbitmq::params::cluster_disk_nodes,
  $cluster_node_type          = $rabbitmq::params::cluster_node_type,
  $cluster_nodes              = $rabbitmq::params::cluster_nodes,
  $config                     = $rabbitmq::params::config,
  $config_cluster             = $rabbitmq::params::config_cluster,
  $config_path                = $rabbitmq::params::config_path,
  $config_stomp               = $rabbitmq::params::config_stomp,
  $default_user               = $rabbitmq::params::default_user,
  $default_pass               = $rabbitmq::params::default_pass,
  $delete_guest_user          = $rabbitmq::params::delete_guest_user,
  $env_config                 = $rabbitmq::params::env_config,
  $env_config_path            = $rabbitmq::params::env_config_path,
  $erlang_cookie              = $rabbitmq::params::erlang_cookie,
  $management_port            = $rabbitmq::params::management_port,
  $node_ip_address            = $rabbitmq::params::node_ip_address,
  $package_apt_pin            = $rabbitmq::params::package_apt_pin,
  $package_ensure             = $rabbitmq::params::package_ensure,
  $package_gpg_key            = $rabbitmq::params::package_gpg_key,
  $package_name               = $rabbitmq::params::package_name,
  $package_provider           = $rabbitmq::params::package_provider,
  $package_source             = $rabbitmq::params::package_source,
  $manage_repos               = $rabbitmq::params::manage_repos,
  $plugin_dir                 = $rabbitmq::params::plugin_dir,
  $port                       = $rabbitmq::params::port,
  $service_ensure             = $rabbitmq::params::service_ensure,
  $service_manage             = $rabbitmq::params::service_manage,
  $service_name               = $rabbitmq::params::service_name,
  $ssl                        = $rabbitmq::params::ssl,
  $ssl_cacert                 = $rabbitmq::params::ssl_cacert,
  $ssl_cert                   = $rabbitmq::params::ssl_cert,
  $ssl_key                    = $rabbitmq::params::ssl_key,
  $ssl_management_port        = $rabbitmq::params::ssl_management_port,
  $ssl_stomp_port             = $rabbitmq::params::ssl_stomp_port,
  $ssl_verify                 = $rabbitmq::params::ssl_verify,
  $ssl_fail_if_no_peer_cert   = $rabbitmq::params::ssl_fail_if_no_peer_cert,
  $stomp_ensure               = $rabbitmq::params::stomp_ensure,
  $ldap_auth                  = $rabbitmq::params::ldap_auth,
  $ldap_server                = $rabbitmq::params::ldap_server,
  $ldap_user_dn_pattern       = $rabbitmq::params::ldap_user_dn_pattern,
  $ldap_use_ssl               = $rabbitmq::params::ldap_use_ssl,
  $ldap_port                  = $rabbitmq::params::ldap_port,
  $ldap_log                   = $rabbitmq::params::ldap_log,
  $stomp_port                 = $rabbitmq::params::stomp_port,
  $version                    = $rabbitmq::params::version,
  $wipe_db_on_cookie_change   = $rabbitmq::params::wipe_db_on_cookie_change,
  $cluster_partition_handling = $rabbitmq::params::cluster_partition_handling,
  $environment_variables      = $rabbitmq::params::environment_variables,
  $config_variables           = $rabbitmq::params::config_variables,
  $config_kernel_variables    = $rabbitmq::params::config_kernel_variables,
) inherits rabbitmq::params {

  validate_bool($admin_enable)
  # Validate install parameters.
  validate_re($package_apt_pin, '^(|\d+)$')
  validate_string($package_ensure)
  validate_string($package_gpg_key)
  validate_string($package_name)
  validate_string($package_provider)
  validate_string($package_source)
  validate_bool($manage_repos)
  validate_re($version, '^\d+\.\d+\.\d+(-\d+)*$') # Allow 3 digits and optional -n postfix.
  # Validate config parameters.
  validate_array($cluster_disk_nodes)
  validate_re($cluster_node_type, '^(ram|disc)$')
  validate_array($cluster_nodes)
  validate_string($config)
  validate_absolute_path($config_path)
  validate_bool($config_cluster)
  validate_bool($config_stomp)
  validate_string($default_user)
  validate_string($default_pass)
  validate_bool($delete_guest_user)
  validate_string($env_config)
  validate_absolute_path($env_config_path)
  validate_string($erlang_cookie)
  validate_re($management_port, '\d+')
  validate_string($node_ip_address)
  validate_absolute_path($plugin_dir)
  validate_re($port, ['\d+','UNSET'])
  validate_re($stomp_port, '\d+')
  validate_bool($wipe_db_on_cookie_change)
  # Validate service parameters.
  validate_re($service_ensure, '^(running|stopped)$')
  validate_bool($service_manage)
  validate_string($service_name)
  validate_bool($ssl)
  validate_string($ssl_cacert)
  validate_string($ssl_cert)
  validate_string($ssl_key)
  validate_string($ssl_management_port)
  validate_re($ssl_management_port, '\d+')
  validate_string($ssl_stomp_port)
  validate_re($ssl_stomp_port, '\d+')
  validate_bool($stomp_ensure)
  validate_bool($ldap_auth)
  validate_string($ldap_server)
  validate_string($ldap_user_dn_pattern)
  validate_bool($ldap_use_ssl)
  validate_re($ldap_port, '\d+')
  validate_bool($ldap_log)
  validate_hash($environment_variables)
  validate_hash($config_variables)
  validate_hash($config_kernel_variables)

  include '::rabbitmq::install'
  include '::rabbitmq::config'
  include '::rabbitmq::service'
  include '::rabbitmq::management'

  if $rabbitmq::manage_repos == true {
    case $::osfamily {
      'RedHat', 'SUSE':
        { include '::rabbitmq::repo::rhel' }
      'Debian':
        { include '::rabbitmq::repo::apt' }
      default:
        { }
    }
  }

  if $admin_enable and $service_manage {
    include '::rabbitmq::install::rabbitmqadmin'

    rabbitmq_plugin { 'rabbitmq_management':
      ensure  => present,
      require => Class['rabbitmq::install'],
      notify  => Class['rabbitmq::service'],
      provider => 'rabbitmqplugins'
    }

    Class['::rabbitmq::service'] -> Class['::rabbitmq::install::rabbitmqadmin']
  }

  if $stomp_ensure {
    rabbitmq_plugin { 'rabbitmq_stomp':
      ensure  => $stomp_ensure,
      require => Class['rabbitmq::install'],
      notify  => Class['rabbitmq::service'],
      provider => 'rabbitmqplugins'
    }
  }

  if ($ldap_auth) {
    rabbitmq_plugin { 'rabbitmq_auth_backend_ldap':
      ensure   => present,
      require  => Class['rabbitmq::install'],
      notify   => Class['rabbitmq::service'],
      provider => 'rabbitmqplugins',
    }
  }

  # Anchor this as per #8040 - this ensures that classes won't float off and
  # mess everything up.  You can read about this at:
  # http://docs.puppetlabs.com/puppet/2.7/reference/lang_containment.html#known-issues
  anchor { 'rabbitmq::begin': }
  anchor { 'rabbitmq::end': }

  Anchor['rabbitmq::begin'] -> Class['::rabbitmq::install']
    -> Class['::rabbitmq::config'] ~> Class['::rabbitmq::service']
    -> Class['::rabbitmq::management'] -> Anchor['rabbitmq::end']

  # Make sure the various providers have their requirements in place.
  Class['::rabbitmq::install'] -> Rabbitmq_plugin<| |>
  Class['::rabbitmq::install::rabbitmqadmin'] -> Rabbitmq_exchange<| |>

}
