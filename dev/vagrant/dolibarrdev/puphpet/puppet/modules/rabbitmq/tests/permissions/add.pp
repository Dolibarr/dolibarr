rabbitmq_user { 'blah7':
  password => 'foo',
}
rabbitmq_vhost { 'test5': }
rabbitmq_user_permissions { 'blah7@test5':
  configure_permission => 'config2',
  read_permission      => 'ready',
  #write_permission     => 'ready',
}
