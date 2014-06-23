# Define puppi::report
#
# This define creates a file with a report command that can be used locally.
#
# Usage:
# puppi::report { "Retrieve files":
#   command  => "report_mail.sh",
#   argument => "roots@example42.com",
#   priority => "10",
#   user   => "root",
#   project  => "spysite",
# }
#
define puppi::report (
  $project,
  $command,
  $arguments = '',
  $priority  = '50',
  $user      = 'root',
  $enable    = true ) {

  require puppi::params

  # Autoinclude the puppi class
  include puppi

  $ensure = bool2ensure($enable)

  file { "${puppi::params::projectsdir}/${project}/report/${priority}-${name}":
    ensure  => $ensure,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => "su - ${user} -c \"export project=${project} && ${puppi::params::scriptsdir}/${command} ${arguments}\"\n",
    tag     => 'puppi_report',
  }

}
