# requires
#   puppetlabs-apt
#   puppetlabs-stdlib
class rabbitmq::repo::apt(
  $location    = 'http://www.rabbitmq.com/debian/',
  $release     = 'testing',
  $repos       = 'main',
  $include_src = false,
  $key         = '056E8E56',
  $key_source  = 'http://www.rabbitmq.com/rabbitmq-signing-key-public.asc',
  ) {

  $pin = $rabbitmq::package_apt_pin

  Class['rabbitmq::repo::apt'] -> Package<| title == 'rabbitmq-server' |>

  apt::source { 'rabbitmq':
    location    => $location,
    release     => $release,
    repos       => $repos,
    include_src => $include_src,
    key         => $key,
    key_source  => $key_source,
  }

  if $pin {
    validate_re($pin, '\d\d\d')
    apt::pin { 'rabbitmq':
      packages => 'rabbitmq-server',
      priority => $pin,
    }
  }
}
