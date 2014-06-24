# See README.me for options.
class mysql::server::config {

  $options = $mysql::server::options

  File {
    owner  => 'root',
    group  => $mysql::server::root_group,
    mode   => '0400',
  }

  file { '/etc/mysql':
    ensure => directory,
    mode   => '0755',
  }

  file { '/etc/mysql/conf.d':
    ensure  => directory,
    mode    => '0755',
    recurse => $mysql::server::purge_conf_dir,
    purge   => $mysql::server::purge_conf_dir,
  }

  if $mysql::server::manage_config_file  {
    file { $mysql::server::config_file:
      content => template('mysql/my.cnf.erb'),
      mode    => '0644',
    }
  }
}
