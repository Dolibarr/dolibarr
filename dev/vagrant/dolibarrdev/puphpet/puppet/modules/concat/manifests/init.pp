# == Define: concat
#
# Sets up so that you can use fragments to build a final config file,
#
# === Options:
#
# [*ensure*]
#   Present/Absent
# [*path*]
#   The path to the final file. Use this in case you want to differentiate
#   between the name of a resource and the file path.  Note: Use the name you
#   provided in the target of your fragments.
# [*owner*]
#   Who will own the file
# [*group*]
#   Who will own the file
# [*mode*]
#   The mode of the final file
# [*force*]
#   Enables creating empty files if no fragments are present
# [*warn*]
#   Adds a normal shell style comment top of the file indicating that it is
#   built by puppet
# [*force*]
# [*backup*]
#   Controls the filebucketing behavior of the final file and see File type
#   reference for its use.  Defaults to 'puppet'
# [*replace*]
#   Whether to replace a file that already exists on the local system
# [*order*]
# [*ensure_newline*]
# [*gnu*]
#   Deprecated
#
# === Actions:
# * Creates fragment directories if it didn't exist already
# * Executes the concatfragments.sh script to build the final file, this
#   script will create directory/fragments.concat.   Execution happens only
#   when:
#   * The directory changes
#   * fragments.concat != final destination, this means rebuilds will happen
#     whenever someone changes or deletes the final file.  Checking is done
#     using /usr/bin/cmp.
#   * The Exec gets notified by something else - like the concat::fragment
#     define
# * Copies the file over to the final destination using a file resource
#
# === Aliases:
#
# * The exec can notified using Exec["concat_/path/to/file"] or
#   Exec["concat_/path/to/directory"]
# * The final file can be referenced as File["/path/to/file"] or
#   File["concat_/path/to/file"]
#
define concat(
  $ensure         = 'present',
  $path           = $name,
  $owner          = undef,
  $group          = undef,
  $mode           = '0644',
  $warn           = false,
  $force          = false,
  $backup         = 'puppet',
  $replace        = true,
  $order          = 'alpha',
  $ensure_newline = false,
  $gnu            = undef
) {
  validate_re($ensure, '^present$|^absent$')
  validate_absolute_path($path)
  validate_string($owner)
  validate_string($group)
  validate_string($mode)
  if ! (is_string($warn) or $warn == true or $warn == false) {
    fail('$warn is not a string or boolean')
  }
  validate_bool($force)
  validate_string($backup)
  validate_bool($replace)
  validate_re($order, '^alpha$|^numeric$')
  validate_bool($ensure_newline)
  if $gnu {
    warning('The $gnu parameter to concat is deprecated and has no effect')
  }

  include concat::setup

  $safe_name            = regsubst($name, '[/:]', '_', 'G')
  $concatdir            = $concat::setup::concatdir
  $fragdir              = "${concatdir}/${safe_name}"
  $concat_name          = 'fragments.concat.out'
  $script_command       = $concat::setup::script_command
  $default_warn_message = '# This file is managed by Puppet. DO NOT EDIT.'
  $bool_warn_message    = 'Using stringified boolean values (\'true\', \'yes\', \'on\', \'false\', \'no\', \'off\') to represent boolean true/false as the $warn parameter to concat is deprecated and will be treated as the warning message in a future release'

  case $warn {
    true: {
      $warn_message = $default_warn_message
    }
    'true', 'yes', 'on': {
      warning($bool_warn_message)
      $warn_message = $default_warn_message
    }
    false: {
      $warn_message = ''
    }
    'false', 'no', 'off': {
      warning($bool_warn_message)
      $warn_message = ''
    }
    default: {
      $warn_message = $warn
    }
  }

  $warnmsg_escaped = regsubst($warn_message, '\'', '\'\\\'\'', 'G')
  $warnflag = $warnmsg_escaped ? {
    ''      => '',
    default => "-w '${warnmsg_escaped}'"
  }

  $forceflag = $force ? {
    true  => '-f',
    false => '',
  }

  $orderflag = $order ? {
    'numeric' => '-n',
    'alpha'   => '',
  }

  $newlineflag = $ensure_newline ? {
    true  => '-l',
    false => '',
  }

  File {
    backup  => false,
  }

  if $ensure == 'present' {
    file { $fragdir:
      ensure => directory,
      mode   => '0750',
    }

    file { "${fragdir}/fragments":
      ensure  => directory,
      mode    => '0750',
      force   => true,
      ignore  => ['.svn', '.git', '.gitignore'],
      notify  => Exec["concat_${name}"],
      purge   => true,
      recurse => true,
    }

    file { "${fragdir}/fragments.concat":
      ensure => present,
      mode   => '0640',
    }

    file { "${fragdir}/${concat_name}":
      ensure => present,
      mode   => '0640',
    }

    file { $name:
      ensure  => present,
      owner   => $owner,
      group   => $group,
      mode    => $mode,
      replace => $replace,
      path    => $path,
      alias   => "concat_${name}",
      source  => "${fragdir}/${concat_name}",
      backup  => $backup,
    }

    # remove extra whitespace from string interpolation to make testing easier
    $command = strip(regsubst("${script_command} -o \"${fragdir}/${concat_name}\" -d \"${fragdir}\" ${warnflag} ${forceflag} ${orderflag} ${newlineflag}", '\s+', ' ', 'G'))

    # if puppet is running as root, this exec should also run as root to allow
    # the concatfragments.sh script to potentially be installed in path that
    # may not be accessible by a target non-root owner.
    exec { "concat_${name}":
      alias     => "concat_${fragdir}",
      command   => $command,
      notify    => File[$name],
      subscribe => File[$fragdir],
      unless    => "${command} -t",
      path      => $::path,
      require   => [
        File[$fragdir],
        File["${fragdir}/fragments"],
        File["${fragdir}/fragments.concat"],
      ],
    }
  } else {
    file { [
      $fragdir,
      "${fragdir}/fragments",
      "${fragdir}/fragments.concat",
      "${fragdir}/${concat_name}"
    ]:
      ensure => absent,
      force  => true,
    }

    file { $path:
      ensure => absent,
      backup => $backup,
    }

    $absent_exec_command = $::kernel ? {
      'windows' => 'cmd.exe /c exit 0',
      default   => 'true',
    }

    $absent_exec_path = $::kernel ? {
      'windows' => $::path,
      default   => '/bin:/usr/bin',
    }

    exec { "concat_${name}":
      alias   => "concat_${fragdir}",
      command => $absent_exec_command,
      path    => $absent_exec_path
    }
  }
}

# vim:sw=2:ts=2:expandtab:textwidth=79
