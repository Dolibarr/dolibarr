class { 'rabbitmq::server':
    config_stomp => true,
}

$rabbitmq_plugins = [ 'amqp_client', 'rabbitmq_stomp' ]

rabbitmq_plugin { $rabbitmq_plugins:
  ensure   => present,
  require  => Class['rabbitmq::server'],
  provider => 'rabbitmqplugins',
}
