# Class for installing a MongoDB client shell (CLI).
#
# == Parameters
#
# [ensure] Desired ensure state of the package. Optional.
#   Defaults to 'true'
#
# [package_name] Name of the package to install the client from. Default
#   is repository dependent.
#
class mongodb::client (
  $ensure       = $mongodb::params::ensure_client,
  $package_name = $mongodb::params::client_package_name,
) inherits mongodb::params {
  case $::osfamily {
    'RedHat', 'Linux': {
      class { 'mongodb::client::install': }
    }
    'Debian': {
      warning ('Debian client is included by default with server. Please use ::mongodb::server to install the mongo client for Debian family systems.')
    }
    default: {
      # no action taken, failure happens in params.pp
    }
  }
}
