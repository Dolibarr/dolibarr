# Class: nginx::package::solaris
#
# This module manages NGINX package installation on solaris based systems
#
# Parameters:
#
# *package_name*
# Needs to be specified. SFEnginx,CSWnginx depending on where you get it.
#
# *package_source* 
# Needed in case of Solaris 10.
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
# This class file is not called directly
class nginx::package::solaris(
    $package_name   = undef,
    $package_source = '',
    $package_ensure = 'present'
  ){
  package { $package_name:
	ensure 		=> $package_ensure,
  	source 		=> $package_source
  }
}
