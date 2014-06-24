#
class rabbitmq::management {

  $delete_guest_user = $rabbitmq::delete_guest_user

  if $delete_guest_user {
    rabbitmq_user{ 'guest':
      ensure   => absent,
      provider => 'rabbitmqctl',
    }
  }

}
