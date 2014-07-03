# Private class: See README.md.
class mysql::params {

  $manage_config_file     = true
  $old_root_password      = ''
  $purge_conf_dir         = false
  $restart                = false
  $root_password          = 'UNSET'
  $server_package_ensure  = 'present'
  $server_service_manage  = true
  $server_service_enabled = true
  # mysql::bindings
  $bindings_enable         = false
  $java_package_ensure     = 'present'
  $java_package_provider   = undef
  $perl_package_ensure     = 'present'
  $perl_package_provider   = undef
  $php_package_ensure      = 'present'
  $php_package_provider    = undef
  $python_package_ensure   = 'present'
  $python_package_provider = undef
  $ruby_package_ensure     = 'present'
  $ruby_package_provider   = undef


  case $::osfamily {
    'RedHat': {
      if $::operatingsystem == 'Fedora' and (is_integer($::operatingsystemrelease) and $::operatingsystemrelease >= 19 or $::operatingsystemrelease == "Rawhide") {
        $client_package_name = 'mariadb'
        $server_package_name = 'mariadb-server'
      } else {
        $client_package_name = 'mysql'
        $server_package_name = 'mysql-server'
      }
      $basedir             = '/usr'
      $config_file         = '/etc/my.cnf'
      $datadir             = '/var/lib/mysql'
      $log_error           = '/var/log/mysqld.log'
      $pidfile             = '/var/run/mysqld/mysqld.pid'
      $root_group          = 'root'
      $server_service_name = 'mysqld'
      $socket              = '/var/lib/mysql/mysql.sock'
      $ssl_ca              = '/etc/mysql/cacert.pem'
      $ssl_cert            = '/etc/mysql/server-cert.pem'
      $ssl_key             = '/etc/mysql/server-key.pem'
      $tmpdir              = '/tmp'
      # mysql::bindings
      $java_package_name   = 'mysql-connector-java'
      $perl_package_name   = 'perl-DBD-MySQL'
      $php_package_name    = 'php-mysql'
      $python_package_name = 'MySQL-python'
      $ruby_package_name   = 'ruby-mysql'
    }

    'Suse': {
      $client_package_name   = $::operatingsystem ? {
        /OpenSuSE/           => 'mysql-community-server-client',
        /(SLES|SLED)/        => 'mysql-client',
      }
      $server_package_name   = $::operatingsystem ? {
        /OpenSuSE/           => 'mysql-community-server',
        /(SLES|SLED)/        => 'mysql',
      }
      $basedir             = '/usr'
      $config_file         = '/etc/my.cnf'
      $datadir             = '/var/lib/mysql'
      $log_error           = $::operatingsystem ? {
        /OpenSuSE/         => '/var/log/mysql/mysqld.log',
        /(SLES|SLED)/      => '/var/log/mysqld.log',
      }
      $pidfile             = $::operatingsystem ? {
        /OpenSuSE/         => '/var/run/mysql/mysqld.pid',
        /(SLES|SLED)/      => '/var/lib/mysql/mysqld.pid',
      }
      $root_group          = 'root'
      $server_service_name = 'mysql'
      $socket              = $::operatingsystem ? {
        /OpenSuSE/         => '/var/run/mysql/mysql.sock',
        /(SLES|SLED)/      => '/var/lib/mysql/mysql.sock',
      }
      $ssl_ca              = '/etc/mysql/cacert.pem'
      $ssl_cert            = '/etc/mysql/server-cert.pem'
      $ssl_key             = '/etc/mysql/server-key.pem'
      $tmpdir              = '/tmp'
      # mysql::bindings
      $java_package_name   = 'mysql-connector-java'
      $perl_package_name   = 'perl-DBD-mysql'
      $php_package_name    = 'apache2-mod_php53'
      $python_package_name = 'python-mysql'
      $ruby_package_name   = $::operatingsystem ? {
        /OpenSuSE/         => 'rubygem-mysql',
        /(SLES|SLED)/      => 'ruby-mysql',
      }
    }

    'Debian': {
      $client_package_name = 'mysql-client'
      $server_package_name = 'mysql-server'

      $basedir             = '/usr'
      $config_file         = '/etc/mysql/my.cnf'
      $datadir             = '/var/lib/mysql'
      $log_error           = '/var/log/mysql/error.log'
      $pidfile             = '/var/run/mysqld/mysqld.pid'
      $root_group          = 'root'
      $server_service_name = 'mysql'
      $socket              = '/var/run/mysqld/mysqld.sock'
      $ssl_ca              = '/etc/mysql/cacert.pem'
      $ssl_cert            = '/etc/mysql/server-cert.pem'
      $ssl_key             = '/etc/mysql/server-key.pem'
      $tmpdir              = '/tmp'
      # mysql::bindings
      $java_package_name   = 'libmysql-java'
      $perl_package_name   = 'libdbd-mysql-perl'
      $php_package_name    = 'php5-mysql'
      $python_package_name = 'python-mysqldb'
      $ruby_package_name   = 'libmysql-ruby'
    }

    'FreeBSD': {
      $client_package_name = 'databases/mysql55-client'
      $server_package_name = 'databases/mysql55-server'
      $basedir             = '/usr/local'
      $config_file         = '/var/db/mysql/my.cnf'
      $datadir             = '/var/db/mysql'
      $log_error           = "/var/db/mysql/${::hostname}.err"
      $pidfile             = '/var/db/mysql/mysql.pid'
      $root_group          = 'wheel'
      $server_service_name = 'mysql-server'
      $socket              = '/tmp/mysql.sock'
      $ssl_ca              = undef
      $ssl_cert            = undef
      $ssl_key             = undef
      $tmpdir              = '/tmp'
      # mysql::bindings
      $java_package_name   = 'databases/mysql-connector-java'
      $perl_package_name   = 'p5-DBD-mysql'
      $php_package_name    = 'php5-mysql'
      $python_package_name = 'databases/py-MySQLdb'
      $ruby_package_name   = 'databases/ruby-mysql'
    }

    default: {
      case $::operatingsystem {
        'Amazon': {
          $client_package_name = 'mysql'
          $server_package_name = 'mysql-server'
          $basedir             = '/usr'
          $config_file         = '/etc/my.cnf'
          $datadir             = '/var/lib/mysql'
          $log_error           = '/var/log/mysqld.log'
          $pidfile             = '/var/run/mysqld/mysqld.pid'
          $root_group          = 'root'
          $server_service_name = 'mysqld'
          $socket              = '/var/lib/mysql/mysql.sock'
          $ssl_ca              = '/etc/mysql/cacert.pem'
          $ssl_cert            = '/etc/mysql/server-cert.pem'
          $ssl_key             = '/etc/mysql/server-key.pem'
          $tmpdir              = '/tmp'
          # mysql::bindings
          $java_package_name   = 'mysql-connector-java'
          $perl_package_name   = 'perl-DBD-MySQL'
          $php_package_name    = 'php-mysql'
          $python_package_name = 'MySQL-python'
          $ruby_package_name   = 'ruby-mysql'
        }

        default: {
          fail("Unsupported osfamily: ${::osfamily} operatingsystem: ${::operatingsystem}, module ${module_name} only support osfamily RedHat, Debian, and FreeBSD, or operatingsystem Amazon")
        }
      }
    }
  }

