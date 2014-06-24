# Define puppi::initialize
#
# This define creates a file with a initialize command that can be used locally.
#
# Usage:
# puppi::initialize { "Retrieve files":
#   command  => "get_file.sh",
#   argument => "/remote/dir/file",
#   priority => "10",
#   user   => "root",
#   project  => "spysite",
# }
#
define puppi::initialize (
  $command,
  $project,
  $arguments = '',
  $priority  = '50',
  $user      = 'root',
  $enable    = true ) {

  require puppi
  require puppi::params

  $ensure = bool2ensure($enable)

  file { "${puppi::params::projectsdir}/${project}/initialize/${priority}-${name}":
    ensure  => $ensure,
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => "su - ${user} -c \"export project=${project} && ${puppi::params::scriptsdir}/${command} ${arguments}\"\n",
    tag     => 'puppi_initialize',
  }

}
