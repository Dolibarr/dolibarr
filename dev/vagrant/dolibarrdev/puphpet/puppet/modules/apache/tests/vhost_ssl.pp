## SSL-enabled vhosts
# SSL-enabled vhosts respond only to HTTPS queries.

# Base class. Turn off the default vhosts; we will be declaring
# all vhosts below.
class { 'apache':
  default_vhost => false,
}

# Non-ssl vhost
apache::vhost { 'first.example.com non-ssl':
  servername => 'first.example.com',
  port       => '80',
  docroot    => '/var/www/first',
}

# SSL vhost at the same domain
apache::vhost { 'first.example.com ssl':
  servername => 'first.example.com',
  port       => '443',
  docroot    => '/var/www/first',
  ssl        => true,
}
