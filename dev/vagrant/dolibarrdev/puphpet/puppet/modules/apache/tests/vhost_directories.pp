# Base class. Declares default vhost on port 80 and default ssl
# vhost on port 443 listening on all interfaces and serving
# $apache::docroot
class { 'apache': }

# Example from README adapted.
apache::vhost { 'readme.example.net':
  docroot     => '/var/www/readme',
  directories => [
    {
      'path'         => '/var/www/readme',
      'ServerTokens' => 'prod' ,
    },
    {
      'path'  => '/usr/share/empty',
      'allow' => 'from all',
    },
  ],
}

# location test
apache::vhost { 'location.example.net':
  docroot     => '/var/www/location',
  directories => [
    {
      'path'         => '/location',
      'provider'     => 'location',
      'ServerTokens' => 'prod'
    },
  ],
}

# files test, curedly disable access to accidental backup files.
apache::vhost { 'files.example.net':
  docroot     => '/var/www/files',
  directories => [
    {
      'path'     => '(\.swp|\.bak|~)$',
      'provider' => 'filesmatch',
      'deny'     => 'from all'
    },
  ],
}

