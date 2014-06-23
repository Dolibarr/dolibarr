# This class installs the PL/Perl procedural language for postgresql. See
# README.md for more details.
class postgresql::server::plperl(
  $package_ensure = 'present',
  $package_name   = $postgresql::server::plperl_package_name
) {
  package { 'postgresql-plperl':
    ensure => $package_ensure,
    name   => $package_name,
    tag    => 'postgresql',
  }

  if($package_ensure == 'present' or $package_ensure == true) {
    anchor { 'postgresql::server::plperl::start': }->
    Class['postgresql::server::install']->
    Package['postgresql-plperl']->
    Class['postgresql::server::service']->
    anchor { 'postgresql::server::plperl::end': }
  } else {
    anchor { 'postgresql::server::plperl::start': }->
    Class['postgresql::server::service']->
    Package['postgresql-plperl']->
    Class['postgresql::server::install']->
    anchor { 'postgresql::server::plperl::end': }
  }

}
