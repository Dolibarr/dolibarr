  # Class: rabbitmq::params
#
#   The RabbitMQ Module configuration settings.
#
class rabbitmq::params {

  case $::osfamily {
    'Archlinux': {
      $package_ensure   = 'installed'
      $package_name     = 'rabbitmq'
      $service_name     = 'rabbitmq'
      $package_source   = ''
      $version          = '3.1.3-1'
      $base_version     = regsubst($version,'^(.*)-\d$','\1')
      # This must remain at the end as we need $base_version and $version defined first
    }
    'Debian': {
      $package_ensure   = 'installed'
      $package_name     = 'rabbitmq-server'
      $service_name     = 'rabbitmq-server'
      $package_provider = 'apt'
      $package_source   = ''
      $version          = '3.1.5'
    }
    'RedHat': {
      $package_ensure   = 'installed'
      $package_name     = 'rabbitmq-server'
      $service_name     = 'rabbitmq-server'
      $package_provider = 'yum'
      $version          = '3.1.5-1'
      $base_version     = regsubst($version,'^(.*)-\d$','\1')
      # This must remain at the end as we need $base_version and $version defined first.
      $package_source   = "http://www.rabbitmq.com/releases/rabbitmq-server/v${base_version}/rabbitmq-server-${version}.noarch.rpm"
    }
    'SUSE': {
      $package_ensure   = 'installed'
      $package_name     = 'rabbitmq-server'
      $service_name     = 'rabbitmq-server'
      $package_provider = 'zypper'
      $version          = '3.1.5-1'
      $base_version     = regsubst($version,'^(.*)-\d$','\1')
      # This must remain at the end as we need $base_version and $version defined first.
      $package_source   = "http://www.rabbitmq.com/releases/rabbitmq-server/v${base_version}/rabbitmq-server-${version}.noarch.rpm"
    }
    default: {
      fail("The ${module_name} module is not supported on an ${::osfamily} based system.")
    }
  }

  #install
  $admin_enable               = true
  $management_port            = '15672'
  $package_apt_pin            = ''
  $package_gpg_key            = 'http://www.rabbitmq.com/rabbitmq-signing-key-public.asc'
  $manage_repos               = true
  $service_ensure             = 'running'
  $service_manage             = true
  #config
  $cluster_disk_nodes         = []
  $cluster_node_type          = 'disc'
  $cluster_nodes              = []
  $config                     = 'rabbitmq/rabbitmq.config.erb'
  $config_cluster             = false
  $config_path                = '/etc/rabbitmq/rabbitmq.config'
  $config_stomp               = false
  $default_user               = 'guest'
  $default_pass               = 'guest'
  $delete_guest_user          = false
  $env_config                 = 'rabbitmq/rabbitmq-env.conf.erb'
  $env_config_path            = '/etc/rabbitmq/rabbitmq-env.conf'
  $erlang_cookie              = 'EOKOWXQREETZSHFNTPEY'
  $node_ip_address            = 'UNSET'
  $plugin_dir                 = "/usr/lib/rabbitmq/lib/rabbitmq_server-${version}/plugins"
  $port                       = '5672'
  $ssl                        = false
  $ssl_cacert                 = 'UNSET'
  $ssl_cert                   = 'UNSET'
  $ssl_key                    = 'UNSET'
  $ssl_management_port        = '5671'
  $ssl_stomp_port             = '6164'
  $ssl_verify                 = 'verify_none'
  $ssl_fail_if_no_peer_cert   = 'false'
  $stomp_ensure               = false
  $ldap_auth                  = false
  $ldap_server                = 'ldap'
  $ldap_user_dn_pattern       = 'cn=${username},ou=People,dc=example,dc=com'
  $ldap_use_ssl               = false
  $ldap_port                  = '389'
  $ldap_log                   = false
  $stomp_port                 = '6163'
  $wipe_db_on_cookie_change   = false
  $cluster_partition_handling = 'ignore'
  $environment_variables      = {}
  $config_variables           = {}
  $config_kernel_variables    = {}
}
