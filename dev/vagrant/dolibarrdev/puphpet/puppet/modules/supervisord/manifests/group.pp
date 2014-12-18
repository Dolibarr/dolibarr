# Define: supervisord::group
#
# This define creates an group configuration file
#
# Documentation on parameters available at:
# http://supervisord.org/configuration.html#group-x-section-settings
#
define supervisord::group (
  $programs,
  $ensure   = present,
  $priority = undef
) {

  include supervisord

  # parameter validation
  validate_array($programs)
  if $priority { validate_re($priority, '^\d+', "invalid priority value of: ${priority}") }

  $progstring = array2csv($programs)
  $conf = "${supervisord::config_include}/group_${name}.conf"

  file { $conf:
    ensure  => $ensure,
    owner   => 'root',
    mode    => '0755',
    content => template('supervisord/conf/group.erb'),
    notify  => Class['supervisord::reload']
  }
}
