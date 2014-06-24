# Define puppi::project
#
# This define creates and configures a Puppi project
# You must use different puppi::deploy and puppi::rollback defines
# to to build up the commands list
#
define puppi::project (
  $enable = true ) {

  require puppi::params

  $ensure = any2bool($enable) ? {
    false   => 'absent',
    default => 'directory',
  }

  $ensurefile = bool2ensure($enable)

  # Create Project subdirs
  file {
    "${puppi::params::projectsdir}/${name}":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true;

    "${puppi::params::projectsdir}/${name}/check":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true,
      recurse => true,
      purge   => true,
      require => File["${puppi::params::projectsdir}/${name}"];

    "${puppi::params::projectsdir}/${name}/rollback":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true,
      recurse => true,
      purge   => true,
      require => File["${puppi::params::projectsdir}/${name}"];

    "${puppi::params::projectsdir}/${name}/deploy":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true,
      recurse => true,
      purge   => true,
      require => File["${puppi::params::projectsdir}/${name}"];

    "${puppi::params::projectsdir}/${name}/initialize":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true,
      recurse => true,
      purge   => true,
      require => File["${puppi::params::projectsdir}/${name}"];
      
    "${puppi::params::projectsdir}/${name}/configure":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true,
      recurse => true,
      purge   => true,
      require => File["${puppi::params::projectsdir}/${name}"];

    "${puppi::params::projectsdir}/${name}/report":
      ensure  => $ensure,
      mode    => '0755',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      force   => true,
      recurse => true,
      purge   => true,
      require => File["${puppi::params::projectsdir}/${name}"];
  }

  # Create Project configuration file
  file {
    "${puppi::params::projectsdir}/${name}/config":
      ensure  => $ensurefile,
      content => template('puppi/project/config.erb'),
      mode    => '0644',
      owner   => $puppi::params::configfile_owner,
      group   => $puppi::params::configfile_group,
      require => File["${puppi::params::projectsdir}/${name}"];
  }

}
