# Define: pagios::plugin
#
# Adds a yum plugin
#
# Usage:
# With standard source package:
# yum::plugin { 'priorities': }
#
# With custom config file source
# yum::plugin { 'priorities':
#   source => 'puppet:///modules/example42/yum/plugin-priorities'
# }
#
# With custom package name (default is taken from $name)
# yum::plugin { 'priorities':
#   package_name => 'yum-priorities'
# }
#
define yum::plugin (
  $package_name = '',
  $source       = '',
  $enable       = true
  ) {

  include yum

  $ensure = bool2ensure( $enable )

  $yum_plugins_prefix = $yum::osver[0] ? {
    5       => 'yum',
    6       => 'yum-plugin',
    default => 'yum-plugin',
  }

  $real_package_name = $package_name ? {
    ''      => "${yum_plugins_prefix}-${name}",
    default => $package_name,
  }

  package { $real_package_name :
    ensure => $ensure
  }

  if ( $source != '' ) {
    file { "yum_plugin_conf_${name}":
      ensure  => $ensure,
      path    => "${yum::plugins_config_dir}/${name}.conf",
      owner   => root,
      group   => root,
      mode    => '0755',
      source  => $source,
    }
  }
}
