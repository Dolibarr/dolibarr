# define: nginx::resource::upstream
#
# This definition creates a new upstream proxy entry for NGINX
#
# Parameters:
#   [*members*]               - Array of member URIs for NGINX to connect to. Must follow valid NGINX syntax.
#   [*ensure*]                - Enables or disables the specified location (present|absent)
#   [*upstream_cfg_prepend*]  - It expects a hash with custom directives to put before anything else inside upstream
#   [*upstream_fail_timeout*] - Set the fail_timeout for the upstream. Default is 10 seconds - As that is what Nginx does normally.
#
# Actions:
#
# Requires:
#
# Sample Usage:
#  nginx::resource::upstream { 'proxypass':
#    ensure  => present,
#    members => [
#      'localhost:3000',
#      'localhost:3001',
#      'localhost:3002',
#    ],
#  }
#
#  Custom config example to use ip_hash, and 20 keepalive connections
#  create a hash with any extra custom config you want.
#  $my_config = {
#    'ip_hash'   => '',
#    'keepalive' => '20',
#  }
#  nginx::resource::upstream { 'proxypass':
#    ensure              => present,
#    members => [
#      'localhost:3000',
#      'localhost:3001',
#      'localhost:3002',
#    ],
#    upstream_cfg_prepend => $my_config,
#  }
define nginx::resource::upstream (
  $members,
  $ensure = 'present',
  $upstream_cfg_prepend = undef,
  $upstream_fail_timeout = '10s',
) {

  validate_array($members)
  validate_re($ensure, '^(present|absent)$',
    "${ensure} is not supported for ensure. Allowed values are 'present' and 'absent'.")
  if ($upstream_cfg_prepend != undef) {
    validate_hash($upstream_cfg_prepend)
  }

  File {
    owner => 'root',
    group => 'root',
    mode  => '0644',
  }

  file { "/etc/nginx/conf.d/${name}-upstream.conf":
    ensure  => $ensure ? {
      'absent' => absent,
      default  => 'file',
    },
    content => template('nginx/conf.d/upstream.erb'),
    notify  => Class['nginx::service'],
  }
}
