# Class php::devel
#
# Installs php devel package
#
class php::devel {

  if $php::package_devel != ''
  and ! defined(Package[$php::package_devel]) {
    package { $php::package_devel :
      ensure => $php::manage_package,
    }
  }
}
