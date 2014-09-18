# === Class: concat::setup
#
# Sets up the concat system. This is a private class.
#
# [$concatdir]
#   is where the fragments live and is set on the fact concat_basedir.
#   Since puppet should always manage files in $concatdir and they should
#   not be deleted ever, /tmp is not an option.
#
# It also copies out the concatfragments.sh file to ${concatdir}/bin
#
class concat::setup {
  if $caller_module_name != $module_name {
    warning("${name} is deprecated as a public API of the ${module_name} module and should no longer be directly included in the manifest.")
  }

  if $::concat_basedir {
    $concatdir = $::concat_basedir
  } else {
    fail ('$concat_basedir not defined. Try running again with pluginsync=true on the [master] and/or [main] section of your node\'s \'/etc/puppet/puppet.conf\'.')
  }
  
  # owner and mode of fragment files (on windows owner and access rights should be inherited from concatdir and not explicitly set to avoid problems)
  $fragment_owner = $osfamily ? { 'windows' => undef, default => $::id }
  $fragment_mode  = $osfamily ? { 'windows' => undef, default => '0640' }

  $script_name = $::kernel ? {
    'windows' => 'concatfragments.rb',
    default   => 'concatfragments.sh'
  }

  $script_path = "${concatdir}/bin/${script_name}"

  $script_owner = $osfamily ? { 'windows' => undef, default => $::id }

  $script_mode = $osfamily ? { 'windows' => undef, default => '0755' }

  $script_command   = $::kernel ? {
    'windows' => "ruby.exe ${script_path}",
    default   => $script_path
  }

  File {
    backup => false,
  }

  file { $script_path:
    ensure => file,
    owner  => $script_owner,
    mode   => $script_mode,
    source => "puppet:///modules/concat/${script_name}",
  }

  file { [ $concatdir, "${concatdir}/bin" ]:
    ensure => directory,
    mode   => '0755',
  }
}
