# == Class: erlang
#
# Module to install an up-to-date version of Erlang from the
# official repositories
#
# === Parameters
# [*version*]
#   The package version to install, passed to ensure.
#   Defaults to present.
#
class erlang (
  $epel_enable              = $erlang::params::epel_enable,
  $key_signature            = $erlang::params::key_signature,
  $local_repo_location      = $erlang::params::local_repo_location,
  $package_name             = $erlang::params::package_name,
  $remote_repo_location     = $erlang::params::remote_repo_location,
  $remote_repo_key_location = $erlang::params::remote_repo_key_location,
  $repos                    = $erlang::params::repos,
  $version                  = 'present',
) inherits erlang::params {
  validate_string($version)

  case $::osfamily {
    'Debian' : {
      include '::apt'
      include '::erlang::repo::apt'
    }
    'RedHat' : {
      if $epel_enable {
        # Include epel as this is a requirement for erlang in RHEL6.
        include '::epel'
        Class['epel'] -> Package[$package_name]
      }

      # This is only needed on RHEL5, RHEL6 has erlang in EPEL.
      if $::operatingsystemrelease =~ /^5/ {
        include '::erlang::repo::yum'
      }
    }
    default : {
    }
  }

  package { $package_name: ensure => $version, }
}
