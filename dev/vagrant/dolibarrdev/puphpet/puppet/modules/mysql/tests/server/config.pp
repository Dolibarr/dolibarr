mysql::server::config { 'testfile':
  settings => {
    'mysqld' => {
      'bind-address' => '0.0.0.0',
      'read-only'    => true,
    },
    'client' => {
      'port' => '3306'
    }
  }
}
