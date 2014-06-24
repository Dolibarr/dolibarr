# Class: php::pear
#
# Installs Pear for PHP module
#
# Usage:
# include php::pear
#
# == Parameters
#
# Standard class parameters
# Define the general class behaviour and customizations
#
# [*package*]
#   Name of the package to install. Defaults to 'php-pear'
#
# [*version*]
#   Version to install. Defaults to 'present'
#
# [*install_package*]
#   Boolean. Determines if any package should be installed to support the PEAR functionality.
#   Can be false if PEAR was already provided by another package or module.
#   Default: true
#
class php::pear (
  $package         = $php::package_pear,
  $install_package = true,
  $version         = 'present',
  $path            = '/usr/bin:/usr/sbin:/bin:/sbin'
  ) inherits php {

  if ( $install_package ) {
    package { 'php-pear':
      ensure => $version,
      name   => $package,
    }
  }

}
