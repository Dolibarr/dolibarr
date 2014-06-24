## custom mods

# Base class. Declares default vhost on port 80 and default ssl
# vhost on port 443 listening on all interfaces and serving
# $apache::docroot, and declaring a custom set of modules.
class { 'apache':
  default_mods => [
    'info',
    'alias',
    'mime',
    'env',
    'setenv',
    'expires',
  ],
}

