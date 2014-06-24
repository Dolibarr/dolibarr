# Class: apache::proxy
#
# This class enabled the proxy module for Apache
#
# Actions:
#   - Enables Apache Proxy module
#
# Requires:
#
# Sample Usage:
#
class apache::proxy {
  warning('apache::proxy is deprecated; please use apache::mod::proxy')
  include ::apache::mod::proxy
}
