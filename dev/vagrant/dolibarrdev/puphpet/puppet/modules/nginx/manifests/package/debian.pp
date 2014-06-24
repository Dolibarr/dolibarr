# Class: nginx::package::debian
#
# This module manages NGINX package installation on debian based systems
#
# Parameters:
#
# There are no default parameters for this class.
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
# This class file is not called directly
class nginx::package::debian(
    $manage_repo    = true,
    $package_name   = 'nginx',
    $package_source = 'nginx',
    $package_ensure = 'present'
  ) {

  
  $distro = downcase($::operatingsystem)

  package { $package_name:
    ensure  => $package_ensure,
    require => Anchor['nginx::apt_repo'],
  }

  anchor { 'nginx::apt_repo' : }

  include '::apt'

  if $manage_repo {
    case $package_source {
      'nginx': {
        apt::source { 'nginx':
          location   => "http://nginx.org/packages/${distro}",
          repos      => 'nginx',
          key        => '7BD9BF62',
          key_source => 'http://nginx.org/keys/nginx_signing.key',
          notify     => Exec['apt_get_update_for_nginx'],
        }
      }
      'passenger': {
        ensure_resource('package', 'apt-transport-https', {'ensure' => 'present' })

        apt::source { 'nginx':
          location   => 'https://oss-binaries.phusionpassenger.com/apt/passenger',
          repos      => "main",
          key        => '561F9B9CAC40B2F7',
          key_source => 'https://oss-binaries.phusionpassenger.com/auto-software-signing-gpg-key.txt',
          notify     => Exec['apt_get_update_for_nginx'],
        }

        package { 'passenger':
          ensure  => 'present',
          require => Anchor['nginx::apt_repo'],
        }
      }
      default: {}
    }

    exec { 'apt_get_update_for_nginx':
      command     => '/usr/bin/apt-get update',
      timeout     => 240,
      returns     => [ 0, 100 ],
      refreshonly => true,
      before      => Anchor['nginx::apt_repo'],
    }
  }
}
