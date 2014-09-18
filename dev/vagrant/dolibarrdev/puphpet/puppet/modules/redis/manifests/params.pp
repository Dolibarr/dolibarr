# Class: redis::params
#
# This class configures parameters for the puppet-redis module.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
class redis::params {

  case $::operatingsystem {
    'centos', 'redhat', 'fedora', 'amazon': {
      $package        = 'redis'
      $service        = 'redis'
      $conf           = '/etc/redis.conf'
      $conf_template  = 'redis.rhel.conf.erb'
      $conf_logrotate = '/etc/logrotate.d/redis'
      $pidfile        = '/var/run/redis/redis.pid'
      $logfile        = '/var/log/redis/redis.log'
    }
    'ubuntu', 'debian': {
      $package        = 'redis-server'
      $service        = 'redis-server'
      $conf           = '/etc/redis/redis.conf'
      $conf_template  = 'redis.debian.conf.erb'
      $conf_logrotate = '/etc/logrotate.d/redis-server'
      $pidfile        = '/var/run/redis/redis-server.pid'
      $logfile        = '/var/log/redis/redis-server.log'
    }
    default: {
      fail("Unsupported osfamily: ${::osfamily} operatingsystem: ${::operatingsystem}, module ${module_name} only support osfamily RedHat and Debian")
    }
  }

}
