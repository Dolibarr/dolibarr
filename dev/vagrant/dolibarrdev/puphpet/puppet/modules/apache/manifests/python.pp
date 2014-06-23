# Class: apache::python
#
# This class installs Python for Apache
#
# Parameters:
# - $php_package
#
# Actions:
#   - Install Apache Python package
#
# Requires:
#
# Sample Usage:
#
class apache::python {
  warning('apache::python is deprecated; please use apache::mod::python')
  include ::apache::mod::python
}
