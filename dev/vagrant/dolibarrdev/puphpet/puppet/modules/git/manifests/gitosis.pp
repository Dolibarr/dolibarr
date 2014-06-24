# Class: gitosis
#
# This installs and configures gitosis 
#
# Requires:
#  - Class[git]
#
class git::gitosis {
  include ::git
  package {'gitosis':
    ensure => present
  }
}
