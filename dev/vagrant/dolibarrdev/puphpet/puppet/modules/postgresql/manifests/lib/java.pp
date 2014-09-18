# This class installs the postgresql jdbc connector. See README.md for more
# details.
class postgresql::lib::java (
  $package_name   = $postgresql::params::java_package_name,
  $package_ensure = 'present'
) inherits postgresql::params {

  validate_string($package_name)

  package { 'postgresql-jdbc':
    ensure => $package_ensure,
    name   => $package_name,
  }

}
