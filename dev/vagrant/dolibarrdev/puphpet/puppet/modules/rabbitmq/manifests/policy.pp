define rabbitmq::policy (
  $pattern,
  $definition,
  $vhost    = '/',
  $priority = 0,
) {

  exec { "rabbitmq policy: ${title}":
    command => "rabbitmqctl set_policy -p ${vhost} '${name}' '${pattern}' '${definition}' ${priority}",
    unless  => "rabbitmqctl list_policies | grep -qE '^${vhost}\\s+${name}\\s+${pattern}\\s+${definition}\\s+${priority}$'",
    path    => ['/bin','/sbin','/usr/bin','/usr/sbin'],
    require => Class['rabbitmq::service'],
    before  => Anchor['rabbitmq::end']
  }
}
