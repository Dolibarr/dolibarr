node default {

  $rabbitmq_plugins = [ 'amqp_client', 'rabbitmq_stomp' ]

  class { 'rabbitmq::server':
    config => '[ {rabbit_stomp, [{tcp_listeners, [1234]} ]} ].',
  }

  # Required for MCollective
  rabbitmq_plugin { $rabbitmq_plugins:
    ensure   => present,
    require  => Class['rabbitmq::server'],
    provider => 'rabbitmqplugins',
  }
}

