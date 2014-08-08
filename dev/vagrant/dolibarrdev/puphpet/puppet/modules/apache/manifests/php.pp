# Class: apache::php
#
# This class installs PHP for Apache
#
# Parameters:
# - $php_package
#
# Actions:
#   - Install Apache PHP package
#
# Requires:
#
# Sample Usage:
#
class apache::php {
  warning('apache::php is deprecated; please use apache::mod::php')
  include ::apache::mod::php
}
