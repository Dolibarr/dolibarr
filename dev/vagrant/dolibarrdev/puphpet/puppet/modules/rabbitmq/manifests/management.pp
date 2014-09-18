#
class rabbitmq::management {

  $delete_guest_user = $rabbitmq::delete_guest_user

  if $delete_guest_user {
    rabbitmq_user{ 'guest':
      ensure   => absent,
      provider => 'rabbitmqctl',
    }
  }

  if $rabbitmq::config_mirrored_queues {
    rabbitmq::policy { 'ha-all':
      pattern  => '.*',
      definition => '{"ha-mode":"all","ha-sync-mode":"automatic"}'
    }
  }
}