  case $::operatingsystem {
    'Ubuntu': {
      $server_service_provider = upstart
    }
    default: {
      $server_service_provider = undef
    }
  }

  $default_options = {
    'client'          => {
      'port'          => '3306',
      'socket'        => $mysql::params::socket,
    },
    'mysqld_safe'        => {
      'nice'             => '0',
      'log-error'        => $mysql::params::log_error,
      'socket'           => $mysql::params::socket,
    },
    'mysqld'                  => {
      'basedir'               => $mysql::params::basedir,
      'bind-address'          => '127.0.0.1',
      'datadir'               => $mysql::params::datadir,
      'expire_logs_days'      => '10',
      'key_buffer_size'       => '16M',
      'log-error'             => $mysql::params::log_error,
      'max_allowed_packet'    => '16M',
      'max_binlog_size'       => '100M',
      'max_connections'       => '151',
      'myisam_recover'        => 'BACKUP',
      'pid-file'              => $mysql::params::pidfile,
      'port'                  => '3306',
      'query_cache_limit'     => '1M',
      'query_cache_size'      => '16M',
      'skip-external-locking' => true,
      'socket'                => $mysql::params::socket,
      'ssl'                   => false,
      'ssl-ca'                => $mysql::params::ssl_ca,
      'ssl-cert'              => $mysql::params::ssl_cert,
      'ssl-key'               => $mysql::params::ssl_key,
      'thread_cache_size'     => '8',
      'thread_stack'          => '256K',
      'tmpdir'                => $mysql::params::tmpdir,
      'user'                  => 'mysql',
    },
    'mysqldump'             => {
      'max_allowed_packet'  => '16M',
      'quick'               => true,
      'quote-names'         => true,
    },
    'isamchk'      => {
      'key_buffer_size' => '16M',
    },
  }

}
