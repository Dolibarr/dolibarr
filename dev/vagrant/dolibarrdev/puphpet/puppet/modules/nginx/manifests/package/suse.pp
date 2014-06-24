# Class: nginx::package::suse
#
# This module manages NGINX package installation for SuSE based systems
#
# Parameters:
#
# There are no default parameters for this class.
#
# Actions:
#  This module contains all of the required package for SuSE. Apache and all
#  other packages listed below are built into the packaged RPM spec for
#  SuSE and OpenSuSE.
# Requires:
#
# Sample Usage:
#
# This class file is not called directly
class nginx::package::suse {

  $suse_packages = [
    'nginx-0.8', 'apache2', 'apache2-itk', 'apache2-utils', 'gd', 'libapr1',
    'libapr-util1', 'libjpeg62', 'libpng14-14', 'libxslt', 'rubygem-daemon_controller',
    'rubygem-fastthread', 'rubygem-file-tail', 'rubygem-passenger',
    'rubygem-passenger-nginx', 'rubygem-rack', 'rubygem-rake', 'rubygem-spruz',
  ]

  package { $suse_packages:
    ensure => $nginx::package_ensure,
  }
}
