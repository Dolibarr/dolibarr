# = Class yum::cron
#
#
class yum::cron {

  $manage_update_package = $yum::bool_update_disable ? {
    true    => absent,
    default => present,
  }

  $manage_update_service_ensure = $yum::bool_update_disable ? {
    true    => stopped,
    default => running,
  }

  $manage_update_service_enable = $yum::bool_update_disable ? {
    true    => false,
    default => true,
  }

  $manage_update_file = $yum::bool_update_disable ? {
    true    => absent,
    default => present,
  }

  package { 'yum-cron':
    ensure => $manage_update_package,
  }

  service { 'yum-cron':
    ensure     => $manage_update_service_ensure,
    name       => $yum::service,
    enable     => $manage_update_service_enable,
    hasstatus  => true,
    hasrestart => true,
    require    => Package['yum-cron'],
  }

  file { 'yum-cron':
    ensure   => $manage_update_file,
    path     => '/etc/sysconfig/yum-cron',
    content  => template($yum::update_template),
    require  => Package['yum-cron'],
    notify   => Service['yum-cron'],
  }

}
