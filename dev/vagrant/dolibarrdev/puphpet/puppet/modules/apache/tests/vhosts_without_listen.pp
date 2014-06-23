## Declare ip-based and name-based vhosts
# Mixing Name-based vhost with IP-specific vhosts requires `add_listen =>
# 'false'` on the non-IP vhosts

# Base class. Turn off the default vhosts; we will be declaring
# all vhosts below.
class { 'apache':
  default_vhost => false,
}


# Add two an IP-based vhost on 10.0.0.10, ssl and non-ssl
apache::vhost { 'The first IP-based vhost, non-ssl':
  servername => 'first.example.com',
  ip         => '10.0.0.10',
  port       => '80',
  ip_based   => true,
  docroot    => '/var/www/first',
}
apache::vhost { 'The first IP-based vhost, ssl':
  servername => 'first.example.com',
  ip         => '10.0.0.10',
  port       => '443',
  ip_based   => true,
  docroot    => '/var/www/first-ssl',
  ssl        => true,
}

# Two name-based vhost listening on 10.0.0.20
apache::vhost { 'second.example.com':
  ip      => '10.0.0.20',
  port    => '80',
  docroot => '/var/www/second',
}
apache::vhost { 'third.example.com':
  ip      => '10.0.0.20',
  port    => '80',
  docroot => '/var/www/third',
}

# Two name-based vhosts without IPs specified, so that they will answer on either 10.0.0.10 or 10.0.0.20 . It is requried to declare
# `add_listen => 'false'` to disable declaring "Listen 80" which will conflict
# with the IP-based preceeding vhosts.
apache::vhost { 'fourth.example.com':
  port       => '80',
  docroot    => '/var/www/fourth',
  add_listen => false,
}
apache::vhost { 'fifth.example.com':
  port       => '80',
  docroot    => '/var/www/fifth',
  add_listen => false,
}
