#
class rabbitmq::install::rabbitmqadmin {

  $management_port = $rabbitmq::management_port

  staging::file { 'rabbitmqadmin':
    target  => '/var/lib/rabbitmq/rabbitmqadmin',
    source  => "http://localhost:${management_port}/cli/rabbitmqadmin",
    require => [
      Class['rabbitmq::service'],
      Rabbitmq_plugin['rabbitmq_management']
    ],
  }

  file { '/usr/local/bin/rabbitmqadmin':
    owner   => 'root',
    group   => 'root',
    source  => '/var/lib/rabbitmq/rabbitmqadmin',
    mode    => '0755',
    require => Staging::File['rabbitmqadmin'],
  }

}
