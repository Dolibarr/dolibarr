# Define: sysctl
#
# Manage sysctl variable values.
#
# Parameters:
#  $value:
#    The value for the sysctl parameter. Mandatory, unless $ensure is 'absent'.
#  $prefix:
#    Optional prefix for the sysctl.d file to be created. Default: none.
#  $ensure:
#    Whether the variable's value should be 'present' or 'absent'.
#    Defaults to 'present'.
#
# Sample Usage :
#  sysctl { 'net.ipv6.bindv6only': value => '1' }
#
define sysctl (
  $value   = undef,
  $prefix  = undef,
  $comment = undef,
  $ensure  = undef,
) {

  include sysctl::base

  # If we have a prefix, then add the dash to it
  if $prefix {
    $sysctl_d_file = "${prefix}-${title}.conf"
  } else {
    $sysctl_d_file = "${title}.conf"
  }

  # The permanent change
  file { "/etc/sysctl.d/${sysctl_d_file}":
    ensure  => $ensure,
    owner   => 'root',
    group   => 'root',
    mode    => '0644',
    content => template("${module_name}/sysctl.d-file.erb"),
    notify  => [
      Exec["sysctl-${title}"],
      Exec["update-sysctl.conf-${title}"],
    ],
  }

  if $ensure != 'absent' {

    # The immediate change + re-check on each run "just in case"
    exec { "sysctl-${title}":
      command     => "/sbin/sysctl -p /etc/sysctl.d/${sysctl_d_file}",
      refreshonly => true,
      require     => File["/etc/sysctl.d/${sysctl_d_file}"],
    }

    # For the few original values from the main file
    exec { "update-sysctl.conf-${title}":
      command     => "sed -i -e 's/^${title} *=.*/${title} = ${value}/' /etc/sysctl.conf",
      path        => [ '/usr/sbin', '/sbin', '/usr/bin', '/bin' ],
      refreshonly => true,
      onlyif      => "grep -E '^${title} *=' /etc/sysctl.conf",
    }

  }

}

