# Define puppi::todo
#
# This define creates a basic todo file that simply contains informations
# on how to complete tasks that for time or other reasons could not be
# entirely automated by Puppet.
# The basic idea is to have a quick way to document and check if are completed
# some specific operations that are required to bring a new, puppettized system
# to full operative status.
# This can be useful for cases hard to automate with Puppet:
# - First setup and import of a database needed by an application (module)
# - Installation of a legacy application that involves user interaction
# - Run of any kind of setup/configuration/init command that can't be automated
# It can also be used as a quick reminder on things done by hand and not
# Puppettized for lack of time or skill.
#
# Use the command puppi todo to show the todo present in your node.
# The exit status can be:
# 0 - OK - The task to do has been accomplished because the command specified
#    as check_command returns true (exit status 0)
# 1- WARNING - User hasn't specified a check_command to verify if the todo as
#    been accomplished, so it can't be notified if the todo has been done
# 2- ERROR - The task to do has not been accomplished becuase the command
#    specified as check_command returns an error (exit status different from 0)
#
# == Usage:
# puppi::todo { "cacti_db_install":
#   description => "Manual cacti db installation" ,
# }
#
define puppi::todo (
  $description   = '',
  $notes         = '',
  $check_command = '',
  $run           = '' ) {

  require puppi
  require puppi::params

  $array_run = is_array($run) ? {
    false     => $run ? {
      ''      => [],
      default => split($run, ','),
    },
    default   => $run,
  }

  file { "${puppi::params::tododir}/${name}":
    ensure  => present,
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => template('puppi/todo.erb'),
    tag     => 'puppi_todo',
  }

}
