# This class installs postgresql development libraries. See README.md for more
# details.
class postgresql::lib::devel(
  $package_name   = $postgresql::params::devel_package_name,
  $package_ensure = 'present'
) inherits postgresql::params {

  validate_string($package_name)

  package { 'postgresql-devel':
    ensure => $package_ensure,
    name   => $package_name,
    tag    => 'postgresql',
  }
}
