# = Define: puppi::runscript
#
# This define creates, executes and optionally crontabs a
# simple whose content is directly managed by arguments.
# The script content is provided either with $source or $content arguments.
# It's placed in:
# $destination_path , if provided, or in /usr/local/sbin/${name}
#
# Cron execution times are defined by the $cron argument (Default empty).
# Automatic execution of the script via Puppet is managed by the $autorun
# parameter (default: true).
# Conditional execution of the script at subsequent puppet runs is
# defined by the $refreshonly, $creates, $unless $onlyif parameters
# that map the omonimous exec type arguments.
#
# == Parameters:
#
# [*source*]
#   String. Optional. Default: undef. Alternative to content.
#   Source of the script file to provide for execuution.
#   Sample: source => 'puppet:///modules/site/scripts/my_script',
#
# [*content*]
#   String. Optional. Default: undef. Alternative to source.
#   Content of the script file to provide for execuution.
#   This parameter is alternative to source.
#   Sample: content => 'template(site/scripts/my_script.erb'),
#
# [*destination_path*]
#   String. Optional. Default: ''
#   Path of the provided script. If not provided the script in saved in
#   /usr/local/sbin/${name}
#
# [*parameters*]
#   String. Optional. Default: ''
#   Optional parameters to pass to the script when executing it.
#
# [*autorun*]
#   Boolean. Default: true.
#   Define if to automatically execute the script when Puppet runs.
#
# [*refreshonly*]
#   Boolen. Optional. Default: true
#   Defines the logic of execution of the script when Puppet runs.
#   Maps to the omonymous Exec type argument.
#
# [*creates*]
#   String. Optional. Default: undef
#   Defines the logic of execution of the script when Puppet runs.
#   Maps to the omonymous Exec type argument.
#
# [*onlyif*]
#   String. Optional. Default: undef
#   Defines the logic of execution of the script when Puppet runs.
#   Maps to the omonymous Exec type argument.
#
# [*unless*]
#   String. Optional. Default: undef
#   Defines the logic of execution of the script when Puppet runs.
#   Maps to the omonymous Exec type argument.
#
# [*basedir*]
#   String. Optional. Default: /usr/local/sbin
#   Directory where the runscript scripts are created when destination_path
#   is empty.
#
# [*cron*]
#   String. Optional. Default: ''
#   Optional cron schedule to crontab the execution of the
#   script. Format must be in standard cron style.
#   Example: '0 4 * * *' .
#   By default no cron is scheduled.
#
# [*cron_user*]
#   String. Optional. Default: 'root'
#   When cron is enabled the user that executes the cron job.
#
# [*owner*]
#   Owner of the created script. Default: root.
#
# [*group*]
#   Group of the created script. Default: root.
#
# [*mode*]
#   Mode of the created script. Default: '7550'.
#   NOTE: Keep the execution flag!
#
# [*ensure*]
#   Define if the runscript script and eventual cron job
#   must be present or absent. Default: present.
#
# == Examples
#
# - Minimal setup
# puppi::runscript { 'my_script':
#   source           => 'puppet:///modules/site/scripts/my_script.sh',
#   destination_path => '/usr/local/bin/my_script.sh',
# }
#
define puppi::runscript (
  $source           = undef,
  $content          = undef,
  $destination_path = '',
  $parameters       = '',
  $autorun          = true,
  $refreshonly      = true,
  $creates          = undef,
  $onlyif           = undef,
  $unless           = undef,
  $basedir          = '/usr/local/sbin',
  $cron             = '',
  $cron_user        = 'root',
  $owner            = 'root',
  $group            = 'root',
  $mode             = '0755',
  $ensure           = 'present' ) {

  $real_command = $destination_path ? {
    ''      => "${basedir}/${name}",
    default => $destination_path,
  }

  file { "runscript_${name}":
    ensure  => $ensure,
    path    => $real_command,
    mode    => $mode,
    owner   => $owner,
    group   => $group,
    content => $content,
    source  => $source,
  }

  if $autorun == true {
    exec { "runscript_${name}":
      command     => $real_command,
      refreshonly => $refreshonly,
      creates     => $creates,
      onlyif      => $onlyif,
      unless      => $unless,
      subscribe   => File["runscript_${name}"],
    }
  }

  if $cron != '' {
    file { "runscript_cron_${name}":
      ensure  => $ensure,
      path    => "/etc/cron.d/runscript_${name}",
      mode    => '0644',
      owner   => 'root',
      group   => 'root',
      content => "${cron} ${cron_user} ${real_command} ${parameters}\n",
    }
  }
}
