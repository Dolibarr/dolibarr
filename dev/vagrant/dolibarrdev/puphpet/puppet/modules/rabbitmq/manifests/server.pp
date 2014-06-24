# Class: rabbitmq::server
#
# This module manages the installation and config of the rabbitmq server
#   it has only been tested on certain version of debian-ish systems
# Parameters:
#  [*port*] - port where rabbitmq server is hosted
#  [*delete_guest_user*] - rather or not to delete the default user
#  [*version*] - version of rabbitmq-server to install
#  [*package_name*] - name of rabbitmq package
#  [*service_name*] - name of rabbitmq service
#  [*service_ensure*] - desired ensure state for service
#  [*stomp_port*] - port stomp should be listening on
#  [*node_ip_address*] - ip address for rabbitmq to bind to
#  [*config*] - contents of config file
#  [*env_config*] - contents of env-config file
#  [*config_cluster*] - whether to configure a RabbitMQ cluster
#  [*config_mirrored_queues*] - DEPRECATED (doesn't do anything)
#  [*cluster_disk_nodes*] - DEPRECATED (use cluster_nodes instead)
#  [*cluster_nodes*] - which nodes to cluster with (including the current one)
#  [*cluster_node_type*] - Type of cluster node (disc or ram)
#  [*erlang_cookie*] - erlang cookie, must be the same for all nodes in a cluster
#  [*wipe_db_on_cookie_change*] - whether to wipe the RabbitMQ data if the specified
#    erlang_cookie differs from the current one. This is a sad parameter: actually,
#    if the cookie indeed differs, then wiping the database is the *only* thing you
#    can do. You're only required to set this parameter to true as a sign that you
#    realise this.
# Requires:
#  stdlib
# Sample Usage:
#
# This module is used as backward compability layer for modules
# which require rabbitmq::server instead of rabbitmq class.
# It's still common uasge in many modules.
#
#
# [Remember: No empty lines between comments and class definition]
class rabbitmq::server(
  $port                     = $rabbitmq::params::port,
  $delete_guest_user        = $rabbitmq::params::delete_guest_user,
  $package_name             = $rabbitmq::params::package_name,
  $version                  = $rabbitmq::params::version,
  $service_name             = $rabbitmq::params::service_name,
  $service_ensure           = $rabbitmq::params::service_ensure,
  $service_manage           = $rabbitmq::params::service_manage,
  $config_stomp             = $rabbitmq::params::config_stomp,
  $stomp_port               = $rabbitmq::params::stomp_port,
  $config_cluster           = $rabbitmq::params::config_cluster,
  $cluster_disk_nodes       = $rabbitmq::params::cluster_disk_nodes,
  $cluster_nodes            = $rabbitmq::params::cluster_nodes,
  $cluster_node_type        = $rabbitmq::params::cluster_node_type,
  $node_ip_address          = $rabbitmq::params::node_ip_address,
  $config                   = $rabbitmq::params::config,
  $env_config               = $rabbitmq::params::env_config,
  $erlang_cookie            = $rabbitmq::params::erlang_cookie,
  $wipe_db_on_cookie_change = $rabbitmq::params::wipe_db_on_cookie_change,
  # DEPRECATED
  $manage_service           = undef,
  $config_mirrored_queues   = undef,
) inherits rabbitmq::params {

  if $manage_service != undef {
    warning('The $manage_service parameter is deprecated; please use $service_manage instead')
    $_service_manage = $manage_service
  } else {
    $_service_manage = $service_manage
  }

  if $config_mirrored_queues != undef {
    warning('The $config_mirrored_queues parameter is deprecated; it does not affect anything')
  }

  anchor {'before::rabbimq::class':
    before => Class['rabbitmq'],
  }

  anchor {'after::rabbimq::class':
    require => Class['rabbitmq'],
  }

  class { 'rabbitmq':
    port                      => $port,
    delete_guest_user         => $delete_guest_user,
    package_name              => $package_name,
    version                   => $version,
    service_name              => $service_name,
    service_ensure            => $service_ensure,
    service_manage            => $_service_manage,
    config_stomp              => $config_stomp,
    stomp_port                => $stomp_port,
    config_cluster            => $config_cluster,
    cluster_disk_nodes        => $cluster_disk_nodes,
    cluster_nodes             => $cluster_nodes,
    cluster_node_type         => $cluster_node_type,
    node_ip_address           => $node_ip_address,
    config                    => $config,
    env_config                => $env_config,
    erlang_cookie             => $erlang_cookie,
    wipe_db_on_cookie_change  => $wipe_db_on_cookie_change,
  }
}
