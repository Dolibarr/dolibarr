mysql_grant{'test1@localhost/redmine.*':
  user       => 'test1@localhost',
  table      => 'redmine.*',
  privileges => ['UPDATE'],
}
