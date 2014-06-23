## IP-based vhosts on any listen port
# IP-based vhosts respond to requests on specific IP addresses.

# Base class. Turn off the default vhosts; we will be declaring
# all vhosts below.
class { 'apache':
  default_vhost => false,
}

# Listen on port 80 and 81; required because the following vhosts
# are not declared with a port parameter.
apache::listen { '80': }
apache::listen { '81': }

# IP-based vhosts
apache::vhost { 'first.example.com':
  ip       => '10.0.0.10',
  docroot  => '/var/www/first',
  ip_based => true,
}
apache::vhost { 'second.example.com':
  ip       => '10.0.0.11',
  docroot  => '/var/www/second',
  ip_based => true,
}
