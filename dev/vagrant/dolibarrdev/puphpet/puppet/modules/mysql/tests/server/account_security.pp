class { 'mysql::server':
  config_hash => { 'root_password' => 'password', },
}
class { 'mysql::server::account_security': }
