# PRIVATE CLASS: do not use directly
class mongodb::params inherits mongodb::globals {
  $ensure           = true
  $service_status   = $service_status
  $ensure_client    = true

  # Amazon Linux's OS Family is 'Linux', operating system 'Amazon'.
  case $::osfamily {
    'RedHat', 'Linux': {

      if $mongodb::globals::manage_package_repo {
        $user        = pick($user, 'mongod')
        $group       = pick($group, 'mongod')
        if $::mongodb::globals::version {
          $server_package_name = "mongodb-org-server-${::mongodb::globals::version}"
          $client_package_name = "mongodb-org-${::mongodb::globals::version}"
        } else {
          $server_package_name = 'mongodb-org-server'
          $client_package_name = 'mongodb-org'
        }
        $service_name = pick($service_name, 'mongod')
        $config      = '/etc/mongod.conf'
        $dbpath      = '/var/lib/mongo'
        $logpath     = '/var/log/mongodb/mongod.log'
        $pidfilepath = '/var/run/mongodb/mongod.pid'
        $bind_ip     = pick($bind_ip, ['127.0.0.1'])
        $fork        = true
      } else {
        # RedHat/CentOS doesn't come with a prepacked mongodb
        # so we assume that you are using EPEL repository.
        $user                = pick($user, 'mongodb')
        $group               = pick($group, 'mongodb')
        $server_package_name = pick($server_package_name, 'mongodb-server')
        $client_package_name = pick($client_package_name, 'mongodb')

        $service_name        = pick($service_name, 'mongod')
        $config              = '/etc/mongodb.conf'
        $dbpath              = '/var/lib/mongodb'
        $logpath             = '/var/log/mongodb/mongodb.log'
        $bind_ip             = pick($bind_ip, ['127.0.0.1'])
        $pidfilepath         = '/var/run/mongodb/mongodb.pid'
        $fork                = true
        $journal             = true
      }
    }
    'Debian': {
      if $mongodb::globals::manage_package_repo {
        $user  = pick($user, 'mongodb')
        $group = pick($group, 'mongodb')
        if $::mongodb::globals::version {
          $server_package_name = "mongodb-10gen=${::mongodb::globals::version}"
        } else {
          $server_package_name = 'mongodb-10gen'
        }
        $service_name = 'mongodb'
        $config       = '/etc/mongodb.conf'
        $dbpath       = '/var/lib/mongodb'
        $logpath      = '/var/log/mongodb/mongodb.log'
        $bind_ip      = ['127.0.0.1']
      } else {
        # although we are living in a free world,
        # I would not recommend to use the prepacked
        # mongodb server on Ubuntu 12.04 or Debian 6/7,
        # because its really outdated
        $user                = pick($user, 'mongodb')
        $group               = pick($group, 'mongodb')
        $server_package_name = pick($server_package_name, 'mongodb-server')
        $client_package_name = pick($client_package_name, 'mongodb')
        $service_name        = pick($service_name, 'mongodb')
        $config              = '/etc/mongodb.conf'
        $dbpath              = '/var/lib/mongodb'
        $logpath             = '/var/log/mongodb/mongodb.log'
        $bind_ip             = pick($bind_ip, ['127.0.0.1'])
        $pidfilepath         = undef
      }
      # avoid using fork because of the init scripts design
      $fork = undef
    }
    default: {
      fail("Osfamily ${::osfamily} and ${::operatingsystem} is not supported")
    }
  }

  case $::operatingsystem {
    'Ubuntu': {
      $service_provider = pick($service_provider, 'upstart')
    }
    default: {
      $service_provider = undef
    }
  }

}
