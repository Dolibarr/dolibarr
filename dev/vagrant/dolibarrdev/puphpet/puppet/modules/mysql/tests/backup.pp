class { 'mysql::server':
  config_hash => {'root_password' => 'password'}
}
class { 'mysql::backup':
  backupuser     => 'myuser',
  backuppassword => 'mypassword',
  backupdir      => '/tmp/backups',
}
