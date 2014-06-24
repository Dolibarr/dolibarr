# Class: puppi::one
#
# Installs Puppi 1.0
#
class puppi::one {

  require puppi::params

  # Main configuration file
  file { 'puppi.conf':
    ensure  => present,
    path    => "${puppi::params::basedir}/puppi.conf",
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => template($puppi::template),
    require => File['puppi_basedir'],
  }

  # The Puppi 1.0 command
  file { 'puppi':
    ensure  => present,
    path    => '/usr/sbin/puppi.one',
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => template('puppi/puppi.erb'),
    require => File['puppi_basedir'],
  }

}
