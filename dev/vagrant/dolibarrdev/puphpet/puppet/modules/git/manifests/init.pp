# Class: git
#
# This class installs git
#
# Actions:
#   - Install the git package
#
# Sample Usage:
#  class { 'git': }
#
class git {
  if ! defined(Package['git']) {
    package { 'git':
      ensure => present
    }
  }
}
