#
class mysql::server::install {

  package { 'mysql-server':
    ensure => $mysql::server::package_ensure,
    name   => $mysql::server::package_name,
  }

}
