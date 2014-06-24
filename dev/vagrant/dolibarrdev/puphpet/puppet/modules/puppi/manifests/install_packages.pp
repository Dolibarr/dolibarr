# = Define: puppi::install_packages
#
# This define installs a list of packages without manging them as Puppet
# resources. It's useful when you need a set of packages installed,
# for example as prerequisites to build code from source, but you don't want
# to create Puppet package resources for them (since they may conflict with
# existing classes that provide the same packages.
#
# == Parameters:
#
# [*packages*]
#   String. Required.
#   A space separated list of of the packages to install
#
# [*template*]
#   String. Optional. Default: 'puppi/install_packages.erb'
#   The template to use to generate the script that installs the packages
#
# [*scrips_dir*]
#   String. Optional. Default: '/root/puppi_install_packages'
#    The directory where you place the scripts created by the define.
#
# [*autorun*]
#   Boolean. Default: true.
#   Define if to automatically execute the script when Puppet runs.
#
# [*refreshonly*]
#   Boolean. Optional. Default: true
#   Defines the logic of execution of the script when Puppet runs.
#   Maps to the omonymous Exec type argument.
#
# [*timeout*]
#   String. Optional. Default: '600'
#   Exec timeout in seconds.
#
# [*ensure*]
#   Define if the runscript script and eventual cron job
#   must be present or absent. Default: present.
#
# == Examples
#
# - Minimal setup
# puppi::install_packages { 'build_tools':
#   source           => 'build-essential vim git-core curl bison',
# }
#
define puppi::install_packages (
  $packages,
  $template         = 'puppi/install_packages.erb',
  $scripts_dir      = '/root/puppi_install_packages',
  $autorun          = true,
  $refreshonly      = true,
  $timeout          = '600',
  $ensure           = 'present' ) {

  if ! defined(File[$scripts_dir]) {
    file { $scripts_dir:
      ensure => directory,
      mode   => '0755',
      owner  => 'root',
      group  => 'root',
    }
  }

  file { "install_packages_${name}":
    ensure  => $ensure,
    path    => "${scripts_dir}/${name}",
    mode    => '0755',
    owner   => 'root',
    group   => 'root',
    content => template($template),
  }

  if $autorun == true {
    exec { "install_packages_${name}":
      command     => "${scripts_dir}/${name}",
      refreshonly => $refreshonly,
      subscribe   => File["install_packages_${name}"],
      timeout     => $timeout,
    }
  }

}
